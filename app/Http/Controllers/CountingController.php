<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountingRequest;
use Illuminate\Http\Request;

class CountingController extends Controller
{
    public function getCounting(CountingRequest $request)
    {
        try {
            $result = app('db')->select(
                'select gateways.parking_id, gateways.type, gateways.timezone_offset, operators.operator_id
                from parkings, gateways, operators, currencies
                where parkings.gateway_id = gateways.id
                and gateways.operator_id = operators.id
                and parkings.id = ? limit 1',
                [$request->parking_id]
            );

            $data = [
                'operator_id' => $result[0]->operator_id,
                'parking_id' => $result[0]->parking_id,
            ];

            $ugateway = app('p-connector')->profile('ugateway');
            $ugateway->get('counting', $data);
            if ($ugateway->responseCodeNot(200)) {
                return response()->json([
                    'message' => 'ugateway_down',
                    'success' => false,
                ], 200);
            }

            $counting = [
                'counter_total' => $ugateway->counter_total,
                'counter_free' => $ugateway->counter_free,
                'counter_present' => $ugateway->counter_present,
            ];


            return [
                'data' =>  [$counting],
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "counting from gateway."
            ];
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
    public function editCounting(CountingRequest $request)
    {
        try {

            $result = app('db')->select(
                'select gateways.parking_id, gateways.type, gateways.timezone_offset, operators.operator_id
                from parkings, gateways, operators, currencies
                where parkings.gateway_id = gateways.id
                and gateways.operator_id = operators.id
                and parkings.id = ? limit 1',
                [$request->parking_id]
            );
            $data = [
                'operator_id' => $result[0]->operator_id,
                'parking_id' => $result[0]->parking_id,   
                'free_spots' => $request->free_spots,
                'total_spots' => $request->total_spots,
                
            ];
            
            $ugateway = app('p-connector')->profile('ugateway');
            $ugateway->put('edit/counting', $data);
            
            if ($ugateway->responseCodeNot(200)) {
                return response()->json([
                    'message' => 'ugateway_down',
                    'success' => false,
                ], 200);
            }

            $counting = [
                'counter_total' => $ugateway->counter_total,
                'counter_free' => $ugateway->counter_free,
                'counter_present' => $ugateway->counter_present,
            ];

            return [
                'data' =>  [$counting],
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "counting edited successfully."
            ];

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
