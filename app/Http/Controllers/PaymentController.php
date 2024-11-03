<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Http\Requests\TicketRequest;
use App\Providers\TenancyServiceProvider as ProvidersTenancyServiceProvider;
use Illuminate\Http\Request;
use App\Tenancy\TenancyServiceProvider;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use DateTime;
use DateTimeZone;

class PaymentController extends Controller
{

    public function options()
    {

        switch (tenant()->id) {
            case 'demo':
                $options = [
                    ['key' => 'cash_payment', 'name' => 'Cash Payment', 'logo' => url('images/cash.png')],
                    ['key' => 'wallet', 'name' => 'wallet', 'logo' => url('images/wallet.png')],
                ];
                break;

            default:
                $options = [
                    ['key' => 'cash_payment', 'name' => 'Cash Payment', 'logo' => url('images/cash.png')],
                    ['key' => 'wallet', 'name' => 'wallet', 'logo' => url('images/wallet.png')],
                ];
                break;
        }

        return response()->json(compact('options'), 200);
    }

    public function postPayment(PaymentRequest $request)
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

                if ($result[0]->parking_type == "off_street") {

                    $payment_type = 'post_payment';

                    $data = [
                        'operator_id' => (int)$result[0]->operator_id,
                        'parking_id' => (int)$result[0]->parking_id,
                        'license_plate' => $request->license_plate,
                        'barcode' => $request->barcode,
                    ];

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

                    /** @var \MedianetDev\PConnector\PConnector $ugateway */

                    $ugateway = app('p-connector')->profile('ugateway');
                    $ugateway->get('media/postPayment/consult', $data);

                    if ($ugateway->responseCodeNot(200)) {
                        return response()->json([
                            'message' => trans_db('validation', 'payment_ugateway_down'),
                            'success' => false,
                        ], 200);
                    }
                    $tariff_class = null;
                    $promotion_id = null;
                    $ticket_type =  'Parkimapro_ticket';
                    $ticket_value = $request->ticket_value;

                    $duration = CarbonInterval::create(iso8601_duration($ugateway->ticket_duration))
                        ->locale(app()->getLocale())->forHumans(['parts' => 4, 'join' => true]);

                    $transaction_data = [
                        'tenant' => tenant()->id,
                        'parking' => $request->parking_id,
                        'provider' => $request->provider,
                        'amount' => number_format($ugateway->ticket_amount / pow(10,  $result[0]->fractional_part), 3, '.', ''),
                        'original_amount' => $ugateway->amount_ht / pow(10,  $result[0]->fractional_part),
                        'promotion_id' => $promotion_id,
                        'tariff_class' => $tariff_class,
                        'currency' => $result[0]->code,
                        'client' => $request->get('client', '[]'),
                        'product' => json_encode([
                            'parking_id' => $request->parking_id,
                            'shift_id' => $gateway_shift->shift_id,
                            'license_plate' => $request->license_plate,
                            'phone_number' => $request->phone_number,
                            'type' => $ticket_type,
                            'value' => $ticket_value,
                            'parking_spot_description' => "Default",
                            'entry_datetime' => (new DateTime($ugateway->ticket_entry_time))->format(locale_datetime_format()),
                            'payment_datetime' => (new DateTime('now', new DateTimeZone($result[0]->timezone_offset ?? 'UTC')))->format('Y-m-d H:i:s'),
                            'duration' => CarbonInterval::create(iso8601_duration($ugateway->ticket_duration))
                                ->locale(app()->getLocale())->forHumans(['parts' => 4, 'join' => true]),
                            'amount' => number_format(
                                $ugateway->ticket_amount / ($result[0]->round ?? 1),
                                $result[0]->fractional_part ?? 3
                            ) . ' ' . ($result[0]->symbol ?? ''),
                        ]),

                        'payment_type' => $payment_type,
                    ];

                    $originalDatetime = Carbon::createFromFormat('YmdHis', $ugateway->ticket_entry_time);
                    $ticket_entry_time = $originalDatetime->format('Y-m-d H:i:s');

                    // Split the string into day, hours, and minutes
                    list($days, $hours, $minutes) = explode('-', $ugateway->ticket_duration);
                    // Calculate total hours
                    $totalDurationInHours = ($days * 24) + $hours + ($minutes / 60);

                    BmoovController::saveTransaction($transaction_data, $request->ticket_id);

                    $gateway_data = [
                        'operator_id' => (int)$result[0]->operator_id,
                        'parking_id' => (int)$result[0]->parking_id,
                        'ticket_entry_time' => $ticket_entry_time,
                        'ticket_duration' => $totalDurationInHours,
                        'ticket_amount' => $ugateway->ticket_amount,
                        'pom_desc' =>  "---",
                        'shift_sub_user_id' => (int)$result[0]->shift_sub_user,
                        'license_plate' => $request->license_plate,
                    ];


                    /** @var \MedianetDev\PConnector\PConnector $ugateway */

                    $ugateway = app('p-connector')->profile('ugateway');
                    $ugateway->post('postPayment', $gateway_data)->getResponseStatusCode();

                    if ($ugateway->getResponseStatusCode() !== 204 && $ugateway->getResponseStatusCode() !== 200) {
                        // we should schedule this as cron task
                        $ugateway->log();
                    } else {
                        BmoovController::updateTransaction($request->ticket_id, 'booked', (int)$result[0]->shift_id);

                        $counting_data = [
                            'operator_id' => (int)$result[0]->operator_id,
                            'parking_id' => (int)$result[0]->parking_id,
                        ];

                        /** @var \MedianetDev\PConnector\PConnector $ugateway */
                        $ugateway_counting = app('p-connector')->profile('ugateway');
                        $ugateway_counting->put('decrement/counting', $counting_data);

                        if ($ugateway_counting->responseCodeNot(200)) {
                            return response()->json([
                                'message' => trans_db('validation', 'counting_ugateway_down'),
                                'success' => false,
                            ], 200);
                        }
                    }
                } else {
                    return [
                        'error' =>  [],
                        'status' =>  false,
                        'responseCode' =>  200,
                        'message' => "this parking is not off street."
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
                'parking_id' => (int)$result[0]->parking_id,
                'ticket_id' => $request->ticket_id,
                'entry_datetime' => (new DateTime($ugateway->ticket_entry_time))->format(locale_datetime_format()),
                'payment_datetime' => (new DateTime(
                    'now',
                    new DateTimeZone($result[0]->timezone_offset ?? 'UTC')
                ))->format(locale_datetime_format()),
                'duration' => $duration,
                'amount' => number_format($ugateway->ticket_amount / pow(10,  $result[0]->fractional_part), 3, '.', '') . ' ' . ($result[0]->symbol ?? ''),
            ];

            return [
                'data' =>  $result,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "payment done successfully."
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


    public function prePayment(TicketRequest $request)
    {

        $database = app('db');

        // get the parking_id and operator_id ready to be sent to entervo
        $result = $database->select(
            'select parkings.paid_grace_period, gateways.shift_id, gateways.parking_type, gateways.type, gateways.cashier_contract_id, gateways.cashier_consumer_id, gateways.shift_sub_user,  parkings.promotion_id, currencies.round, currencies.fractional_part, currencies.symbol, currencies.code, gateways.parking_id, gateways.timezone_offset, operators.operator_id
                from parkings, gateways, operators, currencies
                where parkings.gateway_id = gateways.id
                and gateways.operator_id = operators.id
                and gateways.currency_id = currencies.id
                and parkings.id = ? limit 1',
            [$request->parking_id]
        );

        // choose the profile to use
        if ($result[0]->type == "Ugateway") {

            if ($result[0]->parking_type == "on_street") {

                $payment_type = 'pre_payment';

                $data = [
                    'operator_id' => (int)$result[0]->operator_id,
                    'parking_id' => (int)$result[0]->parking_id,
                    'ticket_duration' => $request->ticket_duration,
                    'license_plate' => $request->license_plate,
                    'pom_desc' => "---",
                    'promotion' => 0,
                ];

                // check if shift is valid
                $dataShift = [
                    'operator_id' => (int)$result[0]->operator_id,
                    'parking_id' => (int)$result[0]->parking_id,
                    'user_id' => (int)$result[0]->cashier_consumer_id,
                ];

                $ugateway = app('p-connector')->profile('ugateway');
                $ugateway->get('shift', $dataShift);

                if ($ugateway->responseCodeNot(200)) {
                    return response()->json([
                        'message' => trans_db('validation', 'shift not found'),
                        'success' => false,
                    ], 200);
                }

                /** @var \MedianetDev\PConnector\PConnector $payment */

                $ugateway = app('p-connector')->profile('ugateway');
                $ugateway->get('media/prePayment/consult', $data);


                if ($ugateway->responseCodeNot(200)) {
                    return response()->json([
                        'message' => trans_db('validation', 'payment_ugateway_down'),
                        'success' => false,
                    ], 200);
                }

                $ticket_type =  'Parkimapro_prepayment';
                $reference = rand(100000, 999999);
                $tariff_class = null;
                $promotion_id = null;

                $payment = [
                    'parking' => $request->parking_id,
                    'product' => json_encode([
                        'parking_id' => $request->parking_id,
                        'license_plate' => $request->license_plate,
                        'plate_info' => $request->plate_info,
                        'phone_number' => $request->phone_number,
                        'type' => $ticket_type,
                        'value' => $reference,
                        'parking_spot_description' => "Default",
                        'entry_datetime' => (new DateTime($ugateway->ticket_entry_time))->format(locale_datetime_format()),
                        'duration' => CarbonInterval::create(iso8601_duration($ugateway->ticket_duration))
                            ->locale(app()->getLocale())->forHumans(['parts' => 4, 'join' => true]),
                        'amount' => number_format(
                            $ugateway->ticket_amount / ($result[0]->round ?? 1),
                            $result[0]->fractional_part ?? 3
                        ) . ' ' . ($result[0]->symbol ?? ''),
                        'ticket_expiration_time' => (new DateTime($ugateway->ticket_expiration_time))->format(locale_datetime_format()),
                        'end_tariff_time' => $ugateway->ticket_expiration_time,
                    ]),
                    'provider' => "cash_payment",
                    'amount' => number_format($ugateway->ticket_amount / pow(10,  $result[0]->fractional_part), 3, '.', ''),
                    'original_amount' => $ugateway->amount_ht / pow(10,  $result[0]->fractional_part),
                    'tariff_class' => $tariff_class,
                    'currency' => $result[0]->code,
                    'promotion_id' => $promotion_id,
                    'payment_type' => $payment_type,
                ];

                BmoovController::saveTransaction($payment, $reference);

                $gateway_data = [
                    'parking_id' => (int)$result[0]->parking_id,
                    'operator_id' => (int)$result[0]->operator_id,
                    'ticket_entry_time' => $ugateway->ticket_entry_time,
                    'ticket_duration' => $request->ticket_duration,
                    'ticket_amount' => $ugateway->ticket_amount,
                    'pom_desc' =>  "---",
                    'license_plate' => $request->license_plate,
                    'shift_sub_user_id' => (int)$result[0]->shift_sub_user,
                    'ticket_expiration_time' => $ugateway->ticket_expiration_time,
                    'payment_reference' => $reference,
                ];
                
                /** @var \MedianetDev\PConnector\PConnector $payment */
                
                $ugateway = app('p-connector')->profile('ugateway');
                $ugateway->post('prePayment', $gateway_data);
            
                if ($ugateway->responseCodeNot(200)) {
                    return response()->json([
                        'message' => trans_db('validation', 'payment_ugateway_down'),
                        'success' => false,
                    ], 200);
                }  
    
                if ($ugateway->getResponseStatusCode() !== 204 && $ugateway->getResponseStatusCode() !== 200) {
                    // we should schedule this as cron task
                    $ugateway->log();
                } else {
                    BmoovController::updateTransaction($reference, 'booked', (int)$result[0]->shift_id);

                }

            return [
                'data' =>  $gateway_data,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "payment done successfully."
            ];

            } else {

                return response()->json(['message' => 'This is an off street parking'], 500);
            }
        }
    }


    public function extension(TicketRequest $request)
    {
        $database = app('db');

        // get the parking_id and operator_id ready to be sent to entervo
        $result = $database->select(
            'select parkings.paid_grace_period, gateways.shift_id, gateways.parking_type, gateways.type, gateways.cashier_contract_id, gateways.cashier_consumer_id, gateways.shift_sub_user,  parkings.promotion_id, currencies.round, currencies.fractional_part, currencies.symbol, currencies.code, gateways.parking_id, gateways.timezone_offset, operators.operator_id
                from parkings, gateways, operators, currencies
                where parkings.gateway_id = gateways.id
                and gateways.operator_id = operators.id
                and gateways.currency_id = currencies.id
                and parkings.id = ? limit 1',
            [$request->parking_id]
        );

        // choose the profile to use
        if ($result[0]->type == "Ugateway") {

            if ($result[0]->parking_type == "on_street") {

                $payment_type = 'pre_payment_extension';

                $data = [
                    'operator_id' => (int)$result[0]->operator_id,
                    'parking_id' => (int)$result[0]->parking_id,
                    'ticket_duration' => $request->ticket_duration,
                    'license_plate' => $request->license_plate,
                    'pom_desc' =>  "---",
                    'promotion' => 0,
                ];

                // check if shift is valid
                $dataShift = [
                    'operator_id' => (int)$result[0]->operator_id,
                    'parking_id' => (int)$result[0]->parking_id,
                    'user_id' => (int)$result[0]->cashier_consumer_id,
                ];

                $ugateway = app('p-connector')->profile('ugateway');
                $ugateway->get('shift', $dataShift);

                if ($ugateway->responseCodeNot(200)) {
                    return response()->json([
                        'message' => trans_db('validation', 'payment_ugateway_down'),
                        'success' => false,
                    ], 200);
                }

                /** @var \MedianetDev\PConnector\PConnector $payment */
                $ugateway = app('p-connector')->profile('ugateway');
                $ugateway->get('media/extension/consult', $data);
                
                
                if ($ugateway->responseCodeNot(200)) {
                    return response()->json([
                        'message' => trans_db('validation', 'payment_ugateway_down'),
                        'success' => false,
                    ], 200);
                }
                     
                $ticket_type =  'Parkimapro_prepayment';
                $reference = rand(100000, 999999);
                $tariff_class = null;
                $promotion_id = null;

                $payment = [
                    'parking' => $request->parking_id,
                    'product' => json_encode([
                        'parking_id' => $request->parking_id,
                        'license_plate' => $request->license_plate,
                        'plate_info' => $request->plate_info,
                        'phone_number' => $request->phone_number,
                        'type' => $ticket_type,
                        'value' => $reference,
                        'parking_spot_description' => "Default",
                        'entry_datetime' => (new DateTime($ugateway->ticket_entry_time))->format(locale_datetime_format()),
                        'duration' => CarbonInterval::create(iso8601_duration($ugateway->ticket_duration))
                            ->locale(app()->getLocale())->forHumans(['parts' => 4, 'join' => true]),
                        'amount' => number_format(
                            $ugateway->ticket_amount / ($result[0]->round ?? 1),
                            $result[0]->fractional_part ?? 3
                        ) . ' ' . ($result[0]->symbol ?? ''),
                        'ticket_expiration_time' => (new DateTime($ugateway->ticket_expiration_time))->format(locale_datetime_format()),
                        'end_tariff_time' => $ugateway->ticket_expiration_time,
                    ]),
                    'provider' => "cash_payment",
                    'amount' => number_format($ugateway->ticket_amount / pow(10,  $result[0]->fractional_part), 3, '.', ''),
                    'original_amount' => $ugateway->amount_ht / pow(10,  $result[0]->fractional_part),
                    'tariff_class' => $tariff_class,
                    'currency' => $result[0]->code,
                    'promotion_id' => $promotion_id,
                    'payment_type' => $payment_type,
                ];
                
                BmoovController::saveTransaction($payment, $reference);
                
                $gateway_data = [
                    'parking_id' => (int)$result[0]->parking_id,
                    'operator_id' => (int)$result[0]->operator_id,
                    'ticket_entry_time' => $ugateway->ticket_entry_time,
                    'ticket_duration' => $request->ticket_duration,
                    'ticket_amount' => $ugateway->ticket_amount,
                    'pom_desc' =>  "---",
                    'license_plate' => $request->license_plate,
                    'shift_sub_user_id' => (int)$result[0]->shift_sub_user,
                    'ticket_expiration_time' => Carbon::parse($ugateway->ticket_expiration_time)->format('Y-m-d H:i:s'),
                    'payment_reference' => $reference,
                ];
                


                /** @var \MedianetDev\PConnector\PConnector $payment */              
                $ugateway = app('p-connector')->profile('ugateway');
                $ugateway->post('payExtension', $gateway_data);
              
                if ($ugateway->responseCodeNot(200)) {
                    return response()->json([
                        'message' => trans_db('validation', 'payment_ugateway_down'),
                        'success' => false,
                    ], 200);
                }  
    
                if ($ugateway->getResponseStatusCode() !== 204 && $ugateway->getResponseStatusCode() !== 200) {
                    // we should schedule this as cron task
                    $ugateway->log();
                } else {
                    BmoovController::updateTransaction($reference, 'booked', (int)$result[0]->shift_id);

                }

            return [
                'data' =>  $gateway_data,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "payment done successfully."
            ];

            } else {

                return response()->json(['message' => 'This is an off street parking'], 500);
            }
        }
    }

    public function saveTransactionAndReturnResponse($data, $response)
    {
        app('log')->info(json_encode($data));
        BmoovController::saveTransaction($data, $response->reference);

        return response()->json(['content' => $response->content, 'success' => true], 200);
    }
}
