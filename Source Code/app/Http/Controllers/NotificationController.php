<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use File;
use Mail;
use App\User;
use App\Role;
use App\Bulk;
use App\Bulkemail;
use App\Notification;
use Auth;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UploadTrait;
use App\RolePermission;
use App\Permission;
use Url;
class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->id = Auth::user()->id;
            $userPermission = RolePermission::where('user_id', Auth::user()->id)->get();
            $permArr = [];
            foreach ($userPermission as $key => $perm) {
                $permArr[] = $perm->permission_id;
            }
            $permission = Permission::wherein('id', $permArr)->pluck('url')->toArray();
            $permission[] =  '/admin';
            if (!in_array('/admin/notification', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }

    public function index()
    {
        //echo str_pad(15452, 4, "0", STR_PAD_LEFT);
        $notificationList = Notification::with('role')->orderBy('id', 'desc')->paginate(10);

        return view('notification.index', ['notificationList'=>$notificationList]);
    }

    public function create()
    {
        $roleList   = Role::wherein('id', [1,2,3,7,8,9])->get();
        return view('notification.add', ['roleList'=>$roleList]);
    }




    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'mimes:JPEG,JPG,jpeg,png,jpg,gif,svg|max:2048',
            'title'=>'required',
            'body'=>'required',
            'role_id' =>'required'
        ]);


        if ($validator->fails()) {
            return redirect('admin/addnotification')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $input = $request->all();
            $input['image'] = "";
            if ($files = $request->file('image')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/notification"), $profileImage);
                $input['image'] = "images/notification/".$profileImage;
            }

            $notification = Notification::create($input);
            //get fcm token and fire notification
            $message = $request->body;
            $userdData = User::where('role_id', $request->role_id)->select('devicetoken','language')->get();
            $notifyMsg1['title'] = $request->title;
            $notifyMsg1['message'] = $request->body;
            $notifyMsg1['image'] = url($input['image']);
            $notifyMsg1['type'] = 'custom';
           
            $devicetokenArr = [];
            foreach ($userdData as $value) {
                if ($value->devicetoken && ($value->devicetoken != 'NA')) {
                    //$devicetokenArr[] = $value->devicetoken;
                    if($value->language == $request->language){
                        $this->sendPushNotification(array($value->devicetoken),$notifyMsg1, '1');
                    }
                    
                }
            }
           
            $queryStatus = "Successful";
            

            return redirect('admin/notification')->with('message', $queryStatus);
        }
    }
    public function sendPushNotification(array $device_tokens,array $msg, $device_type)
	{
			//$FIREBASE_API_KEY="AAAAMRPYmqU:APA91bH91L6cHaEAJlboAuNPMC-B06qc1dS1RnDtZA7xFS165w1A25E8F-ZqeP0AHQjCGKZz5Cg3617KR-QuLMxVgMNUhnLejvJY1EVnwXU7C8jxD10DViSnaeCW9jOd4rY9p2j_zmFG";
			$FIREBASE_API_KEY="AAAAoztDo6Y:APA91bE5S04pwIRsw_IeQFBPVnZ79ugI076sRHYq8CKwb8DA3FNrMfLoLpN2SAATrpzQnn2tkL1sGAKUicp4bKaQ0PplnIZNLlW9AfL37PbL5hF9_y4fWOvp4MRmXM1fsdaNasVVpyD9";
			// prep the bundle
			$test['aps']['mutable-content']=1;
			if($device_type==1){
				$fields = array
					(
					'registration_ids' => $device_tokens,
					'data' => $msg,
					//'apns'=>$test,
					);	
			}elseif($device_type==2){
				$fields = array
					(
					'registration_ids' => $device_tokens,
					'notification' => $msg,
					//"category"=>'SECRET',
					"mutable_content"=>true,
					
					);
			}
			$headers = array
			(
			'Authorization: key=' . $FIREBASE_API_KEY,
			'Content-Type: application/json',
			//'apns'=>$test,
			);

			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers);
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true);
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($fields));
			$result[] = curl_exec($ch );
			$info = curl_getinfo($ch);
			curl_close( $ch );
			//print_r($result);
			return $result;
}
    public function bulkindex()
    {
        $bulklist = Bulk::with('role')->orderBy('id', 'desc')->paginate(10);
        return view('bulksms.index', ['bulklist'=>$bulklist]);
    }

    public function addbulk()
    {
        $roleList   = Role::wherein('id', [1,2,3])->get();
        return view('bulksms.add', ['roleList'=>$roleList]);
    }


    public function storebulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required',
            'role_id' =>'required'
        ]);


        if ($validator->fails()) {
            return redirect('admin/addbulk')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $mobile = array();
            foreach ($request->role_id as $role) {
                $input['role_id'] = $role;
                $input['message'] = $request->message;
                $category = Bulk::create($input);
            }
            $mobile = User::whereIn('users.role_id', $request->role_id)->select('mobile')->get();

            //$no = '8890203788, 8766068415';

            $noo = '';
            foreach($mobile as $item){
                $noo .= $item->mobile.",";
            }



            // foreach ($mobile as $item) {
                $this->sendotp($noo, $request->message);
            // }


            return redirect('admin/sendbulkmessage');







        }
    }


    
    /**
     * Email 
     */

    
    public function bulkemailindex() {
        $bulklist = Bulkemail::with('role')->orderBy('id', 'desc')->paginate(10);
        return view('bulkemail.index', ['bulklist'=>$bulklist]);
    } 
     
    public function addemailbulk() {
        $roleList   = Role::wherein('id', [1,2,3])->get();
        return view('bulkemail.add', ['roleList'=>$roleList]);
    }

    public function storeemailbulk(Request $request) {
        $validator = Validator::make($request->all(), [
            'message' => 'required',
            'role_id' =>'required'
        ]);


        if ($validator->fails()) {
            return redirect('admin/addnotification')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $mobile = array();
            foreach ($request->role_id as $role) {
                $input['role_id'] = $role;
                $input['message'] = $request->message;
                $category = Bulkemail::create($input);
            }
            $emails = User::whereIn('users.role_id', $request->role_id)->select('email')->get();

            //$no = '8890203788, 8766068415';

            $noo = '';
            foreach($mobile as $item){
                $noo .= $item->mobile.",";
            }


            $user = ['email' => 'anshuman.myteam11@gmail.com', 'name' => 'Anshuman'];
            // foreach ($mobile as $item) {
                Mail::send('emails.bulkemail', ['user' => $user], function ($m) use ($user) {
                    $m->from('undp@undp.com', 'UNDP');
        
                    $m->to('anshuman.myteam11@gmail.com', 'Anshuman')->subject('Your Reminder!');
                });
            // }


            return redirect('admin/sendbulkemail');
        }
    }



    public function sendotp($no, $otp)
    {
        $username="Mobile_1-KSLKAR";
        $password="kslkar@1234";
        $senderid="KSLKAR";
        $message = $otp;
        $messageUnicode="à¤®à¥‹à¤¬à¤¾à¤‡à¤²à¤¸à¥‡à¤µà¤¾à¤®à¥‡à¤‚à¤†à¤ªà¤•à¤¾à¤¸à¥à¤µà¤¾à¤—à¤¤à¤¹à¥ˆ "; //message content in unicode
        //$mobileno = $mobile; //if single sms need to be send use mobileno keyword
        $mobileNos = $no; //if bulk sms need to send use mobileNos as keyword and mobile number seperated by commas as value
        $deptSecureKey= "b36befee-c007-4749-b6da-d8a0d6ca5e41"; //departsecure key for encryption of message...
        $encryp_password=sha1(trim($password));


        $key=hash('sha512', trim($username).trim($senderid).trim($message).trim($deptSecureKey));

        $url = "https://msdgweb.mgov.gov.in/esms/sendsmsrequest";

        $data = array(
            "username"        => trim($username),
            "password"        => trim($encryp_password),
            "senderid"        => trim($senderid),
            "content"         => trim($message),
            "smsservicetype"  => "bulkmsg",
            "mobileno"        => trim($mobileNos),
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
        echo $result; //output from server displayed
        curl_close($post);
    }
}
