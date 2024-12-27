<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountingRequest;
use App\Http\Requests\ShiftRequest;
use Illuminate\Http\Request;

class ShiftController extends Controller
{

    /**
     * @OA\Post(
     *      path="/api/check/shift",
     *      operationId="checkShift",
     *      tags={"Shift Configuration"},
     *      security={{"bearerAuth": {}}},
     *      summary="check Shift",
     * 
     *   @OA\Parameter(
     *         name="Accept",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             default="application/json"
     *         )
     * ),
     * 
     *      @OA\Parameter(
     *          name="parking_id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *     @OA\Response(
     *         response="200",
     *         description="check Shift",
     *         @OA\JsonContent(
     *             type="object",
     *         )
     *     ),
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     */

    public function checkShift(CountingRequest $request)
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

            if ($result[0]->type == "Ugateway") {

                    if ((int)$result[0]->cashier_consumer_id) {

                        // Check for an open shift
                        $dataShift = [
                            'operator_id' => (int)$result[0]->operator_id,
                            'parking_id' => (int)$result[0]->parking_id,
                            'user_id' => (int)$result[0]->cashier_consumer_id,
                        ];

                        $gateway_shift = app('p-connector')->profile('ugateway');
                        $gateway_shift->get('shift', $dataShift);

                        if ($gateway_shift->responseCodeNot(200)) {

                            return [
                                'data' =>  [],
                                'status' =>  false,
                                'responseCode' =>  404,
                                'message' => "shift not found."
                            ];
                        }

                        return [
                            'data' =>  [],
                            'status' =>  true,
                            'responseCode' =>  200,
                            'message' => "open shift is founded."
                        ];
                    }
            }
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


    /**
     * @OA\Post(
     *      path="/api/open/shift",
     *      operationId="openShift",
     *      tags={"Shift Configuration"},
     *      security={{"bearerAuth": {}}},
     *      summary="Open Shift",
     * 
     *   @OA\Parameter(
     *         name="Accept",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             default="application/json"
     *         )
     * ),
     * 
     *      @OA\Parameter(
     *          name="parking_id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="starting_amount",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *     @OA\Response(
     *         response="200",
     *         description="check Shift",
     *         @OA\JsonContent(
     *                  @OA\Schema(
     *                      example={
     *                           "status": "string",
     *                           "shift_id": "integer",
     *                           "strat_shift_date": "datetime",
     *                           "end_shift_date": "datetime",
     *                           "total_revenue": "integer",
     *                           "revenue_details": "string",
     *                           "starting_amount": "integer",
     *                           "total_amount": "integer",
     *                       }
     * ),
     *             type="object",
     *         )
     *     ),
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     */

    public function openShift(ShiftRequest $request)
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

            if ($result[0]->type == "Ugateway") {

                    if ((int)$result[0]->cashier_consumer_id) {

                        // Check for an open shift
                        $dataShift = [
                            'operator_id' => (int)$result[0]->operator_id,
                            'parking_id' => (int)$result[0]->parking_id,
                            'user_id' => (int)$result[0]->cashier_consumer_id,
                        ];

                        $gateway_shift = app('p-connector')->profile('ugateway');
                        $gateway_shift->get('shift', $dataShift);

                        if ($gateway_shift->responseCodeNot(200)) {
                            $open_shift = app('p-connector')->profile('ugateway');
                            $open_shift->post('shift/open', $dataShift);

                            return [
                                'data' =>  [],
                                'status' =>  true,
                                'responseCode' =>  200,
                                'message' => "shift successfully opened."
                            ];
                        }

                        $closing_shift = [
                            'operator_id' => (int)$result[0]->operator_id,
                            'parking_id' => (int)$result[0]->parking_id,
                            'user_id' => (int)$result[0]->cashier_consumer_id,
                            'shift_id' => $gateway_shift->shift_id,
                        ];

                        $result = app('db')->select(
                            'SELECT *
                            FROM transactions
                            WHERE JSON_EXTRACT(product, \'$.shift_id\') = ?',
                            [$gateway_shift->shift_id]
                        );

                        $collection = collect($result);
                        $total_revenue = $collection->sum('paid_amount');

                        $groupedResults = $collection->groupBy('provider');
                        $totals = $groupedResults->map(function ($transactions) {
                            return $transactions->sum('paid_amount');
                        });


                        $close_shift = app('p-connector')->profile('ugateway');
                        $close_shift->put('shift/close', $closing_shift);

                        if ($close_shift->responseCodeNot(200)) {
                            return [
                                'error' =>  [],
                                'status' =>  false,
                                'responseCode' =>  500,
                                'message' => "closing shift failed, check gateway."
                            ];
                        }

                        $shift_report = [
                            'shift_id' => $gateway_shift->shift_id,
                            'strat_shift_date' => $close_shift->create_date_time,
                            'end_shift_date' => $close_shift->close_date_time,
                            'total_revenue' => $total_revenue,
                            'revenue_details' => $totals,
                            'starting_amount' => $request->starting_amount,
                            'total_amount' => ($total_revenue + $request->starting_amount)
                        ];

                        $open_shift = app('p-connector')->profile('ugateway');
                        $open_shift->post('shift/open', $dataShift);

                        if ($open_shift->responseCodeNot(200)) {
                            return [
                                'error' =>  [],
                                'status' =>  false,
                                'responseCode' =>  500,
                                'message' => "opening shift failed, check gateway."
                            ];
                        }

                        return [
                            'data' =>  [$shift_report],
                            'status' =>  true,
                            'responseCode' =>  200,
                            'message' => "shift successfully closed, new shift is open now"
                        ];
                    }

                    return [
                        'error' =>  [],
                        'status' =>  false,
                        'responseCode' =>  404,
                        'message' => "wrong or missing shift user."
                    ];
                }
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
    
    /**
     * @OA\Post(
     *      path="/api/close/shift",
     *      operationId="closeShift",
     *      tags={"Shift Configuration"},
     *      security={{"bearerAuth": {}}},
     *      summary="Close old Shift and open new one",
     * 
     *   @OA\Parameter(
     *         name="Accept",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             default="application/json"
     *         )
     * ),
     * 
     *      @OA\Parameter(
     *          name="parking_id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="starting_amount",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *     @OA\Response(
     *         response="200",
     *         description="check Shift",
     *         @OA\JsonContent(
     *                      example={
     *                           "shift_id": "integer",
     *                           "strat_shift_date": "datetime",
     *                           "end_shift_date": "datetime",
     *                           "total_revenue": "integer",
     *                           "revenue_details": "string",
     *                           "starting_amount": "integer",
     *                           "total_amount": "integer",
     *                       },
     *             type="object",
     *         )
     *     ),
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     */

    public function closeShift(ShiftRequest $request)
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

            if ($result[0]->type == "Ugateway") {

                    if ((int)$result[0]->cashier_consumer_id) {

                        // Check for an open shift
                        $dataShift = [
                            'operator_id' => (int)$result[0]->operator_id,
                            'parking_id' => (int)$result[0]->parking_id,
                            'user_id' => (int)$result[0]->cashier_consumer_id,
                        ];

                        $gateway_shift = app('p-connector')->profile('ugateway');
                        $gateway_shift->get('shift', $dataShift);

                        if ($gateway_shift->responseCodeNot(200)) {
                            return [
                                'error' =>  [],
                                'status' =>  false,
                                'responseCode' =>  404,
                                'message' => "shift not found, please check the gateway."
                            ];
                        }

                        $closing_shift = [
                            'operator_id' => (int)$result[0]->operator_id,
                            'parking_id' => (int)$result[0]->parking_id,
                            'user_id' => (int)$result[0]->cashier_consumer_id,
                            'shift_id' => $gateway_shift->shift_id,
                        ];

                        $result = app('db')->select(
                            'SELECT *
                            FROM transactions
                            WHERE JSON_EXTRACT(product, \'$.shift_id\') = ?',
                            [$gateway_shift->shift_id]
                        );

                        $collection = collect($result);
                        $total_revenue = $collection->sum('paid_amount');

                        $groupedResults = $collection->groupBy('provider');
                        $totals = $groupedResults->map(function ($transactions) {
                            return $transactions->sum('paid_amount');
                        });


                        $close_shift = app('p-connector')->profile('ugateway');
                        $close_shift->put('shift/close', $closing_shift);

                        if ($close_shift->responseCodeNot(200)) {
                            return [
                                'error' =>  [],
                                'status' =>  false,
                                'responseCode' =>  500,
                                'message' => "closing shift failed, check gateway."
                            ];
                        }

                        $shift_report = [
                            'shift_id' => $gateway_shift->shift_id,
                            'strat_shift_date' => $close_shift->create_date_time,
                            'end_shift_date' => $close_shift->close_date_time,
                            'total_revenue' => $total_revenue,
                            'revenue_details' => $totals,
                            'starting_amount' => $request->starting_amount,
                            'total_amount' => ($total_revenue + $request->starting_amount)
                        ];

                        return [
                            'data' =>  [$shift_report],
                            'status' =>  true,
                            'responseCode' =>  200,
                            'message' => "shift successfully closed."
                        ];
                    }

                    return [
                        'error' =>  [],
                        'status' =>  false,
                        'responseCode' =>  404,
                        'message' => "wrong or missing shift user."
                    ];

            }
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
