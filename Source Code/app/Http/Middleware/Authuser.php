<?php

namespace App\Http\Middleware;

use Closure;
use App\User;
use App\Documents;
use App\Location;

class Authuser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $condition = false)
    {
        $apitoken = $request->header('apitoken');

        $language = 'en';

        if($request->header('language')) {

            $language = $request->header('language');

        }


        if($apitoken) {

            $user = User::where(['api_token' => $apitoken ])->first();


            // is_blocked_byadmin
            // 'isActive' => 1
            if(!$user) {


                $response   = array('status' => false, 'statusCode' => 401, 'message'=> "Please provide valid api-token." );

                return response()->json($response, 201);

            }else{

                if(($user->is_blocked_byadmin == 1) && ($user->isActive == 0)) {
                    $response   = array('status' => false, 'statusCode' => 403, 'message'=> "Your profile has been blocked by admin, Please contact your admin for more information." );

                    return response()->json($response, 201);
                }

                if($user->isActive == 0) {
                    $response   = array('status' => false, 'statusCode' => 401, 'message'=> "Please provide valid api-token." );

                    return response()->json($response, 201);
                }

                if($user->is_blocked_byadmin == 1) {
                    $response   = array('status' => false, 'statusCode' => 403, 'message'=> "Your profile has been blocked by admin, Please contact your admin for more information." );

                    return response()->json($response, 201);
                }

            if($user->is_otp_verified == 0) {

                $response   = array('status' => false, 'statusCode' => 600, 'message'=> "OTP is not verified.");

                return response()->json($response, 201);
            }

            // if($user->is_document_added == 0) {
            //     $response   = array('status' => false, 'statusCode' => 600, 'message'=> "We have not recieved your documents, Please wait till we verify them.");

            //     return response()->json($response, 201);
            // }

            $apptype = $request->header('app-type');



            if(($user->role_id == 1) && ($apptype == 'Admin')) {
                $queryStatus    = "Login Failed Please enter correct credentials.";
                $statusCode     = 401;
                $status         = false;
                $data           = [];

                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
                return response()->json($response, 201);
            }

            // Commenting this condition due to to allow login shg / artisan

            // if((($user->role_id == 2) && ($apptype != 'Admin')) || (($user->role_id == 3) && ($apptype != 'Admin'))) {
            //     $queryStatus    = "Login Failed Please enter correct credentials.";
            //     $statusCode     = 401;
            //     $status         = false;
            //     $data           = [];

            //     $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
            //     return response()->json($response, 201);
            // }


            if(($apptype == "Admin") && ($condition == false)) {

                $document = Documents::where('user_id', $user->id)->first();

                if($user->role_id == 2) {
                    if($user->is_document_added == 1) {

                        if($document->is_adhar_verify == 0) {
                            //$response   = array('status' => false, 'statusCode' => 700, 'message'=> "We have recieved your documents, Please wait till we verify them." );

                           // return response()->json($response, 201);
                        }
                    }
                }

                if($user->role_id == 3) {

                    if($user->is_document_added == 1) {

                        if(($document->is_adhar_verify == 0) || ($document->is_pan_verify == 0) || ($document->is_brn_verify == 0)) {

                            //$response   = array('status' => false, 'statusCode' => 700, 'message'=> "We have recieved your documents, Please wait till we verify them." );

                            //return response()->json($response, 201);
                        }
                    }
                }




            }
                $updateLanguage = User::where('id', $user->id)->update(['language' => $language]);

                $request->merge(array("user" => $user));
            }
        }else{
            $response   = array('status' => false, 'statusCode' => 401, 'message'=> "Please provide valid api-token." );

            return response()->json($response, 201);
        }

        return $next($request);
    }
}
