<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use File;
use App\User;
use App\Category;
use App\Documents;
use App\ProductCertification;
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

class ProductCertificationController extends Controller
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

            if (!in_array('/admin/certificate', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {

        $query = ProductCertification::leftJoin('product_masters as pm','pm.id','product_certifications.product_id')->leftJoin('users','users.id','pm.user_id')->select('product_certifications.*','users.*','pm.*','product_certifications.id as certificate_id')->orderBy('product_certifications.id', 'desc');
        if ($request->has('s')) {
            $searchString = trim($request->s);
            $categories =  $query->where(function ($q) use ($searchString) {
                $q->where('pm.localname_en', 'like', '%'.$searchString.'%');
                $q->orWhere('users.organization_name', 'like', '%'.$searchString.'%');
                $q->orWhere('pm.product_id_d', $searchString);
            });
        }
        $certificateData =  $query->paginate(10);


        return view('product_certificate.index', ['certificateData'=>$certificateData]);
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
        $certificateData = ProductCertification::where('id', $id)->first();
        return view('product_certificate.view', ['certificateData'=>$certificateData]);
    }

    public function acceptCertificate($id, $status, $status_col)
    {
        $certificateUpdate = ProductCertification::where('id', $id)->first();
        if ($certificateUpdate) {
            $input[$status_col] = $status;
            $updated = $certificateUpdate->update($input);          
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
