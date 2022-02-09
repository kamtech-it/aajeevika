<?php

namespace App\Http\Middleware;

use Closure;
use App\User;
use App\Location;
use App\ProductMaster;
use DB;

class Authapi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $apitoken = $request->header('apitoken');
        $token    = $request->header('app-key');

        if ($token != "laravelUNDP") {
            /*
            if($apitoken) {

                $user = User::where(['api_token' => $apitoken])->first();



                if(!$user) {
                    $response   = array('status' => false, 'statusCode' => 400, 'message'=> "Please provide valid api-token." );

                    return response()->json($response, 201);

                }else{
                    $request->merge(array("user" => $user));
                }
            }*/
            $response   = array('status' => false , 'statusCode' =>400, 'message'=> "Please provide app-key." );

            return response()->json($response, 201);
        } else {
            $DISTANCE_KILOMETERS = 5000;

            $lat = $request->header('lat');
            $log = $request->header('log');



            $allUserId = [];
            $activeuser = [];

            if ($lat && $log) {
                $getUserids = DB::select(DB::raw(
                    "SELECT * FROM (
                    SELECT *,
                        (
                            (
                                (
                                    acos(
                                        sin(( $lat * pi() / 180))
                                        *
                                        sin(( `lat` * pi() / 180)) + cos(( $lat * pi() /180 ))
                                        *
                                        cos(( `lat` * pi() / 180)) * cos((( $log - `log`) * pi()/180)))
                                ) * 180/pi()
                            ) * 60 * 1.1515 * 1.609344
                        )
                    as distance FROM `locations`
                ) 	locations
                WHERE distance <= $DISTANCE_KILOMETERS
                ORDER BY distance ASC
                "
                ));

                //   echo "<pre>"; print_r($getUserids); die("check");


                $allUserId = [];
                foreach ($getUserids as $value) {
                    $allUserId[] = $value->user_id;
                }


                $activeuser = [];

                foreach ($allUserId as $value) {
                    $userId = ProductMaster::where(['user_id' => $value, 'is_active' => 1, 'is_draft' => 0])->select('user_id')->first();
                    if ($userId) {
                        $activeuser[] = $userId->user_id;
                    }
                }
               // echo "<pre>"; print_r($activeuser); die("check in authapi");

                $request->merge(array("shgartisanId" => $activeuser));
            } else {
                $request->merge(array("shgartisanId" => $activeuser));
            }
        }

        return $next($request);
    }
}
