<?php
/**
 * Created by PhpStorm.
 * User: mostafa
 * Date: 7/19/19
 * Time: 9:17 PM
 */
namespace App\Controllers;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SmsController
{

    public function send(Request $request)
    {
        $response = new JsonResponse(
            ['number' => $request->query->get('number')],
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
        return $response;
    }

}