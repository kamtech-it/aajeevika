<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\User;
use App\Role;
use App\ProductMaster;
use App\RolePermission;
use App\Permission;
use App\City;
use Auth;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminController extends Controller
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

            if (!in_array('/admin/adminuser', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = DB::table('users')
            ->join('states', 'users.state_id', '=', 'states.id')
            ->join('cities', 'users.district', '=', 'cities.id')
            ->select('users.*', 'cities.name as district_name', 'states.name as state_name')
            ->whereIn('users.role_id', [4,5])
            ->orderBy('users.id', 'desc');
            

        $keyword = $request->s;


        if ($request->has('s')) {
            $query = DB::table('users')
                ->join('states', 'users.state_id', '=', 'states.id')
                ->join('cities', 'users.district', '=', 'cities.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->select('users.*', 'cities.name as district_name', 'states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')

                ->where(function ($query1) use ($keyword, $query) {
                    $query1->where('users.name', 'LIKE', '%'.$keyword.'%');
                    $query1->orWhere('users.email', $keyword);
                    $query1->orWhere('users.mobile', $keyword);
                })

               
                ->whereIn('users.role_id', [4,5])
                ->orderBy('users.id', 'desc');
        }
        $userData = $query->get();

        return view('admin.index', ['userData' => $userData ]);
    }

    public function create()
    {
        $roleList   = Role::wherein('id', [4,5])->get();
        $states     = DB::table('states')->where('country_id', '=', 101)->get();
        $allPermission = Permission::where('status', 1)->get();
        $districtData =  City::where('state_id', 17)->get();
        return view('admin.add', ['roleList' => $roleList, 'stateList'=>$states, 'districtData'=>$districtData,  'allPermission' => $allPermission]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required',
            'email'     => 'required|email|unique:users',
            'mobile'    => 'required|unique:users',
            // 'district'  => 'required',
            // 'state_id'     => 'required',
            'role_id'      => 'required',
            'password'  => 'min:6|required_with:confirm_password|same:confirm_password',
            'password_confirmation' => 'min:6',
            'permission'   => 'required'
        ]);

        if ($validator->fails()) {
            return redirect('admin/addadminuser')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $input = $request->all();
            $input['state_id'] = 17;
            $input['password'] = Hash::make($request->password);
            $input['api_token'] = Str::random(60);

            try {
                $user = User::create($input);
                $userId = $user->id;

                $flight = RolePermission::where('user_id', $userId)->delete();

                $allPermission = $request->permission;
              

                foreach ($request->permission as $item) {
                    $create = RolePermission::create([
                        'user_id' => $userId,
                        'permission_id' => $item
                    ]);
                }


                $queryStatus = "Successful";
            } catch (Exception $e) {
                $queryStatus = "Not success";
            }

            return redirect('admin/adminuser');
        }
    }
    public function show($id)
    {
    }

    public function edit($id)
    {
        $id =  decrypt($id);
        $adminDetail = User::where(['id' => $id])->first();

        $roleList    = Role::wherein('id', [4,5])->get();
        $states      = DB::table('states')->where('country_id', '=', 101)->get();
        $disctrict   = DB::table('cities')->where('state_id', '=', $adminDetail->state_id)->get();
        $allPermission = Permission::where('status', 1)->get();

        $userPermission = RolePermission::where('user_id', $id)->get();

        $roleassignedpermission = [];
        
        foreach ($userPermission as $value) {
            $roleassignedpermission[] = $value->permission_id;
        }

        return view('admin.edit', ['adminDetail' => $adminDetail, 'id' => $id, 'roleList' => $roleList, 'stateList'=>$states,'districtList'=>$disctrict, 'allPermission' => $allPermission, 'userPermission' => $roleassignedpermission]);
    }

    public function update(Request $request, $id)
    {
        $id = decrypt($id);
        $validator = Validator::make($request->all(), [
            'name'      => 'required',
            'email'     => 'required|email|',
            'mobile'    => 'required|',
            'district'  => 'required',
            'state_id'     => 'required',
            'role_id'      => 'required',
            'permission'  => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $adminUpdate  = User::where('id', $id)->first();
            if ($adminUpdate) {
            
                // dd($request->all());
                
                $admin = $adminUpdate->update($request->all());

                $flight = RolePermission::where('user_id', $id)->delete();


                foreach ($request->permission as $item) {
                    $create = RolePermission::create([
                       'user_id' => $id,
                       'permission_id' => $item
                   ]);
                }
       

                $userData = DB::table('users')
               ->join('states', 'users.state_id', '=', 'states.id')
               ->join('cities', 'users.district', '=', 'cities.id')
               ->select('users.*', 'cities.name as district_name', 'states.name as state_name')
               ->whereIn('users.role_id', [4,5])
               ->get();
                return redirect('admin/adminuser');
            }
        }
    }

    public function destroy($id, $status)
    {
        $userStatus = User::where('id', $id)->first();

        if ($userStatus) {
            $input['isActive'] = $status;
            $deleteProduct = ProductMaster::where(['user_id' => $id])->update(['is_active' => $status]);

            if ($status == 0) {
                $block_status = 1;
            } else {
                $block_status = 0;
            }



            $input['is_blocked_byadmin'] = $block_status;
            $updated = $userStatus->update($input);

            return redirect()->back();
        }
    }
}
