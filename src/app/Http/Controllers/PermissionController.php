<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Permission;
use App\RolePermission;


class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware(function ($request, $next) {
        //     $this->id = Auth::user()->id;
        //     $userPermission = RolePermission::where('user_id', Auth::user()->id)->get();
        //     $permArr = [];
        //     foreach ($userPermission as $key => $perm) {
        //         $permArr[] = $perm->permission_id;
        //     }
        //     $permission = Permission::wherein('id', $permArr)->pluck('url')->toArray();
        //     $permission[] =  '/admin';
        //     if (!in_array('/admin/notification', $permission)) {
        //         return redirect('admin');
        //     }
        //     return $next($request);
        // });
    }
    public function index()
    {
        $permissionList = Permission::paginate(10);


        return view('permission.index', [ 'permissionList' => $permissionList ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('permission.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permission_name' => 'required',
            'url'       => 'required'    

        ]);

     
        if ($validator->fails()) {
            return redirect('admin/addpermission')
                        ->withErrors($validator)
                        ->withInput();
        }

        $addPermission = Permission::create($request->all());

        if($addPermission) {
            return redirect('admin/permission');
        }else{
            return redirect('admin/addpermission')
            ->withErrors($validator)
            ->withInput(['msg' => 'Failed to add data.']);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $permisionData = Permission::where('id', $id)->first();

        return view('permission.edit', ['permission' => $permisionData]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'permission_name' => 'required',
            'url'       => 'required'    

        ]);

     
        if ($validator->fails()) {
            return redirect('admin/addpermission')
                        ->withErrors($validator)
                        ->withInput();
        }
        $input['permission_name'] = $request->permission_name;
        $input['url'] = $request->url;


        $update = Permission::where('id', $id)->update($input);
        return redirect('admin/permission');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, $status)
    {
        // echo "Id : ".$id."<br>";
        // echo "Status : ".$status."<br>";
        // die("check");

        $update = Permission::where('id', $id)->update(['status' => $status]);
        return redirect('admin/permission');
    }
}
