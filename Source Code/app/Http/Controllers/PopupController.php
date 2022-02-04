<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use File;
use App\User;
use App\Category;
use App\Role;
use App\PopupManager;
use Auth;
use DB;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UploadTrait;
use App\RolePermission;
use App\Permission;

class PopupController extends Controller
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
            if (!in_array('/admin/popupmanager', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $popupList = PopupManager::orderBy('id','DESC')->paginate(10);
        return view('popup.index', ['popupList'=>$popupList]);
    }

    public function create()
    {
        $roleList   = Role::wherein('id', [1,2,3])->get();
        return view('popup.add', ['roleList'=>$roleList]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'background_image' => 'required|mimes:jpeg,JPEG,JPG,png,jpg,gif,svg|max:2048',
            'title'=>'required',
            //'description'=>'required',
            'role_id' => 'required',
            'language' => 'required',
            //'action' =>'required'
        ]);

        if ($validator->fails()) {
            return redirect('admin/addpopup')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $input = $request->all();
            if ($files = $request->file('background_image')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/popup"), $profileImage);
                $input['background_image'] = "images/popup/".$profileImage;
            }

            foreach ($request->role_id as $role) {
                $input['role_id'] = $role;
                $input['admin_id'] = Auth::user()->id;
                $disablepopup = PopupManager::where(['role_id' => $role, 'language' => $request->language ])->update(['status' => 0]);
                $category = PopupManager::create($input);
            }

            $queryStatus = "Successful";
            return redirect('admin/popupmanager')->with('message', $queryStatus);
        }
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $id = decrypt($id);
        $roleList   = Role::wherein('id', [1,2,3])->get();
        $popupDetail= PopupManager::where(['id' => $id])->first();

        return view('popup.edit', ['roleList'=>$roleList,'popupDetail'=>$popupDetail,'id'=>$id]);
    }

    public function update(Request $request, $id)
    {
        $id = decrypt($id);
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'image' => 'mimes:jpeg,png,jpg,JPEG,JPGgif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return redirect()->back();
        } else {
            $input = $request->all();

            if ($files = $request->file('background_image')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/popup"), $profileImage);
                $input['background_image'] = "images/popup/".$profileImage;
            }
            $input['admin_id'] = Auth::user()->id;

            $popupdateUpdate  = PopupManager::where('id', $id)->first();
            if ($popupdateUpdate) {
                $cats = $popupdateUpdate->update($input);

                return redirect('admin/popupmanager');
            }
        }
    }

    public function destroy($id)
    {
        $id = decrypt($id);
        $popupdateUpdate  = PopupManager::where('id', $id)->first();

       
        $role = $popupdateUpdate->role_id;
        $language = $popupdateUpdate->language;

        $checkpopup = PopupManager::where(['role_id' => $role, 'language' => $language])->first();

        // if (!$checkpopup) {
            if ($popupdateUpdate->status == 0) {
                $input['status'] = 1;
            } else {
                $input['status'] = 0;
            }
    
    
            if ($popupdateUpdate) {
                $cats = $popupdateUpdate->update($input);
    
                return redirect('admin/popupmanager');
            }
        // } else {
        //     return redirect('admin/popupmanager');
        // }
    }

    public function deletepopupfinal($id)
    {
        $id = decrypt($id);
        $popupdateUpdate  = PopupManager::where('id', $id)->delete();
        return redirect('admin/popupmanager');
    }
}
