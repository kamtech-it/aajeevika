<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Otphistory;
use App\Role;
use App\Address;
use App\Pincode;
use DB;
use App\Country;
use App\State;
use App\City;
use App\Documents;
use App\ProductMaster;
use Illuminate\Support\Facades\Storage;
use App\Reason;
use App\Location;
use App\Rating;
use App\IndividualInterest;
use App\IndRating;

use Mail;

//add models here
// use App\Models\User;

class LoginController extends Controller
{

    /* login */
    public function loginUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:mobile',
            'mobile' => 'required_without:email',
            'password'  => 'required',
            //'role_id'  => 'required',
        ]);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }
        $language = $request->header('language');
        $email      = $request->input('email');
        $mobile     = $request->input('mobile');
        $password   = $request->input('password');
        $roleId   = $request->input('role_id');

        // IF Email Found
        if($roleId == 1 || $roleId == 9 || $roleId == 10){
            if ($email) {
                $query = [ 'email' => $email,'role_id' =>$roleId];
            }
    
            if ($mobile) {
                $query = [ 'mobile' => $mobile ,'role_id' =>$roleId];
            }
            $roleIds =[$roleId];
        }else{
            if ($email) {
                $query = [ 'email' => $email];
            }
    
            if ($mobile) {
                $query = [ 'mobile' => $mobile];
            }
            $roleIds =[2,3,7,8];
        }
        $user       = User::select('id', 'api_token', 'password', 'isActive', 'is_address_added', 'is_promotional_mail', 'name', 'email', 'mobile', 'email_verified_at', 'profileImage','collection_center_id', 'role_id', 'title', 'is_email_verified', 'is_otp_verified', 'is_address_added', 'is_document_added', 'is_blocked_byadmin')
        ->where($query)
        ->whereIn('role_id',$roleIds)
        ->first();
        if ($user) {      

            $requestfrom = $request->header('app-type');
            if (($user->isActive == 0) && ($user->is_blocked_byadmin == 1)) {
                if($language == 'hi'){
                    $queryStatus    = "आपकी प्रोफाइल एडमिन ने ब्लॉक कर दी है, अधिक जानकारी के लिए कपया एडमिन से मिलें।";
                }else{
                    $queryStatus    = "Your profile has been blocked by admin, Please contact your admin for more information.";
                }
                $statusCode     = 403;
                $status         = false;
                $data           = [];

                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
                return response()->json($response, 201);
            }

            if ($user->isActive == 0) {
                
                if($language == 'hi'){
                $queryStatus    = "उपयोगकर्ता नहीं मिले ";
            }else{
                $queryStatus    = "User not found.";
            }
                $statusCode     = 401;
                $status         = false;

                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
                return response()->json($response, 201);
            }



            if (!Hash::check($password, $user->password)) {
                if($language == 'hi'){
                    $queryStatus    = "लाग इन नहीं हो पा रहा, कृपया सही पासवर्ड लिखें.";
                }else{
                    $queryStatus    = "Login Failed Please enter correct password.";
                }
                
                $statusCode     = 401;
                $status         = false;
                $data           = [];

                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
            } else {

                // Code Update for check OTP verified
                if ($user->is_otp_verified != 0) {
                    $fcmToken = $request->header('devicetoken');
                   
                    $updateUser = User::where('id', $user->id)->update(['devicetoken' => $fcmToken]);

                    // Get User Address
                    $personal = null;
                    $office = null;
                    $registered = null;

                    if ($user->role_id == 1) {
                        $personal = Address::where(['user_id' => $user->id, 'address_type' => 'personal'])->first();

                        if ($personal) {
                            $countryName = Country::where('id', $personal->country)->select('id', 'name')->first();
                            $stateName   = State::where('id', $personal->state)->select('id', 'name')->first();
                            $district    = City::where('id', $personal->district)->select('id', 'name')->first();
                            $personal = [
                                'id'               => $personal->id,
                                'address_line_one' => $personal->address_line_one,
                                'address_line_two' => $personal->address_line_two,
                                'pincode'          => $personal->pincode,
                                'countryId'        => $countryName->id,
                                'stateId'          => $stateName->id,
                                'districtId'       => $district->id,
                                'country'          => $countryName->name,
                                'state'            => $stateName->name,
                                'district'         => $district->name
                            ];
                        }
                    }

                    if ($user->role_id == 2) {

                             //    Fetch Document Status on 31 12 2020
                        if ($user->is_document_added == 1) {
                            $documentstatus = Documents::where('user_id', $user->id)->select('is_adhar_verify', 'is_pan_verify', 'is_brn_verify')->first();

                            $user['is_adhar_verify'] = $documentstatus->is_adhar_verify;
                            // $user['is_pan_verify']   = $documentstatus->is_pan_verify;
                                // $user['is_brn_verify']   = $documentstatus->is_brn_verify;
                        }

                        $registered = Address::where(['user_id' => $user->id, 'address_type' => 'registered'])->first();

                        if ($registered) {
                            $countryName = Country::where('id', $registered->country)->select('id', 'name')->first();
                            $stateName   = State::where('id', $registered->state)->select('id', 'name')->first();
                            $district    = City::where('id', $registered->district)->select('id', 'name')->first();
                            $registered = [
                                'id'               => $registered->id,
                                'address_line_one' => $registered->address_line_one,
                                'address_line_two' => $registered->address_line_two,
                                'pincode'          => $registered->pincode,
                                'countryId'        => $countryName->id,
                                'stateId'          => $stateName->id,
                                'districtId'       => $district->id,
                                'country'          => $countryName->name,
                                'state'            => $stateName->name,
                                'district'         => $district->name
                            ];
                        }
                    }

                    if ($user->role_id == 3) {

                    //    Fetch Document Status on 31 12 2020
                        if ($user->is_document_added == 1) {
                            $documentstatus = Documents::where('user_id', $user->id)->select('is_adhar_verify', 'is_pan_verify', 'is_brn_verify')->first();

                            $user['is_adhar_verify'] = $documentstatus->is_adhar_verify;
                            $user['is_pan_verify']   = $documentstatus->is_pan_verify;
                            $user['is_brn_verify']   = $documentstatus->is_brn_verify;
                        }


                        $registered = Address::where(['user_id' => $user->id, 'address_type' => 'registered'])->first();
                        if ($registered) {
                            $countryName = Country::where('id', $registered->country)->select('id', 'name')->first();
                            $stateName   = State::where('id', $registered->state)->select('id', 'name')->first();
                            $district    = City::where('id', $registered->district)->select('id', 'name')->first();
                            $registered = [
                                'id'               => $registered->id,
                                'address_line_one' => $registered->address_line_one,
                                'address_line_two' => $registered->address_line_two,
                                'pincode'          => $registered->pincode,
                                'countryId'        => $countryName->id,
                                'stateId'          => $stateName->id,
                                'districtId'       => $district->id,
                                'country'          => $countryName->name,
                                'state'            => $stateName->name,
                                'district'         => $district->name
                            ];
                        }

                        $office = Address::where(['user_id' => $user->id, 'address_type' => 'office'])->first();

                        if ($office) {
                            $countryName = Country::where('id', $office->country)->select('id', 'name')->first();
                            $stateName   = State::where('id', $office->state)->select('id', 'name')->first();
                            $district    = City::where('id', $office->district)->select('id', 'name')->first();
                            $office = [
                                'id'               => $office->id,
                                'address_line_one' => $office->address_line_one,
                                'address_line_two' => $office->address_line_two,
                                'pincode'          => $office->pincode,
                                'countryId'        => $countryName->id,
                                'stateId'          => $stateName->id,
                                'districtId'       => $district->id,
                                'country'          => $countryName->name,
                                'state'            => $stateName->name,
                                'district'         => $district->name
                            ];
                        }
                    }

                    $address = [
                         'personal'   => $personal,
                         'office'     => $office,
                         'registered' => $registered
                     ];
                     if($language == 'hi'){
                        $queryStatus    = "सफलतापूर्वक लॉगिन करें";
                    }else{
                        $queryStatus    = "Login Successful.";
                    }
                    $user['selectInterest'] = false;
                    if($roleId  == 9){
                        $checkIndividualInterest =IndividualInterest::where('user_id',$user->id)->first();
                        if($checkIndividualInterest){
                            $user['selectInterest'] = true;
                        }
                    }
                    $statusCode     = 200;
                    $status         = true;
                    $data           = ['user' => $user , 'address' => $address];

                    $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => $data);
                } else {

                    // Status Update to false on 2 - 1 - 2021 disucssion with manish Jangir
                    
                    if($language == 'hi'){
                        $queryStatus    = "ओटीपी सत्यापित नहीं है, कृपया ओटीपी को सत्यापित करें";
                    }else{
                        $queryStatus    = "OTP is not Verified Please verify OTP.";
                    }
                    $statusCode     = 600;
                    $status         = true;
                    $otp            = rand(1111, 9999);

                    // echo "<pre>"; print_r($user); die("check");

                    $otpStatus = Otphistory::create(['mobile_no' => $user->mobile, 'otp' => $otp ]);

                    $data           = ['otp' => $otp, 'is_otp_verified' => $user->is_otp_verified, 'mobile' => $user->mobile];

                    $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => $data);
                }
            }
        } else {
            if($language == 'hi'){
                $queryStatus    = "अनधिकत उपयोगकर्ता";
            }else{
                $queryStatus    = "Invalid user.";
            }
            
            $statusCode     = 401;
            $status         = false;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
        }



        return response()->json($response, 201);
    }

    /* Registration */
    public function registration(Request $request)
    {
        $language = $request->header('language');
        $devicetoken = 'NA';
        $emailUnique = 'Email already exists';
            $mobileUnique = 'Mobile number already exists ';
        if($language =='hi'){
            $emailUnique = 'ईमेल पहले से ही मौजूद है';
            $mobileUnique = 'मोबाइल नंबर पहले से ही मौजूद है। ';
        }
        if ($request->header('devicetoken')) {
            $devicetoken = $request->header('devicetoken');
        }
        $rules = [
            //'email'      => 'required|email|unique:users',
            'mobile'     => 'required|unique:users',
            'name'       => 'required',
            'password'   => 'required|min:6',
            // 'language'   => 'required',
            'role_id'    => 'required'
        ];
        if($request->role_id ==1){
            if($request->email){
                $rules['email'] = 'required|email|unique:users';
                
            }else{
                //....
            }
        }else{
            $rules['email'] = 'required|email|unique:users';
        }
        
        $customMessages = [
            'email.unique' =>  $emailUnique,
            'mobile.unique' => $mobileUnique
        ];
        // if ($request->role_id == 1) {
        //     $rules['country_id'] = 'required';
        //     $rules['state_id']   = 'required';
        //     $rules['district']   = 'required';
        // }
        $validator = Validator::make($request->all(), $rules, $customMessages);

        if ($validator->fails()) {
            $status = 400;
            $checkuser = User::orWhere(['email' => $request->email, 'mobile' => $request->mobile])->first();
            if ($checkuser) {
                $status = 409;
            }

            $response = array('status' => false , 'statusCode' => $status);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }



        $input = $request->all();
        $input['language'] = $language;

        $input['is_promotional_mail'] = 0;

        if ($request->is_promotional_mail == 'true') {
            $input['is_promotional_mail'] = 1;
            // $data = serialize($request->all());
        }

        $input['password']              = Hash::make($request->password);
        $input['api_token']             = Str::random(60);
        $input['language']              = $language;
        $input['devicetoken']           = $devicetoken;
        if($request->role_id !=1 || $request->role_id !=9){
            $input['member_id']             = $request->member_id;
            $input['organization_name']     = $request->organization_name;
            $input['member_designation']   = $request->member_designation;
        }
        



        $user       = User::create($input);
        $input['id'] = $user->id;
        //add seller profile image
        /** Upload Images */
        if($request->hasFile('profileimage')){
        $image_file_1 = $request->file('profileimage');
        $folder = public_path('images/users/' . $user->id . '/');

        if (!Storage::exists($folder)) {
            Storage::makeDirectory($folder, 0775, true, true);
        }

        $image_file_1_image = date('YmdHis') . rand(111, 9999). "userimage." . $image_file_1->getClientOriginalExtension();
        $aa = $image_file_1->move($folder, $image_file_1_image);

        $image_file_1_image_name = $image_file_1_image;
        $image_file_1_image = 'images/users/'.$user->id.'/'.$image_file_1_image_name;


        $updateuser = User::where('id', $user->id)->update(['profileImage'=> $image_file_1_image]);

    }
        if ($input['is_promotional_mail'] == true) {
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
            // echo $result; //output from server displayed
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

        if ($request->role_id == 1) {
            //$addAddress = Address::create(['user_id' => $user->id,'user_role_id' => $user->role_id, 'country' => $user->country_id, 'state' => $user->state_id, 'district' => $user->district, 'address_type' => 'personal']);
        }

        $queryStatus;

        try {
            if($language == 'hi'){
                $queryStatus    = "पंजीकरण सफल हुआ, कृपया मोबाइल नंबर सत्यापित करें।";
            }else{
                $queryStatus    = "Registration Successfull. Please verify your Mobile no.";
            }
            
            $statusCode     = 200;
            $status         = true;
            $otp            = rand(1111, 9999);

            $this->sendotp($request->mobile, $otp);

            $otpStatus = Otphistory::create(['mobile_no' => $request->mobile, 'otp' => $otp ]);
        } catch (Exception $e) {
            if($language == 'hi'){
                $queryStatus    = "Failed to register user";
            }else{
                $queryStatus = "Failed to register user";
            }
            
            $statusCode = 401;
            $status = false;
            $otp = '';
        }
        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => ['otp' => $otp] );

        return response()->json($response, 201);
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

    /**
     * OTP Verify
     */

    public function verifyotp(Request $request)
    {
        $language = $request->header('language');
        $validator = Validator::make($request->all(), [
            'otp'        => 'required',
            'mobile'     => 'required',
            // 'type'       => 'required'
            ]);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }


        $otpCheck = Otphistory::where(['mobile_no' => $request->mobile, 'otp' => $request->otp, 'status' => 1])->first();

        $queryStatus;

        if ($otpCheck) {
            if ($request->type == 'updatemobile') {
                $userId = $request->user_id;
                $updatemobile = User::where('id', $request->user_id)->update(['mobile'=>$request->mobile,'is_otp_verified' => 1 ]);
                
            }
            $updateuserstatus = User::where('mobile', $request->mobile)->update([ 'is_otp_verified' => 1 ]);

            if($language == 'hi'){
                $queryStatus    = "ओटीपी सत्यापन सफल हुआ।";
            }else{
                $queryStatus    = "Otp verified successfully.";
            }
            
            $statusCode     = 200;
            $status         = true;

            $otpUpdate = Otphistory::where(['mobile_no' => $request->mobile, 'otp' => $request->otp, 'status' => 1])->update(['status' => 0]);

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

            if ($request->type == 'signup') {
                $userDetail = User::where([ 'mobile' => $request->mobile ])->select('id', 'api_token', 'password', 'isActive', 'is_address_added', 'is_promotional_mail', 'name', 'email', 'mobile', 'email_verified_at', 'profileImage', 'role_id', 'title', 'is_email_verified')->first();

                // Get User Address
                $personal = null;
                $office = null;
                $registered = null;

                if ($userDetail->role_id == 1) {
                    $personal = Address::where(['user_id' => $userDetail->id, 'address_type' => 'personal'])->first();

                    if ($personal) {
                        $countryName = Country::where('id', $personal->country)->select('id', 'name')->first();
                        $stateName   = State::where('id', $personal->state)->select('id', 'name')->first();
                        $district    = City::where('id', $personal->district)->select('id', 'name')->first();
                        $personal = [
                            'id'               => $personal->id,
                            'address_line_one' => $personal->address_line_one,
                            'address_line_two' => $personal->address_line_two,
                            'pincode'          => $personal->pincode,
                            'countryId'        => $countryName->id,
                            'stateId'          => $stateName->id,
                            'districtId'       => $district->id,
                            'country'          => $countryName->name,
                            'state'            => $stateName->name,
                            'district'         => $district->name
                        ];
                    }
                }

                if ($userDetail->role_id == 2) {
                    $registered = Address::where(['user_id' => $userDetail->id, 'address_type' => 'registered'])->first();

                    if ($registered) {
                        $countryName = Country::where('id', $registered->country)->select('id', 'name')->first();
                        $stateName   = State::where('id', $registered->state)->select('id', 'name')->first();
                        $district    = City::where('id', $registered->district)->select('id', 'name')->first();
                        $office = [
                            'id'               => $registered->id,
                            'address_line_one' => $registered->address_line_one,
                            'address_line_two' => $registered->address_line_two,
                            'pincode'          => $registered->pincode,
                            'countryId'        => $countryName->id,
                            'stateId'          => $stateName->id,
                            'districtId'       => $district->id,
                            'country'          => $countryName->name,
                            'state'            => $stateName->name,
                            'district'         => $district->name
                        ];
                    }
                }

                if ($userDetail->role_id == 3) {
                    $registered = Address::where(['user_id' => $userDetail->id, 'address_type' => 'registered'])->first();
                    if ($registered) {
                        $countryName = Country::where('id', $registered->country)->select('id', 'name')->first();
                        $stateName   = State::where('id', $registered->state)->select('id', 'name')->first();
                        $district    = City::where('id', $registered->district)->select('id', 'name')->first();
                        $registered = [
                            'id'               => $registered->id,
                            'address_line_one' => $registered->address_line_one,
                            'address_line_two' => $registered->address_line_two,
                            'pincode'          => $registered->pincode,
                            'countryId'        => $countryName->id,
                            'stateId'          => $stateName->id,
                            'districtId'       => $district->id,
                            'country'          => $countryName->name,
                            'state'            => $stateName->name,
                            'district'         => $district->name
                        ];
                    }

                    $office = Address::where(['user_id' => $userDetail->id, 'address_type' => 'office'])->first();

                    if ($office) {
                        $countryName = Country::where('id', $office->country)->select('id', 'name')->first();
                        $stateName   = State::where('id', $office->state)->select('id', 'name')->first();
                        $district    = City::where('id', $office->district)->select('id', 'name')->first();
                        $office = [
                            'id'               => $office->id,
                            'address_line_one' => $office->address_line_one,
                            'address_line_two' => $office->address_line_two,
                            'pincode'          => $office->pincode,
                            'countryId'        => $countryName->id,
                            'stateId'          => $stateName->id,
                            'districtId'       => $district->id,
                            'country'          => $countryName->name,
                            'state'            => $stateName->name,
                            'district'         => $district->name
                        ];
                    }
                }

                $address = [
                        'personal'   => $personal,
                        'office'     => $office,
                        'registered' => $registered
                    ];


                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => [
                        'user' => $userDetail,
                        'address'    => $address
                    ]);
            }
        } else {
            if($language == 'hi'){
                $queryStatus    = "कृपया वैधानिक ओटीपी डालें।";
            }else{
                $queryStatus    = "Please enter valid OTP";
            }
            
            $statusCode     = 400;
            $status         = false;
            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
        }






        return response()->json($response, 201);
    }

    /**
     * resend otp
     */

    public function resendotp(Request $request)
    {
        $rules = [
            'mobile'     => 'required',
        ];

        $language = $request->header('language');
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400 );
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        if ($request->type == 'updatemobile') {
            $checkmobile = User::where(['mobile' => $request->mobile, 'isActive' => 1])->first();
            if ($checkmobile) {
                $response = array('status' => false , 'statusCode' =>400 );
                if($language == 'hi'){
                    $response['message'] = "मोबाइल नंबर पहले से ही मौजूद है।";
                }else{
                    $response['message'] = "Mobile number already exists";
                }
                
                return response()->json($response);
            }
        }

        $otp            = rand(1111, 9999);
        $queryStatus;
        $otpUpdateStatus = Otphistory::where(['mobile_no'=> $request->mobile ])->update(['status' => 0]);
        $otpStatus = Otphistory::create(['mobile_no' => $request->mobile, 'otp' => $otp ]);
        $this->sendotp($request->mobile, $otp);
        $queryStatus = "OTP Sent.";
        $statusCode = 200;
        $status = true;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,  'data' => [ 'otp' => $otp] );

        return response()->json($response, 201);
    }

    /**
     * forget password
     */

    public function forgetpassword(Request $request)
    {
        $apptype = $request->header('app-type');
        $language = $request->header('language');
        $validator = Validator::make($request->all(), [
            'mobile'     => 'required',
        ]);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400 );
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        $user = User::where('mobile', $request->mobile)->first();
        if(!$user){
            $response = array('status' => false , 'statusCode' =>400 );
            if($language == 'hi'){
                $response['message'] = "कृपया वैध मोबाइल नंबर डालें।";
            }else{
                $response['message'] = "Please enter valid mobile number.";
            }
            
            return response()->json($response);
        }
        if ((($user->role_id == 2) && ($apptype != 'Admin')) || (($user->role_id == 3) && ($apptype != 'Admin'))) {
            $response = array('status' => false , 'statusCode' =>400 );
            if($language == 'hi'){
                $response['message'] = "कृपया वैध मोबाइल नंबर डालें।";
            }else{
                $response['message'] = "Please enter valid mobile number.";
            }
            
            return response()->json($response);
        }

        if ((($user->role_id == 1) && ($apptype == 'Admin'))) {
            $response = array('status' => false , 'statusCode' =>400 );
            if($language == 'hi'){
                $response['message'] = "कृपया वैध मोबाइल नंबर डालें।";
            }else{
                $response['message'] = "Please enter valid mobile number.";
            }
            return response()->json($response);
        }
        if($language == 'hi'){
            $queryStatus    = "ओटीपी भेजा है, कृपया सत्यापित करें।";
        }else{
            $queryStatus    = "OTP Sent Please verify.";
        }
        $statusCode     = 200;
        $status         = true;
        $otp            = rand(1111, 9999);
        $this->sendotp($request->mobile, $otp);
        $otpStatus = Otphistory::create(['mobile_no' => $request->mobile, 'otp' => $otp ]);
        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => [ 'otp' => $otp ] );
        return response()->json($response, 201);
    }

    /**
     *
     * change password
     */
    public function changepassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile'     => 'required|exists:users',
            'password'   => 'required|min:6',
            'otp'        => 'required'
        ]);
        $language = $request->header('language');
        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }
        if($language == 'hi'){
            $queryStatus    = "पासवर्ड सफलतापूर्वक बदला गया है।";
        }else{
            $queryStatus    = "Password changed successfully.";
        }
        
        $statusCode     = 200;
        $status         = true;

        $password = Hash::make($request->password);
        $mobile   = $request->mobile;

        $checkOtp = Otphistory::where(['mobile_no' => $request->mobile, 'otp' => $request->otp, 'status' => 0])->first();

        if (!$checkOtp) {
            if($language == 'hi'){
                $queryStatus    = "पासवर्ड नहीं बदला जा सका है।";
            }else{
                $queryStatus    = "Password failed to changed.";
            }
            
            $statusCode     = 401;
            $status         = false;
        }

        $userUpdate = User::where(['mobile' => $mobile ])->update(['mobile' => $mobile, 'password' => $password ]);

        if (!$userUpdate) {
            if($language == 'hi'){
                $queryStatus    = "पासवर्ड नहीं बदला जा सका है।";
            }else{
                $queryStatus    = "Password failed to changed.";
            }
            $statusCode     = 401;
            $status         = false;
        }


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        return response()->json($response, 201);
    }
    public function updatepassword(Request $request)
    { 
        $apitoken = $request->header('apitoken');
        $language = $request->header('language');
        $user = User::where(['api_token'=> $apitoken])->first();
        
        $input = $request->all();
        $userid = $user->id;
        $rules = array(
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $arr = array("status" => false, "statusCode"=>400, "message" => $validator->errors()->first(), "data" => array());
        } else {
            try {
                if ((Hash::check(request('old_password'), $user->password)) == false) {
                    if($language == 'hi'){
                        $queryStatus    = "अपना पुराना पासवर्ड जांच लें।";
                    }else{
                        $queryStatus    = "Check your old password.";
                    }
                    $arr = array("status" => false,"statusCode"=>400, "message" => $queryStatus, "data" => array());

                    
                   


                } else if ((Hash::check(request('new_password'), $user->password)) == true) {
                    if($language == 'hi'){
                        $queryStatus    = " कृप्या वह पासवर्ड डालें, जो कि वर्तमान पासवर्ड जैसा नहीं है। ";
                    }else{
                        $queryStatus    = "Please enter a password which is not similar then current password.";
                    }
                    $arr = array("status" => false, "statusCode"=>400, "message" => $queryStatus, "data" => array());
                } else {
                    User::where('id', $userid)->update(['password' => Hash::make($input['new_password'])]);
                    if($language == 'hi'){
                        $queryStatus    = "पासवर्ड सफलतापूर्वक अपडेट हो गया है। ";
                    }else{
                        $queryStatus    = "Password updated successfully.";
                    }
                    $arr = array("status" => true, "statusCode"=>200, "message" => $queryStatus, "data" => array());
                }
            } catch (\Exception $ex) {
                if (isset($ex->errorInfo[2])) {
                    $msg = $ex->errorInfo[2];
                } else {
                    $msg = $ex->getMessage();
                }
                $arr = array("status" => false, "statusCode"=>400, "message" => $msg, "data" => array());
            }
        }
        return response()->json($arr);

    }




    /**
     *
     * role
     */

    public function role()
    {
        $userRole = Role::wherein('id', [1, 2, 3, 7, 8.9])->select('id', 'role_name')->get();

        $queryStatus    = "All Role.";
        $statusCode     = 200;
        $status         = true;

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => [
               'role' => $userRole
           ]);

        return response()->json($response, 201);
    }

    /**
    * get profile
    */

    public function getprofile(Request $request)
    {
        $apitoken = $request->header('apitoken');
        $language = $request->header('language');
        $user = User::where(['api_token'=> $apitoken])->select('id', 'collection_center_id','isActive', 'is_address_added', 'is_promotional_mail', 'name', 'email', 'mobile', 'email_verified_at', 'profileImage', 'role_id', 'title', 'is_email_verified', 'is_address_added', 'is_document_added', 'member_id', 'organization_name', 'member_designation')->first();

        if ($user && $apitoken) {
            if($language == 'hi'){
                $queryStatus    = "उपयोगकर्ता की जानकारी। ";
            }else{
                $queryStatus    = "User Detail.";
            }
            
            $statusCode     = 200;
            $status         = true;

            // Get User Address
            $personal   = null;
            $office     = null;
            $registered = null;
            $countrylName = 'name  as name';
            $stateLname = 'name as name';
            $distLname = 'name as name';
            $blockLname = 'name  as name';
            if ($language == 'hi') {
                $countrylName = 'name_kn  as name';
                $stateLname = 'name_kn  as name';
                $distLname = 'name_kn  as name';
                $blockLname = 'name_kn  as name';
            }
            if ($user->role_id == 1) {
                $personal = Address::where(['user_id' => $user->id, 'address_type' => 'personal'])->first();

                if ($personal) {
                    $countryName = Country::where('id', $personal->country)->select('id', $countrylName)->first();
                    $stateName   = State::where('id', $personal->state)->select('id', $stateLname)->first();
                    $district    = City::where('id', $personal->district)->select('id', $distLname)->first();
                    $personal = [
                        'id'               => $personal->id,
                        'address_line_one' => $personal->address_line_one,
                        'address_line_two' => $personal->address_line_two,
                        'pincode'          => $personal->pincode,
                        'countryId'        => $countryName->id,
                        'stateId'          => $stateName->id,
                        'districtId'       => $district->id,
                        'country'          => $countryName->name,
                        'state'            => $stateName->name,
                        'district'         => $district->name
                    ];
                }
            }

            if ($user->role_id == 2) {
                $registered = Address::where(['user_id' => $user->id, 'address_type' => 'registered'])->first();

                if ($user->is_document_added == 1) {
                    $documentstatus = Documents::where('user_id', $user->id)->select('is_adhar_verify','pancard_file','brn_file','adhar_card_front_file','adhar_card_back_file', 'is_pan_verify', 'is_brn_verify','is_aadhar_added','is_pan_added','is_brn_added')->first();

                    $user['is_adhar_verify'] = $documentstatus->is_adhar_verify;
                    $user['is_pan_verify']   = $documentstatus->is_pan_verify;
                    $user['is_brn_verify']   = $documentstatus->is_brn_verify;
                   
                    $user['is_aadhar_added']   = $documentstatus->is_aadhar_added;
                    $user['is_pan_added']   = $documentstatus->is_pan_added;
                    $user['is_brn_added']   = $documentstatus->is_brn_added;

                    $user['adhar_card_front_file']   = $documentstatus->adhar_card_front_file;
                    $user['adhar_card_back_file']   = $documentstatus->adhar_card_back_file;
                    $user['pancard_file']   = $documentstatus->pancard_file;
                    $user['brn_file']   = $documentstatus->brn_file;

                }

                if ($registered) {
                    $countryName = Country::where('id', $registered->country)->select('id', $countrylName)->first();
                    $stateName   = State::where('id', $registered->state)->select('id', $stateLname)->first();
                    $district    = City::where('id', $registered->district)->select('id', $distLname)->first();
                    $blockData   = DB::table('blocks')->select('id',$blockLname)->where('id', $registered->block)->first();
                    $registered = [
                        'id'               => $registered->id,
                        'address_line_one' => $registered->address_line_one,
                        'address_line_two' => $registered->address_line_two,
                        'pincode'          => $registered->pincode,
                        'countryId'        => $countryName->id,
                        'stateId'          => $stateName->id,
                        'districtId'       => $district->id,
                        'block'            => $blockData->name,
                        'blockId'            => $blockData->id,
                        'country'          => $countryName->name,
                        'state'            => $stateName->name,
                        'district'         => $district->name
                    ];
                }

                //  Add Personal address in profile while shgartisan login on user app

                $personal = Address::where(['user_id' => $user->id, 'address_type' => 'personal'])->first();

                if ($personal) {
                    $countryName = Country::where('id', $personal->country)->select('id', 'name','name_kn')->first();
                    $stateName   = State::where('id', $personal->state)->select('id', 'name','name_kn')->first();
                    $district    = City::where('id', $personal->district)->select('id', 'name','name_kn')->first();
                    $personal = [
                        'id'               => $personal->id,
                        'address_line_one' => $personal->address_line_one,
                        'address_line_two' => $personal->address_line_two,
                        'pincode'          => $personal->pincode,
                        'countryId'        => $countryName->id,
                        'stateId'          => $stateName->id,
                        'districtId'       => $district->id,
                        'country'          =>  $countryName->name,
                        'state'            =>  $stateName->name,
                        'district'         => $district->name
                    ];
                }

                $office = Address::where(['user_id' => $user->id, 'address_type' => 'office'])->first();

                if ($office) {
                    $countryName = Country::where('id', $office->country)->select('id', 'name')->first();
                    $stateName   = State::where('id', $office->state)->select('id', 'name')->first();
                    $district    = City::where('id', $office->district)->select('id', 'name')->first();
                    $office = [
                        'id'               => $office->id,
                        'address_line_one' => $office->address_line_one,
                        'address_line_two' => $office->address_line_two,
                        'pincode'          => $office->pincode,
                        'countryId'        => $countryName->id,
                        'stateId'          => $stateName->id,
                        'districtId'       => $district->id,
                        'country'          => $countryName->name,
                        'state'            => $stateName->name,
                        'district'         => $district->name
                    ];
                }
            }

            if ($user->role_id == 3 || $user->role_id == 7 || $user->role_id == 8 || $user->role_id == 9) {
                //    Fetch Document Status on 31 12 2020

                if ($user->is_document_added == 1) {
                    $documentstatus = Documents::where('user_id', $user->id)->select('is_adhar_verify','pancard_file','brn_file','adhar_card_front_file','adhar_card_back_file', 'is_pan_verify', 'is_brn_verify','is_aadhar_added','is_pan_added','is_brn_added')->first();

                    $user['is_adhar_verify'] = $documentstatus->is_adhar_verify;
                    $user['is_pan_verify']   = $documentstatus->is_pan_verify;
                    $user['is_brn_verify']   = $documentstatus->is_brn_verify;
                   
                    $user['is_aadhar_added']   = $documentstatus->is_aadhar_added;
                    $user['is_pan_added']   = $documentstatus->is_pan_added;
                    $user['is_brn_added']   = $documentstatus->is_brn_added;

                    $user['adhar_card_front_file']   = $documentstatus->adhar_card_front_file;
                    $user['adhar_card_back_file']   = $documentstatus->adhar_card_back_file;
                    $user['pancard_file']   = $documentstatus->pancard_file;
                    $user['brn_file']   = $documentstatus->brn_file;
                }

                $registered = Address::where(['user_id' => $user->id, 'address_type' => 'registered'])->first();

                if ($registered) {
                    $countryName = Country::where('id', $registered->country)->select('id', $countrylName)->first();
                    $stateName   = State::where('id', $registered->state)->select('id', $stateLname)->first();
                    $district    = City::where('id', $registered->district)->select('id',$distLname)->first();
                    $blockData   = DB::table('blocks')->select('id',$blockLname)->where('id', $registered->block)->first();
                    $registered = [
                        'id'               => $registered->id,
                        'address_line_one' => $registered->address_line_one,
                        'address_line_two' => $registered->address_line_two,
                        'pincode'          => $registered->pincode,
                        'block'            => $blockData->name,
                        'blockId'            => $blockData->id,
                        'countryId'        => $countryName->id,
                        'stateId'          => $stateName->id,
                        'districtId'       => $district->id,
                        'country'          => $countryName->name,
                        'state'            => $stateName->name,
                        'district'         => $district->name
                    ];
                }

                $office = Address::where(['user_id' => $user->id, 'address_type' => 'office'])->first();

                if ($office) {
                    $countryName = Country::where('id', $office->country)->select('id', 'name')->first();
                    $stateName   = State::where('id', $office->state)->select('id', 'name')->first();
                    $district    = City::where('id', $office->district)->select('id', 'name')->first();
                    $office = [
                        'id'               => $office->id,
                        'address_line_one' => $office->address_line_one,
                        'address_line_two' => $office->address_line_two,
                        'pincode'          => $office->pincode,
                        'countryId'        => $countryName->id,
                        'stateId'          => $stateName->id,
                        'districtId'       => $district->id,
                        'country'          => $countryName->name,
                        'state'            => $stateName->name,
                        'district'         => $district->name
                    ];
                }

                //  Add Personal address in profile while shgartisan login on user app

                $personal = Address::where(['user_id' => $user->id, 'address_type' => 'personal'])->first();

                if ($personal) {
                    $countryName = Country::where('id', $personal->country)->select('id', 'name')->first();
                    $stateName   = State::where('id', $personal->state)->select('id', 'name')->first();
                    $district    = City::where('id', $personal->district)->select('id', 'name')->first();
                    $personal = [
                        'id'               => $personal->id,
                        'address_line_one' => $personal->address_line_one,
                        'address_line_two' => $personal->address_line_two,
                        'pincode'          => $personal->pincode,
                        'countryId'        => $countryName->id,
                        'stateId'          => $stateName->id,
                        'districtId'       => $district->id,
                        'country'          => $countryName->name,
                        'state'            => $stateName->name,
                        'district'         => $district->name
                    ];
                }
            }

            $address = [
                'personal'   => $personal,
                'office'     => $office,
                'registered' => $registered
            ];

            $location = Location::where('user_id', $user->id)->select('id as locationId', 'lat', 'log')->first();

            $user->lat = null;
            $user->log = null;

            if ($location) {
                $user->lat = $location->lat;
                $user->log = $location->log;
            }

            $ratingCount = Rating::where('user_id', $user->id)->count();
            $ratingStarAvg = DB::table('ratings')->where('user_id', $user->id)->avg('rating');
            $rating = [
                'reviewCount' => $ratingCount,
                'ratingAvgStar' => $ratingStarAvg
            ];
            // echo "<pre>"; print_r($user); die("check");
            $user['selectInterest'] = false;
            $intData = [];
            if($user->role_id  == 9){
                $checkIndividualInterest =IndividualInterest::where('user_id',$user->id)->first();
                if($checkIndividualInterest){
                    $user['selectInterest'] = true;
                }
                //get selected interest
                $individualInterest =IndividualInterest::select('id','user_id','individual_interest_list_id')->where('user_id',$user->id)->with('indInterest')->get();
                foreach($individualInterest as $ival){
                    $intData[] =['id'=>$ival->id,'name_en'=>$ival->indInterest->name_en,'name_hi'=>$ival->indInterest->name_hi,'image'=>$ival->indInterest->image];
                }
                $indratingCount = IndRating::where('user_id', $user->id)->count();
                $indratingStarAvg = DB::table('ind_ratings')->where('user_id', $user->id)->avg('rating');
                $rating = [
                    'reviewCount' => $indratingCount,
                    'ratingAvgStar' => $indratingStarAvg
                ];
            }

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => [
                'user' => $user, 'address' =>$address, 'rating' => $rating, 'individualInterest' => $intData
            ] );

            return response()->json($response, 201);
        } else {
            if($language == 'hi'){
                $queryStatus    = "कृपया सही एपीआई टाकन बतायें";
            }else{
                $queryStatus    = "Please provide valid api token.";
            }
            
            $statusCode     = 400;
            $status         = false;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

            return response()->json($response, 201);
        }
    }

    /**
     * delete profile
     */

    public function deleteprofile(Request $request)
    {
        $user = $request->user;
        $language = $request->header('language');
        // $rules = [
        //     'reason' => 'required'
        // ];

        // $validator = Validator::make($request->all(), $rules);

        // if ($validator->fails()) {
        //     $response = array('status' => false , 'statusCode' =>400);
        //     $response['message'] = $validator->messages()->first();
        //     return response()->json($response);
        // }

        $deleteUser = User::where(['id' => $user->id, 'isActive' => 1])->update([ 'isActive' => 0 ]);

        // $addreason = Reason::create(['user_id' => $user->id, 'reason' => $request->reason]);

        $product = ProductMaster::where(['user_id' => $user->id, 'is_active' => 1, 'is_draft' => 0])->get();

        if (count($product) > 0) {
            $deleteProduct = ProductMaster::where(['user_id' => $user->id, 'is_active' => 1, 'is_draft' => 0])->update(['is_active' => 0]);
        }
        if($language == 'hi'){
            $queryStatus    = "उपयोगकर्ता प्रोफाइल हटाने में असफल";
        }else{
            $queryStatus    = "Failed to delete user profile.";
        }
       
        $statusCode     = 400;
        $status         = false;

        if ($deleteUser) {
            if($language == 'hi'){
                $queryStatus    = "उपयोगकर्ता प्रोफाइल हटा दी गई";
            }else{
                $queryStatus    = "User profile deleted";
            }
            
            $statusCode     = 200;
            $status         = true;
        }

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

        return response()->json($response, 201);
    }

    /**
     * update user profile
     */

    public function updateuserprofile(Request $request)
    {
        $user = $request->user;
        $language = $request->header('language');
        $rules = [
            'name'  => 'required',
            //'email' => 'required|email'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        if ($user->email == $request->email) {
            $query = ['name' => $request->name];
        } else {
            $checkEmail = User::where(['email' => $request->email, 'isActive' => 1])->first();

            if ($checkEmail) {
                $response = array('status' => false , 'statusCode' =>400);
                if($language == 'hi'){
                    $response['message'] = "ईमेल अस्तित्व में है";
                }else{
                    $response['message'] = "Email already exists";
                }
                
                return response()->json($response);
            } else {
                $query = ['name'=> $request->name, 'email' => $request->email];
            }
        }

        if (($user->role_id == 2) || ($user->role_id == 3)) {
            // $rules['title'] = 'required';
            $rules['member_id'] = 'required';
            $rules['organization_name'] = 'required';
            $rules['member_designation'] = 'required';


            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $response = array('status' => false , 'statusCode' =>400);
                $response['message'] = $validator->messages()->first();
                return response()->json($response);
            }
            $query['member_id'] = $request->member_id;
            $query['organization_name'] = $request->organization_name;
            $query['member_designation'] = $request->member_designation;

            // $query['title'] = $request->title;
        }

        $updateuser = User::where('id', $user->id)->update($query);

        $response = array('status' => true , 'statusCode' =>200);
        if($language == 'hi'){
            $response['message'] = "उपयोगकर्ता प्रोफाइल अपडेट हो गई है";
        }else{
            $response['message'] = "User Profile Updated!";
        }
        
        return response()->json($response);
    }

    /**
     * add address
     */

    public function addaddress(Request $request)
    {
        $apitoken =  $request->header('apitoken');
        $language = $request->header('language');
        $user = User::where(['api_token'=> $apitoken])->select('id', 'name', 'email', 'mobile', 'role_id', 'is_address_added')->first();

        if ($user && $apitoken) {
            $validator = Validator::make($request->all(), [
                'address_line_one'      => 'required_without:address_line_two',
                'address_line_two'      => 'required_without:address_line_one',
                'pincode'               => 'required',
                'country'               => 'required',
                'state'                 => 'required',
                'district'              => 'required',
                'village'                 => 'required',
                'block'              => 'required'
                // 'address_type'          => 'required|in:personal,registered,office'
            ]);

            if ($validator->fails()) {
                $response = array('status' => false , 'statusCode' =>400);
                $response['message'] = $validator->messages()->first();
                return response()->json($response);
            }

            $requestData = $request->all();
            $requestData['user_id'] = $user->id;
            $requestData['user_role_id'] = $user->role_id;
            $requestData['address_type'] = 'personal';

            $is_address_added = 1;



            $addaddress = Address::create($requestData);
            $updateusertable = User::where('id', $user->id)->update(['country_id' => $request->country, 'state_id' => $request->state, 'district' => $request->district , 'city_id' => $request->district, 'village' => $request->village ]);

            $userUPdate = User::where(['id' => $user->id])->update(['is_address_added' => $is_address_added]);

            if($language == 'hi'){
                $queryStatus    = "पता सफलतापूर्वक जोड़ दिया गया है";
            }else{
                $queryStatus    = "Address added successfully.";
            }
            
            $statusCode     = 200;
            $status         = true;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

            return response()->json($response, 201);
        } else {
            if($language == 'hi'){
                $queryStatus    = "कृपया सही एपीआई टाकन बतायें";
            }else{
                $queryStatus    = "Please provide valid api_token.";
            }
            
            $statusCode     = 400;
            $status         = false;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

            return response()->json($response, 201);
        }
    }
    /**
     * get address
     */
    public function getaddress(Request $request)
    {
        $apitoken =  $request->header('apitoken');
        $language = $request->header('language');
        $user = User::where(['api_token'=> $apitoken])->first();



        if ($user && $apitoken) {
            $useraddress = DB::table('addresses')
                            ->where('addresses.user_id', $user->id)
                            ->join('countries', 'countries.id', '=', 'addresses.country')
                            ->join('states', 'states.id', '=', 'addresses.state')
                            ->join('cities', 'cities.id', '=', 'addresses.district')
                            ->select('addresses.*', 'countries.name as country', 'states.name as state', 'cities.name as district')
                            ->first();

            if ($useraddress) {
                if($language == 'hi'){
                    $queryStatus    = "उपयोगकर्ता का पता";
                }else{
                    $queryStatus    = "User Address.";
                }
               
                $statusCode     = 200;
                $status         = true;

                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => [ 'useraddress' => $useraddress ] );

                return response()->json($response, 201);
            } else {
                if($language == 'hi'){
                    $queryStatus    = "उपयोगकर्ता का पता नहीं मिला";
                }else{
                    $queryStatus    = "User Address not found.";
                }
               
                $statusCode     = 400;
                $status         = false;

                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

                return response()->json($response, 201);
            }
        } else {
            if($language == 'hi'){
                $queryStatus    = "कृपया सही एपीआई टाकन बतायें";
            }else{
                $queryStatus    = "Please provide valid api token.";
            }
            
            $statusCode     = 400;
            $status         = false;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

            return response()->json($response, 201);
        }
    }
    /**
     * update address
     */
    public function updateaddress(Request $request)
    {
        $apitoken = $request->header('apitoken');
        $language = $request->header('language');
        $user = User::where(['api_token'=> $apitoken])->first();
        if ($user && $apitoken) {
            $validator = Validator::make($request->all(), [
                'address_line_one'      => 'required_without:address_line_two',
                'address_line_two'      => 'required_without:address_line_one',
                'pincode'               => 'required',
                'country'               => 'required',
                'state'                 => 'required',
                'district'              => 'required',
                'id'                    => 'required',
                //'village'               => 'required',
                'block'                 => 'required'
                // 'lat'                   => 'required',
                // 'log'                   => 'required'
            ]);

            if ($validator->fails()) {
                $response = array('status' => false , 'statusCode' =>400);
                $response['message'] = $validator->messages()->first();
                return response()->json($response);
            }

            // $lat = $request->header('lat');
            // $log = $request->header('log');

            if (isset($request->lat)) {
                $lat = $request->lat;
            }
            if (isset($request->log)) {
                $log = $request->log;
            }


            // if ($user->role_id != 1) {
            //     $getLocation = Pincode::where('pin_code', $request->pincode)->first();

            //     if (!$getLocation) {
            //         $response = array('status' => false , 'statusCode' => 400 );
            //         $response['message'] = "Please enter valid pincode.";
            //         return response()->json($response);
            //     }
            // }




            $useraddressupdate = Address::where('id', $request->id)->update([
                'address_line_one'      => $request->address_line_one,
                'address_line_two'      => $request->address_line_two,
                'pincode'               => $request->pincode,
                'country'               => $request->country,
                'state'                 => $request->state,
                'district'              => $request->district,
                'village'               => $request->village,
                'block'                 => $request->block
            ]);

            $updateusertable = User::where('id', $user->id)->update(['country_id' => $request->country, 'state_id' => $request->state, 'district' => $request->district , 'city_id' => $request->district, 'village' => $request->village, 'block' => $request->block ]);


            $checkLocation = Location::where('user_id', $user->id)->first();



            if ($user->role_id != 1) {
                if ($checkLocation) {
                    $locationupdate = Location::where('user_id', $user->id)->update(['lat' => $lat, 'log' => $log]);
                } else {
                    $addLocation = Location::create([
                        'user_id' => $user->id,
                        'lat'     => $lat,
                        'log'     => $log
                    ]);
                }
            }





            if ($useraddressupdate && $updateusertable) {
                if($language == 'hi'){
                    $queryStatus    = "पता सफलतापूर्वक जोड़ दिया गया है ";
                }else{
                    $queryStatus    = "Address update successfully.";
                }
                
                $statusCode     = 200;
                $status         = true;

                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

                return response()->json($response, 201);
            } else {
                $queryStatus    = "Failed to update address.";
                $statusCode     = 400;
                $status         = false;

                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

                return response()->json($response, 201);
            }
        } else {
            $queryStatus    = "Please provide valid api_token.";
            $statusCode     = 400;
            $status         = false;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

            return response()->json($response, 201);
        }
    }
    /**
     * update mobile Number
     */

    public function updatemobile(Request $request)
    {
        $apitoken = $request->header('apitoken');
        $language = $request->header('language');
        $user = User::where(['api_token'=> $apitoken])->first();

        if ($user && $apitoken) {
            $validator = Validator::make($request->all(), [

                'mobile'      => 'required|unique:users',
                'otp'         => 'required'

            ]);

            if ($validator->fails()) {
                $response = array('status' => false , 'statusCode' =>400);
                $response['message'] = $validator->messages()->first();
                return response()->json($response);
            }

            $checkOtp = Otphistory::where(['mobile_no' => $request->mobile, 'otp' => $request->otp, 'status' => 0])->first();

            if (!$checkOtp) {
                if($language == 'hi'){
                    $queryStatus    = "मोबाइल नंबर अपडेट असफल";
                }else{
                    $queryStatus    = "Failed to update mobile number.";
                }
                
                $statusCode     = 400;
                $status         = false;

                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

                return response()->json($response, 201);
            }

            $updateMobile = User::where(['id' => $user->id])->update(['mobile' => $request->mobile]);

            if (!$updateMobile) {
                if($language == 'hi'){
                    $queryStatus    = "मोबाइल नंबर अपडेट असफल";
                }else{
                    $queryStatus    = "Failed to update mobile number.";
                }
                $statusCode     = 400;
                $status         = false;

                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

                return response()->json($response, 201);
            }

            if($language == 'hi'){
                $queryStatus    = "मोबाइल नंबर सफलतापूर्वक अपडेट";
            }else{
                $queryStatus    = "Mobile number updated successfully.";
            }

            
            $statusCode     = 200;
            $status         = true;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

            return response()->json($response, 201);
        } else {
            $queryStatus    = "Please provide valid api_token.";
            $statusCode     = 400;
            $status         = false;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

            return response()->json($response, 201);
        }
    }

    


    public function adddocumentandaddress(Request $request)
    {
        $apitoken = $request->header('apitoken');
        $language = $request->header('language');
        $user = User::where(['api_token'=> $apitoken])->first();

        if ($user && $apitoken) {
            // $roleId = $user->role_id;
            $validator = Validator::make($request->all(), [
                'role_id'      => 'required',
            ]);

            if ($validator->fails()) {
                $response = array('status' => false , 'statusCode' => 400 );
                $response['message'] = $validator->messages()->first();
                return response()->json($response);
            }

            //$roleIdUpdate = User::where('id', $user->id)->update(['role_id' => $request->role_id]);

            $roleId = $request->role_id;

            if ($roleId == 2) {
                $validator = Validator::make($request->all(), [
                    // 'adhar_card_no'      => 'required',
                    // 'adhar_name'         => 'required',
                    // 'adhar_card_front_file'    => 'required|mimes:jpeg,jpg,png,gif|required|max:10000',
                    // 'adhar_card_back_file'    => 'required|mimes:jpeg,jpg,png,gif|required|max:10000',

                    // 'adhar_dob'          => 'required',

                    // 'lat'                => 'required',
                    // 'log'                => 'required',


                    'address_line_one_registered' => 'required_without:address_line_two_registered',
                    'address_line_two_registered' => 'required_without:address_line_one_registered',
                    'pincode_registered'          => 'required',
                    'country_registered'          => 'required',
                    'state_registered'            => 'required',
                    'district_registered'         => 'required',
                   // 'village_registered'            => 'required',
                    // 'block_registered'         => 'required',

                ]);

                if ($validator->fails()) {
                    $response = array('status' => false , 'statusCode' => 400 );
                    $response['message'] = $validator->messages()->first();
                    return response()->json($response);
                }
            }
            

            // Create Folder for Document Adhar Card
            if($request->hasFile('adhar_card_front_file')){
                $adhar_card_file = $request->file('adhar_card_front_file');
                $folder = public_path('images/document/' . $user->id . '/');
    
                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }
    
                $adhar_card_image = date('YmdHis') . "adhar." . $adhar_card_file->getClientOriginalExtension();
                $aa = $adhar_card_file->move($folder, $adhar_card_image);
    
                $adhar_card_image_name = $adhar_card_image;
                $adhar_card_user_image = 'images/document/'.$user->id.'/'.$adhar_card_image_name;
            }else{
                $adhar_card_user_image = NULL;
                
            }
            

            // Create Folder for Document Back Side Adhar Card
            if($request->hasFile('adhar_card_back_file')){
                $adhar_card_back_file = $request->file('adhar_card_back_file');
                $folder = public_path('images/document/' . $user->id . '/');
    
                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }
    
                $adhar_card_back_image = date('YmdHis') . "adharbackside." . $adhar_card_file->getClientOriginalExtension();
                $aa = $adhar_card_back_file->move($folder, $adhar_card_back_image);
    
                $adhar_card_back_image_name = $adhar_card_back_image;
                $adhar_card_user_back_image = 'images/document/'.$user->id.'/'.$adhar_card_back_image_name;
                $updateDocument['is_aadhar_added' ] = 1;
            }else{
                $adhar_card_user_back_image = NULL;
            }
            


            // Create Folder for Document Pan Card
            if ($roleId == 3 || $roleId == 2 || $roleId == 7 || $roleId == 8) {
                if ($request->hasFile('pancard_file')) {
                    $pancard_file = $request->file('pancard_file');
                    $folder = public_path('images/document/' . $user->id . '/');
    
                    if (!Storage::exists($folder)) {
                        Storage::makeDirectory($folder, 0775, true, true);
                    }
    
                    $pan_card_image = date('YmdHis') . "pancard." . $pancard_file->getClientOriginalExtension();
                    $ab = $pancard_file->move($folder, $pan_card_image);
    
                    $pan_card_image_name = $pan_card_image;
                    $pan_card_user_image = 'images/document/'.$user->id.'/'.$pan_card_image_name;
                    $updateDocument['is_pan_added' ] = 1;
                }
                if ($request->hasFile('brn_file')) {
                    
                    // Create Folder for Document BRN Card

                    $brn_file = $request->file('brn_file');
                    $folder = public_path('images/document/' . $user->id . '/');

                    if (!Storage::exists($folder)) {
                        Storage::makeDirectory($folder, 0775, true, true);
                    }

                    $brn_image = date('YmdHis') . "brn." . $brn_file->getClientOriginalExtension();
                    $ac = $brn_file->move($folder, $brn_image);

                    $brn_card_image_name = $brn_image;
                    $brn_card_user_image = 'images/document/'.$user->id.'/'.$brn_card_image_name;
                    $updateDocument['is_brn_added' ] = 1;
                }
            }



            // Document Array for ARTISAn

            $updateDocument['user_id' ] = $user->id;
            $updateDocument['adhar_card_no'] = $request->adhar_card_no;
            $updateDocument['adhar_name'] = $request->adhar_name;
            $updateDocument['adhar_card_front_file'] = $adhar_card_user_image;
            $updateDocument['adhar_card_back_file' ] = $adhar_card_user_back_image;
            $updateDocument['adhar_dob'] = $request->adhar_dob;

            //                => $user->id,
            //     'adhar_card_no'      => $request->adhar_card_no,
            //     'adhar_name'         => $request->adhar_name,
            //     'adhar_card_front_file'    => $adhar_card_user_image,
            //     'adhar_card_back_file'     => $adhar_card_user_back_image,
            //     'adhar_dob'          => $request->adhar_dob,
            // ];
            // Document Array for SHG

            if ($roleId == 3 || $roleId == 2 || $roleId == 7 || $roleId == 8 ) {

                // $updateDocument = [
                //     'pancard_name'       => $request->pancard_name,
                //     'pancard_no'         => $request->pancard_no,
                //     'pancard_file'       => $pan_card_user_image,
                //     'pancard_dob'        => $request->pancard_dob,
                // ];

                if ($request->hasFile('pancard_file')) {
                    $updateDocument['pancard_name'] = $request->pancard_name;
                    $updateDocument['pancard_no'] = $request->pancard_no;
                    $updateDocument['pancard_file'] = $pan_card_user_image;
                    $updateDocument['pancard_dob'] = $request->pancard_dob;
                }

                if ($request->hasFile('brn_file')) {
                    $updateDocument['brn_no'] = $request->brn_no;
                    $updateDocument['brn_name'] = $request->brn_name;
                    $updateDocument['brn_file'] = $brn_card_user_image;
                }

                // $updateDocument = [
                //     'user_id'            => $user->id,
                //     'adhar_card_no'      => $request->adhar_card_no,
                //     'adhar_name'         => $request->adhar_name,
                //     'adhar_card_front_file'    => $adhar_card_user_image,
                //     'adhar_card_back_file'     => $adhar_card_user_back_image,

                //     'adhar_dob'          => $request->adhar_dob,

                //     'pancard_name'       => $request->pancard_name,
                //     'pancard_no'         => $request->pancard_no,
                //     'pancard_file'       => $pan_card_user_image,
                //     'pancard_dob'        => $request->pancard_dob,

                //     'brn_no'             => $request->brn_no,
                //     'brn_name'           => $request->brn_name,
                //     'brn_file'           => $brn_card_user_image,
                // ];
            }


            $addDocument = Documents::create($updateDocument);

            
            // Registered Address Array for SHG

            $shgregsiteraddress = [
                'user_id'                     => $user->id,
                'user_role_id'            => $roleId,
                'address_line_one' => $request->address_line_one_registered,
                'address_line_two' => $request->address_line_two_registered,
                'pincode'          => $request->pincode_registered,
                'country'          => $request->country_registered,
                'state'            => $request->state_registered,
                'district'         => $request->district_registered,
                'village'           => $request->village,
                'block'             => $request->block,
                'address_type'         => 'registered'
            ];

            $userAddressUpdate = User::where('id', $user->id)->update([ 'country_id' => $request->country_registered, 'state_id' => $request->state_registered, 'city_id' => $request->district_registered, 'district' => $request->district_registered, 'village' => $request->village, 'block' => $request->block]);

            $addshgregsiteraddress = Address::create($shgregsiteraddress);

            $checkCondition = $addshgregsiteraddress && $addDocument;
            if ($roleId == 3) {
                $checkCondition = ($addshgregsiteraddress && $addDocument);
            }


            if ($checkCondition) {

                // Add Location Lat & Long witn user id


                $addLocation = Location::create([
                    'user_id' => $user->id,
                    'lat'     => $request->lat,
                    'log'     => $request->log
                ]);

                // is_document_verified verified added by default on 5 jan 2021

                $userDocumentUpdate = User::where(['id' => $user->id])->update(['is_document_added' => 1, 'is_document_verified' => 1]);
                //$userDocumentUpdate = User::where(['id' => $user->id])->update(['is_document_added' => 1, 'is_address_added' => 1]);
                $useraddressUpdate = User::where(['id' => $user->id])->update(['is_address_added' => 1]);

                // Update role id
                // $updaterole = User::where('id', $user->id)->update(['role_id' => $request->role_id]);
                if($language == 'hi'){
                    $queryStatus    = "दस्तावेज अपलोडिंग और पता जोड़ना सफलतापूर्वक किया गया ";
                }else{
                    $queryStatus    = "Successfuly document uploaded and adress added.";
                }
                
                $statusCode     = 200;
                $status         = true;

                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

                return response()->json($response, 201);
            } else {
                if($language == 'hi'){
                    $queryStatus    = "दस्तावेज अपलोडिंग और पता जोड़ना नहीं हो पाया ";
                }else{
                    $queryStatus    = "Failed to add address and upload document.";
                }
                
                $statusCode     = 400;
                $status         = false;

                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

                return response()->json($response, 201);
            }
        } else {
            $queryStatus    = "Please provide valid api_token.";
            $statusCode     = 400;
            $status         = false;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

            return response()->json($response, 201);
        }
    }

    /**
     * getdocument and address
     */
    public function getdocumentandaddress(Request $request)
    {
        $apitoken = $request->header('apitoken');
        $language = $request->header('language');
        $user = User::where(['api_token'=> $apitoken])->first();

        if ($user && $apitoken) {
            $userofficeaddress = DB::table('addresses')
                            ->where('addresses.user_id', $user->id)
                            ->where('addresses.address_type', 'office')
                            ->join('countries', 'countries.id', '=', 'addresses.country')
                            ->join('states', 'states.id', '=', 'addresses.state')
                            ->join('cities', 'cities.id', '=', 'addresses.district')
                            ->select('addresses.*', 'countries.name as country', 'states.name as state', 'cities.name as district')
                            ->get();
            $userregisteredaddress = DB::table('addresses')
                            ->where('addresses.user_id', $user->id)
                            ->where('addresses.address_type', 'registered')
                            ->join('countries', 'countries.id', '=', 'addresses.country')
                            ->join('states', 'states.id', '=', 'addresses.state')
                            ->join('cities', 'cities.id', '=', 'addresses.district')
                            ->select('addresses.*', 'countries.name as country', 'states.name as state', 'cities.name as district')
                            ->get();
            $document = Documents::where('user_id', $user->id)->first();
            if($language == 'hi'){
                $queryStatus    = "उपयोगकर्ता का पता और दस्तावेज";
            }else{
                $queryStatus    = "User Address and Document.";
            }
            
            $statusCode     = 200;
            $status         = true;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => ['document' => $document, 'userregisteredaddress' => $userregisteredaddress, 'userofficeaddress' => $userofficeaddress ] );

            return response()->json($response, 201);
        } else {
            $queryStatus    = "Please provide valid api_token.";
            $statusCode     = 400;
            $status         = false;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

            return response()->json($response, 201);
        }
    }

    /**
     * update document and address
     */
    public function updatedocument(Request $request)
    {
        $user = $request->user;
        $language = $request->header('language');
        $rules = [
            'is_adhar_update' => 'required',
            'is_pan_update'   => 'required',
            'is_brn_update'   => 'required'
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' => 400 );
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        if ($request->is_adhar_update == 1) {
            $rules = [
                'adhar_card_no'      => 'required',
                'adhar_name'         => 'required',
                'adhar_card_front_file'    => 'required|mimes:jpeg,jpg,png,gif|required|max:10000',
                'adhar_card_back_file'    => 'required|mimes:jpeg,jpg,png,gif|required|max:10000',
                'adhar_dob'          => 'required',
            ];
        }


        if ($request->is_pan_update == 1) {
            $rules = [
                'pancard_name'       => 'required',
                'pancard_no'         => 'required',
                'pancard_file'       => 'required|mimes:jpeg,jpg,png,gif|required|max:10000',
                'pancard_dob'        => 'required',
            ];
        }

        if ($request->is_brn_update == 1) {
            $rules = [
                'brn_no'             => 'required',
                'brn_name'           => 'required',
                'brn_file'           => 'required|mimes:jpeg,jpg,png,gif|required|max:10000',
            ];
        }



        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' => 400 );
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        if ($request->is_adhar_update == 1) {
            // Create Folder for Document Adhar Card

            $adhar_card_file = $request->file('adhar_card_front_file');
            $folder = public_path('images/document/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $adhar_card_image = date('YmdHis') . "adhar." . $adhar_card_file->getClientOriginalExtension();
            $aa = $adhar_card_file->move($folder, $adhar_card_image);

            $adhar_card_image_name = $adhar_card_image;
            $adhar_card_user_image = 'images/document/'.$user->id.'/'.$adhar_card_image_name;

            // Create Folder for Document Back Side Adhar Card

            $adhar_card_back_file = $request->file('adhar_card_back_file');
            $folder = public_path('images/document/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $adhar_card_back_image = date('YmdHis') . "adharbackside." . $adhar_card_file->getClientOriginalExtension();
            $aa = $adhar_card_back_file->move($folder, $adhar_card_back_image);

            $adhar_card_back_image_name = $adhar_card_back_image;
            $adhar_card_user_back_image = 'images/document/'.$user->id.'/'.$adhar_card_back_image_name;

            $updateDocument = [

                'adhar_card_no'      => $request->adhar_card_no,
                'adhar_name'         => $request->adhar_name,
                'adhar_card_front_file'    => $adhar_card_user_image,
                'adhar_card_back_file'     => $adhar_card_user_back_image,
                'adhar_dob'          => $request->adhar_dob,
                'is_aadhar_added'          => 1,
            ];

            $updateDocument = Documents::where('user_id', $user->id)->update($updateDocument);
            if($language == 'hi'){
                $queryStatus    = "आधार अपडेट करने में असफल";
            }else{
                $queryStatus    = "Failed to update adhar.";
            }
            
            $statusCode     = 400;
            $status         = false;

            if ($updateDocument) {
                if($language == 'hi'){
                    $queryStatus    = "दस्तावेज सफलतापूर्व अपलोड";
                }else{
                    $queryStatus    = "Successfuly document uploaded.";
                }
                
                $statusCode     = 200;
                $status         = true;
            }

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

            return response()->json($response, 201);
        }

        if ($request->is_pan_update == 1) {
            $pancard_file = $request->file('pancard_file');
            $folder = public_path('images/document/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $pan_card_image = date('YmdHis') . "pancard." . $pancard_file->getClientOriginalExtension();
            $ab = $pancard_file->move($folder, $pan_card_image);

            $pan_card_image_name = $pan_card_image;
            $pan_card_user_image = 'images/document/'.$user->id.'/'.$pan_card_image_name;

            $pandata = [
                'pancard_name'       => $request->pancard_name,
                'pancard_no'         => $request->pancard_no,
                'pancard_file'       => $pan_card_user_image,
                'pancard_dob'        => $request->pancard_dob,
                'is_pan_added'        => 1,
            ];

            $updateDocument = Documents::where('user_id', $user->id)->update($pandata);
            if($language == 'hi'){
                $queryStatus    = "PAN करने में असफल";
            }else{
                $queryStatus    = "Failed to update PAN.";
            }
            
            $statusCode     = 400;
            $status         = false;

            if ($updateDocument) {
                if($language == 'hi'){
                    $queryStatus    = "दस्तावेज सफलतापूर्व अपलोड";
                }else{
                    $queryStatus    = "Successfuly document uploaded.";
                }
                
                $statusCode     = 200;
                $status         = true;
            }

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

            return response()->json($response, 201);
        }

        if ($request->is_brn_update == 1) {
            $brn_file = $request->file('brn_file');
            $folder = public_path('images/document/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $brn_image = date('YmdHis') . "brn." . $brn_file->getClientOriginalExtension();
            $ac = $brn_file->move($folder, $brn_image);

            $brn_card_image_name = $brn_image;
            $brn_card_user_image = 'images/document/'.$user->id.'/'.$brn_card_image_name;

            $updatebrn = [
                'brn_no'             => $request->brn_no,
                'brn_name'           => $request->brn_name,
                'brn_file'           => $brn_card_user_image,
                'is_brn_added'        => 1,
            ];

            $updateDocument = Documents::where('user_id', $user->id)->update($updatebrn);

            $queryStatus    = "Failed to update PAN.";
            $statusCode     = 400;
            $status         = false;

            if ($updateDocument) {
                if($language == 'hi'){
                    $queryStatus    = "दस्तावेज सफलतापूर्व अपलोड";
                }else{
                    $queryStatus    = "Successfuly document uploaded.";
                }
                $statusCode     = 200;
                $status         = true;
            }

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

            return response()->json($response, 201);
        }
    }

    /**
     * update profile
     *  */
    public function updateprofileimage(Request $request)
    {
        $user = $request->user;
        $language = $request->header('language');
        $rules = [
            'profileimage' => 'required|max:30000|mimes:jpg,jpeg,png,svg'
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' => 400 );
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        /** Upload Images */

        $image_file_1 = $request->file('profileimage');
        $folder = public_path('images/users/' . $user->id . '/');

        if (!Storage::exists($folder)) {
            Storage::makeDirectory($folder, 0775, true, true);
        }

        $image_file_1_image = date('YmdHis') . rand(111, 9999). "userimage." . $image_file_1->getClientOriginalExtension();
        $aa = $image_file_1->move($folder, $image_file_1_image);

        $image_file_1_image_name = $image_file_1_image;
        $image_file_1_image = 'images/users/'.$user->id.'/'.$image_file_1_image_name;


        $updateuser = User::where('id', $user->id)->update(['profileImage'=> $image_file_1_image]);

        if($language == 'hi'){
            $queryStatus    = "प्रोफाइल चित्र सफलतापूर्वक अपलोड";
        }else{
            $queryStatus    = "Profile Image updated successfully.";
        }
        
        $statusCode     = 200;
        $status         = true;

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        return response()->json($response, 201);
    }
    //update language not in use
    public function updateLanguage(Request $request)
    { 
        $apitoken = $request->header('apitoken');
        $language = $request->language;
        $user = User::where(['api_token'=> $apitoken])->first();
        
        $rules = array(
            'language' => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $arr = array("status" => false, "statusCode"=>400, "message" => $validator->errors()->first(), "data" => array());
        } else {
            if($user){
                
                    User::where('id', $user->id)->update(['language' => $language]);
                    $statusCode     = 200;
                    $status         = true;
                    $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> 'Update successfully');
            
                    return response()->json($response, 201);
            }else{
                $queryStatus    = "Please provide valid api_token.";
                $statusCode     = 400;
                $status         = false;
                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );
                return response()->json($response, 201);
            }
           
        }

    }

}
