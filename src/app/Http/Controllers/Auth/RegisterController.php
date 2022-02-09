<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Otphistory;
use App\Role;
use App\Address;
use DB;
use App\Country;
use App\State;
use App\City;
use App\Documents;
use App\ProductMaster;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/verifyotp/signup';

    // protected function redirectTo()
    // {

    //     if(Auth::user()->role_id==1){
    //         return '/';
    //     }elseif((Auth::user()->role_id== 2) || (Auth::user()->role_id== 3)){
    //         return '/';
    //     }

    // }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'mobile'=>'required|min:10|max:10|unique:users'

        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {

        $input = $data;
        if(isset($data['is_promotional_mail'] ) && isset($data['is_promotional_mail'] )   == true) {

            $url = "https://undp.svaptech.tk/blog/wp-json/apicall/newletter";
            // $newsletterdata = ['user'=> json_encode()];
            $post = curl_init();
            //curl_setopt($post, CURLOPT_SSLVERSION, 5); // uncomment for systems supporting TLSv1.1 only
            curl_setopt($post, CURLOPT_SSLVERSION, 6); // use for systems supporting TLSv1.2 or comment the line
            curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($post, CURLOPT_URL, $url);
            // curl_setopt($post, CURLOPT_POST, json_encode($input));
            curl_setopt($post, CURLOPT_POSTFIELDS, http_build_query($input));
            curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($post); //result from mobile seva server
            echo $result; //output from server displayed
            curl_close($post);


            // $url = "https://undp.svaptech.tk/blogkn/wp-json/apicall/newletter";
            // // $newsletterdata = ['user'=> json_encode()];
            // $post = curl_init();
            // //curl_setopt($post, CURLOPT_SSLVERSION, 5); // uncomment for systems supporting TLSv1.1 only
            // curl_setopt($post, CURLOPT_SSLVERSION, 6); // use for systems supporting TLSv1.2 or comment the line
            // curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
            // curl_setopt($post, CURLOPT_URL, $url);
            // // curl_setopt($post, CURLOPT_POST, json_encode($input));
            // curl_setopt($post, CURLOPT_POSTFIELDS, http_build_query($input));
            // curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);
            // $result = curl_exec($post); //result from mobile seva server
            // echo $result; //output from server displayed
            // curl_close($post);

        }


        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'mobile' => $data['mobile'],
            'country_id' => $data['country_id'],
            'state_id' =>$data['state_id'],
            'district' => $data['district'],
            'city_id' => $data['district'],
            'role_id' => $data['role_id'],
            'api_token' => Str::random(60),
            'language' => 'en',
        ]);

        if ($user->role_id == 1) {
            $addAddress = Address::create(['user_id' => $user->id,'user_role_id' => $user->role_id, 'country' => $user->country_id, 'state' => $user->state_id, 'district' => $user->district, 'address_type' => 'personal']);
        }

        $otp  = rand(1111, 9999);
        $this->sendotp($data['mobile'], $otp);
        Otphistory::create(['mobile_no' => $user->mobile, 'otp' => $otp ]);
        return $user;
    }
    public function showRegistrationForm(Request $request)
    {
        $input = $request->all();
        if (isset($input['type']) && isset($input['id'])) {
            $type = $request->query('type');
            $id  = $request->query('id');
            $link =  url($type).'/'.$id;
            session(['url.followuplink' => $link]);
        }
        return view('auth.register');
    }


    /**
     * Send OTP
     */

    public function sendotp($mobile, $otp)
    {
        $username="Mobile_1-KSLKAR";
        $password="kslkar@1234";
        $senderid="KSLKAR";
        $message="Please verify your mobile using this OTP " .$otp;
        // $messageUnicode="à¤®à¥‹à¤¬à¤¾à¤‡à¤²à¤¸à¥‡à¤µà¤¾à¤®à¥‡à¤‚à¤†à¤ªà¤•à¤¾à¤¸à¥à¤µà¤¾à¤—à¤¤à¤¹à¥ˆ "; //message content in unicode
        $mobileno = $mobile; //if single sms need to be send use mobileno keyword
        // $mobileNos= "9587777762,8766068415"; //if bulk sms need to send use mobileNos as keyword and mobile number seperated by commas as value
        $deptSecureKey= "b36befee-c007-4749-b6da-d8a0d6ca5e41"; //departsecure key for encryption of message...
        $encryp_password=sha1(trim($password));


        $key=hash('sha512', trim($username).trim($senderid).trim($message).trim($deptSecureKey));

        $url = "https://msdgweb.mgov.gov.in/esms/sendsmsrequest";

        $data = array(
            "username"        => trim($username),
            "password"        => trim($encryp_password),
            "senderid"        => trim($senderid),
            "content"         => trim($message),
            "smsservicetype"  =>"otpmsg",
            "mobileno"        =>trim($mobileno),
            "key"             => trim($key)
         );

        $fields = '';

        foreach ($data as $key => $value) {
            $fields .= $key . '=' . urlencode($value) . '&';
        }
        rtrim($fields, '&');
        $post = curl_init();
        //curl_setopt($post, CURLOPT_SSLVERSION, 5); // uncomment for systems supporting TLSv1.1 only
         curl_setopt($post, CURLOPT_SSLVERSION, 6); // use for systems supporting TLSv1.2 or comment the line
         curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($post, CURLOPT_URL, $url);
        curl_setopt($post, CURLOPT_POST, count($data));
        curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($post); //result from mobile seva server
        //  echo $result; //output from server displayed
        curl_close($post);
    }
}
