<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Validator;
use Validator;
use App\Role;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Excel;
use App\RolePermission;
use App\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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

            if (!in_array('/admin/role', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        //$allRole = Role::all();


        $query = DB::table('roles');

        if ($request->has('s')) {
            $query->where('role_name', 'LIKE', '%' . Input::get('s') . '%');
        }

        $allRole = $query->get();







        return view('role.index',['roleList' =>$allRole]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        return view('role.add');
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
            'role_name' => 'required|unique:roles',

        ]);

        if ($validator->fails()) {
            return redirect('admin/addrole')
                        ->withErrors($validator)
                        ->withInput();
        }else{
            $queryStatus;
            try{
                Role::create(['role_name' => $request->role_name,'created_by' => Auth::id()]);
                $queryStatus = "Successful";
            }catch(Exception $e) {
                $queryStatus = "Not success";
            }


            return view('role.add')->with('message', $queryStatus);
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
        $roleDetail = Role::where(['id' => $id])->first();
       
        return view('role.edit', ['roleDetail' => $roleDetail, 'id' => $id]);
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
            'role_name' => 'required|unique:roles',

        ]);

        if ($validator->fails()) {
            return redirect('admin/addrole')
                        ->withErrors($validator)
                        ->withInput();
        }else{
            $queryStatus;
            try{
                Role::where('id', $id)->update(['role_name'=> $request->role_name]);
                $queryStatus = "Updated Successful!";

            }catch(Exception $e) {
                $queryStatus = "Update Failed!";
            }



            return redirect('admin/adminuser');


        }
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
