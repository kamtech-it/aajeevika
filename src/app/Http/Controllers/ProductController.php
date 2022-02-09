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
use App\ProductMaster;
use App\PopularProduct;
use App\ProductTemplate;
use App\Material;
use Illuminate\Support\Facades\Input;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UploadTrait;
use Excel;
use App\RolePermission;
use App\Permission;

class ProductController extends Controller
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
            if (!in_array('/admin/products', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = ProductMaster::with('category', 'subcategory', 'material', 'template', 'user', 'popular')
         //   ->where('is_active', '=', '1')
            ->where('is_draft', '=', '0');

        if ($request->has('s')) {
            // $templateIds  = ProductTemplate::select('id')->where('name_en', 'LIKE', '%' . Input::get('s') . '%')
            // ->get()->toArray();
            //$query->whereIn('template_id', $templateIds);
            $query->where('localname_en', 'LIKE', '%' . Input::get('s') . '%');
            $query->orWhere('product_id_d', 'LIKE', '%' . Input::get('s') . '%');
        }

        if (Auth::user()->role_id == '4') {
            $dist = Auth::user()->district;
            $query->whereHas('user', function ($qq) use ($dist) {
                return $qq->where('district', '=', $dist);
            });
        }

        $productData1 = $query->latest()->get();
        $productData = $query->paginate(10);


        if ($request->has('exportdata')) {
            if ($request->exportlist == 'all') {
                $data = $productData1;
                return Excel::create('allproducts', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('Product Name');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Product Local Name English');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Product Local Name Hindi');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('SHG Enterprise / CLF Name');
                        });

                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('Category');
                        });

                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Sub Category');
                        });

                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Product Type');
                        });

                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Price');
                        });
                        
                        
                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Quantity');
                        });
                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Price Unit');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value['id']);
                                $sheet->cell('B'.$i, $value['template']['name_en']);
                                $sheet->cell('C'.$i, $value['localname_en']);
                                $sheet->cell('D'.$i, $value['localname_kn']);
                                $sheet->cell('E'.$i, $value['user']['name']);
                                $sheet->cell('F'.$i, $value['category']['name_en']);
                                $sheet->cell('G'.$i, $value['subcategory']['name_en']);
                                $sheet->cell('H'.$i, $value['material']['name_en']);
                                $sheet->cell('I'.$i, $value['price']);
                                $sheet->cell('J'.$i, $value['qty']);
                                $sheet->cell('K'.$i, $value['price_unit']);
                            }
                        }
                    });
                })->download('xlsx');
            }
            if ($request->exportlist == 'category') {
                $category_id = $request->category_name;

                $productData2 =  ProductMaster::with(
                    ["category",'user']
                )
                ->where('is_active', '=', '1')
                ->where('is_draft', '=', '0')
                ->where('categoryId', '=', $category_id)
                ->get();




                $data = $productData2;
                return Excel::create('categorywise', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('Product Name');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Product Local Name English');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Product Local Name Hindi');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('SHG/ Artisan Name');
                        });

                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('Category');
                        });

                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Sub Category');
                        });

                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Product Type');
                        });
                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Price');
                        });

                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Quantity');
                        });
                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Price Unit');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value['id']);
                                $sheet->cell('B'.$i, $value['template']['name_en']);
                                $sheet->cell('C'.$i, $value['localname_en']);
                                $sheet->cell('D'.$i, $value['localname_kn']);
                                $sheet->cell('E'.$i, $value['user']['name']);
                                $sheet->cell('F'.$i, $value['category']['name_en']);
                                $sheet->cell('G'.$i, $value['subcategory']['name_en']);
                                $sheet->cell('H'.$i, $value['material']['name_en']);
                                $sheet->cell('I'.$i, $value['price']);
                                $sheet->cell('J'.$i, $value['qty']);
                                $sheet->cell('K'.$i, $value['price_unit']);
                            }
                        }
                    });
                })->download('xlsx');
            }

            if ($request->exportlist == 'subcategory') {
                $category_id = $request->category_name;
                $subcategoryId = $request->subcategory_name;

                $productData2 =  ProductMaster::with(
                    ["category",'user']
                )
                ->where('is_active', '=', '1')
                ->where('is_draft', '=', '0')
                ->where('categoryId', '=', $category_id)
                ->where('subcategoryId', '=', $subcategoryId)
                ->get();




                $data = $productData2;
                return Excel::create('subcategory', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('Product Name');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Product Local Name English');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Product Local Name Hindi');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('SHG/ Artisan Name');
                        });

                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('Category');
                        });

                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Sub Category');
                        });

                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Product Type');
                        });
                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Price');
                        });

                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Quantity');
                        });
                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Price Unit');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value['id']);
                                $sheet->cell('B'.$i, $value['template']['name_en']);
                                $sheet->cell('C'.$i, $value['localname_en']);
                                $sheet->cell('D'.$i, $value['localname_kn']);
                                $sheet->cell('E'.$i, $value['user']['name']);
                                $sheet->cell('F'.$i, $value['category']['name_en']);
                                $sheet->cell('G'.$i, $value['subcategory']['name_en']);
                                $sheet->cell('H'.$i, $value['material']['name_en']);
                                $sheet->cell('I'.$i, $value['price']);
                                $sheet->cell('J'.$i, $value['qty']);
                                $sheet->cell('K'.$i, $value['price_unit']);
                            }
                        }
                    });
                })->download('xlsx');
            }

            if ($request->exportlist == 'material') {
                $category_id = $request->category_name;
                $subcategoryId = $request->subcategory_name;

                $material = $request->material_name;


                $productData2 =  ProductMaster::with(
                    ["category",'user']
                )
               // ->where('is_active', '=', '1')
                ->where('is_draft', '=', '0')
                ->where('categoryId', '=', $category_id)
                ->where('subcategoryId', '=', $subcategoryId)
                ->where('material_id', '=', $material)
                ->get();




                $data = $productData2;
                return Excel::create('material', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('Product Name');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Product Local Name English');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Product Local Name Hindi');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('SHG/ Artisan Name');
                        });

                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('Category');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Sub Category');
                        });

                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Product Type');
                        });



                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Price');
                        });

                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Quantity');
                        });
                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Price Unit');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value['id']);
                                $sheet->cell('B'.$i, $value['template']['name_en']);
                                $sheet->cell('C'.$i, $value['localname_en']);
                                $sheet->cell('D'.$i, $value['localname_kn']);
                                $sheet->cell('E'.$i, $value['user']['name']);
                                $sheet->cell('F'.$i, $value['category']['name_en']);
                                $sheet->cell('G'.$i, $value['subcategory']['name_en']);
                                $sheet->cell('H'.$i, $value['material']['name_en']);
                                $sheet->cell('I'.$i, $value['price']);
                                $sheet->cell('J'.$i, $value['qty']);
                                $sheet->cell('K'.$i, $value['price_unit']);
                            }
                        }
                    });
                })->download('xlsx');
            }
        }


        if ($request->has('viewdata')) {
            if ($request->exportlist == 'all') {
                $data = $productData;
            }
            if ($request->exportlist == 'category') {
                $category_id = $request->category_name;

                $query = ProductMaster::with('category', 'subcategory', 'material', 'template', 'user', 'popular')
                ->where('is_active', '=', '1')
                ->where(['categoryId' => $category_id ])
                ->where('is_draft', '=', '0')

                ->paginate(10);


                return view('product.index', ['productData' => $query]);


            }

            if ($request->exportlist == 'subcategory') {
                $category_id = $request->category_name;
                $subcategoryId = $request->subcategory_name;

                $query = ProductMaster::with('category', 'subcategory', 'material', 'template', 'user', 'popular')
                ->where('is_active', '=', '1')
                ->where(['categoryId' => $category_id, 'subcategoryId'=> $subcategoryId ])
                ->where('is_draft', '=', '0')
                ->paginate(10);


                return view('product.index', ['productData' => $query]);


            }

            if ($request->exportlist == 'material') {
                $category_id = $request->category_name;
                $subcategoryId = $request->subcategory_name;

                $material = $request->material_name;


                $query = ProductMaster::with('category', 'subcategory', 'material', 'template', 'user', 'popular')
                ->where('is_active', '=', '1')
                ->where(['categoryId' => $category_id, 'subcategoryId'=> $subcategoryId ])
                ->where('is_draft', '=', '0')
                ->where('material_id', '=', $material)
                ->paginate(10);


                return view('product.index', ['productData' => $query]);


            }
        }

        return view('product.index', ['productData' => $productData]);
    }




    public function show($id)
    {
        $id = decrypt($id);
        $productDetail = ProductMaster::with('category', 'subcategory', 'material', 'template', 'user')->where('id', $id)->first();
        return view('product.view', ['productDetail' => $productDetail]);
    }


    public function edit($id)
    {
        $id = decrypt($id);
        $productDetail = ProductMaster::with('category', 'subcategory', 'material', 'template', 'user')->where('id', $id)->first();

        $categoryData = Category::where(['parent_id' => 0])->where('is_active',1)->get();
        $subcategoryData   = DB::table('categories')->where('parent_id', '=', $productDetail->categoryId)->where('is_active',1)->get();
        $materialData =  Material::where(['category_id' => $productDetail->categoryId , 'subcategory_id'=>$productDetail->subcategoryId])->get();


        return view('product.edit', ['categoryData'=>$categoryData,'subcategoryData'=>$subcategoryData,'productDetail' => $productDetail,'materialData'=>$materialData, 'id'=>$id]);
    }


    public function update(Request $request, $id)
    {
        $id  = decrypt($id);
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'image_1' => 'mimes:jpeg,png,jpg,JPEG,JPGgif,svg|max:2048',
            'image_2' => 'mimes:jpeg,png,jpg,JPEG,JPGgif,svg|max:2048',
            'image_3' => 'mimes:jpeg,png,jpg,JPEG,JPGgif,svg|max:2048',
            'image_4' => 'mimes:jpeg,png,jpg,JPEG,JPGgif,svg|max:2048',
            'image_5' => 'mimes:jpeg,png,jpg,JPEG,JPGgif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return redirect('admin/editproduct/'.$id)->withErrors($validator)->withInput();
        } else {
            $input = $request->all();

            if ($files = $request->file('image_1')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/product"), $profileImage);
                $input['image_1'] = "images/product/".$profileImage;
            }
            if ($files = $request->file('image_2')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/product"), $profileImage);
                $input['image_2'] = "images/product/".$profileImage;
            }
            if ($files = $request->file('image_3')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/product"), $profileImage);
                $input['image_3'] = "images/product/".$profileImage;
            }
            if ($files = $request->file('image_4')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/product"), $profileImage);
                $input['image_4'] = "images/product/".$profileImage;
            }
            if ($files = $request->file('image_5')) {
                $profileImage = date('YmdHis') . "." . $files->getClientOriginalExtension();
                $aa =  $files->move(public_path("images/product"), $profileImage);
                $input['image_5'] = "images/categoproductry/".$profileImage;
            }

            $productUpdate  = ProductMaster::where('id', $id)->first();
            if ($productUpdate) {
                if (!$request->parent_id) {
                    unset($input['parent_id']);
                }
                $cats = $productUpdate->update($input);
                return redirect('admin/products');
            }
        }
    }





    public function addtopopular($id)
    {
        $input = array();
        $input['product_id'] = $id;
        $input['admin_id'] = Auth::user()->id;

        $popularCount = PopularProduct::count();

        if ($popularCount == 10) {
            return redirect()->back();
        } else {
            $popularProducts = PopularProduct::create($input);
            return redirect()->back();
        }
    }

    public function removepopular($id)
    {
        $pp     = PopularProduct::where('product_id', $id)->first();
        if ($pp) {
            $pops   = $pp->delete();
        }
        return redirect()->back();
    }

    public function create()
    {
    }


    public function store(Request $request)
    {
        //
    }








    public function destroy($id, $status)
    {
       // $id = decrypt($id); 
        $input['is_active'] = $status;
        $individualUpdate  = ProductMaster::where('id', $id)->first();
        if ($individualUpdate) {
            $cats = $individualUpdate->update($input);
            return redirect('admin/products');
        }
    }
}
