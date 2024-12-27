<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnforcementRequest;
use App\Providers\TenancyServiceProvider as ProvidersTenancyServiceProvider;
use App\Tenancy\TenancyServiceProvider;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use DateTime;
use DateTimeZone;

class EnforcementController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/get/products",
     *      operationId="getEnforcementProducts",
     *      tags={"Enforcement"},
     *      security={{"bearerAuth": {}}},
     *      summary="get enforcement products",
     * 
     *   @OA\Parameter(
     *         name="Accept",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             default="application/json"
     *           )
     *        ),
     * 
     *     @OA\Response(
     *         response="200",
     *         description="get enforcement products",
     *         @OA\JsonContent(
     *             type="object",
     *         )
     *     ),
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     */
    public function getProduct(EnforcementRequest $request)
    {
        $product = app('db')->select(
            'select type, gravity, amount
            from enforcement_products'
        );

        return [
            'data' =>  $product,
            'status' =>  true,
            'responseCode' =>  200,
            'message' => "Done successfully."
        ];
    }

      /**
     * @OA\Post(
     *      path="/api/make/enforcement",
     *      operationId="makeEnforcement",
     *      tags={"Enforcement"},
     *      security={{"bearerAuth": {}}},
     *      summary="make Enforcement",
     * 
     *   @OA\Parameter(
     *         name="Accept",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             default="application/json"
     *         )
     *      ),
     * 
     *      @OA\Parameter(
     *          name="parking_id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="agent_name",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              default= "---"
     *          )
     *      ),
     *
     *      @OA\Parameter(
     *          name="type",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              default= "***"
     *          )
     *      ),
     *
     *      @OA\Parameter(
     *          name="gravity",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              default= "***"
     *          )
     *      ),
     *
     *      @OA\Parameter(
     *          name="cause",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              default= "***"
     *          )
     *      ),
     * 
     *
     *      @OA\Parameter(
     *          name="amount",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              default=00
     *          )
     *      ),
     * 
     *      @OA\Parameter(
     *          name="license_plate",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     * 
     *      @OA\Parameter(
     *          name="phone_number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     * 
     *      @OA\Parameter(
     *          name="enforced_car_picture",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              default="----"
     *          )
     *      ),
     * 
     * 
     *     @OA\Response(
     *         response="200",
     *         description="make enforcement",
     *         @OA\JsonContent(
     *             type="object",
     *         )
     *     ),
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     */

    public function makeEnforcement(EnforcementRequest $request)
    {
        try {

            // get the parking_id and operator_id ready
            $result = app('db')->select(
                'select parkings.paid_grace_period, gateways.shift_id, gateways.parking_type, gateways.type, gateways.cashier_contract_id, gateways.cashier_consumer_id, gateways.shift_sub_user,  parkings.promotion_id, currencies.round, currencies.fractional_part, currencies.symbol, currencies.code, gateways.parking_id, gateways.timezone_offset, operators.operator_id
            from parkings, gateways, operators, currencies
            where parkings.gateway_id = gateways.id
            and gateways.operator_id = operators.id
            and gateways.currency_id = currencies.id
            and parkings.id = ? limit 1',
                [$request->parking_id]
            );

            if ($result[0]->type == "Ugateway") {

                if ($result[0]->parking_type == "on_street") {

                    // check if shift is valid
                    $dataShift = [
                        'operator_id' => (int)$result[0]->operator_id,
                        'parking_id' => (int)$result[0]->parking_id,
                        'user_id' => (int)$result[0]->cashier_consumer_id,
                    ];

                    $gateway_shift = app('p-connector')->profile('ugateway');
                    $gateway_shift->get('shift', $dataShift);

                    if ($gateway_shift->responseCodeNot(200)) {
                        return response()->json([
                            'message' => trans_db('validation', 'missing shift ID'),
                            'success' => false,
                        ], 200);
                    }

                    $enforcement_reference = rand(100000000000, 999999999999);

                    $enforcement_data = [
                        'tenant' => tenant()->id,
                        'parking' => $request->parking_id,
                        'agent_shift' => $gateway_shift->shift_id,
                        'agent_name' => $request->agent_name,
                        'enforcement_date' => (new DateTime('now', new DateTimeZone($result[0]->timezone_offset ?? 'UTC')))->format('Y-m-d H:i:s'),
                        'type' => $request->type,
                        'gravity' => $request->gravity,
                        'cause' => $request->cause,
                        'amount' => $request->amount,

                        // 'amount' => number_format(
                        //     $request->amount,
                        //     $result[0]->fractional_part ?? 3
                        // ) . ' ' . ($result[0]->symbol ?? ''),

                        'payment_status' => 'pending',
                        'payment_methode' => $request->payment_methode,
                        'enforcement_date_payment' => '00-00-00 00:00:00',
                        'enforcement_reference' => $enforcement_reference,
                        'enforced_license_plate' => $request->license_plate,
                        'enforced_phone_number' => $request->phone_number,
                        'enforced_car_picture' => $request->enforced_car_picture,

                        //  'currency' => $result[0]->code,                 
                    ];


                    BmoovController::saveEnforcement($enforcement_data);

                    $gateway_data = [
                        'operator_id' => (int)$result[0]->operator_id,
                        'parking_id' => (int)$result[0]->parking_id,
                        'agent_shift' => $gateway_shift->shift_id,
                        'agent_name' => $request->agent_name,
                        'enforcement_date' => (new DateTime('now', new DateTimeZone($result[0]->timezone_offset ?? 'UTC')))->format('Y-m-d H:i:s'),
                        'type' => $request->type,
                        'gravity' => $request->gravity,
                        'cause' => $request->cause,
                        'amount' => $request->amount,
                        'payment_status' => 'pending',
                        'payment_methode' => '--',
                        'enforcement_reference' => $enforcement_reference,
                        'enforced_license_plate' => $request->license_plate,
                        'enforced_phone_number' => $request->phone_number,
                        'enforced_car_picture' => $request->enforced_car_picture,
                    ];


                    /** @var \MedianetDev\PConnector\PConnector $ugateway */

                    $ugateway = app('p-connector')->profile('ugateway');
                    $ugateway->post('make/enforcement', $gateway_data)->getResponseStatusCode();


                    if ($ugateway->responseCodeNot(200)) {

                        return [
                            'error' =>  [],
                            'status' =>  false,
                            'responseCode' =>  200,
                            'message' => "gateway down."
                        ];

                        $ugateway->log();
                    }
                } else {

                    return [
                        'error' =>  [],
                        'status' =>  false,
                        'responseCode' =>  200,
                        'message' => "this parking is not on street."
                    ];
                }
            } else {

                return [
                    'error' =>  [],
                    'status' =>  false,
                    'responseCode' =>  200,
                    'message' => "this parking belgons to another gateway."
                ];
            }

            $result = [
                'enforcement_date' => (new DateTime('now', new DateTimeZone($result[0]->timezone_offset ?? 'UTC')))->format('Y-m-d H:i:s'),
                'type' => $request->type,
                'gravity' => $request->gravity,
                'cause' => $request->cause,
                'amount' => number_format(
                    $request->amount,
                    $result[0]->fractional_part ?? 3
                ) . ' ' . ($result[0]->symbol ?? ''),
                'enforced_license_plate' => $request->license_plate,
                'enforced_phone_number' => $request->phone_number,
                'enforcement_reference' => $enforcement_reference
            ];

            return [
                'data' =>  $result,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "Done successfully."
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

     /**
     * @OA\Get(
     *      path="/api/check/enforcement",
     *      operationId="checkEnforcement",
     *      tags={"Enforcement"},
     *      security={{"bearerAuth": {}}},
     *      summary="check whether a given plate carries a penalty",
     * 
     *   @OA\Parameter(
     *         name="Accept",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             default="application/json"
     *           )
     *        ),
     *      @OA\Parameter(
     *          name="enforced_license_plate",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *          )
     *      ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="check whether a given plate carries a penalty",
     *         @OA\JsonContent(
     *             type="object",
     *         )
     *     ),
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     */
    public function checkEnforcement(EnforcementRequest $request)
    {

        $result = app('db')->select(
            'select enforced_license_plate, amount, cause, type, enforcement_reference
        from enforcements
        where enforcements.enforced_license_plate = ?
        order by id desc limit 1',
            [$request->enforced_license_plate]
        );

        if ($result) {

            $paid =  app('db')->select(
                'select enforced_license_plate, payment_methode
            from enforcements
            where enforcements.enforced_license_plate = ?
            order by id desc limit 1',
                [$request->enforced_license_plate]
            );

            if ($paid[0]->payment_methode == null) {

                return [
                    'data' =>  $result,
                    'status' =>  true,
                    'responseCode' =>  200,
                    'message' => "plate enforced."
                ];
            }

            return [
                'data' =>  $paid,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "enforcement paid."
            ];
            
        } else {

            return [
                'error' =>  [],
                'status' =>  false,
                'responseCode' =>  404,
                'message' => "this plate is not enforced."
            ];
        }
    }

    public function payEnforcement(EnforcementRequest $request)
    {

        $result = app('db')->select(
            'select parkings.paid_grace_period, gateways.shift_id, gateways.parking_type, gateways.type, gateways.cashier_contract_id, gateways.cashier_consumer_id, gateways.shift_sub_user,  parkings.promotion_id, currencies.round, currencies.fractional_part, currencies.symbol, currencies.code, gateways.parking_id, gateways.timezone_offset, operators.operator_id
        from parkings, gateways, operators, currencies
        where parkings.gateway_id = gateways.id
        and gateways.operator_id = operators.id
        and gateways.currency_id = currencies.id
        and parkings.id = ? limit 1',
            [$request->parking_id]
        );

        $product = app('db')->select(
            'select enforced_license_plate, amount, cause, type
        from enforcements
        where enforcements.enforced_license_plate = ? and enforcement_reference = ?
        order by id desc limit 1',
            [$request->enforced_license_plate, $request->enforcement_reference]
        );

        // dd($product);

        if ($result[0]->type == "Ugateway") {

            if ($result[0]->parking_type == "on_street") {

                // check if shift is valid
                $dataShift = [
                    'operator_id' => (int)$result[0]->operator_id,
                    'parking_id' => (int)$result[0]->parking_id,
                    'user_id' => (int)$result[0]->cashier_consumer_id,
                ];

                $gateway_shift = app('p-connector')->profile('ugateway');
                $gateway_shift->get('shift', $dataShift);

                if ($gateway_shift->responseCodeNot(200)) {

                    return response()->json([
                        'message' => trans_db('validation', 'missing shift ID'),
                        'success' => false,
                    ], 200);
                }

                $data = [
                    'identifier' => env('PAYMENT_IDENTIFIER', 'bmoov_parkimapro'),
                    'tenant' => tenant()->id,
                    'provider' => $request->payment_methode,
                    'amount' => number_format($request->amount / $result[0]->round ?? 1, $result[0]->fractional_part, '.', ''),
                    'currency' => $result[0]->code,
                    'client' =>  "{}",

                    'product' => json_encode([
                        'enforcement_date' => (new DateTime('now', new DateTimeZone($result[0]->timezone_offset ?? 'UTC')))->format('Y-m-d H:i:s'),
                        'type' => $request->type,
                        'gravity' => $request->gravity,
                        'cause' => $request->cause,
                        'amount' => number_format(
                            $request->amount,
                            $result[0]->fractional_part ?? 3
                        ) . ' ' . ($result[0]->symbol ?? ''),
                        'enforced_license_plate' => $request->license_plate,
                        'enforced_phone_number' => $request->phone_number,

                    ]),

                    'application_type' => 'web:react-js',
                    'application_version' => "1.0.0",
                    'type' => 'ONE_TIME_PAYMENT',
                    'ip_address' => optional(json_decode($request->get('client', '[]')))->IPv4 ?? '0.0.0.0',
                    'payment_type' =>  'enforcement_payment',
                ];

                /** @var \MedianetDev\PConnector\PConnector $payment */
                $payment = app('p-connector')->profile('payment');

                $payment->post('get/interface', $data);

                dd($payment);

                return 200 == $payment->getResponseStatusCode()
                    ? $this->saveTransactionAndReturnResponse(array_merge($data, [
                        'parking' => $request->qr,
                    ]), $payment->getResponseBody())
                    : response()->json([
                        'message' => trans_db('validation', 'payment_pay_engine_down'),
                        'success' => false,
                    ], 200);
            } else {

                return [
                    'error' =>  [],
                    'status' =>  false,
                    'responseCode' =>  200,
                    'message' => "this parking is not on street."
                ];
            }
        } else {

            return [
                'error' =>  [],
                'status' =>  false,
                'responseCode' =>  200,
                'message' => "this parking belgons to another gateway."
            ];
        }

        $result = [];

        return [
            'data' =>  $result,
            'status' =>  true,
            'responseCode' =>  200,
            'message' => "Done successfully."
        ];
    }



      /**
     * @OA\Post(
     *      path="/api/cashPay/enforcement",
     *      operationId="payEnforcement",
     *      tags={"Enforcement"},
     *      security={{"bearerAuth": {}}},
     *      summary="cash pay enforcement",
     * 
     *   @OA\Parameter(
     *         name="Accept",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             default="application/json"
     *         )
     *      ),
     * 
     *      @OA\Parameter(
     *          name="parking_id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *          )
     *      ),
     *
     *      @OA\Parameter(
     *          name="enforced_license_plate",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *          )
     *      ),
     *
     *      @OA\Parameter(
     *          name="enforcement_reference",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *          )
     *      ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="make enforcement",
     *         @OA\JsonContent(
     *             type="object",
     *         )
     *     ),
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     */
    public function cashPayEnforcement(EnforcementRequest $request)
    {
        $result = app('db')->select(
            'select parkings.paid_grace_period, gateways.shift_id, gateways.parking_type, gateways.type, gateways.cashier_contract_id, gateways.cashier_consumer_id, gateways.shift_sub_user,  parkings.promotion_id, currencies.round, currencies.fractional_part, currencies.symbol, currencies.code, gateways.parking_id, gateways.timezone_offset, operators.operator_id
        from parkings, gateways, operators, currencies
        where parkings.gateway_id = gateways.id
        and gateways.operator_id = operators.id
        and gateways.currency_id = currencies.id
        and parkings.id = ? limit 1',
            [$request->parking_id]
        );
        // dd($result[0]->type );
        if ($result[0]->type == "Ugateway") {

            if ($result[0]->parking_type == "on_street") {

                // check if shift is valid
                $dataShift = [
                    'operator_id' => (int)$result[0]->operator_id,
                    'parking_id' => (int)$result[0]->parking_id,
                    'user_id' => (int)$result[0]->cashier_consumer_id,
                ];

                $gateway_shift = app('p-connector')->profile('ugateway');
                $gateway_shift->get('shift', $dataShift);
                if ($gateway_shift->responseCodeNot(200)) {

                    return response()->json([
                        'message' => trans_db('validation', 'missing shift ID'),
                        'success' => false,
                    ], 200);
                }

                $data = [
                    'operator_id' => (int)$result[0]->operator_id,
                    'parking_id' => (int)$result[0]->parking_id,
                    'tenant' => tenant()->id,
                    'payment_methode' => 'cash-payment',
                    'payment_status' => 'paid',
                    'enforcement_date_payment' => (new DateTime('now', new DateTimeZone($result[0]->timezone_offset ?? 'UTC')))->format('Y-m-d H:i:s'),
                    'enforcement_reference' => $request->enforcement_reference,
                    'enforced_license_plate' => $request->enforced_license_plate,
                    // 'enforced_phone_number' => $request->phone_number,
                ];

                $gateway_payment = app('p-connector')->profile('ugateway');
                $gateway_payment->post('cash/pay', $data);

                if ($gateway_payment->responseCodeNot(200)) {

                    $data['payment_status'] = 'failed';

                    return response()->json([
                        'message' => trans_db('validation', 'missing shift ID'),
                        'success' => false,
                    ], 200);
                }

                $data['payment_status'] = 'booked';

                if ($data['payment_status'] === 'paid' || $data['payment_status'] === 'booked') {

                    // Get enforcement

                    $product = app('db')->select(
                        'select enforced_license_plate, amount, cause, type
                    from enforcements
                    where enforcements.enforced_license_plate = ? and enforcement_reference = ?
                    order by id desc limit 1',
                        [$request->enforced_license_plate, $request->enforcement_reference]
                    );

                    //  dd($product);

                    // $enforcement_reference = $data['enforcement_reference'] . '-' . $gateway_shift->shift_id;
                    $status = 'booked';
                    $payment_methode = 'cash-payment';

                    app('db')->update(
                        'update enforcements set payment_methode = \'' . $payment_methode . '\',payment_status = \'' . $status . '\',enforcement_date_payment = \''  . date('Y-m-d H:i:s') . '\'  where enforcements.enforced_license_plate = ? and enforcement_reference = ?
                    order by id desc limit 1',
                        [$request->enforced_license_plate, $request->enforcement_reference]
                    );
                } else {

                    $status = 'failed';
                    $payment_methode = 'cash-payment';

                    app('db')->update(
                        'update enforcements set payment_methode = \'' . $payment_methode . '\',payment_status = \'' . $status . '\',enforcement_date_payment = \'' . date('Y-m-d H:i:s') . '\'  where enforcements.enforced_license_plate = ? and enforcement_reference = ?
                    order by id desc limit 1',
                        [$request->enforced_license_plate, $request->enforcement_reference]
                    );
                }

                $product = app('db')->select(
                    'select enforced_license_plate, amount, cause, type, enforcement_date_payment
                from enforcements
                where enforcements.enforced_license_plate = ? and enforcement_reference = ?
                order by id desc limit 1',
                    [$request->enforced_license_plate, $request->enforcement_reference]
                );

                $receipt = [
                    'amount' => $product[0]->amount,
                    'cause' => $product[0]->cause,
                    'type' => $product[0]->type,
                    'enforcement_date_payment' => $product[0]->enforcement_date_payment,
                    'enforced_license_plate' => $product[0]->enforced_license_plate,
                ];

                return [
                    'data' =>  $receipt,
                    'status' =>  true,
                    'responseCode' =>  200,
                    'message' => "enforcement paid successfully."
                ];
            } else {

                return [
                    'error' =>  [],
                    'status' =>  false,
                    'responseCode' =>  200,
                    'message' => "this parking is not on street."
                ];
            }
        } else {

            return [
                'error' =>  [],
                'status' =>  false,
                'responseCode' =>  200,
                'message' => "this parking belgons to another gateway."
            ];
        }

        $result = [];

        return [
            'data' =>  $result,
            'status' =>  true,
            'responseCode' =>  200,
            'message' => "Done successfully."
        ];
    }
}
