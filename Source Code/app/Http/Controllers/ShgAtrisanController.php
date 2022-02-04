<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use File;
use App\User;
use App\Category;
use App\Material;
use App\ProductMaster;
use App\Role;
use App\City;
use Auth;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UploadTrait;
use Illuminate\Support\Facades\Input;
use Excel;
use App\RolePermission;
use App\Permission;
use App\Helpers\Helper;
class ShgAtrisanController extends Controller
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
            if (!in_array('/admin/shgartisans', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {

        // $query = User::whereIn('users.role_id', [2,3])->orderBy('id', 'DESC');

        $districtList = City::where('state_id', 39)->where('is_district',1)->get();

        $query = DB::table('users')
        ->join('states', 'users.state_id', '=', 'states.id')
        ->join('cities', 'users.district', '=', 'cities.id')
        ->leftjoin('blocks', 'users.block', '=', 'blocks.id')
        ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
        ->select('users.*', 'cities.name as district_name', 'blocks.name as block_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')
        ->where('addresses.address_type', 'registered')
        ->whereIn('users.role_id', [2,3,7,8])->orderBy('users.id', 'desc');
        $keyword = $request->s;
        if ($request->has('s')) {
            //dd($keyword);
            $query = DB::table('users')
                ->join('states', 'users.state_id', '=', 'states.id')
                ->join('cities', 'users.district', '=', 'cities.id')
                ->leftjoin('blocks', 'users.block', '=', 'blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->select('users.*', 'cities.name as district_name', 'blocks.name as block_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')

                ->where(function ($query1) use ($keyword, $query) {
                    $query1->where('users.name', 'LIKE', '%'.$keyword.'%');
                    $query1->orWhere('users.email', $keyword);
                    $query1->orWhere('users.mobile', $keyword);
                })

                ->where('addresses.address_type', 'registered')
                ->whereIn('users.role_id', [2,3])
                ->orderBy('users.id', 'desc');
        }


        if (Auth::user()->role_id == '4') {
            $district = Auth::user()->district;
            $query->where('users.district', '=', "$district");
        }
        

        //for export
        $shgartisanData1 = $query->get();

        //for view

        // dd($request);

        if ($request->has('exportdata')) {

            //all listed
            if ($request->exportlist == 'all') {
                $data = $shgartisanData1;

                
                return Excel::create('allshgclf', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('name');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Email');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Mobile');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Type');
                        });
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('District');
                        });
                       
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Block');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Address');
                        });

                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Pincode');
                        });
                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Organization Name');
                        });
                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Member Designation');
                        });
                        $sheet->cell('L1', function ($cell) {
                            $cell->setValue('Total Product');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->name);
                                $sheet->cell('C'.$i, $value->email);
                                $sheet->cell('D'.$i, $value->mobile);
                                if ($value->role_id == 2) {
                                    $sheet->cell('E'.$i, 'CLF');
                                } else {
                                    $sheet->cell('E'.$i, 'SHG Enterprise');
                                }
                                $sheet->cell('F'.$i, $value->district_name);
                                $sheet->cell('G'.$i, $value->block_name);

                                $sheet->cell('H'.$i, $value->address_line_one.' '.$value->address_line_two.' '.$value->pincode);
                                $sheet->cell('I'.$i, $value->pincode);
                                $sheet->cell('J'.$i, $value->organization_name);
                                $sheet->cell('K'.$i, $value->member_designation);
                                $sheet->cell('L'.$i, Helper::getTotalActiveProduct($value->id));
                            }
                        }
                    });
                })->download('xlsx');
            }



            if ($request->exportlist == 'shg') {
                $query = DB::table('users')
                ->join('states', 'users.state_id', '=', 'states.id')
                ->join('cities', 'users.district', '=', 'cities.id')
                ->leftjoin('blocks', 'users.block', '=', 'blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->select('users.*', 'cities.name as district_name', 'blocks.name as block_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')
                ->where('addresses.address_type', 'registered')
                ->whereIn('users.role_id', [3])->orderBy('users.id', 'desc');
                $data = $query->get();


                return Excel::create('allshg', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('name');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Email');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Mobile');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Type');
                        });
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('District');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Block');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Address');
                        });
                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Pincode');
                        });

                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Organization Name');
                        });
                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Member Designation');
                        });
                        $sheet->cell('L1', function ($cell) {
                            $cell->setValue('Total Product');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->name);
                                $sheet->cell('C'.$i, $value->email);
                                $sheet->cell('D'.$i, $value->mobile);
                                if ($value->role_id == 2) {
                                    $sheet->cell('E'.$i, 'Artisan');
                                } else {
                                    $sheet->cell('E'.$i, 'SHG');
                                }
                                $sheet->cell('F'.$i, $value->district_name);
                                $sheet->cell('G'.$i, $value->block_name);
                                $sheet->cell('H'.$i, $value->address_line_one.' '.$value->address_line_two.' '.$value->pincode);
                                $sheet->cell('I'.$i, $value->pincode);
                                $sheet->cell('J'.$i, $value->organization_name);
                                $sheet->cell('K'.$i, $value->member_designation);
                                $sheet->cell('L'.$i, Helper::getTotalActiveProduct($value->id));
                            }
                        }
                    });
                })->download('xlsx');
            }


            if ($request->exportlist == 'district') {
                $district = $request->district_id;
              
               
                $query = DB::table('users')
                ->where('users.district', $district)
                ->join('states', 'users.state_id', '=', 'states.id')
                ->join('cities', 'users.district', '=', 'cities.id')
                ->leftjoin('blocks', 'users.block', '=', 'blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->select('users.*', 'cities.name as district_name', 'blocks.name as block_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')
                ->where('addresses.address_type', 'registered')
                ->where('users.role_id', 3)->orderBy('users.id', 'desc');
                $data = $query->get();

                return Excel::create('AllShgArtisanaDistrictwise', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('name');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Email');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Mobile');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Type');
                        });
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('District');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Block');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Address');
                        });
                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Organization Name');
                        });
                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Member Designation');
                        });
                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Total Product');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->name);
                                $sheet->cell('C'.$i, $value->email);
                                $sheet->cell('D'.$i, $value->mobile);
                                if ($value->role_id == 2) {
                                    $sheet->cell('E'.$i, 'Artisan');
                                } else {
                                    $sheet->cell('E'.$i, 'SHG');
                                }
                                $sheet->cell('F'.$i, $value->district_name);
                                $sheet->cell('G'.$i, $value->block_name);
                                $sheet->cell('H'.$i, $value->address_line_one.' '.$value->address_line_two.' '.$value->pincode);
                                $sheet->cell('I'.$i, $value->organization_name);
                                $sheet->cell('J'.$i, $value->member_designation);
                                $sheet->cell('L'.$i, Helper::getTotalActiveProduct($value->id));
                            }
                        }
                    });
                })->download('xlsx');
            }



            if ($request->exportlist == 'artisan') {
                $query = DB::table('users')
                ->join('states', 'users.state_id', '=', 'states.id')
                ->join('cities', 'users.district', '=', 'cities.id')
                ->leftjoin('blocks', 'users.block', '=', 'blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->select('users.*', 'cities.name as district_name', 'blocks.name as block_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')
                ->where('addresses.address_type', 'registered')
                ->whereIn('users.role_id', [2])->orderBy('users.id', 'desc');
                $data = $query->get();


                return Excel::create('allartisan', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('name');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Email');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Mobile');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Type');
                        });
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('District');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Block');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Address');
                        });
                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Pincode');
                        });
                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Organization Name');
                        });
                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Member Designation');
                        });
                        $sheet->cell('L1', function ($cell) {
                            $cell->setValue('Total Product');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->name);
                                $sheet->cell('C'.$i, $value->email);
                                $sheet->cell('D'.$i, $value->mobile);
                                if ($value->role_id == 2) {
                                    $sheet->cell('E'.$i, 'CLF');
                                } else {
                                    $sheet->cell('E'.$i, 'SHG');
                                }
                                $sheet->cell('F'.$i, $value->district_name);
                                $sheet->cell('G'.$i, $value->block_name);
                                $sheet->cell('H'.$i, $value->address_line_one.' '.$value->address_line_two.' '.$value->pincode);
                                $sheet->cell('I'.$i, $value->pincode);
                                $sheet->cell('J'.$i, $value->organization_name);
                                $sheet->cell('K'.$i, $value->member_designation);
                                $sheet->cell('L'.$i, Helper::getTotalActiveProduct($value->id));
                            }
                        }
                    });
                })->download('xlsx');
            }

            if ($request->exportlist == 'category') {
                $category_id = $request->category_name;
                $data = ProductMaster::with('user', 'user.address_registerd', 'user.userdistrict')
                ->select('user_id')
                ->where(['categoryId'=> $category_id,'is_active' => 1, 'is_draft' =>  0])
                ->distinct()
                ->get();



                        
                return Excel::create('categorywise', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('name');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Email');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Mobile');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Type');
                        });
                        
                        
                        //Extra
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('District');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Block');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Address');
                        });
                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Pincode');
                        });

                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Organization Name');
                        });

                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Member Designation');
                        });
                        $sheet->cell('L1', function ($cell) {
                            $cell->setValue('Total Product');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value['user']['id']);
                                $sheet->cell('B'.$i, $value['user']['name']);
                                $sheet->cell('C'.$i, $value['user']['email']);
                                $sheet->cell('D'.$i, $value['user']['mobile']);
                                if ($value['user']['role_id'] == 2) {
                                    $sheet->cell('E'.$i, 'Artisan');
                                } else {
                                    $sheet->cell('E'.$i, 'SHG');
                                }
                                $sheet->cell('F'.$i, $value['user']['userdistrict']['name']);
                                $sheet->cell('G'.$i, $value['user']['userBlock']['name']);
                                $sheet->cell('H'.$i, $value['user']['address_registerd']['address_line_one'].', '.$value['user']['userdistrict']['name']);
                                $sheet->cell('I'.$i, $value['user']['address_registerd']['pincode']);
                                $sheet->cell('J'.$i, $value['user']['organization_name']);
                                $sheet->cell('K'.$i, $value['user']['member_designation']);
                                $sheet->cell('L'.$i, Helper::getTotalActiveProduct($value['user']['id']));
                            }
                        }
                    });
                })->download('xlsx');
            }

            if ($request->exportlist == 'subcategory') {
                $category_id = $request->category_name;
                $subcategory_id = $request->subcategory_name;

                $data =  ProductMaster::with('user', 'user.address_registerd', 'user.userdistrict')
                ->select('user_id')
                ->where(['categoryId'=> $category_id,'subcategoryId'=>$subcategory_id])
                ->distinct()->get();

                return Excel::create('subcategorywise', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('name');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Email');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Mobile');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Type');
                        });
                       
                        //Extra
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('District');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Block');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Address');
                        });
                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Pincode');
                        });

                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Organization Name');
                        });

                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Member Designation');
                        });
                        $sheet->cell('L1', function ($cell) {
                            $cell->setValue('Total Product');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value['user']['id']);
                                $sheet->cell('B'.$i, $value['user']['name']);
                                $sheet->cell('C'.$i, $value['user']['email']);
                                $sheet->cell('D'.$i, $value['user']['mobile']);
                                if ($value['user']['role_id'] == 2) {
                                    $sheet->cell('E'.$i, 'Artisan');
                                } else {
                                    $sheet->cell('E'.$i, 'SHG');
                                }
                                $sheet->cell('F'.$i, $value['user']['userdistrict']['name']);
                                $sheet->cell('G'.$i, $value['user']['userBlock']['name']);
                                $sheet->cell('H'.$i, $value['user']['address_registerd']['address_line_one'].', '.$value['user']['userdistrict']['name']);
                                $sheet->cell('I'.$i, $value['user']['address_registerd']['pincode']);
                                $sheet->cell('J'.$i, $value['user']['organization_name']);
                                $sheet->cell('K'.$i, $value['user']['member_designation']);
                                $sheet->cell('L'.$i, Helper::getTotalActiveProduct($value['user']['id']));
                            }
                        }
                    });
                })->download('xlsx');
            }

            if ($request->exportlist == 'material') {
                $category_id = $request->category_name;
                $subcategory_id = $request->subcategory_name;
                $material_id = $request->material_name;

                $data =  ProductMaster::with('user', 'user.address_registerd', 'user.userdistrict')->select('user_id')->where(['categoryId'=> $category_id,'subcategoryId'=>$subcategory_id, 'material_id'=> $material_id])->distinct()->get();

                return Excel::create('materialwise', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('name');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Email');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Mobile');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Type');
                        });

                        //Extra
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('District');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Block');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Address');
                        });
                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Pincode');
                        });
                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Organization Name');
                        });

                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Member Designation');
                        });
                        $sheet->cell('L1', function ($cell) {
                            $cell->setValue('Total Product');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value['user']['id']);
                                $sheet->cell('B'.$i, $value['user']['name']);
                                $sheet->cell('C'.$i, $value['user']['email']);
                                $sheet->cell('D'.$i, $value['user']['mobile']);
                                if ($value['user']['role_id'] == 2) {
                                    $sheet->cell('E'.$i, 'Artisan');
                                } else {
                                    $sheet->cell('E'.$i, 'SHG');
                                }
                                $sheet->cell('F'.$i, $value['user']['userdistrict']['name']);
                                $sheet->cell('G'.$i, $value['user']['userBlock']['name']);
                                $sheet->cell('H'.$i, $value['user']['address_registerd']['address_line_one'].', '.$value['user']['userdistrict']['name']);
                                $sheet->cell('I'.$i, $value['user']['address_registerd']['pincode']);
                                $sheet->cell('J'.$i, $value['user']['organization_name']);
                                $sheet->cell('K'.$i, $value['user']['member_designation']);
                                $sheet->cell('L'.$i, Helper::getTotalActiveProduct($value['user']['id']));
                            }
                        }
                    });
                })->download('xlsx');
            }

            if ($request->exportlist == 'product') {
                $category_id = $request->category_name;
                $subcategory_id = $request->subcategory_name;
                $material_id = $request->material_name;
                $template_id = $request->products_name;

                $data =  ProductMaster::with('user', 'user.address_registerd', 'user.userdistrict')->select('user_id')->where(['is_active' => 1, 'is_draft' =>  0,'categoryId'=> $category_id,'subcategoryId'=>$subcategory_id, 'material_id'=> $material_id, 'template_id'=>$template_id])->groupBy('user_id')->get();

                return Excel::create('productnamewise', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('name');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Email');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Mobile');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Type');
                        });

                        //Extra
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('District');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Block');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Address');
                        });
                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Pincode');
                        });
                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Organization Name');
                        });

                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Member Designation');
                        });
                        $sheet->cell('L1', function ($cell) {
                            $cell->setValue('Total Product');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value['user']['id']);
                                $sheet->cell('B'.$i, $value['user']['name']);
                                $sheet->cell('C'.$i, $value['user']['email']);
                                $sheet->cell('D'.$i, $value['user']['mobile']);
                                if ($value['user']['role_id'] == 2) {
                                    $sheet->cell('E'.$i, 'Artisan');
                                } else {
                                    $sheet->cell('E'.$i, 'SHG');
                                }
                                $sheet->cell('F'.$i, $value['user']['userdistrict']['name']);
                                $sheet->cell('G'.$i, $value['user']['userBlock']['name']);
                                $sheet->cell('H'.$i, $value['user']['address_registerd']['address_line_one'].', '.$value['user']['userdistrict']['name']);
                                $sheet->cell('I'.$i, $value['user']['address_registerd']['pincode']);
                                $sheet->cell('J'.$i, $value['user']['organization_name']);
                                $sheet->cell('K'.$i, $value['user']['member_designation']);
                                $sheet->cell('L'.$i, Helper::getTotalActiveProduct($value['user']['id']));
                            }
                        }
                    });
                })->download('xlsx');
            }
        }


        if ($request->has('viewdata')) {
            if ($request->exportlist == 'shg') {
                $query = DB::table('users')
                ->join('states', 'users.state_id', '=', 'states.id')
                ->join('cities', 'users.district', '=', 'cities.id')
                ->leftjoin('blocks', 'users.block', '=', 'blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->select('users.*', 'cities.name as district_name', 'blocks.name as block_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')
                ->where('addresses.address_type', 'registered')
                ->where('users.role_id', 3)->orderBy('users.id', 'desc');
            }
            if ($request->exportlist == 'artisan') {
                //return 'artisan';
                $query = DB::table('users')
                ->join('states', 'users.state_id', '=', 'states.id')
                ->join('cities', 'users.district', '=', 'cities.id')
                ->leftjoin('blocks', 'users.block', '=', 'blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->select('users.*', 'cities.name as district_name', 'blocks.name as block_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')
                ->where('addresses.address_type', 'registered')
                ->where('users.role_id', 2)->orderBy('users.id', 'desc');
            }


            if ($request->exportlist == 'district') {
               $district = $request->district_id;
               
                $query = DB::table('users')
                ->where('users.district', $district)
                ->join('states', 'users.state_id', '=', 'states.id')
                ->join('cities', 'users.district', '=', 'cities.id')
                ->leftjoin('blocks', 'users.block', '=', 'blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->select('users.*', 'cities.name as district_name', 'blocks.name as block_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')
                ->where('addresses.address_type', 'registered')
                ->where('users.role_id', 3)->orderBy('users.id', 'desc');
            }

            //all listed
            if ($request->exportlist == 'all') {
                $shgartisanData = $shgartisanData1;
            }

            if ($request->exportlist == 'category') {
                $category_id = $request->category_name;

                $usersids = ProductMaster::where(['categoryId' => $category_id, 'is_active' => 1, 'is_draft' =>  0])->groupBy('user_id')->pluck('user_id');
                
                $query = DB::table('users')
                ->join('states', 'users.state_id', '=', 'states.id')
                ->leftjoin('cities', 'users.district', '=', 'cities.id')
                ->leftjoin('blocks', 'users.block', '=', 'blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->distinct('users.id')
                ->select('users.*', 'cities.name as district_name','blocks.name as block_name', 'states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')
                ->where('addresses.address_type', 'registered')

                ->whereIn('users.role_id', [2,3])
                ->whereIn('users.id', $usersids)
                ->orderBy('users.id', 'desc');

                $shgartisanData = $query->paginate(10);

                $return_array = array();
                //dd($request->all());
                $allcategortyData = Category::where(['parent_id' => 0, "is_active"=>1])->get();

                
                

                return view('shgartisan.index', ['shgartisanData' => $shgartisanData,'districtList'=>$districtList ,'allcategortyData'=>$allcategortyData]);
            }

            if ($request->exportlist == 'subcategory') {
                $category_id = $request->category_name;
                $subcategory_id = $request->subcategory_name;


                $usersids = ProductMaster::where(['categoryId' => $category_id, 'subcategoryId'=>$subcategory_id, 'is_active' => 1, 'is_draft' =>  0])->groupBy('user_id')->pluck('user_id');


                $query = DB::table('users')
                ->join('states', 'users.state_id', '=', 'states.id')
                ->leftjoin('cities', 'users.district', '=', 'cities.id')
                ->leftjoin('blocks', 'users.block', '=', 'blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->distinct('users.id')
                ->select('users.*', 'cities.name as district_name', 'blocks.name as block_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')
                ->where('users.name', 'LIKE', '%' . Input::get('s') . '%')
                ->where('addresses.address_type', 'registered')
                ->whereIn('users.role_id', [2,3])
                ->whereIn('users.id', $usersids)
                ->orderBy('users.id', 'desc');

                $shgartisanData = $query->paginate(10);





                $allcategortyData = Category::where(['parent_id' => 0, "is_active"=>1])->get();
                $allSubcategortyData = Category::where(['parent_id' => $category_id, "is_active"=>1])->get();

                return view('shgartisan.index', ['shgartisanData' => $shgartisanData,'districtList'=>$districtList,'allcategortyData'=>$allcategortyData,'allSubcategortyData'=>$allSubcategortyData]);



                // $shgartisanData =  ProductMaster::with('user')->select('user_id')->where(['categoryId'=> $category_id,'subcategoryId'=>$subcategory_id])->distinct()->paginate(10);
            }

            if ($request->exportlist == 'material') {
                $category_id = $request->category_name;
                $subcategory_id = $request->subcategory_name;
                $material_id = $request->material_name;


                $usersids = ProductMaster::where(['categoryId' => $category_id, 'subcategoryId'=>$subcategory_id, 'material_id'=> $material_id, 'is_active' => 1, 'is_draft' =>  0])->groupBy('user_id')->pluck('user_id');


                $query = DB::table('users')
                ->join('states', 'users.state_id', '=', 'states.id')
                ->leftjoin('cities', 'users.district', '=', 'cities.id')
                ->leftjoin('blocks', 'users.block', '=', 'blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->distinct('users.id')
                ->select('users.*', 'cities.name as district_name', 'blocks.name as block_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')
                ->where('users.name', 'LIKE', '%' . Input::get('s') . '%')
                ->where('addresses.address_type', 'registered')
                ->whereIn('users.role_id', [2,3,7,8])
                ->whereIn('users.id', $usersids)
                ->orderBy('users.id', 'desc');

                $shgartisanData = $query->paginate(10);
                return view('shgartisan.index', ['shgartisanData' => $shgartisanData,'districtList'=>$districtList]);



                // $shgartisanData =  ProductMaster::with('user')->select('user_id')->where(['categoryId'=> $category_id,'subcategoryId'=>$subcategory_id, 'material_id'=> $material_id])->distinct()->paginate(10);
            }

            if ($request->exportlist == 'product') {
                 //dd($request->products_name);
                $category_id = $request->category_name;
                $subcategory_id = $request->subcategory_name;
                $material_id = $request->material_name;
                $template_id = $request->products_name;
                $usersids = ProductMaster::where(['categoryId' => $category_id, 'subcategoryId'=>$subcategory_id, 'material_id'=> $material_id, 'template_id'=>$template_id, 'is_active' => 1, 'is_draft' =>  0])->groupBy('user_id')->pluck('user_id');
                $query = DB::table('users')
                ->join('states', 'users.state_id', '=', 'states.id')
                ->leftjoin('cities', 'users.district', '=', 'cities.id')
                ->leftjoin('blocks', 'users.block', '=', 'blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->distinct('users.id')
                ->select('users.*', 'cities.name as district_name', 'states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')
                ->where('users.name', 'LIKE', '%' . Input::get('s') . '%')
                ->where('addresses.address_type', 'registered')
                ->whereIn('users.role_id', [2,3,7,8])
                ->whereIn('users.id', $usersids)
                ->orderBy('users.id', 'desc');

                $shgartisanData = $query->paginate(10);
                return view('shgartisan.index', ['shgartisanData' => $shgartisanData, 'districtList'=>$districtList]);





                // $shgartisanData =  ProductMaster::with('user')->select('user_id')->where(['categoryId'=> $category_id,'subcategoryId'=>$subcategory_id, 'material_id'=> $material_id, 'template_id'=>$template_id])->distinct()->paginate(10);
            }
        }
        $filter = $request->exportlist;
        $shgartisanData = $query->paginate(10);
        return view('shgartisan.index', ['shgartisanData' => $shgartisanData, 'filter' => $filter ,'districtList'=>$districtList]);
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
        $shgartisanDetail = User::with('address_registerd', 'userRole', 'products.material', 'products.category', 'country', 'state', 'city')->where('id', $id)->first();

        // echo "<pre>"; print_r($shgartisanDetail); die("chgeck");

        return view('shgartisan.view', ['shgartisanDetail' => $shgartisanDetail]);
    }

    public function edit($id)
    {
    }

    public function update(Request $request, $id)
    {
    }


    public function destroy($id, $status)
    {
        $id =  decrypt($id);
        $userStatus = User::where('id', $id)->first();

        if ($userStatus) {
            $input['isActive'] = $status;
            

            if ($status == 0) {
                $deleteProduct = ProductMaster::where(['user_id' => $id])->update(['is_active' => 0]);
                $block_status = 1;

            } else {
                $deleteProduct = ProductMaster::where(['user_id' => $id])->update(['is_active' => 1]);
                $block_status = 0;
            
            }



            $input['is_blocked_byadmin'] = $block_status;
            $updated = $userStatus->update($input);

            return redirect()->back();
        }




        // $input['isActive'] = $status;
        // $userUpdate  = User::where('id', $id)->first();
        // if ($userUpdate) {
        //     $userUpdate->update($input);
        //     return redirect()->back();
        // }
    }
}
