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
use App\IndProductMaster;
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
use View;


class IndividualProductController extends Controller
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

        $query = DB::table('ind_product_masters')
        ->leftJoin('ind_categories','ind_categories.id','=','ind_product_masters.cat_id')
        ->select('ind_product_masters.*','ind_categories.name_en as category_name')
        ->orderBy('id','desc');


        if ($request->has('s')) {
            $query = DB::table('ind_product_masters')
            ->leftJoin('categories','categories.id','=','ind_product_masters.cat_id')
            ->select('ind_product_masters.*','ind_product_masters.name_en as category_name')
            ->where('ind_product_masters.name_en', 'LIKE', '%' . Input::get('s') . '%')
            ->orWhere('ind_product_masters.name_hi', 'LIKE', '%' . Input::get('s') . '%')
            ->orderBy('id','desc');
        }

        //for export
        $categoryData1 = $query->get()->toArray();
        //for view
        $productData = $query->paginate(10);

        if ($request->has('exportlist')) {
            if ($request->exportlist == 'all') {
                $data =  $categoryData1;
                return Excel::create('allIndividualProduct', function ($excel) use ($data) {
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
                            $cell->setValue('Category Name');
                        });

                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->name_en);
                                $sheet->cell('C'.$i, $value->name_hi);
                                $sheet->cell('D'.$i, $value->category_name);
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

        
        return view('individual_product.index', ['productData' => $productData]);
    }

    public function create($catId = false)
    {
        $ind_category = IndCategory::all();
        return view('individual_product.add',compact('ind_category'));
    }



    public function store(Request $request)
    {

         // echo print_r($request->all());die;
        // echo "<pre>"; print_r($request->all()); die("check");

        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpeg,jpg,JPEG,JPG,png,svg|max:5000',
            'name_hi'=>'required',
            'name_en'=>'required',
            'image'=>'required',
            'cat_id'=>'required',
        ]);


        if ($validator->fails()) {
            return redirect('admin/individual/addproduct')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $input = $request->all();

            $input['slug'] = Str::slug($request->name_en);

            if ($files = $request->file('image')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/ind_product"), $profileImage);
                $input['image'] = "images/ind_product/".$profileImage;
            }

            
            $category = IndProductMaster::create($input);
            $queryStatus = "Successful";
            return redirect('admin/individual/products')->with('message', $queryStatus);
        }
    }
    public function edit($id)
    {
        
        $ind_category = IndCategory::all();
        $categoryDetail = IndProductMaster::where(['id' => $id])->first();
        return view('individual_product.edit', ['productDetail' => $categoryDetail,'ind_category'=>$ind_category, 'id' => $id,]);
    }

    public function ajaxshow()
    {
        $output = (string)View::make('individual_product.showhide');
        print_r($output);

    }


    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
           'name_en'=>'required',
           'name_hi'=>'required',
           'cat_id'=>'required',
        ]);
        if ($validator->fails()) {
            return redirect('admin/individual/editproduct/'.$id)->withErrors($validator)->withInput();
        } else {
            $input = $request->all();
            $input['slug'] = Str::slug($request->name_en);
            if ($files = $request->file('image')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/ind_product"), $profileImage);
                $input['image'] = "images/ind_product/".$profileImage;
            }
           // $input['admin_id'] = Auth::user()->id;

            $categoryUpdate  = IndProductMaster::where('id', $id)->first();
            if ($categoryUpdate) {
                
                $cats = $categoryUpdate->update($input);
                // $categoryData = DB::table('categories')
                // ->join('users', 'categories.admin_id', '=', 'users.id')
                // ->select('categories.*', 'users.name as admin_name')
                // ->get();
                // return view('category.index', ['categoryData' => $categoryData]);
                return redirect('admin/individual/products');
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
        $individualUpdate  = IndProductMaster::where('id', $id)->first();
        if ($individualUpdate) {
            $cats = $individualUpdate->update($input);
            return redirect('admin/individual/products');
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
