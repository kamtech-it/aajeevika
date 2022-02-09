<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use File;
use App\User;
use App\Category;
use App\IndCategory;
use App\Material;
use App\ProductMaster;
use App\RolePermission;
use App\Permission;
use App\Role;
use Auth;
use DB;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UploadTrait;
use Illuminate\Support\Facades\Input;
use Excel;

class IndividualCategoryController extends Controller
{
    public function __construct(Request $request)
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

            if (!in_array('/admin/category', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }


    public function index(Request $request)
    {

        $query = DB::table('ind_categories')
        ->select('ind_categories.*')
        ->orderBy('id','desc');


        if ($request->has('s')) {
            $query = DB::table('ind_categories')
            ->select('ind_categories.*')
            ->where('ind_categories.name_en', 'LIKE', '%' . Input::get('s') . '%')
            ->orWhere('ind_categories.name_hi', 'LIKE', '%' . Input::get('s') . '%')
            ->orderBy('id','desc');
        }

        //for export
        $categoryData1 = $query->get()->toArray();
        //for view
        $categoryData = $query->paginate(10);

        if ($request->has('exportlist')) {
            if ($request->exportlist == 'all') {
                $data =  $categoryData1;
                return Excel::create('allcategory', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('Name English');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Name Hindi');
                        });

                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->name_en);
                                $sheet->cell('c'.$i, $value->name_hi);
                            }
                        }
                    });
                })->download('xlsx');
            }

            if ($request->exportlist == 'subcat') {
                $query = DB::table('categories')
                ->join('users', 'categories.admin_id', '=', 'users.id')
                ->select('categories.*', 'users.name as admin_name', 'parent_id')
                ->where('parent_id', '!=', '0');
                $data = $query->get()->toArray();
                return Excel::create('allsubcategoies', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('Name English');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Name Hindi');
                        });

                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Parent ID');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Parent  Name');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->name_en);
                                $sheet->cell('C'.$i, $value->name_kn);
                                $sheet->cell('D'.$i, $value->parent_id);
                                $sheet->cell('E'.$i,  Helper::getCatBySubCat($value->parent_id)->name_en );
                            }
                        }
                    });
                })->download('xlsx');
            }
        }

        
        return view('individual_category.index', ['categoryData' => $categoryData]);
    }

    public function create($catId = false)
    {
        return view('individual_category.add');
    }



    public function store(Request $request)
    {

        // echo "<pre>"; print_r($request->all()); die("check");

        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpeg,jpg,JPEG,JPG,png,svg|max:5000',
            'name_hi'=>'required',
            'name_en'=>'required'
        ]);


        if ($validator->fails()) {
            return redirect('admin/indcategory/addcategory')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $input = $request->all();

            $input['slug'] = Str::slug($request->name_en);

            if ($files = $request->file('image')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/ind_category"), $profileImage);
                $input['image'] = "images/ind_category/".$profileImage;
            }

            
            $category = IndCategory::create($input);
            $queryStatus = "Successful";
            return redirect('admin/indcategory')->with('message', $queryStatus);
        }
    }
    public function edit($id)
    {
        $categoryDetail = IndCategory::where(['id' => $id])->first();
        
        return view('individual_category.edit', ['categoryDetail' => $categoryDetail, 'id' => $id,]);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
           'name_en'=>'required',
           'name_hi'=>'required',
        ]);
        if ($validator->fails()) {
            return redirect('admin/indcategory/editcategory/'.$id)->withErrors($validator)->withInput();
        } else {
            $input = $request->all();
            $input['slug'] = Str::slug($request->name_en);
            if ($files = $request->file('image')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/ind_category"), $profileImage);
                $input['image'] = "images/ind_category/".$profileImage;
            }
           // $input['admin_id'] = Auth::user()->id;

            $categoryUpdate  = IndCategory::where('id', $id)->first();
            if ($categoryUpdate) {
                
                $cats = $categoryUpdate->update($input);
                // $categoryData = DB::table('categories')
                // ->join('users', 'categories.admin_id', '=', 'users.id')
                // ->select('categories.*', 'users.name as admin_name')
                // ->get();
                // return view('category.index', ['categoryData' => $categoryData]);
                return redirect('admin/indcategory');
            }
        }
    }

    public function view($id)
    {
        $categoryData = IndCategory::with('subcategory')->where(['id' => $id])->first();
        // dd($categoryData);

        return view('category.view', [ 'categoryData'=>$categoryData]);
    }


    public function destroy($id, $status)
    {
       // $id = decrypt($id); 
        $input['status'] = $status;
        $individualUpdate  = IndCategory::where('id', $id)->first();
        if ($individualUpdate) {
            $cats = $individualUpdate->update($input);
            return redirect('admin/indcategory');
        }
    }


    public function enabledisablecategory(Request $request, $id, $status)
    {
        $catStatus = IndCategory::where('id', $id)->first();


        if ($catStatus) {
            $input['is_active'] = $status;

            $deleteProduct = ProductMaster::where(['categoryId' => $id])->update(['is_active' => $status]);
            $deleteSubcategory = Category::where('parent_id', $id)->update(['is_active' => $status]);
            $deleteMaterial =Material::where('category_id', $id)->update(['is_active'=> $status]);




            $updated = $catStatus->update($input);
            return redirect()->back();
        }
    }
}
