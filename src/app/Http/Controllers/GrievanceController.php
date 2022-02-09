<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use File;
use App\User;
use App\Category;
use App\Grievance;
use App\GrievanceIssueType;
use App\GrievanceMessage;
use App\Role;
use App\City;
use Auth;
use DB;
use App\RolePermission;
use App\Permission;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UploadTrait;
use Illuminate\Support\Facades\Input;
use Excel;

class GrievanceController extends Controller
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

            if (!in_array('/admin/grievance', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {

        //$query = Grievance::leftJoin('grievance_issue_types as git','git.id','grievances.issue_type_id')->leftJoin('users','users.id','grievances.user_id')->select('grievances.*','users.*','git.*','grievances.id as grievance_id','grievances.status as grievance_status')->orderBy('grievances.last_message_date', 'desc');
        $query = Grievance::leftJoin('grievance_issue_types as git','git.id','grievances.issue_type_id')->leftJoin('users','users.id','grievances.user_id')->select('grievances.*','users.*','git.*','grievances.id as grievance_id','grievances.status as grievance_status')->orderBy('grievances.id', 'desc');
        if ($request->has('s')) {
            $searchString = trim($request->s);
            $categories =  $query->where(function ($q) use ($searchString) {
                $q->where('mobile', 'like', '%'.$searchString.'%');
                $q->orWhere('users.name', 'like', '%'.$searchString.'%');
                $q->orWhere('ticket_id', $searchString);
            });
        }
        $grievanceData =  $query->paginate(10);

        return view('grievance.index', ['grievanceData'=>$grievanceData]);
    }


    public function create()
    {
    }


    public function grievanceReply(Request $request)
    {
        $model = new GrievanceMessage();
        $model->grievance_id = $request->grievance_id;
        $model->message = $request->message;
        $model->type = 'by_admin';
       // $model->last_message_date = date('y-m-d H:i:s');
        $model->save();
        if($model->save()){
            $messageDate= date('y-m-d H:i:s');
                Grievance::where('id',$request->grievance_id)->update(['last_message_date' => $messageDate]);

                //send notification 
                $grievanceData = Grievance::with('getUser')->where('id',$request->grievance_id)->first();
                $notifyMsg1['id'] = $grievanceData->id;
                $notifyMsg1['ticket_id_d'] = $grievanceData->ticket_id;
                $notifyMsg1['title'] = $grievanceData->ticket_id;
                $notifyMsg1['type'] = 'admin_grievance';
                $notifyMsg1['message']=$request->message;
                    if($grievanceData->getUser->devicetoken){
                    $this->sendPushNotification(array($grievanceData->getUser->devicetoken),$notifyMsg1, '1');
                    }
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Message save.'
                ]
            );
        }
        return false;
    }
//notification function
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

    public function show($id)
    {
        $grievance_id = decrypt($id);
        //$grievance_row= Grievance::first(['ticket_id','status','created_at']);
        $grievance =  Grievance::find($grievance_id);
        //$grievance->getMessage;
        //$grievance->getIssue;
        //echo "<pre>"; print_r($grievance_row); die();
        //$grievanceData = GrievanceMessage::where('grievance_id', $grievance_id)->get();

        return view('grievance.view', ['grievanceMessages'=>$grievance]);
    }

    public function closeTicket($id, $status)
    {
        $id = decrypt($id);
        $ticketUpdate = Grievance::where('id', $id)->first();
        if ($ticketUpdate) {
            $input['status'] = $status;
            $updated = $ticketUpdate->update($input);          
            return redirect()->back()->withErrors(['msg', 'The Message']);
        }
    }


    public function edit($id)
    {
    }


    public function update(Request $request, $id)
    {
    }



    public function destroy($id)
    {
    }
}
