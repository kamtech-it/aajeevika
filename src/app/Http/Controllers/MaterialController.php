<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use File;
use App\User;
use App\Category;
use App\Material;
use App\Role;
use Auth;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UploadTrait;
use App\RolePermission;
use App\Permission;
use Illuminate\Support\Facades\Input;

class MaterialController extends Controller
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
            if (!in_array('/admin/material', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }


    public function index( Request $request)
    {
        $materialData =  Material::with('category:id,name_en', 'subcategory:id,name_en')->orderBy('id','DESC')->paginate(10);

        // echo "<pre>"; print_r($materialData); die("check");


        if ($request->has('s')) {
            $materialData =  Material::with('category:id,name_en', 'subcategory:id,name_en')
            ->where('name_en', 'LIKE', '%' . Input::get('s') . '%')
            ->orWhere('name_kn', 'LIKE', '%' . Input::get('s') . '%')
            ->orderBy('id','DESC')->paginate(10);
        }



        return view('material.index', ['materialData'=>$materialData]);
    }


    public function create()
    {

        //$categoryData = DB::table('categories')->get();
        $categoryData = Category::where(['parent_id' => 0, 'is_active' => 1])->get();
        return view('material.add', ['categoryData' => $categoryData,]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'image' => 'required|mimes:jpeg,JPEG,JPG,png,jpg,gif,svg|max:2048',
            'name_kn'=>'required',
            'name_en'=>'required',
            'category_id'=>'required',
            'subcategory_id' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect('admin/addmaterial')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $input = $request->all();
            if ($files = $request->file('image')) {
                $materialImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/material"), $materialImage);
                $input['image'] = "$materialImage";
            }
            $input['admin_id'] = Auth::user()->id;
            $material = Material::create($input);
            $queryStatus = "Successful";
            return redirect('admin/material')->with('message', $queryStatus);
        }

        //
    }


    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        $id = decrypt($id);
        $materialData =  Material::where(['id' => $id ])->first();
        $categoryData = Category::where(['parent_id' => 0])->where('is_active',1)->get();
        $subcategoryData   = DB::table('categories')->where('parent_id', '=', $materialData->category_id)->where('is_active',1)->get();

        return view('material.edit', ['categoryData' => $categoryData, 'subcategoryData'=>$subcategoryData, 'materialData'=>$materialData, 'id' => $id,]);
    }


    public function update(Request $request, $id)
    {
        $id = decrypt($id);
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            // 'image' => 'image|mimes:jpeg,png,JPEG,JPG,jpg,gif,svg|max:2048',
            'name_kn'=>'required',
            'name_en'=>'required',
            'category_id'=>'required',
            'subcategory_id'=>'required'

        ]);

        if ($validator->fails()) {
            return redirect('admin/editMaterial/'.$id)->withErrors($validator)->withInput();
        } else {
            $input = $request->all();
            if ($files = $request->file('image')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/material"), $profileImage);
                $input['image'] = "$profileImage";
            }
            $input['admin_id'] = Auth::user()->id;

            $materialUpdate  = Material::where('id', $id)->first();
            if ($materialUpdate) {
                $materials = $materialUpdate->update($input);

                $materialData = DB::table('materials')

                    // ->join('categories as acat', 'materials.category_id', '=', 'categories.id')
                    // ->join('categories as bcat', 'materials.subcategory_id', '=', 'categories.parent_id')
                    // ->join('users', 'materials.admin_id', '=', 'users.id')
                    // ->select('materials.*', 'users.name as admin_name','acat.name as category_name' ,'bcat.name as category_name')

                ->get();

                return redirect('admin/material');

                // return view('material.index',['materialData'=>$materialData]);
            }
        }
    }


    public function destroy($id)
    {
        //
    }
}
