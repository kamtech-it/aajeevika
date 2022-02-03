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
use Session;
use App\CollectionCenter;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UploadTrait;
use Illuminate\Support\Facades\Input;
use Excel;
use App\State;
use App\City;
use App\Block;
use App\Exports\CollectionExport;


class CollectionController extends Controller
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

        $query = DB::table('collection_centers')
        ->leftJoin('states','collection_centers.state_id','=','states.id')
        ->leftJoin('cities','collection_centers.city_id','=','cities.id')
        ->leftJoin('blocks','collection_centers.block_id','=','blocks.id')
        ->select('collection_centers.*','states.name as state_name','cities.name as city_name','blocks.name as block_name')
        ->orderBy('id','desc');


        if ($request->has('s')) {
            $query = DB::table('collection_centers')
            ->leftJoin('states','collection_centers.state_id','=','states.id')
            ->select('collection_centers.*','states.name as state_name')
            ->where('collection_centers.name_en', 'LIKE', '%' . Input::get('s') . '%')
            ->orWhere('collection_centers.name_hi', 'LIKE', '%' . Input::get('s') . '%')
            ->orderBy('id','desc');
        }

        //for export
        $categoryData1 = $query->get()->toArray();
        //for view
        $collectionData = $query->paginate(10);

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
                //return Excel::download(new CollectionExport, 'collectionExport.xlsx');

            }

            if ($request->exportlist == 'subcat') {
                $query = DB::table('collection_centers')
                ->select('categories.*');
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

        
        return view('collection_center.index', ['collectionData' => $collectionData]);
    }

    public function create($catId = false)
    {
        $states = State::where('country_id','101')->get();
        $cities = City::where('state_id','39')->get();
        $blocks = Block::all();
        return view('collection_center.add',compact('states','cities','blocks'));
    }


    public function cityAjax(Request $request)
    {
        
        $city = City::where('state_id',$request->state_id)->get();
        $output = '<option value="">Select City</option>';
        foreach($city as $c)
        {
          
            $output.='<option value="'.$c->id.'">'.$c->name.'</option>';
        }
          print_r($output);
    }


    public function blockAjax(Request $request)
    {
        
        $city = Block::where('city_id',$request->city_id)->get();
        $output = '<option value="">Select Block</option>';
        foreach($city as $c)
        {
          
            $output.='<option value="'.$c->id.'">'.$c->name.'</option>';
        }
          print_r($output);
    }


    public function storeUser(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'name'=>'required',
            'email'=>'required',
            'password' => 'min:6|required_with:password_confirmation|same:password_confirmation',
            'password_confirmation' => 'min:6',
        ]);


        if ($validator->fails()) {
            return redirect('admin/collection-center/adduser/'.$request->collection_id)
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $input = $request->all();

            $input['slug'] = Str::slug($request->name);
            $input['role_id']='10';
             $users = User::where('collection_center_id',$request->collection_center_id)->get();
                //print_r($users);die;
             if(count($users)>=5)
             {
                Session::flash('max_user', 'Max Collection User are Exist!');
                 return redirect()->back();
             }   
            
           
            
            $category = User::create($input);
            $queryStatus = "Successful";
            return redirect('admin/collection-center')->with('message', $queryStatus);
        }

    }

    public function store(Request $request)
    {

        // echo "<pre>"; print_r($request->all()); die("check");

        $validator = Validator::make($request->all(), [
            'name_hi'=>'required',
            'name_en'=>'required'

        ]);


        if ($validator->fails()) {
            return redirect('admin/indcategory/addcollection')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $input = $request->all();

            $input['slug'] = Str::slug($request->name_en);

           
            
            $category = CollectionCenter    ::create($input);
            $queryStatus = "Successful";
            return redirect('admin/collection-center')->with('message', $queryStatus);
        }
    }
    public function edit($id)
    {
        //echo $id;die;
        $states = State::where('country_id','101')->get();
        $cities = City::where('state_id','39')->get();
        $blocks = Block::all();
        $collectionDetail = CollectionCenter::where(['id' => $id])->first();
        
        return view('collection_center.edit', ['collectionDetail' => $collectionDetail, 'id' => $id,'states'=>$states,
        'cities'=>$cities,'blocks'=>$blocks]);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
           'name_en'=>'required',
           'name_hi'=>'required',
        ]);
        if ($validator->fails()) {
            return redirect('admin/collection-center/editcollection/'.$id)->withErrors($validator)->withInput();
        } else {
            $input = $request->all();
         
           // $input['admin_id'] = Auth::user()->id;

            $categoryUpdate  = CollectionCenter::where('id', $id)->first();
            if ($categoryUpdate) {
                
                $cats = $categoryUpdate->update($input);
                // $categoryData = DB::table('categories')
                // ->join('users', 'categories.admin_id', '=', 'users.id')
                // ->select('categories.*', 'users.name as admin_name')
                // ->get();
                // return view('category.index', ['categoryData' => $categoryData]);
                return redirect('admin/collection-center');
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
        $individualUpdate  = CollectionCenter::where('id', $id)->first();
        if ($individualUpdate) {
            $cats = $individualUpdate->update($input);
            return redirect('admin/collection-center');
        }
    }


    // Ind user for collection center
    public function adduser($id)
    {
        return view('collection_center.add_user',compact('id'));
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
