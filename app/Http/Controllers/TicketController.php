<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketRequest;
use Illuminate\Http\Request;
use Carbon\CarbonInterval;
use DateTime;
use DateTimeZone;

class TicketController extends Controller
{

  
    /**
     * @OA\Get(
     *      path="/api/entryTicket/create",
     *      operationId="createTicket",
     *      tags={"Ticket Classification"},
     *      security={{"bearerAuth": {}}},
     *      summary="Create Postpayment Ticket",
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
     *         description="Create entry ticket",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "parking_id": 0,
     *                 "ticket_id": "string",
     *                 "entry_datetime": "string",
     *                 "barcode_data": "string",
     *                 "qrcode_data": "string"
     *             }
     *         )
     *     ),
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     */

    public function entryTicket(TicketRequest $request)
    {
        try {

            $result = app('db')->select(
                'select parkings.unpaid_grace_period, parkings.promotion_id, currencies.round, currencies.symbol, currencies.fractional_part, gateways.parking_id, gateways.type, gateways.timezone_offset, operators.operator_id
                from parkings, gateways, operators, currencies
                where parkings.gateway_id = gateways.id
                and gateways.operator_id = operators.id
                and gateways.currency_id = currencies.id
                and parkings.id = ? limit 1',
                [$request->parking_id]
            );

            $data = [
                'operator_id' => $result[0]->operator_id,
                'parking_id' => $result[0]->parking_id,
            ];

            $ugateway = app('p-connector')->profile('ugateway');
            $ugateway->get('entryTicket/create', $data);

            if ($ugateway->responseCodeNot(200)) {
                return response()->json([
                    'message' => 'ugateway_down',
                    'success' => false,
                ], 200);
            }

            if ($ugateway->getResponseStatusCode() == 400) {
                return 'invalid_ticket';
            }

            $result = [
                'parking_id' => $ugateway->parking_id,
                'ticket_id' => $ugateway->ticket_id,
                'entry_datetime' => (new DateTime($ugateway->entry_time))->format(locale_datetime_format()),
                'barcode_data' => $ugateway->barcode_data,
                'qrcode_data' => $ugateway->qrcode_data,

            ];
            return [
                'data' =>  $result,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "Entry ticket created successfully."
            ];
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return [
                'error' =>  [],
                'status' =>  false,
                'responseCode' =>  200,
                'message' => "Service is unavailable right now."
            ];
        }
    }

    /**
     * @OA\Get(
     *      path="/api/ticket/consult",
     *      operationId="consultTicket",
     *      tags={"Ticket Classification"},
     *      security={{"bearerAuth":{}}},
     *      summary="Create Postpayment Ticket",
     *      @OA\Parameter(
     *          name="parking_id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="barcode",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *     @OA\Response(
     *         response="200",
     *         description="Create entry ticket",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "parking_id": 0,
     *                 "ticket_id": "string",
     *                 "license_plate": "string",
     *                 "ticket_entry_time": "string",
     *                 "ticket_tariff_time": "string",
     *                 "ticket_duration": "string",
     *                 "original_amount": 0,
     *                 "discount_amount": 0,
     *                 "ticket_amount": 0
     *             }
     *         )
     *     ),
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     */

    public function consultTicket(TicketRequest $request)
    {
        try {

            $result = app('db')->select(
                'select parkings.unpaid_grace_period, parkings.promotion_id, currencies.round, currencies.symbol, currencies.fractional_part, gateways.parking_id, gateways.type, gateways.timezone_offset, operators.operator_id
                from parkings, gateways, operators, currencies
                where parkings.gateway_id = gateways.id
                and gateways.operator_id = operators.id
                and gateways.currency_id = currencies.id
                and parkings.id = ? limit 1',
                [$request->parking_id]
            );

            $data = [
                'operator_id' => $result[0]->operator_id,
                'parking_id' => $result[0]->parking_id,
                'license_plate' => "Default",
                'barcode' => $request->barcode,
            ];

            $ugateway = app('p-connector')->profile('ugateway');
            $ugateway->get('media/postPayment/consult', $data);

            if ($ugateway->responseCodeNot(200)) {
                return response()->json([
                    'message' => 'ugateway_down',
                    'success' => false,
                ], 200);
            }

            if ($ugateway->getResponseStatusCode() == 400) {
                return 'invalid_ticket';
            }

            if (CarbonInterval::create(iso8601_duration($ugateway->ticket_duration))->total('hours') == 0) {
                $step = (1) . ' ' . ($result[0]->symbol ?? '') . '/H';
            }

            $result = [
                'parking_id' => $ugateway->parking_id,
                'ticket_id' => $ugateway->ticket_id,
                'entry_datetime' => (new DateTime($ugateway->ticket_entry_time))->format(locale_datetime_format()),
                'payment_datetime' => (new DateTime(
                    'now',
                    new DateTimeZone($result[0]->timezone_offset ?? 'UTC')
                ))->format(locale_datetime_format()),
                'duration' => CarbonInterval::create(iso8601_duration($ugateway->ticket_duration))
                    ->locale(app()->getLocale())->forHumans(['parts' => 4, 'join' => true]),
                'amount' => number_format($ugateway->ticket_amount / pow(10,  $result[0]->fractional_part), 3, '.', '') . ' ' . ($result[0]->symbol ?? ''),
            ];

            return [
                'data' =>  $result,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "Entry ticket created successfully."
            ];
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return [
                'error' =>  [],
                'status' =>  false,
                'responseCode' =>  200,
                'message' => "Service is unavailable right now."
            ];
        }
    }
}
