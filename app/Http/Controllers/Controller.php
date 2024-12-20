<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;


/**
 * @OA\Info(
 *     title="ParkimaPro API",
 *     version="0.1"
 * )
 * 
 * @OA\Parameter(
 *     name="Accept",
 *     in="header",
 *     required=true,
 *     @OA\Schema(
 *         type="string",
 *         default="application/json"
 *     ),
 *     description="The media type accepted by the client"
 * )
 * 
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Authentication Bearer Token",
 *     name="Authorization",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 * 
 * @OA\Server(
 *     url="http://demo.pro.bmoov.test",
 *     description="API server"
 * )
 */


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
