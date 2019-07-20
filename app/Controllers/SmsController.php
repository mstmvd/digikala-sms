<?php
/**
 * Created by PhpStorm.
 * User: mostafa
 * Date: 7/19/19
 * Time: 9:17 PM
 */

namespace App\Controllers;

use App\Facade\Cache;
use App\Models\Sms;
use App\Models\SmsLog;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SmsController
{
    public function send(Request $request)
    {
        $number = $request->query->get('number');
        $body = $request->query->get('body');
        $smsApis = Cache::get('SMS_APIS');
        shuffle($smsApis);
        $httpClient = new MockHttpClient(function ($method, $url, $options) {
            return new MockResponse('', ['http_code' => rand(1, 5) == 1 ? 500 : 200]);
        });
        $callApiRes = $this->callApi($httpClient, $number, $body, $smsApis);
        $smsParams = ['number' => $number, 'body' => $body, 'api' => null, 'status' => null, 'call_at' => date('Y-m-d H:i:s')];
        if ($callApiRes['success'] === null) {
            //todo add job to sent this sms later
            $smsParams['status'] = 'FAILED';
        } else {
            $smsParams['status'] = 'SENT';
            $smsParams['api'] = $callApiRes['success']['api'];
            $smsParams['call_at'] = $callApiRes['success']['call_at'];
        }
        $sms = new Sms($smsParams);
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

        return new JsonResponse(['message' => $smsParams['status'] === 'SENT' ? 'sms sent' : 'sms send failed']);
    }

    private function callApi($httpClient, $number, $body, $smsApis, $i = 0)
    {
        static $res = ['fails' => [], 'success' => null];
        if ($i >= sizeof($smsApis)) {
            return $res;
        }
        try {
            $api = $smsApis[$i];
            $callAt = date('Y-m-d H:i:s');
            $response = $httpClient->request('GET', $api, ['query' => ['number' => $number, 'body' => $body]]);
            if (floor($response->getStatusCode() / 100) == 2) {
                $res['success'] = ['api' => $api, 'call_at' => $callAt];
                return $res;
            } else {
                $res['fails'][] = ['api' => $api, 'call_at' => $callAt];
                return $this->callApi($httpClient, $number, $body, $smsApis, $i + 1);
            }
        } catch (TransportExceptionInterface $e) {
        }
        return $res;
    }

}