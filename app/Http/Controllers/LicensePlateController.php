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
        try {
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

            return [
                'data' =>  $transaction,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "true."
            ];
   // Initialize a new Laravel collection
   $collection = collect();

   // Loop through each object in the response array
   foreach ($transaction as $obj) {
       // Parse the JSON string in the 'product' field
       $productData = json_decode($obj["product"], true); // Ensure decoding as an associative array

       // Loop through each key-value pair in the parsed data
       foreach ($productData as $key => $value) {
           // If the key does not exist in the collection, initialize it with an empty array
           if (!$collection->has($key)) {
               $collection->put($key, []);
           }
           // Append the ID of the object to the array associated with the key
           $collection->get($key)[] = $value;
       }
   }

    dd($collection);



        } catch (\Throwable $th) {

            app('log')->error($th->getMessage());

            return [
                'error' =>  [],
                'status' =>  false,
                'responseCode' =>  500,
                'message' => "Server error."
            ];
        }
    }
}
