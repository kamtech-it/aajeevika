<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Permission;
use App\RolePermission;
use App\Role;


class RolepermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        $rolePermission = Permission::where('status', 1)->get();
        $role = Role::where('id', $id)->first();

        $assignedpermission = RolePermission::where([ 'role_id' => $id, 'status' =>1 ])->select('permission_id')->get();

        $roleassignedpermission = [];
        
        foreach($assignedpermission as $value) {
            $roleassignedpermission[] = $value->permission_id;
        }

        // echo "<pre>"; print_r($roleassignedpermission); die("check");

        return view('rolepermission.index', ['rolePermission' => $rolePermission, 'role' => $role, 'assignedpermission' => $roleassignedpermission ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'permision' => 'required',
               

        ]);

     
        if ($validator->fails()) {
            return redirect('admin/rolepermission/'.$id)
                        ->withErrors($validator)
                        ->withInput();
        }
        
        $flight = RolePermission::where('role_id', $id)->delete();


        foreach($request->permision as $item) {
            $create = RolePermission::create([
                'role_id' => $id,
                'permission_id' => $item
            ]);   
        }

        return redirect('admin/rolepermission/'.$id);

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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
