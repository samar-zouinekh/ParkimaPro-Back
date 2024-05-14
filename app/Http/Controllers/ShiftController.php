<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountingRequest;
use App\Http\Requests\ShiftRequest;
use Illuminate\Http\Request;

class ShiftController extends Controller
{

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

                if ($result[0]->parking_type == "off_street") {

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

                return [
                    'error' =>  [],
                    'status' =>  false,
                    'responseCode' =>  500,
                    'message' => "wrong or missing parking type."
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

                if ($result[0]->parking_type == "off_street") {

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

                return [
                    'error' =>  [],
                    'status' =>  false,
                    'responseCode' =>  500,
                    'message' => "wrong or missing parking type."
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

                if ($result[0]->parking_type == "off_street") {

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

                return [
                    'error' =>  [],
                    'status' =>  false,
                    'responseCode' =>  500,
                    'message' => "wrong or missing parking type."
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
