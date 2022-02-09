<?php

namespace App\Http\Controllers\Forntend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Otphistory;
use App\Role;
use App\Address;
use App\Location;

use App\Reason;
use App\Pincode;
use DB;
use App\Country;
use App\State;
use App\City;
use App\Documents;
use App\ProductMaster;
use Illuminate\Support\Facades\Storage;
use Auth;
use Illuminate\Support\Facades\Crypt;
use App\Category;
use App\ProductTemplate;
use App\Material;
use App\Rules\MatchOldPassword;
// use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    private $headers;
    public function __construct()
    {
        $this->headers = [
            'Content-Type' => 'application/json',
            'app-key' => 'laravelUNDP',

        ];

        $this->middleware('auth');
    }

    public function index()
    {
    }

    public function viewProfile()
    {
        $user = Auth::user();
        // dd('sdf');
        Auth::user()->role_id;
        Auth::user()->country;
        Auth::user()->state;
        Auth::user()->city;
        Auth::user()->district;
        Auth::user()->address_registerd;
        Auth::user()->address_personal;
        Auth::user()->document;
        Auth::user()->address;


        // Get User Address
        $personal   = null;
        $office     = null;
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
        } else {
            $registered = Address::where(['user_id' => $user->id, 'address_type' => 'registered'])->first();

            if ($user->is_document_added == 1) {
                $documentstatus = Documents::where('user_id', $user->id)->select('is_adhar_verify', 'is_pan_verify', 'is_brn_verify')->first();

                $user['is_adhar_verify'] = $documentstatus->is_adhar_verify;
                // $user['is_pan_verify']   = $documentstatus->is_pan_verify;
                // $user['is_brn_verify']   = $documentstatus->is_brn_verify;
            }

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

        return view('frontend.user.profile', ['address'=>$address]);
    }

    public function editprofile(Request $request)
    {
        $user = Auth::user();
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'unique:users,email,' . $user->id
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $query = ['name'=> $request->name, 'email' => $request->email];

        if (($user->role_id == 2) || ($user->role_id == 3)) {
            $rules['title'] = 'required';
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $query['title'] = $request->title;
        }

        $updateuser = User::where('id', $user->id)->update($query);
        return redirect()->back()->with('message', 'Profile Successfully Updated.')->withInput();
    }

    public function deleteprofile(Request $request)
    {
        $user = Auth::user();

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

        $addreason = Reason::create(['user_id' => $user->id, 'reason' => $request->reason]);

        $product = ProductMaster::where(['user_id' => $user->id, 'is_active' => 1, 'is_draft' => 0])->get();

        if (count($product) > 0) {
            $deleteProduct = ProductMaster::where(['user_id' => $user->id, 'is_active' => 1, 'is_draft' => 0])->update(['is_active' => 0]);
        }

        Auth::logout();

        return redirect('/');
    }

    public function deleteproduct(Request $request, $id)
    {
        $id = decrypt($id);
        $productData  = ProductMaster::where(['id'=>$id, 'user_id' => Auth::user()->id])->first();

        if ($productData) {

            $lastURL = url()->previous();
            $urlArr =  explode("/", $lastURL);
            $getURL = $urlArr['3'];

            //$deleteProduct = ProductMaster::where(['id' => $id])->update(['is_active' => 0,'is_draft'=>0]);
            $deleteProduct = ProductMaster::where(['id' => $id])->delete();
            
            if ($getURL == "draft") {
                return redirect()->back()->with('message', "Product Deleted Succesfully");
            } else {
                return redirect('profile/home');
            }

        } else {

            abort(404);
        }
    }


    public function changemobileno(Request $request)
    {
        return view('frontend.user.change_mobile');
    }


    public function changepassword(Request $request)
    {
        return view('frontend.user.change_pass');
    }


    public function updatechnagepassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'password' => 'required|confirmed|min:4',
            'current_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                if (!\Hash::check($value, $user->password)) {
                    return $fail(__('The current password is incorrect.'));
                }
            }],
        ]);
    
        User::find(auth()->user()->id)->update(['password'=> Hash::make($request->password)]);
    
        //dd('Password change successfully.');

        return redirect('profile/home');
    }
    



     

    public function updatemobileno(Request $request)
    {
        $rules = [
            'mobile'=>'required|min:10|max:10|unique:users'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $otp = rand(1111, 9999);
        $this->sendotp($request->mobile, $otp);
        $otpUpdateStatus = Otphistory::where(['mobile_no'=> $request->mobile ])->update(['status' => 0]);
        $otpStatus = Otphistory::create(['mobile_no' => $request->mobile, 'otp' => $otp ]);
        $encrypted = Crypt::encryptString($request->mobile);

        return redirect('/verifyotp/'.$encrypted.'?type=change');
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

    public function add_document()
    {
        return view('frontend.user.add_document');
    }
    public function add_document_update(Request $request)
    {
        $user = Auth::user();
        $roleId = $user->role_id;
        if ($roleId == 2) {
            $validator = Validator::make($request->all(), [
                'adhar_card_no'                 => 'required|digits:12',
                'adhar_name'                    => 'required',
                'adhar_card_front_file'         => 'required|mimes:JPEG,JPG,jpeg,jpg,png,gif|required|max:10000',
                'adhar_card_back_file'          => 'required|mimes:JPEG,JPG,jpeg,jpg,png,gif|required|max:10000',
                'adhar_dob'                     => 'required',
                'address_line_one_registered'   => 'required',
                // 'address_line_two_registered'   => 'required',
                'pincode_registered'            => 'required',
                'country_registered'            => 'required',
                'state_registered'              => 'required',
                'district_registered'           => 'required',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator);
            }
        }
        if ($roleId == 3) {
            $validator = Validator::make($request->all(), [
                'adhar_card_no'         => 'required|digits:12',
                'adhar_name'            => 'required',
                'adhar_card_front_file' => 'required|mimes:JFIF,jfif,JPEG,JPG,jpeg,jpg,png,gif|required|max:10000',
                'adhar_card_back_file'  => 'required|mimes:JFIF,jfif,JPEG,JPG,jpeg,jpg,png,gif|required|max:10000',
                'adhar_dob'          => 'required',
                'pancard_name'       => 'required',
                'pancard_no'         => 'required|regex:/^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$/',
                'pancard_file'       => 'required|mimes:JFIF,jfif,JPEG,JPG,jpeg,jpg,png,gif|required|max:10000',
                'pancard_dob'        => 'required',
                'brn_no'             => 'required|digits:14',
                'brn_name'           => 'required',
                'brn_file'           => 'required|mimes:JFIF,jfif,JPEG,JPG,jpeg,jpg,png,gif|required|max:10000',
                // 'address_line_one_office' => 'required',
                // 'address_line_two_office' => 'required',
                // 'pincode_office'          => 'required',
                // 'country_office'          => 'required',
                // 'state_office'            => 'required',
                // 'district_office'         => 'required',
                'address_line_one_registered' => 'required',
                // 'address_line_two_registered' => 'required',
                'pincode_registered'          => 'required',
                'country_registered'          => 'required',
                'state_registered'            => 'required',
                'district_registered'         => 'required',
            ]);

            if ($validator->fails()) {
                if ($validator->fails()) {
                    return redirect()->back()->withInput()->withErrors($validator->messages()->first());
                }
            }
        }


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


        // Create Folder for Document Pan Card
        if ($roleId == 3) {
            $pancard_file = $request->file('pancard_file');
            $folder = public_path('images/document/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $pan_card_image = date('YmdHis') . "pancard." . $pancard_file->getClientOriginalExtension();
            $ab = $pancard_file->move($folder, $pan_card_image);

            $pan_card_image_name = $pan_card_image;
            $pan_card_user_image = 'images/document/'.$user->id.'/'.$pan_card_image_name;


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
        }

        $latlogs = Pincode::select('lat', 'log')->where(['pin_code'=> $request->pincode_registered ])->first();
        $updateDocument = [
            'user_id'            => $user->id,
            'adhar_card_no'      => $request->adhar_card_no,
            'adhar_name'         => $request->adhar_name,
            'adhar_card_front_file'    => $adhar_card_user_image,
            'adhar_card_back_file'     => $adhar_card_user_back_image,
            'adhar_dob'          => $request->adhar_dob,

        ];
        // Document Array for SHG

        if ($roleId == 3) {
            $updateDocument = [
                'user_id'            => $user->id,
                'adhar_card_no'      => $request->adhar_card_no,
                'adhar_name'         => $request->adhar_name,
                'adhar_card_front_file'    => $adhar_card_user_image,
                'adhar_card_back_file'     => $adhar_card_user_back_image,
                'adhar_dob'          => $request->adhar_dob,
                'pancard_name'       => $request->pancard_name,
                'pancard_no'         => $request->pancard_no,
                'pancard_file'       => $pan_card_user_image,
                'pancard_dob'        => $request->pancard_dob,
                'brn_no'             => $request->brn_no,
                'brn_name'           => $request->brn_name,
                'brn_file'           => $brn_card_user_image,

            ];
        }




        $addDocument = Documents::create($updateDocument);

        // Office Address Array for SHG

        if ($roleId == 3) {
            $shgofficeaddress = [
                'user_id'          => $user->id,
                'user_role_id'     => $roleId,
                'address_line_one' => $request->address_line_one_office,
                'address_line_two' => $request->address_line_two_office,
                'pincode'          => $request->pincode_office,
                'country'          => $request->country_office,
                'state'            => $request->state_office,
                'district'         => $request->district_office,
                'address_type'     => 'office'
            ];
            //$addshgofficeaddress = Address::create($shgofficeaddress);
        }

        // Registered Address Array for SHG
        $shgregsiteraddress = [
            'user_id'          => $user->id,
            'user_role_id'     => $roleId,
            'address_line_one' => $request->address_line_one_registered,
            'address_line_two' => $request->address_line_two_registered,
            'pincode'          => $request->pincode_registered,
            'country'          => $request->country_registered,
            'state'            => $request->state_registered,
            'district'         => $request->district_registered,
            'address_type'     => 'registered'
        ];
        $addshgregsiteraddress = Address::create($shgregsiteraddress);

        $checkCondition = $addshgregsiteraddress && $addDocument;
        if ($roleId == 3) {
            $checkCondition = ($addshgregsiteraddress && $addDocument);
        }
        if ($checkCondition) {
            $addLocation = Location::create([
                'user_id' => $user->id,
                'lat' => $latlogs->lat,
                'log' => $latlogs->log,
            ]);
            $userDocumentUpdate = User::where(['id' => $user->id])->update(['is_document_added' => 1, 'is_document_verified' => 1]);
            $useraddressUpdate = User::where(['id' => $user->id])->update(['is_address_added' => 1]);

            // Update role id
            $updaterole     = User::where('id', $user->id)->update(['role_id' => $user->role_id]);
            $queryStatus    = "Successfuly document uploaded and adress added.";
            $statusCode     = 200;
            $status         = true;
            $response       = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );


            $followuplink = session()->get('url.intended');
            if ($followuplink == "") {
                return redirect('/');
            } else {
                return redirect($followuplink);
            }


            //return redirect('/');
        }
    }


    public function updatedoc($type)
    {
        return view('frontend.user.udpatedoc', ['type'=>$type]);
    }
    public function updatedoc_update(Request $request, $type)
    {
        //echo $type;
        $user = Auth::user();
        if ($type == 'aadhar') {
            $rules = [
                'adhar_card_no'      => 'required',
                'adhar_name'         => 'required',
                'adhar_card_front_file'    => 'required|mimes:JPEG,JPG,jpeg,jpg,png,gif|required|max:10000',
                'adhar_card_back_file'    => 'required|mimes:JPEG,JPG,jpeg,jpg,png,gif|required|max:10000',
                'adhar_dob'          => 'required',
            ];
        }
        if ($type == 'pan') {
            $rules = [
                'pancard_name'       => 'required',
                'pancard_no'         => 'required',
                'pancard_file'       => 'required|mimes:JPEG,JPG,jpeg,jpg,png,gif|required|max:10000',
                'pancard_dob'        => 'required',
            ];
        }
        if ($type == 'brn') {
            $rules = [
                'brn_no'             => 'required',
                'brn_name'           => 'required',
                'brn_file'           => 'required|mimes:JPEG,JPG,jpeg,jpg,png,gif|required|max:10000',
            ];
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
            ->withInput()
            ->withErrors($validator->messages()->first());
        }

        if ($type == 'aadhar') {
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
            ];

            $updateDocument = Documents::where('user_id', $user->id)->update($updateDocument);

            if ($updateDocument) {
                return redirect('/profile')->with('message', 'Aadhar Card Updated ');
            }
        }

        if ($type == 'pan') {
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
            ];

            $updateDocument = Documents::where('user_id', $user->id)->update($pandata);

            if ($updateDocument) {
                return redirect('/profile')->with('message', 'PAN Card Updated ');
            }
        }
        if ($type == 'brn') {
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
            ];

            $updateDocument = Documents::where('user_id', $user->id)->update($updatebrn);

            if ($updateDocument) {
                return redirect('/profile')->with('message', 'BRN Updated ');
            }
        }
    }


    public function get_template(Request $request)
    {
        $language = $request->session()->get('weblangauge');
        $templatename = 'name_en  as name';
        if ($language == 'kn') {
            $templatename = 'name_kn  as name';
        }
        $template = ProductTemplate::where(['category_id' => $request->categoryId, 'subcategory_id' => $request->subcategoryId, 'material_id' => $request->materialId ])
        ->select($templatename, 'id', 'description_en', 'description_kn', 'length', 'width', 'height', 'weight', 'volume')->get();

        $queryStatus    = "No template found!";
        $statusCode     = 400;
        $status         = false;

        $response   = array( 'status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

        if (count($template) > 0) {
            $queryStatus    = "All Template!";
            $statusCode     = 200;
            $status         = true;

            $response   = array( 'status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => ['template' => $template] );
        }


        return response()->json($response, 201);
    }

    public function addproduct(Request $request)
    {
        $language = $request->session()->get('weblangauge');
        $catname = 'name_en  as name';

        if ($language == 'kn') {
            $catname = 'name_kn  as name';
        }
        $categoryData = Category::select('id', $catname)->where(['parent_id'=> 0, 'is_active'=>1])->get();
        return view('frontend.user.addproduct', ['categoryData'=>$categoryData]);
    }


    public function addproduct_step_2(Request $request)
    {

        //Set Validation on Product with steps
        //dd($request->all());


        $validator = Validator::make($request->all(), [
            'categoryId'    => 'required',
            'subcategoryId' => 'required',
            'material_id'   => 'required',
            'tempalte_id'   =>'required',
            'price' => 'required',
            'qty' => 'required',
            'localname_en' => 'required',
            'localname_kn' => 'required'



        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }


        $dataset_1 = $request->all();
        session()->put('dataset_1', $dataset_1);
        $language = $request->session()->get('weblangauge');
        $templatename = 'name_en  as name';
        if ($language == 'kn') {
            $templatename = 'name_kn  as name';
        }
        $template = ProductTemplate::where(['id'=>$request->tempalte_id])
        ->select($templatename, 'id', 'description_en', 'description_kn', 'length', 'width', 'height', 'weight', 'volume')->first();



        return view('frontend.user.addproduct_2', ['dataset_1'=>$dataset_1,'template' => $template]);
    }

    public function addprpduct_final(Request $request)
    {
        //dd($request->all());

        $validator = Validator::make($request->all(), [
            'image_1'    => 'required',
            'image_2'    => 'required',
            'image_3'    => 'required',
            'image_4'    => 'required',
            'image_5'    => 'required',
        ]);



        // if ($validator->fails()) {
        //     return redirect()->back()->withErrors($validator)->withInput();
        // }

        /** Upload Images */
        $user = Auth::user();

        $image_file_1 = $request->file('image_1');
        $folder = public_path('images/product/' . $user->id . '/');

        if (!Storage::exists($folder)) {
            Storage::makeDirectory($folder, 0775, true, true);
        }

        $image_file_1_image = date('YmdHis') . rand(111, 9999). "productimage1." . $image_file_1->getClientOriginalExtension();
        $aa = $image_file_1->move($folder, $image_file_1_image);

        $image_file_1_image_name = $image_file_1_image;
        $image_file_1_image = 'images/product/'.$user->id.'/'.$image_file_1_image_name;

        $dataset_2['image_1'] = $image_file_1_image;

        /** Upload Image 2 */

        if ($request->file('image_2')) {
            $image_file_2 = $request->file('image_2');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_2_image = date('YmdHis') . rand(111, 9999). "productimage2." . $image_file_2->getClientOriginalExtension();
            $aa = $image_file_2->move($folder, $image_file_2_image);

            $image_file_2_image_name = $image_file_2_image;
            $image_file_2_image = 'images/product/'.$user->id.'/'.$image_file_2_image_name;

            $dataset_2['image_2'] = $image_file_2_image;
        }


        /** Upload Image 3 */

        if ($request->file('image_3')) {
            $image_file_3 = $request->file('image_3');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_3_image = date('YmdHis') . rand(111, 9999). "productimage3." . $image_file_3->getClientOriginalExtension();
            $aa = $image_file_3->move($folder, $image_file_3_image);

            $image_file_3_image_name = $image_file_3_image;
            $image_file_3_image = 'images/product/'.$user->id.'/'.$image_file_3_image_name;

            $dataset_2['image_3'] = $image_file_3_image;
        }


        /** Upload Image 4 */

        if ($request->file('image_4')) {
            $image_file_4 = $request->file('image_4');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_4_image = date('YmdHis') . rand(111, 9999). "productimage4." . $image_file_4->getClientOriginalExtension();
            $aa = $image_file_4->move($folder, $image_file_4_image);

            $image_file_4_image_name = $image_file_4_image;
            $image_file_4_image = 'images/product/'.$user->id.'/'.$image_file_4_image_name;

            $dataset_2['image_4'] = $image_file_4_image;
        }

        /** Upload Image 5 */

        if ($request->file('image_5')) {
            $image_file_5 = $request->file('image_5');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_5_image = date('YmdHis') . rand(111, 9999)."productimage5." . $image_file_5->getClientOriginalExtension();
            $aa = $image_file_5->move($folder, $image_file_5_image);

            $image_file_5_image_name = $image_file_5_image;
            $image_file_5_image = 'images/product/'.$user->id.'/'.$image_file_5_image_name;

            $dataset_2['image_5'] = $image_file_5_image;
        }







        $dataset_2['req'] =  $request->except(['image_1', 'image_2', 'image_3','image_4','image_5']);


        // session()->put('dataset_2', $dataset_2);
        $dataset_2= json_encode($dataset_2);

        $dataset_1 = $request->session()->get('dataset_1');
        $language = $request->session()->get('weblangauge');



        $templatename = 'name_en  as name';
        if ($language == 'kn') {
            $templatename = 'name_kn  as name';
        }
        $template = ProductTemplate::where(['id'=>$dataset_1['tempalte_id']])
        ->select($templatename, 'id', 'description_en', 'description_kn', 'length', 'width', 'height', 'weight', 'volume')->first();
        return view('frontend.user.addproduct_final', ['template' => $template, 'dataset_2'=>$dataset_2]);
    }


    public function addproduct_to_db(Request $request)
    {
        $dataset_1 = $request->session()->get('dataset_1');

        // $dataset_2 = $request->session()->get('dataset_2');
        $dataset_2 = json_decode($request->dataset_2, true);


        $input = $request->all();

        //dd($dataset_2);
        $input['categoryId'] = $dataset_1['categoryId'];
        $input['subcategoryId'] = $dataset_1['subcategoryId'];
        $input['material_id'] = $dataset_1['material_id'];
        $input['template_id'] = $dataset_1['tempalte_id'];
        $input['price'] = $dataset_1['price'];
        $input['qty'] = $dataset_1['qty'];
        $input['localname_en'] = $dataset_1['localname_en'];
        $input['localname_kn'] = $dataset_1['localname_kn'];


        if (isset($dataset_2['image_1'])) {
            $input['image_1'] = $dataset_2['image_1'];
        }
        if (isset($dataset_2['image_2'])) {
            $input['image_2'] = $dataset_2['image_2'];
        }
        if (isset($dataset_2['image_3'])) {
            $input['image_3'] = $dataset_2['image_3'];
        }
        if (isset($dataset_2['image_4'])) {
            $input['image_4'] = $dataset_2['image_4'];
        }
        if (isset($dataset_2['image_5'])) {
            $input['image_5'] = $dataset_2['image_5'];
        }

        if (isset($dataset_2['req']['length'])) {
            $input['length'] = $dataset_2['req']['length'];
            $input['length_unit'] = $dataset_2['req']['length_unit'];
        }
        if (isset($dataset_2['req']['width'])) {
            $input['width'] = $dataset_2['req']['width'];
            $input['width_unit'] = $dataset_2['req']['width_unit'];
        }
        if (isset($dataset_2['req']['height'])) {
            $input['height'] = $dataset_2['req']['height'];
            $input['height_unit'] = $dataset_2['req']['height_unit'];
        }
        if (isset($dataset_2['req']['vol'])) {
            $input['vol'] = $dataset_2['req']['vol'];
            $input['vol_unit'] = $dataset_2['req']['vol_unit'];
        }
        if (isset($dataset_2['req']['weight'])) {
            $input['weight'] = $dataset_2['req']['weight'];
            $input['weight_unit'] = $dataset_2['req']['weight_unit'];
        }

        if (isset($dataset_2['req']['video_url'])) {
            $input['video_url'] = $dataset_2['req']['video_url'];
        }

        $user = Auth::user();
        $input['user_id'] = $user->id;
        $product = ProductMaster::where(['user_id' => $user->id, 'is_draft' => 1])->first();


        if ($product && ($input['submit'] == 'draft')) {
            return redirect()->back()->withErrors('You have already product in your draft, Please add or remove from draft.');
        } elseif ($input['submit'] == 'draft') {
            $input['is_draft'] = 1;
        } else {
            $input['is_draft'] = 0;
        }

        $addProduct = ProductMaster::create($input);
        if ($input['submit'] == 'draft') {
            return redirect('/draft');
        }
        return redirect('/profile/home');
    }


    public function editproduct(Request $request, $id)
    {
        $id = decrypt($id);
        //check id with user
        $productData  = ProductMaster::where(['id'=>$id, 'user_id' => Auth::user()->id])->first();

        if ($productData) {
            $language = $request->session()->get('weblangauge');
            $catname = 'name_en  as name';
            $material_name = 'name_en  as name';
            $templatename = 'name_en as name';
            if ($language == 'kn') {
                $catname = 'name_kn  as name';
                $material_name = 'name_kn  as name';
                $templatename = 'name_kn as name';
            }
            $productData  = ProductMaster::where('id', $id)->first();
            $categoryData = Category::select('id', $catname)->where('parent_id', 0)->get();
            $subcategoryData = Category::select('id', $catname)->where('parent_id', $productData->categoryId)->get();
            $materials = Material::where('subcategory_id', $productData->subcategoryId)->select($material_name, 'id')->get();


            $template = ProductTemplate::where(['category_id' => $productData->categoryId, 'subcategory_id' => $productData->subcategoryId, 'material_id' => $productData->material_id ])
                ->select($templatename, 'id', 'description_en', 'description_kn', 'length', 'width', 'height', 'weight', 'volume')->get();

            //dd($template);

            return view('frontend.user.editproduct', ['categoryData'=>$categoryData,'productData'=>$productData,'subcategoryData'=>$subcategoryData,'materials'=>$materials,'template'=>$template]);
        } else {
            abort(404);
        }
    }


    public function editproduct_step_2(Request $request, $id)
    {
        $id = decrypt($id);
        //Set Validation on Product with steps
        //dd($request->all());
        $productData  = ProductMaster::where(['id'=>$id, 'user_id' => Auth::user()->id])->first();

        if ($productData) {
            $validator = Validator::make($request->all(), [
            'categoryId'    => 'required',
            'subcategoryId' => 'required',
            'material_id'   => 'required',
            'tempalte_id'   =>'required',
            'price' => 'required',
            'qty' => 'required',
            'localname_en' => 'required',
            'localname_kn' => 'required'



        ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }


            $dataset_1 = $request->all();
            session()->put('dataset_1', $dataset_1);
            $language = $request->session()->get('weblangauge');
            $templatename = 'name_en  as name';
            if ($language == 'kn') {
                $templatename = 'name_kn  as name';
            }
            $template = ProductTemplate::where(['id'=>$request->tempalte_id])
        ->select($templatename, 'id', 'description_en', 'description_kn', 'length', 'width', 'height', 'weight', 'volume')->first();
            $productData  = ProductMaster::where('id', $id)->first();


            return view('frontend.user.editproduct_2', ['dataset_1'=>$dataset_1,'template' => $template,'productData'=>$productData]);
        } else {
            abort(404);
        }
    }

    public function editprpduct_final(Request $request, $id)
    {
        $id = decrypt($id);
        //dd($request->all());
        $productData  = ProductMaster::where(['id'=>$id, 'user_id' => Auth::user()->id])->first();
        if ($productData) {
            $productData  = ProductMaster::where('id', $id)->first();

            /** Upload Images */
            $user = Auth::user();
            if ($request->file('image_1')) {
                $image_file_1 = $request->file('image_1');
                $folder = public_path('images/product/' . $user->id . '/');

                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }
                $image_file_1_image = date('YmdHis') . rand(111, 9999). "productimage1." . $image_file_1->getClientOriginalExtension();
                $aa = $image_file_1->move($folder, $image_file_1_image);

                $image_file_1_image_name = $image_file_1_image;
                $image_file_1_image = 'images/product/'.$user->id.'/'.$image_file_1_image_name;

                $dataset_2['image_1'] = $image_file_1_image;
            }
            /** Upload Image 2 */
            if ($request->file('image_2')) {
                $image_file_2 = $request->file('image_2');
                $folder = public_path('images/product/' . $user->id . '/');
                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }
                $image_file_2_image = date('YmdHis') . rand(111, 9999). "productimage2." . $image_file_2->getClientOriginalExtension();
                $aa = $image_file_2->move($folder, $image_file_2_image);
                $image_file_2_image_name = $image_file_2_image;
                $image_file_2_image = 'images/product/'.$user->id.'/'.$image_file_2_image_name;
                $dataset_2['image_2'] = $image_file_2_image;
            }


            /** Upload Image 3 */

            if ($request->file('image_3')) {
                $image_file_3 = $request->file('image_3');
                $folder = public_path('images/product/' . $user->id . '/');

                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }

                $image_file_3_image = date('YmdHis') . rand(111, 9999). "productimage3." . $image_file_3->getClientOriginalExtension();
                $aa = $image_file_3->move($folder, $image_file_3_image);

                $image_file_3_image_name = $image_file_3_image;
                $image_file_3_image = 'images/product/'.$user->id.'/'.$image_file_3_image_name;

                $dataset_2['image_3'] = $image_file_3_image;
            }


            /** Upload Image 4 */

            if ($request->file('image_4')) {
                $image_file_4 = $request->file('image_4');
                $folder = public_path('images/product/' . $user->id . '/');

                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }

                $image_file_4_image = date('YmdHis') . rand(111, 9999). "productimage4." . $image_file_4->getClientOriginalExtension();
                $aa = $image_file_4->move($folder, $image_file_4_image);

                $image_file_4_image_name = $image_file_4_image;
                $image_file_4_image = 'images/product/'.$user->id.'/'.$image_file_4_image_name;

                $dataset_2['image_4'] = $image_file_4_image;
            }

            /** Upload Image 5 */

            if ($request->file('image_5')) {
                $image_file_5 = $request->file('image_5');
                $folder = public_path('images/product/' . $user->id . '/');

                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }

                $image_file_5_image = date('YmdHis') . rand(111, 9999)."productimage5." . $image_file_5->getClientOriginalExtension();
                $aa = $image_file_5->move($folder, $image_file_5_image);

                $image_file_5_image_name = $image_file_5_image;
                $image_file_5_image = 'images/product/'.$user->id.'/'.$image_file_5_image_name;

                $dataset_2['image_5'] = $image_file_5_image;
            }




            $dataset_2['req'] =  $request->except(['image_1', 'image_2', 'image_3','image_4','image_5']);




            //$dataset_2 = $request->all();
            // dd()

            if ($request->img1 ||$request->image_1) {
                if (!$request->file('image_1')) {
                    $dataset_2['image_1'] = $request->img1;
                }
            } else {
                $dataset_2['image_1'] = null;
            }

            if ($request->img2 || $request->image_2) {
                if (!$request->file('image_2')) {
                    $dataset_2['image_2'] = $request->img2;
                }
            } else {
                $dataset_2['image_2'] = null;
            }

            if ($request->img3 || $request->image_3) {
                if (!$request->file('image_3')) {
                    $dataset_2['image_3'] = $request->img3;
                }
            } else {
                $dataset_2['image_3'] = null;
            }

            if ($request->img4 || $request->image_4) {
                if (!$request->file('image_4')) {
                    $dataset_2['image_4'] = $request->img4;
                }
            } else {
                $dataset_2['image_4'] = null;
            }

            if ($request->img5 || $request->image_5) {
                if (!$request->file('image_5')) {
                    $dataset_2['image_5'] = $request->img5;
                }
            } else {
                $dataset_2['image_5'] = null;
            }

            


            $dataset_2  =  json_encode($dataset_2);
           
            //dd($dataset_2 = $request->session()->get('dataset_2'));
            $dataset_1 = $request->session()->get('dataset_1');
            $language = $request->session()->get('weblangauge');
            $templatename = 'name_en  as name';
            if ($language == 'kn') {
                $templatename = 'name_kn  as name';
            }

            $product = ProductMaster::where(['user_id' => $user->id, 'is_draft' => 1])->first();
            
            
            
            
            if ($product) {
                if ($product->id == $id) {
                    $flag = 1;
                } else {
                    $flag = 0;
                }
            } else {
                $flag = 1;
            }

           


            $template = ProductTemplate::where(['id'=>$dataset_1['tempalte_id']])->select($templatename, 'id', 'description_en', 'description_kn', 'length', 'width', 'height', 'weight', 'volume')->first();
            return view('frontend.user.editproduct_final', ['template' => $template, 'productData'=> $productData,'dataset_2'=>$dataset_2,'flag'=>$flag]);
        } else {
            abort(404);
        }
    }


    public function editproduct_to_db(Request $request, $id)
    {
        $id = decrypt($id);
        $productData  = ProductMaster::where(['id'=>$id, 'user_id' => Auth::user()->id])->first();
        if ($productData) {
            $dataset_1 = $request->session()->get('dataset_1');

            
            // $dataset_2 = $request->session()->get('dataset_2');
            $input = $request->all();

            $dataset_2 = json_decode($request->dataset_2, true);
            // dd($dataset_2);

            $input['categoryId'] = $dataset_1['categoryId'];
            $input['subcategoryId'] = $dataset_1['subcategoryId'];
            $input['material_id'] = $dataset_1['material_id'];
            $input['template_id'] = $dataset_1['tempalte_id'];
            $input['price'] = $dataset_1['price'];
            $input['qty'] = $dataset_1['qty'];
            $input['localname_en'] = $dataset_1['localname_en'];
            $input['localname_kn'] = $dataset_1['localname_kn'];



            //if (isset($dataset_2['image_1'])) {
            $input['image_1'] = $dataset_2['image_1'];
            //}
            // if (isset($dataset_2['image_2'])) {
            $input['image_2'] = $dataset_2['image_2'];
            //}
            //if (isset($dataset_2['image_3'])) {
            $input['image_3'] = $dataset_2['image_3'];
            // }
            //if (isset($dataset_2['image_4'])) {
            $input['image_4'] = $dataset_2['image_4'];
            //}
            //if (isset($dataset_2['image_5'])) {
            $input['image_5'] = $dataset_2['image_5'];
            //}



            if (isset($dataset_2['req']['length'])) {
                $input['length'] = $dataset_2['req']['length'];
                $input['length_unit'] = $dataset_2['req']['length_unit'];
            }
            if (isset($dataset_2['req']['width'])) {
                $input['width'] = $dataset_2['req']['width'];
                $input['width_unit'] = $dataset_2['req']['width_unit'];
            }
            if (isset($dataset_2['req']['height'])) {
                $input['height'] = $dataset_2['req']['height'];
                $input['height_unit'] = $dataset_2['req']['height_unit'];
            }
            if (isset($dataset_2['req']['vol'])) {
                $input['vol'] = $dataset_2['req']['vol'];
                $input['vol_unit'] = $dataset_2['req']['vol_unit'];
            }
            if (isset($dataset_2['req']['weight'])) {
                $input['weight'] = $dataset_2['req']['weight'];
                $input['weight_unit'] = $dataset_2['req']['weight_unit'];
            }
            //dd($input);

            if (isset($dataset_2['req']['video_url'])) {
                $input['video_url'] = $dataset_2['req']['video_url'];
            }
            ///dd($input);




            $user = Auth::user();
            $input['user_id'] = $user->id;


            $product = ProductMaster::where(['user_id' => $user->id, 'is_draft' => 1])->first();

           


            if ($input['submit'] == 'draft') {
                $input['is_draft'] = 1;
            } else {
                $input['is_draft'] = 0;
            }
            $submit_name = $input['submit'];
            unset($input['_token']);
            unset($input['submit']);
            unset($input['shgartisanId']);
            unset($input['dataset_2']);



            $updatedraftproduct = ProductMaster::where('id', $id)->update($input);
            if ($submit_name == 'draft') {
                return redirect('/draft');
            }
            return redirect('/profile/home');
        } else {
            abort(404);
        }
    }


    public function getshgartisanhome(Request $request)
    {
        $user = Auth::user();
        $language       = $request->session()->get('weblangauge');
        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($language == 'kn') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }

        $userdata = User::where('id', $user->id)->select('id', 'name', 'title', 'profileImage', 'email', 'mobile')->first();


        $categoryIds = ProductMaster::where(['user_id' => $user->id, 'is_active' => 1, 'is_draft' => 0])->distinct('categoryId')->select('categoryId')->get();
        // $categoryIds = ProductMaster::where(['user_id' => $request->artisanshgid, 'is_active' => 1, 'is_draft' => 0])->groupBy('categoryId')->select('categoryId')->get();

        $catArr = [];

        foreach ($categoryIds as $cat) {
            $catArr[] = $cat->categoryId;
        }

        $categoryDetail = Category::wherein('id', $catArr)->select('id', $catname, 'slug')->get();

        $allProduct = [];

        foreach ($categoryDetail as $key=> $cat) {
            $subcategoryId = Category::where('parent_id', $cat->id)->select('id', $catname, 'slug')->get();
            $sub = [];
            foreach ($subcategoryId as $subcat) {
                $products = ProductMaster::where('subcategoryId', $subcat->id)->where('is_active', 1)->where('is_draft', 0)->where('user_id', $user->id)->with('template:id,'.$templatename)->select($productname, 'price', 'id', 'image_1', 'template_id')->take(4)->get();

                if (count($products) > 0) {
                    $sub[] = [
                        'subCategoryId'     => $subcat->id,
                        'subCategoryName'   => $subcat->name,
                        'subCategoryslug' => $subcat->slug,
                        'products'          => $products
                    ];
                }
            }


            $allProduct[$key]['subCategories'] = $sub;

            $allProduct[$key]['categoryId'] = $cat->id;
            $allProduct[$key]['categoryName'] = $cat->name;
            $allProduct[$key]['categoryName'] = $cat->name;

            $allProduct[$key]['categoryslug'] = $cat->slug;
            //$allProduct[$key]['subCategoryslug'] = $subcategoryId->slug;


            //$allProduct[$key]['subCategoryId'] = $subcategoryId->id;
            //           $allProduct[$key]['subCategoryName'] = $subcategoryId->name;

            $allProduct[$key]['products'] = $products;
        }


        $data = ['user' => $userdata,'allProduct'=>$allProduct];
        return view('frontend.user.home', ['alldetail'=>$data]);
    }

    public function draft(Request $request)
    {
        $user = Auth::user();
        $language = $request->session()->get('weblangauge');

        $descriptionname = 'des_en  as description';
        $productname = 'localname_en as name';

        if ($language == 'kn') {
            $descriptionname = 'des_kn  as description';
            $productname = 'localname_kn  as name';
        }

        $draftproduct = ProductMaster::with('template')->where(['user_id' => $user->id, 'is_draft' => 1])->first();


        return view('frontend.user.draft', ['draftproduct'=>$draftproduct]);
    }

    public function changeprofileimage()
    {
        return view('frontend.user.changeimage');
    }

    public function updateprofileimage(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'profileimage' => 'required|max:30000|mimes:JPEG,JPG,jpg,jpeg,png,svg'
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' => 400 );
            $response['message'] = $validator->messages()->first();
            return redirect()->back()->withErrors($validator);
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


        return redirect('profile')->with('message', 'Profile Image Updated.');
    }


    public function changeaddress(Request $request)
    {
        Auth::user()->address_registerd;
        Auth::user()->address_personal;
        return view('frontend.user.changeaddress');
    }

    public function updateaddress(Request $request)
    {
        $messages = [
            'required' => 'The :attribute field is required.',
            'address_line_one.required' => 'Address is required.'
        ];

        $validator = Validator::make($request->all(), [
            'address_line_one'      => 'required',
            // 'address_line_two'      => 'required_without:address_line_one',
            'pincode'               => 'required',
            'country'               => 'required',
            'state'                 => 'required',
            'district'              => 'required',
            //'address_type'          => 'required|in:personal,registered,office'
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $requestData = $request->all();

        $requestData['user_id'] = Auth::user()->id;
        $requestData['user_role_id'] = Auth::user()->role_id;
        $updateusertable = User::where('id', Auth::user()->id)->update(['country_id' => $request->country, 'state_id' => $request->state, 'district'=>$request->district, 'city_id' => $request->district ]);



        if (Auth::user()->role_id != 1) {
            $latlogs = Pincode::select('lat', 'log')->where(['pin_code'=> $request->pincode ])->first();
            $addLocation = Location::create([
            'user_id' => Auth::user()->id,
            'lat' => $latlogs->lat,
            'log' => $latlogs->log,
            ]);
        }

        //$requestData['address_type'] = $request->address_type;

        $is_address_added = 1;
        unset($requestData['_token']);
        unset($requestData['shgartisanId']);


        $useraddressupdate = Address::where('id', $request->id)->update($requestData);


        $userUPdate = User::where(['id' => Auth::user()->id])->update(['is_address_added' => $is_address_added]);
        return redirect('profile');
    }
}
