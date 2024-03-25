<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketRequest;
use Illuminate\Http\Request;


class TicketController extends Controller
{

    

    /**
     * @OA\Get(
     *      path="/api/entryTicket/create",
     *      operationId="createTicket",
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
     *          name="operator_id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     * 
     *     @OA\Response(
     *         response="200",
     *         description="Create entry ticket",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                      example={
     *                           "parking_id": 0,
     *                           "ticket_id": "string",
     *                           "license_plate": "string",
     *                           "ticket_entry_time": "string",
     *                           "ticket_tariff_time": "string",
     *                           "ticket_duration": "string",
     *                           "original_amount": 0,
     *                           "discount_amount": 0,
     *                           "ticket_amount": 0
     *                       }
     *                 )
     *             )
     *         }
     *     ),
     *
     * )
     */

    public function entryTicket(TicketRequest $request)
    {
        try {
            $data = [
                'operator_id' => $request->operator_id,
                'parking_id' => $request->parking_id,
            ];
            $ugateway = app('p-connector')->profile('ugateway');
            $ugateway->get('entryTicket/create', $data);

            if ($ugateway->responseCodeNot(200)) {
                return response()->json([
                    'message' => 'ugateway_down',
                    'success' => false,
                ], 200);
            }

            $result = [
                'parking_id' => $ugateway->parking_id,
                'operator_id' => $ugateway->operator_id,
                'ticket_id' => $ugateway->ticket_id,
                'entry_time' => $ugateway->entry_time,
                'barcode_data' => $ugateway->barcode_data,
                'qrcode_data' => $ugateway->qrcodeData,

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
