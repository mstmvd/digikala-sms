<?php
/**
 * Created by PhpStorm.
 * User: mostafa
 * Date: 7/19/19
 * Time: 9:17 PM
 */

namespace App\Controllers;

use App\Facade\Cache;
use App\Facade\Template;
use App\Models\Sms;
use App\Models\SmsLog;
use Illuminate\Support\Arr;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SmsController
{
    public function send(Request $request)
    {
        $number = $request->query->get('number'); // todo: validate
        $body = $request->query->get('body'); // todo: validate
        $sms = new Sms(['number' => $number, 'body' => $body, 'api' => null, 'status' => null, 'call_at' => date('Y-m-d H:i:s')]);
        $sms->save();
        $status = $this->sendSms($sms);
        return new JsonResponse(['message' => $status === 'SENT' ? 'sms sent' : 'sms send failed'], $status === 'SENT' ? 200 : 504);
    }

    public function sendFailed()
    {
        $failedSms = Sms::where('status', 'FAILED')->get();
        foreach ($failedSms as $sms) {
            $this->sendSms($sms);
        }
        return new JsonResponse(['message' => 'job done']);
    }

    public function report(Request $request)
    {
        $number = $request->get('number');
        $sentSmsCount = Cache::get('sent_sms_count', function (ItemInterface $item) use ($number) {
            $item->expiresAfter(300);
            $res = Sms::where('status', 'SENT');
            if ($number) {
                $res->where('number', $number);
            }
            return $res->count();
        });
        $apiUseCount = Cache::get('api_use_count', function (ItemInterface $item) use ($number) {
            $item->expiresAfter(300);
            $res = SmsLog:: selectRaw('api, count(*) as total');
            if ($number) {
                $res->whereHas('sms', function ($q) use ($number) {
                    $q->where('number', $number);
                });
            }
            return $res->groupBy('api')
                ->orderBy('api')
                ->get()
                ->toArray();
        });
        $apiFailRatio = Cache::get('api_fail_ratio', function (ItemInterface $item) use ($number) {
            $item->expiresAfter(300);
            $res = SmsLog::selectRaw('api, sum(if (`status` = \'FAILED\', 1, 0)) / count(*) as ratio');
            if ($number) {
                $res->whereHas('sms', function ($q) use ($number) {
                    $q->where('number', $number);
                });
            }
            return $res->groupBy('api')
                ->orderBy('api')
                ->get()
                ->toArray();
        });
        $top10 = Cache::get('top10', function (ItemInterface $item) {
            $item->expiresAfter(300);
            return Sms::selectRaw('number, count(*) as total')
                ->where('status', 'SENT')
                ->groupBy(['number'])
                ->orderBy('total', 'DESC')
                ->limit(10)
                ->get()->toArray();
        });
        $smsApis = Cache::get('SMS_APIS');
        Arr::sort($array);

        return new Response(Template::render('report.php', [
            'number' => $number,
            'sms_apis' => $smsApis,
            'sent_sms_count' => $sentSmsCount,
            'api_use_count' => $apiUseCount,
            'api_fail_ratio' => $apiFailRatio,
            'top10' => $top10
        ]));
    }

    /**
     * @param $sms
     * @return string
     */
    private function sendSms($sms)
    {
        $smsApis = Cache::get('SMS_APIS');
        shuffle($smsApis); //shuffle sms apis for random select

        //create mock http client for apis
        $httpClient = new MockHttpClient(function ($method, $url, $options) {
            $httpCode = rand(1, 5) == 1 ? 500 : 200; //create random http code with 20% chance of fail
            return new MockResponse('', ['http_code' => $httpCode]);
        });
        $callApiRes = $this->callApi($httpClient, $sms, $smsApis);


        $smsParams = [];
        if ($callApiRes['success'] === null) {
            //todo add job to sent this sms later
            $smsParams['status'] = 'FAILED';
        } else {
            $smsParams['status'] = 'SENT';
            $smsParams['api'] = $callApiRes['success']['api'];
            $smsParams['call_at'] = $callApiRes['success']['call_at'];
        }

        //update sms
        $sms->fill($smsParams);
        $sms->save();

        //Log tries of sending sms
        foreach ($callApiRes['fails'] as $fail) {
            $smsLog = new SmsLog(['sms_id' => $sms->id, 'api' => $fail['api'], 'status' => 'FAILED', 'call_at' => $fail['call_at']]);
            $smsLog->save();
        }
        if ($callApiRes['success']) {
            $smsLog = new SmsLog(['sms_id' => $sms->id, 'api' => $callApiRes['success']['api'], 'status' => 'SENT', 'call_at' => $callApiRes['success']['call_at']]);
            $smsLog->save();
        }
        return $smsParams['status'];
    }

    private function callApi($httpClient, $sms, $smsApis, $i = 0)
    {
        static $res = ['fails' => [], 'success' => null];
        if ($i >= sizeof($smsApis)) {
            return $res;
        }
        try {
            $api = $smsApis[$i];
            $callAt = date('Y-m-d H:i:s');
            $response = $httpClient->request('GET', $api, ['query' => ['number' => $sms->number, 'body' => $sms->body]]);
            if (floor($response->getStatusCode() / 100) == 2) {
                $res['success'] = ['api' => $api, 'call_at' => $callAt];
                return $res;
            } else {
                $res['fails'][] = ['api' => $api, 'call_at' => $callAt];
                return $this->callApi($httpClient, $sms, $smsApis, $i + 1);
            }
        } catch (TransportExceptionInterface $e) {
            $res['fails'][] = ['api' => $api, 'call_at' => $callAt];
            return $this->callApi($httpClient, $sms, $smsApis, $i + 1);
        }
    }
}