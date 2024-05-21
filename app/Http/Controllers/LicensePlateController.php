<?php

namespace App\Http\Controllers;

use App\Http\Requests\LicensePlateRequest;
use Faker\Provider\pl_PL\LicensePlate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LicensePlateController extends Controller
{
    public function getPlateList(LicensePlateRequest $request)
    {
        // try {
            // get the parking_id and operator_id ready
            $result = app('db')->select(
                'select gateways.shift_id, gateways.parking_type, gateways.type, gateways.cashier_contract_id, gateways.cashier_consumer_id, gateways.shift_sub_user, gateways.parking_id, gateways.timezone_offset, operators.operator_id
            from parkings, gateways, operators
            where parkings.gateway_id = gateways.id
            and gateways.operator_id = operators.id
            and parkings.id = ? limit 1',
                [$request->parking_id]
            );

            $transaction =  app('db')->select(
                'select transactions.id, transactions.product
            from transactions
            where transactions.parking_id = ?
            and transactions.updated_at >= NOW() - INTERVAL 1 DAY ',
                [$request->parking_id]
            );

            // return  $transaction;

$product= [];

foreach($transaction as $item)
{
// dd($item);
  $productData = json_decode($item->product, true);
    dump(array_push( $product ,[
        "parkingId" => $productData['parking_id'],
        "licensePlate" => $productData['license_plate'],
        "parkingSpotDescription" => $productData['parking_spot_description'],

    ]));  
       
}      

               
      
        

        // } catch (\Throwable $th) {

        //     app('log')->error($th->getMessage());

        //     return [
        //         'error' =>  [],
        //         'status' =>  false,
        //         'responseCode' =>  500,
        //         'message' => "Server error."
        //     ];
        // }
    }
}
