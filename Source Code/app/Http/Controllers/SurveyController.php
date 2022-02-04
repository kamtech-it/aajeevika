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
use App\Survey;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UploadTrait;
use Illuminate\Support\Facades\Input;
use Excel;

class SurveyController extends Controller
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

            if (!in_array('/admin/survey', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {

        $query = Survey::select('id','message', 'google_url', 'start_date','end_date','status')->orderBy('id', 'desc');
        if ($request->has('s')) {
            $searchString = trim($request->s);
            $categories =  $query->where(function ($q) use ($searchString) {
                $q->where('message', 'like', '%'.$searchString.'%');
                //$q->orWhere('users.name', 'like', '%'.$searchString.'%');
                //$q->orWhere('ticket_id', $searchString);
            });
        }
        $surveyData =  $query->paginate(10);

        return view('survey.index', ['surveyData'=>$surveyData]);
    }


    public function create()
    {
        return view('survey.add', []);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message'=>'required',
            'google_url'=>'required'
        ], ['google_url.required' => 'The url field is required.']);


        if ($validator->fails()) {
            return redirect('admin/addsurvey')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $input = $request->all();
            //$input['slug'] = Str::slug($request->name_en);
            //$input['admin_id'] = Auth::user()->id;
            $survey = Survey::create($input);
            $queryStatus = "Successful";
            return redirect('admin/survey')->with('message', $queryStatus);
        }
    }


    public function show($id)
    {
        $id = decrypt($id);
        $certificateData = ProductCertification::where('id', $id)->first();
        return view('survey.view', ['certificateData'=>$certificateData]);
    }

    public function edit($id)
    {
        $surveyDetail = Survey::where(['id' => $id])->first();
        return view('survey.edit', ['surveyDetail' => $surveyDetail]);
    }


    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message'=>'required',
            'google_url'=>'required'
        ], ['google_url.required' => 'The url field is required.']);


        if ($validator->fails()) {
            return redirect('admin/addsurvey')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $input = $request->all();
            $surveyUpdate = Survey::where('id', $id)->first();
            $survey = $surveyUpdate->update($input);
            $queryStatus = "Successful";
            return redirect('admin/survey')->with('message', $queryStatus);
        }
    }



    public function destroy($id)
    {
    }
}
