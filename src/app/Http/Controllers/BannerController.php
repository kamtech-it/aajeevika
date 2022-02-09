<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use File;
use App\User;
use App\Category;
use App\Role;
use Auth;
use DB;
use App\Banner;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UploadTrait;
use App\RolePermission;
use App\Permission;

class BannerController extends Controller
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

            if (!in_array('/admin/banner', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $bannerData = DB::table('banners')->orderBy('id', 'DESC')->get();
        
        return view('banner.index', ['bannerData'=>$bannerData]);
    }

  
    public function create()
    {
        return view('banner.add');
    }

    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:JPEG,JPG,jpeg,png,jpg,gif,svg|max:2048',
            'action'=>'required|url',
        ]);

        if ($validator->fails()) {
            return redirect('admin/addbanner')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $input = $request->all();
            if ($files = $request->file('image')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/banner"), $profileImage);
                $input['image'] = "images/banner/".$profileImage;
            }
          
            $category = Banner::create($input);
            $queryStatus = "Successful";
            return redirect('admin/banner')->with('message', $queryStatus);
        }
    }

   
    public function show($id)
    {
    }

   
    public function edit($id)
    {
        $id = decrypt($id);
        $bannerDetail = Banner::where(['id' => $id])->first();
        return view('banner.edit', ['bannerDetail'=>$bannerDetail, 'id'=>$id ]);
    }

    
    public function update(Request $request, $id)
    {
        $id = decrypt($id);
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'image' => 'mimes:jpeg,JPEG,JPG,png,jpg,gif,svg|max:2048',
            'action'=> 'url',
        ]);


        if ($validator->fails()) {
            return redirect('admin/editbanner/'.$id)->withErrors($validator)->withInput();
        } else {
            $input = $request->all();
            if ($files = $request->file('image')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/banner"), $profileImage);
                $input['image'] = "images/banner/".$profileImage;
            }
            $bannerUpdate  = Banner::where('id', $id)->first();
            if ($bannerUpdate) {
                $cats = $bannerUpdate->update($input);
                return redirect('admin/banner');
            }
        }
    }

    
    public function destroy($id, $status)
    {
        $id = decrypt($id);
        $input['status'] = $status;
        $bannerUpdate  = Banner::where('id', $id)->first();
        if ($bannerUpdate) {
            $cats = $bannerUpdate->update($input);
            return redirect('admin/banner');
        }
    }
    public function del($id)
    {
        $id = decrypt($id);
        $bannerUpdate  = Banner::where('id', $id)->delete();
        return redirect('admin/banner');
    }
}
