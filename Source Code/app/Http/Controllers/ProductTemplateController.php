<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use File;
use App\User;
use App\Category;
use App\ProductTemplate;
use App\Material;
use App\Role;
use Auth;
use DB;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UploadTrait;
use Illuminate\Support\Facades\Input;
use Excel;
use App\RolePermission;
use App\Permission;

class ProductTemplateController extends Controller
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
            if (!in_array('/admin/templates', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $temp = ProductTemplate::with('category', 'subcategory', 'material')->orderBy('id','DESC')->paginate(10);
        $temp2 = ProductTemplate::with('category', 'subcategory', 'material')->orderBy('id','DESC')->get();


        if ($request->has('s')) {
            $temp =  ProductTemplate::with('category', 'subcategory', 'material')
            ->where('name_en', 'LIKE', '%' . Input::get('s') . '%')
            ->orWhere('name_kn', 'LIKE', '%' . Input::get('s') . '%')
            ->orderBy('id','DESC')->paginate(10);


            $temp2 =  ProductTemplate::with('category', 'subcategory', 'material')
            ->where('name_en', 'LIKE', '%' . Input::get('s') . '%')
            ->orWhere('name_kn', 'LIKE', '%' . Input::get('s') . '%')
            ->orderBy('id','DESC')->get();
        }

        if ($request->has('exportlist')) {
            //all listed
            if ($request->exportlist == 'all') {
                $data = $temp2;
                return Excel::create('alltemplate', function ($excel) use ($data) {
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
                            $cell->setValue('Category');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Subcategory');
                        });
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('Material');
                        });

                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Description English');
                        });

                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Description Hindi');
                        });



                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value['id']);
                                $sheet->cell('B'.$i, $value['name_en']);
                                $sheet->cell('C'.$i, $value['name_kn']);
                                $sheet->cell('D'.$i, $value['category']['name_en']);
                                $sheet->cell('E'.$i, $value['subcategory']['name_en']);
                                $sheet->cell('F'.$i, $value['material']['name_en']);
                                $sheet->cell('G'.$i, $value['description_en']);
                                $sheet->cell('H'.$i, $value['description_kn']);
                            }
                        }
                    });
                })->download('xlsx');
            }
        }


        return view('template.index', ['temp'=>$temp]);
    }



    public function create()
    {
        //$categoryData = DB::table('categories')->get();
        $categoryData = Category::where(['parent_id' => 0, 'is_active'=>1])->get();
        return view('template.add', ['categoryData' => $categoryData,]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_kn'=>'required',
            'name_en'=>'required',
            'description_en'=>'required',
            'description_kn'=>'required',
            'subcategory_id'=>'required',
            'category_id'=>'required',
            // 'material_id'=>'required',


            'height'    => 'required_without_all:width,length,volume,weight,no_measurement',
            'width'     => 'required_without_all:height,length,volume,weight,no_measurement',
            'length'    => 'required_without_all:width,height,volume,weight,no_measurement',
            'volume'    => 'required_without_all:width,length,height,weight,no_measurement',
            'weight'    => 'required_without_all:width,length,volume,height,no_measurement',
            'no_measurement'    => 'required_without_all:weight,width,length,volume,height',



        ]);


        if ($validator->fails()) {
            return redirect('admin/addTemplate')->withErrors($validator)->withInput();
        } else {
            $input = $request->all();

            $input['admin_id'] = Auth::user()->id;
            $category = ProductTemplate::create($input);
            $queryStatus = "Successful";
            return redirect('admin/templates')->with('message', $queryStatus);
        }
    }

    public function show()
    {
    }

    public function edit($id)
    {
        $id = decrypt($id);
        $templateData       = ProductTemplate::where(['id' => $id])->first();
        $categoryData       = Category::where(['parent_id' => 0])->get();
        $subcategoryData    = Category::where('parent_id', '=', $templateData->category_id)->get();


        //dd($templateData);

        $materialData = DB::table('materials')
        ->where('category_id', '=', $templateData->category_id)
        ->where('subcategory_id', '=', $templateData->subcategory_id)
        ->get();

        return view('template.edit', [ 'id' => $id,'categoryData' => $categoryData,'subcategoryData'=>$subcategoryData,'templateData'=>$templateData,'materialData'=>$materialData]);
    }

    public function update(Request $request, $id)
    {
        $id = decrypt($id);
        $validator = Validator::make($request->all(), [
            'name_kn'=>'required',
            'name_en'=>'required',
            'description_en'=>'required',
            'description_kn'=>'required',
            //'subcategory_id'=>'required',
            //'category_id'=>'required',
            // 'material_id'=>'required',


            'height'    => 'required_without_all:width,length,volume,weight,no_measurement',
            'width'     => 'required_without_all:height,length,volume,weight,no_measurement',
            'length'    => 'required_without_all:width,height,volume,weight,no_measurement',
            'volume'    => 'required_without_all:width,length,height,weight,no_measurement',
            'weight'    => 'required_without_all:width,length,volume,height,no_measurement',
            'no_measurement'    => 'required_without_all:weight,width,length,volume,height',



        ]);
        if ($validator->fails()) {
            return redirect('admin/editTemplate/'.$id)->withErrors($validator)->withInput();
        } else {
            $input = $request->all();
            $ProductTemplate    =  ProductTemplate::where('id', $id)->first();
            $Productupdate      = $ProductTemplate->update($input);
            return redirect('admin/templates');
        }
    }


    public function destroy()
    {
    }
}
