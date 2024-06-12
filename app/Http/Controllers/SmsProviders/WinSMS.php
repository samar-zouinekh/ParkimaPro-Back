<?php

namespace App\Http\Controllers\SmsProviders;

use DateInterval;
use DateTime;
use Illuminate\Support\Facades\Http;

trait WinSMS
{


    /**
     * Send a simple SMS.
     *
     * @param string $to
     * @param string $content
     *
     * @return bool indicate if the SMS is sent successfully or not (true means the SMS is sent successfully)
     */
    public function sendSms($to, $content): bool
    {
            try {
                $from=  config('sms.providers.win_sms.sender_ID');
                $api_key = config('sms.providers.win_sms.api_key');
                $message = urlencode($content);

               $url = "https://www.winsmspro.com/sms/sms/api?action=send-sms&api_key=$api_key&to=$to&from=$from&sms=$message";
               $response = file_get_contents($url);

               return true;
        } catch (\Throwable $th) {
            app('log')->error($th->getMessage());

            return false;
        }
    }

  /**
     * Send a simple SMS.
     *
     * @param string $to
     * @param string $from
     * @param string $sms
     *
     * @return bool indicate if the SMS is sent successfully or not (true means the SMS is sent successfully)
     */

     public function sendOtp($code, $to): bool
     {
         try {

             $from=  config('sms.providers.win_sms.sender_ID');
             $api_key = config('sms.providers.win_sms.api_key');
             $sms_content =  app('db')->select('select body from sms where sms_key = ?',['express_verification_sms']);
             $sms_body = json_decode(json_encode($sms_content[0]->body), true);
             $body = json_decode(($sms_body), true)[app()->getLocale()];
             $message= str_replace(':code', $code, $body);
             $message = urlencode($message);

            $url = "https://www.winsmspro.com/sms/sms/api?action=send-sms&api_key=$api_key&to=$to&from=$from&sms=$message";
            $response = file_get_contents($url);

            // save the sent code to database
            app('db')->table('sms_users_verifications')->insert([
                'provider' => 'win_sms',
                'phone' => $to,
                'code' => $code,
                'used' => false,
                'expires_at' => (new DateTime())->add(new DateInterval('PT'.config('sms.code_age').'S')),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
       
            // Store the $otp value in the cache for 900 seconds (15 minutes) or retrieve it if already exists

            app('cache')->remember( $to, 900, function () use ($code) {
                try {
                    return $code;
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                }
            });

            // and finally return true.
            return true;
            
         } catch (\Throwable $th) {
             app('log')->error($th->getMessage());

             return false;
         }
     }

    /**
     * Verify the submitted SMS code.
     *
     * @param string $code
     * @param string $phone
     *
     * @return bool true if the verification passes false otherwise
     */

     public function verifySms($code, $phone): bool
     {
         /** @var \Illuminate\Support\Facades\DB $database */
         $database = app('db');

         $result = $database->select(
             'select id from sms_users_verifications where used = 0 and phone = ?
                 and  code = ? and expires_at > ? limit 1',
             [$phone, $code, date('Y-m-d H:i:s')]
         );

         if (! $result) {
             return false;
         }

         // mark the code as used
         $database->update('update sms_users_verifications set used = 1 where id = ?', [$result[0]->id]);

         return true;
     }

      /*
    |--------------------------------------------------------------------------
    | JSON RESPONSES
    |--------------------------------------------------------------------------
    */

    /**
     * Return SMS sent successfully response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function smsSendingSuccess($to)
    {
        limiter()->hit('sms_sending', 60);

        return response()->json([
            'message' => trans_db('validation', 'sms_sending_success'),
            'success' => true,
        ], 200);
    }

    /**
     * Return SMS did not sent response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function smsSendingFailure()
    {
        return response()->json([
            'message' => trans_db('validation', 'sms_sending_error'),
            'success' => false,
        ], 200);
    }

    /**
     * Return too many attempts response.
     *
     * @param int $attempts    the maximum allowed attempts
     * @param int $availableIn the time in seconds before the next attempt is allowed
     *
     * @todo: pass the maximum attempts and the remaining time to the translation function
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function tooManyAttemptsResponse($attempts, $availableIn)
    {
        return response()->json([
            'message' => trans_db('validation', 'sms_too_many_attempts'),
            'success' => false,
        ], 200);
    }

    /**
     * Tell the user that the given code is invalid or expired.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function verificationCodeFailureResponse()
    {
        return response()->json([
            'message' => trans_db('validation', 'wrong_code'),
            'success' => false,
        ], 200);
    }

    /**
     * Tell the user that the verification process is done.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function verificationCodeSuccessResponse()
    {
        return response()->json([
            'message' => 'Ok',
            'success' => true,
        ], 200);
    }
}


