<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnforcementRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use DateTime;
use DateTimeZone;

class EnforcementController extends Controller
{
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

    public function checkEnforcement(EnforcementRequest $request)
    {

        $result = app('db')->select(
            'select enforced_license_plate, amount, cause, type
        from enforcements
        where enforcements.enforced_license_plate = ?
        order by id desc limit 1',
            [$request->enforced_license_plate]
        );

        if ($result) {

            return [
                'data' =>  $result,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "plate enforced."
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

        dd($product);

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

                $salt = substr(str_shuffle(str_repeat(
                    $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
                    ceil(5 / strlen($x))
                )), 1, 5);

                $data = [
                    'identifier' => env('PAYMENT_IDENTIFIER', 'bmoov_express'),
                    'tenant' => tenant()->id,
                    'provider' => $request->provider,
                    // 'amount' => number_format($ugateway->ticket_amount / $result[0]->round ?? 1, $result[0]->fractional_part, '.', ''),
                    // 'original_amount' => number_format($ugateway->original_amount / $result[0]->round ?? 1, $result[0]->fractional_part, '.', ''),

                    // 'amount' => number_format($ugateway->ticket_amount / pow(10,  $result[0]->fractional_part), 3, '.', ''),
                    // 'original_amount' => $ugateway->amount_ht / pow(10,  $result[0]->fractional_part),
                    // 'promotion_id' => $promotion_id,
                    // 'tariff_class' => $tariff_class,
                    'currency' => $result[0]->code,
                    'client' => $request->get('client', '[]'),
                    'product' => json_encode([
                        'parking_id' => $request->qr,
                        'license_plate' => $request->ticket_value,
                        'plate_info' => $request->plate_info,
                        'phone_number' => $request->phone_number,
                        'type' => 'license_plate',
                        'value' => $request->ticket_value,
                        'parking_spot_description' => $request->parking_spot_description,
                        // 'entry_datetime' => (new DateTime($ugateway->ticket_entry_time))->format(locale_datetime_format()),
                        // 'duration' => CarbonInterval::create(iso8601_duration($ugateway->ticket_duration))
                        //     ->locale(app()->getLocale())->forHumans(['parts' => 4, 'join' => true]),
                        // 'amount' => number_format(
                        //     $ugateway->ticket_amount / ($result[0]->round ?? 1),
                        //     $result[0]->fractional_part ?? 3
                        // ) . ' ' . ($result[0]->symbol ?? ''),
                        // 'ticket_expiration_time' => (new DateTime($ugateway->ticket_expiration_time))->format(locale_datetime_format()),
                        // 'end_tariff_time' => $ugateway->ticket_expiration_time,
                    ]),

                    'meta_data' => ['parking_qr' => $salt . base64_encode($request->qr)],
                    'application_type' => 'web:react-js',
                    // 'application_version' => TenancyServiceProvider::VERSION,
                    'type' => 'ONE_TIME_PAYMENT', // or maybe INSTALLMENT
                    'ip_address' => optional(json_decode($request->get('client', '[]')))->IPv4 ?? '0.0.0.0',
                    // 'payment_type' => $payment_type,
                ];

                /** @var \MedianetDev\PConnector\PConnector $payment */
                $payment = app('p-connector')->profile('payment');

                $payment->post('get/interface', $data);

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
}
