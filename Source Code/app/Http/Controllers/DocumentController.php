<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use File;
use App\User;
use App\Category;
use App\Documents;
use App\Material;
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

class DocumentController extends Controller
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

            if (!in_array('/admin/document', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {

        $query = Documents::with('user.userdistrict')->orderBy('id', 'desc');
        if ($request->has('s')) {
            $searchString = $request->s;
            $categories =  $query->whereHas('user', function ($q) use ($searchString) {
                $q->where('name', 'like', '%'.$searchString.'%');
                $q->orWhere('mobile', $searchString);
            });
        }

        if ($request->has('district')) {
            $district = $request->district;
            if($district) {
                $categories =  $query->whereHas('user', function ($q) use ($district) {
                
                    $q->where('district', $district);
                });                
            }

        }

        if (Auth::user()->role_id == 4) {
            $searchString = Auth::user()->district;
            $categories =  $query->whereHas('user', function ($q) use ($searchString) {
                $q->where('district', '=', $searchString);
            });
        }

        $documentData =  $query->paginate(10);

        $districtData =  City::where('state_id', 39)->get();

        return view('document.index', ['documentData'=>$documentData, 'alldistrict' => $districtData]);
    }


    public function create()
    {
    }


    public function store(Request $request)
    {
    }


    public function show($id)
    {
        $id = decrypt($id);
        $documentData = Documents::with('user')->where('id', $id)->first();


        return view('document.view', ['documentData'=>$documentData]);
    }

    public function acceptAdhar($id, $status)
    {
        $aadharUpdate = Documents::where('id', $id)->first();
        $user_id = $aadharUpdate->user_id;


        if ($aadharUpdate) {
            $input['is_adhar_verify'] = $status;
            $updated = $aadharUpdate->update($input);
            if (($aadharUpdate->is_adhar_verify != 1) || ($aadharUpdate->is_pan_verify != 1) || ($aadharUpdate->is_brn_verify != 1)) {
                $userinput['is_document_verified'] = 0;
            } else {
                $userinput['is_document_verified'] = 1;
            }
            $userUpdate = User::where('id', $user_id)->first();
            $userupdated = $userUpdate->update($userinput);

          
            return redirect()->back()->withErrors(['msg', 'The Message']);
        }
    }

    public function acceptPan($id, $status)
    {
        $aadharUpdate = Documents::where('id', $id)->first();
        $user_id = $aadharUpdate->user_id;
        if ($aadharUpdate) {
            $input['is_pan_verify'] = $status;
            $updated = $aadharUpdate->update($input);
            if (($aadharUpdate->is_adhar_verify != 1) || ($aadharUpdate->is_pan_verify != 1) || ($aadharUpdate->is_brn_verify != 1)) {
                $userinput['is_document_verified'] = 0;
            } else {
                $userinput['is_document_verified'] = 1;
            }

            $userUpdate = User::where('id', $user_id)->first();
            $userupdated = $userUpdate->update($userinput);
            
            return redirect()->back()->withErrors(['msg', 'The Message']);
        }
    }

    public function acceptBrn($id, $status)
    {
        $aadharUpdate = Documents::where('id', $id)->first();
        $user_id = $aadharUpdate->user_id;
        if ($aadharUpdate) {
            $input['is_brn_verify'] = $status;
            $updated = $aadharUpdate->update($input);
            if (($aadharUpdate->is_adhar_verify != 1) || ($aadharUpdate->is_pan_verify != 1) || ($aadharUpdate->is_brn_verify != 1)) {
                $userinput['is_document_verified'] = 0;
            } else {
                $userinput['is_document_verified'] = 1;
            }

            $userUpdate = User::where('id', $user_id)->first();
            $userupdated = $userUpdate->update($userinput);


            



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
