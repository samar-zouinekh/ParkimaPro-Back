<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\CheckPasswordRequest;
use App\Http\Requests\PhoneVerifyRequest;
use App\Models\BackpackUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
   // Add this to API that need access token
// security={{"bearerAuth":{}}},

    /**
     *   @OA\Post(
     *     path="/api/check/password",
     *     tags={"Login"},
     *      summary="Check Password",
     *     @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *            @OA\Schema(
     *               type="object",
     *               required={"password"},
     *               @OA\Property(property="password", type="integer")
     *            ),
     *        ),
     *    ),
     *     @OA\Response(
     *          response="200",
     *          description="Issue the token and return the current user info",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                      example={
     *                           "status": "string",
     *                       }
     *                 )
     *             )
     *         }
     *     )
     * )
     */

    public function checkPassword(CheckPasswordRequest $request)
    {
        $password = $request->password;
        try {

            if ($password == generateDailyPassword()) {
                return [
                    'status' =>  true,
                    'responseCode' =>  200,
                    'message' => "Right Password."
                ];
            }

            return [
                'error' => [],
                'status' =>  false,
                'responseCode' =>  400,
                'message' => "Invalid Password."
            ];
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    /**
     * * @OA\Put(
     *     path="/api/phone-auth/verification",
     *     tags={"Login"},
     *      summary="Verify phone number",
     *     @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *            @OA\Schema(
     *               type="object",
     *               required={"phone"},
     *               @OA\Property(property="phone", type="integer")
     *            ),
     *        ),
     *    ),
     *     @OA\Response(
     *          response="200",
     *          description="Issue the token and return the current user info",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                      example={
     *                           "status": "string",
     *                       }
     *                 )
     *             )
     *         }
     *     )
     * )
     */

    public function phoneVerification(PhoneVerifyRequest $request)
    {
        try {
            $result = app('db')->select(
                'SELECT admins.id, admins.name AS admin_name, admins.email, admins.phone,
                roles.id AS role_id, roles.name AS role_name, roles.guard_name
                    from admins
                    JOIN model_has_roles ON admins.id = model_has_roles.model_id
                    JOIN roles ON roles.id = model_has_roles.role_id
                    WHERE roles.name IN ("enforcement_agent", "enforcement_and_ticketing_agent", "ticketing_agent")
                    and admins.phone = ? limit 1',
                [$request->phone]
            );

            if (!$result) {

                return [
                    'error' => [],
                    'status' =>  false,
                    'responseCode' =>  400,
                    'message' => "Invalid Phone Number."
                ];
            }

            $permissions =  app('db')->select(
                'SELECT permissions.name
                FROM permissions
                JOIN role_has_permissions ON permissions.id = role_has_permissions.permission_id
                JOIN roles ON role_has_permissions.role_id = roles.id
                WHERE roles.id = ? ',
                [$result[0]->role_id]
            );


            $permissionsList = array();
            foreach ($permissions as $permission) {
                $permissionsList[] = $permission->name;
            }

            $data = [
                'name' => $result[0]->admin_name,
                'phone' => $result[0]->phone,
                'action' => 'login',
                'permissions' => $permissionsList
            ];

            if (!$data) {

                return [
                    'error' => [],
                    'status' =>  false,
                    'responseCode' =>  400,
                    'message' => "Invalid Phone Number."
                ];
            }

            // Generate unique OTP code

            $otp = rand(100000, 999999);
            $otp = 1234;

            // Store the $otp value in the cache for 900 seconds (15 minutes) or retrieve it if already exists

            app('cache')->remember($request->phone, 900, function () use ($otp) {
                try {
                    return $otp;
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                }
            });

            return [
                'data' =>  $data,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "Agent loged in successfully."
            ];
        } catch (\Exception $e) {
            \Log::info($e);

            return ApiResponse::send([], false, 200, "Service is unavailable right now.");
        }
    }

    /**
     * * @OA\Put(
     *     path="/api/phone-auth/verify",
     *     tags={"Login"},
     *     summary="Verify otp",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *            @OA\Schema(
     *               type="object",
     *               required={"phone", "otp"},
     *               @OA\Property(property="phone", type="integer"),
     *               @OA\Property(property="otp", type="integer"),
     *            ),
     *        ),
     *    ),
     *     @OA\Response(
     *          response="200",
     *          description="Issue the token and return the current user info",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                      example={
     *                           "status": "string",
     *                       }
     *                 )
     *             )
     *         }
     *     )
     * )
     */

    public function phoneVerify(PhoneVerifyRequest $request)
    {
        $otp = app('cache')->get($request->phone);
        // dump($otp);

        if ($request->otp != $otp) {
            return [
                'error' => [],
                'status' =>  false,
                'responseCode' =>  400,
                'message' => "Invalid OTP."
            ];
        }

        if ($otp == $request->otp) {
            $agent = app('db')->select(
                'SELECT admins.id, admins.name AS admin_name, admins.email, admins.phone,
                roles.id AS role_id, roles.name AS role_name, roles.guard_name
                    from admins
                    JOIN model_has_roles ON admins.id = model_has_roles.model_id
                    JOIN roles ON roles.id = model_has_roles.role_id
                    WHERE roles.name IN ("enforcement_agent", "enforcement_and_ticketing_agent", "ticketing_agent")
                    and admins.phone = ? limit 1',
                [$request->phone]
            );

            if (!$agent) {
                return [
                    'error' => 'Unauthorized',
                    'status' =>  0,
                    'responseCode' =>  401,
                    'message' => trans('laravel-auth-api::translation.failed_authentication')
                ];
            }

            $parkingType = app('db')->select(
                'SELECT gateways.parking_type
                    from gateways
                    JOIN parkings ON gateways.id = parkings.gateway_id
                    JOIN agent_parking ON parkings.id = agent_parking.parking_id
                    JOIN admins ON agent_parking.agent_id = admins.id

                    WHERE admins.id = ? limit 1',
                [$agent[0]->id]
            );

            $permissions =  app('db')->select(
                'SELECT permissions.name
                    FROM permissions
                    JOIN role_has_permissions ON permissions.id = role_has_permissions.permission_id
                    JOIN roles ON role_has_permissions.role_id = roles.id
                    WHERE roles.id = ? ',
                [$agent[0]->role_id]
            );

            $permissionsList = array();
            foreach ($permissions as $permission) {
                $permissionsList[] = $permission->name;
            }

            $successAgent = [
                'name' => $agent[0]->admin_name,
                'phone' => $agent[0]->phone,
                'email' => $agent[0]->email,
                'action' => 'login',
                'parking_type' => $parkingType[0]->parking_type,
                'permissions' => $permissionsList,

            ];


            $user = BackpackUser::where('phone', $request->phone)->first();
            $success = [];
            $success['access_token'] = $user->createToken('ApiToken')->accessToken;
            $success['user'] = $successAgent;
            // dd($success);
            app('cache')->forget($request->phone);

            return [
                'data' =>  $success,
                'status' =>  true,
                'responseCode' =>  200,
                'message' => "Right otp."
            ];
        }
        return [
            'error' => [],
            'status' =>  false,
            'responseCode' =>  400,
            'message' => "Invalid OTP."
        ];
    }

}
