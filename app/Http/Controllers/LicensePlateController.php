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

            return  $transaction;


// Initialize a new Laravel collection
$collection = collect($transaction);

// Loop through each object in the collection
$collection->each(function ($item) {
    // Decode the product JSON string as an associative array
    $productData = json_decode($item['product'], true);

    // Check if json_decode failed
    if (json_last_error() !== JSON_ERROR_NONE) {
        Log::error('JSON decode error: ' . json_last_error_msg() . ' for item ID: ' . $item['id']);
        return;
    }

    // Extract the required parameters
    $parkingId = $productData['parking_id'] ?? null;
    $licensePlate = $productData['license_plate'] ?? null;
    $parkingSpotDescription = $productData['parking_spot_description'] ?? null;



                return  $licensePlate;


                // // Make the GET request with the extracted parameters
                // $response = Http::get('http://your-api-endpoint.com', [
                //     'parking_id' => $parkingId,
                //     'license_plate' => $licensePlate,
                //     'parking_spot_description' => $parkingSpotDescription,
                // ]);

                // Process the response (if needed)
                // For example, you can log the response or store it in the database

                // if ($response->successful()) {

                //     $responseData = $response->json();
                //     // Do something with the response data
                // //     Log::info('API response:', $responseData);

                // } else {

                //     Log::error('API request failed for item ID: ' . $item['id']);
                // }
            });


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
