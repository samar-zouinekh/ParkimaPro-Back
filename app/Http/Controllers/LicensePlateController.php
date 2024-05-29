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
            $data = [
                [
                    "id" => 1549,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "tu",
                        "background" => "---",
                        "inputPartOne" => "369",
                        "inputPartTwo" => "6589",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "3",
                    "status" => "ended",
                    "remaining_duration" => "0",
                ],
                [
                    "id" => 1550,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "rs",
                        "background" => "---",
                        "inputPartOne" => "3696589",
                        "inputPartTwo" => "",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "1",
                    "status" => "active",
                    "remaining_duration" => "178",
                ],
                [
                    "id" => 1551,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "tng",
                        "background" => "---",
                        "inputPartOne" => "36",
                        "inputPartTwo" => "96589",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "2",
                    "status" => "active",
                    "remaining_duration" => "60",
                ],
                [
                    "id" => 1552,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "tu",
                        "background" => "---",
                        "inputPartOne" => "369",
                        "inputPartTwo" => "6589",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "1",
                    "status" => "ended",
                    "remaining_duration" => "0",
                ],
                [
                    "id" => 1553,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "ag",
                        "background" => "---",
                        "inputPartOne" => "3696589",
                        "inputPartTwo" => "",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "1",
                    "status" => "active",
                    "remaining_duration" => "30",
                ],
                [
                    "id" => 1554,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "tu",
                        "background" => "---",
                        "inputPartOne" => "369",
                        "inputPartTwo" => "6589",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "2",
                    "status" => "ended",
                    "remaining_duration" => "0",
                ],
                [
                    "id" => 1555,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "ly",
                        "background" => "---",
                        "inputPartOne" => "3696589",
                        "inputPartTwo" => "",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "3",
                    "status" => "active",
                    "remaining_duration" => "120",
                ],
                [
                    "id" => 1556,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "eu",
                        "background" => "---",
                        "inputPartOne" => "3696589",
                        "inputPartTwo" => "",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "1",
                    "status" => "active",
                    "remaining_duration" => "35",
                ],
                [
                    "id" => 1557,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "eu",
                        "background" => "---",
                        "inputPartOne" => "3696589",
                        "inputPartTwo" => "",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "1",
                    "status" => "ended",
                    "remaining_duration" => "0",
                ],
                [
                    "id" => 1558,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "other",
                        "background" => "---",
                        "inputPartOne" => "3696589",
                        "inputPartTwo" => "",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "2",
                    "status" => "active",
                    "remaining_duration" => "90",
                ],
                [
                    "id" => 1559,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "tu",
                        "background" => "---",
                        "inputPartOne" => "369",
                        "inputPartTwo" => "6589",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "2",
                    "status" => "ended",
                    "remaining_duration" => "0",
                ],
                [
                    "id" => 1560,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "tu",
                        "background" => "---",
                        "inputPartOne" => "369",
                        "inputPartTwo" => "6589",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "1",
                    "status" => "active",
                    "remaining_duration" => "60",
                ],
                [
                    "id" => 1561,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "tu",
                        "background" => "---",
                        "inputPartOne" => "369",
                        "inputPartTwo" => "6589",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "3",
                    "status" => "active",
                    "remaining_duration" => "10",
                ],
                [
                    "id" => 1562,
                    "license_plate" => "3696589",
                    "plate_info" => [
                        "type" => "tu",
                        "background" => "---",
                        "inputPartOne" => "369",
                        "inputPartTwo" => "6589",
                    ],
                    "phone_number" => "+21654100503",
                    "duration" => "1",
                    "status" => "active",
                    "remaining_duration" => "20",
                ],

            ];
            return [
                'data' =>  $data,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "License plate list."
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


    public function plateList(LicensePlateRequest $request)
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

            if (!$result) {
                return [
                    'error' =>  [],
                    'status' =>  false,
                    'responseCode' =>  404,
                    'message' => "Parking not found."
                ];
            }

            $transactions =  app('db')->select(
                'select transactions.id, transactions.product, transactions.reference, transactions.payment_type, transactions.created_at
            from transactions
            WHERE parking_id = ?
            AND payment_type = ?
            AND created_at >= NOW() - INTERVAL 1 DAY',
                [$request->parking_id, 'pre_payment']
            );

            // dd($transactions);

            if (!$transactions) {
                return [
                    'error' =>  [],
                    'status' =>  false,
                    'responseCode' =>  404,
                    'message' => "transactions not found."
                ];
            }

            $product = array();

            foreach ($transactions as $transaction) {

                $productData = json_decode($transaction->product, true);

                $product[] = [
                    "licensePlate" => $productData['license_plate'],
                    "parkingSpotDescription" => $productData['parking_spot_description'],
                    "payment_reference" => $transaction->reference,
                    "plate_info" =>  $productData['plate_info'],
                    "phone_number" =>  '+'.$productData['phone_number'],

                ];
            }

            $data = [
                'operator_id' => $result[0]->operator_id,
                'parking_id' => $result[0]->parking_id,
            ];

            $ugateway = app('p-connector')->profile('ugateway');
            $ugateway->get('session/status/list', $data);

            if ($ugateway->responseCodeNot(200)) {
                return response()->json([
                    'message' => 'ugateway_down',
                    'success' => false,
                ], 200);
            }
            $firstCollection = collect($product)->keyBy('payment_reference')->toArray();
            $secondCollection = collect($ugateway->getResponseBody() ?? [])->keyBy('payment_reference')->toArray();

            $tab = [];
            foreach ($firstCollection as $paymentReference => $item) {
                if ($item['payment_reference'] == $paymentReference) {
                    $tab[$paymentReference] = [
                        'licensePlate' => $item['licensePlate'],
                        'payment_reference' => $item['payment_reference'],
                        'plate_info' => $item['plate_info'],
                        'phone_number' => $item['phone_number'],

                        'expiry_date' => !empty($secondCollection[$paymentReference]->expiry_date) ? $secondCollection[$paymentReference]->expiry_date : null,
                        'status' => !empty($secondCollection[$paymentReference]->status) ? $secondCollection[$paymentReference]->status : null,
                        'ticket_duration' => !empty($secondCollection[$paymentReference]->ticket_duration) ? $secondCollection[$paymentReference]->ticket_duration : null,
                        'duration_remaining' => !empty($secondCollection[$paymentReference]->duration_remaining) ? $secondCollection[$paymentReference]->duration_remaining : null,
                    ];
                }
            }

            $tab = collect($tab)->sortByDesc('expiry_date');
            $tab2 = [];
            foreach ($tab as $expiry_date => $item) {
                if (!empty($item) && $item !== null) {
                    $tab2[] = $item;
                }
            }
            foreach ($tab as $licensePlate => $item) {
                if (!isset($tab2[$licensePlate])) {
                    array_push($tab2, $item);
                }
            }

            return [
                'data' =>  $tab2,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "License plate list."
            ];
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
