<?php

namespace App\Http\Middleware;

use Closure;
use App\User;
use Auth;
use App\Location;
use App\ProductMaster;
use DB;
use Session;

class Userlocation
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
        if (Auth::user() && Auth::user()->isActive == 0) {
            Auth::logout();
            Session::flash('message', 'Your account has been blocked by admin, Please contact your admin for more information.');
            Session::flash('alert-class', 'alert-danger');
            return redirect('/login');
        }



        $DISTANCE_KILOMETERS = 5000;

        // if (Auth::user()  && (Auth::user()->role_id == 2 || Auth::user()->role_id == 3)) {
        //     //db query
        //     $activeuser = [];
        //     $request->merge(array("shgartisanId" => $activeuser));
        // } else {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip =  $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // echo $ip;
        // die;



        // $ipdat = json_decode(file_get_contents( "https://api.ipgeolocationapi.com/geolocate/" . $ip));




        // $lat = $ipdat->geo->latitude;
        // $log = $ipdat->geo->longitude;

        /**
         * Secondary API for LAT LOG by Anshuman
         */
        
        $ipdat = json_decode(file_get_contents("http://ipinfo.io/".$ip."/json?token=9b642bf9b48d1a"));
        $logData = explode(',', $ipdat->loc);
        $lat = $logData[0];
        $log = $logData[1];
        // echo "lat".$lat."<br>";
        // echo "log".$log."<br>";
        // echo "<pre>"; print_r($logData); die("check");
         /**
          * End
          */

        // $lat = 15.3173;
        // $log = 75.7139;

        // $lat = $ipdat->geoplugin_latitude;
        // $log = $ipdat->geoplugin_longitude;


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

            // echo "<pre>"; print_r($getUserids); die("check");


            $allUserId = [];
            foreach ($getUserids as $value) {
                $allUserId[] = $value->user_id;
            }
            // dd($allUserId);

            $activeuser = [];

            foreach ($allUserId as $value) {
                //echo $value;
                $userId = ProductMaster::where(['user_id' => $value, 'is_active' => 1, 'is_draft' => 0])->select('user_id')->first();
                if ($userId) {
                    $activeuser[] = $userId->user_id;
                }
            }

            //dd($activeuser);
            if (count($activeuser) == 0) {
                $activeuser = [];
            }

            // echo "<pre>";
            // print_r($activeuser);
            // die("check in authapi");

            $request->merge(array("shgartisanId" => $activeuser));
        }
        // }








        return $next($request);
    }
}
