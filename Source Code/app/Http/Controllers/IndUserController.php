<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\User;
use App\Role;
use Auth;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Input;
use Excel;
use App\RolePermission;
use App\Permission;
class IndUserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
            if (!in_array('/admin/users', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }
    public function index(Request $request)
    {
        // $query = DB::table('users')
        //     ->join('states', 'users.state_id', '=', 'states.id')
        //     ->join('cities', 'users.district', '=', 'cities.id')
        //     ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
        //     ->select('users.*', 'cities.name as district_name', 'states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two')
        //     ->where('addresses.address_type', 'personal')
        //     ->where('users.role_id', 1)->orderBy('users.id', 'desc');

     //   $user = DB::table('users')->where('role_id','9')->get();
      //  print_r($user);die;

        $query = DB::table('users')
            ->leftjoin('states', 'users.state_id', '=', 'states.id')
            ->leftjoin('cities', 'users.district', '=', 'cities.id')
            ->leftJoin('blocks','users.block','=','blocks.id')
            ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
            ->select('users.*', 'cities.name as district_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two',
                     'blocks.name as block_name')
            ->where('users.role_id', '9')->orderBy('users.id', 'desc');





        if ($request->has('s')) {
            $query->where('users.name', 'LIKE', '%' . Input::get('s') . '%');
          //  $query->orWhere('users.email', Input::get('s'));
           // $query->orWhere('users.mobile', Input::get('s'));
            $query->where('users.role_id',9);

           
        }
        $userData1  =  $query->get()->toArray();


        $userData  =  $query->paginate(10);
       // print_r($userData);die;

        // echo "<pre>"; print_r($userData); die("check");
        if ($request->has('exportdata')) {
            //all listed
            if ($request->exportlist == 'all') {
                $data = $userData1;
              //  print_r($data);die;
                return Excel::create('allusers', function ($excel) use ($data) {
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
                 /*     $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Type');
                        }); */
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('District');
                        });
                   /*     $sheet->cell('F1', function ($cell) {
                            $cell->setValue('Village');
                        }); */
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('Block');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('State');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Address');
                        });

                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->name);
                                $sheet->cell('C'.$i, $value->email);
                                $sheet->cell('D'.$i, $value->mobile);
                                $sheet->cell('E'.$i, $value->district_name);
                                //$sheet->cell('F'.$i, 'NA');
                                $sheet->cell('F'.$i, $value->block_name);

                                $sheet->cell('G'.$i, $value->state_name);
                                $sheet->cell('H'.$i, $value->address_line_one.' '.$value->address_line_two.' '.$value->pincode);


                            }
                        }
                    });
                })->download('xlsx');
            }

            if ($request->exportlist == 'state') {
                $innerquery = User::whereIn('users.role_id', [9])->orderBy('id', 'DESC');
                $innerquery->where('state_id', '=', Input::get('state_name'));
                
                $userData1  =  $innerquery->get();
                


                $data = $userData1;
                return Excel::create('statesusers', function ($excel) use ($data) {
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

                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->name);
                                $sheet->cell('C'.$i, $value->email);
                                $sheet->cell('D'.$i, $value->mobile);
                                $sheet->cell('E'.$i, 'User');
                            }
                        }
                    });
                })->download('xlsx');
            }

            if ($request->exportlist == 'district') {
                $innerquery = User::whereIn('users.role_id', [9])->orderBy('id', 'DESC');
                $innerquery->where('users.district', '=', Input::get('district_name'));
                $innerquery->leftjoin('states', 'users.state_id', '=', 'states.id')
                ->leftjoin('cities', 'users.district', '=', 'cities.id')
                ->leftJoin('blocks','users.block','=','blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->select('users.*', 'cities.name as district_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two',
                         'blocks.name as block_name');
                $userData1  =  $innerquery->get();

                $data = $userData1;
                return Excel::create('districtusers', function ($excel) use ($data) {
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
                            $cell->setValue('District');
                        });
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('Block');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Address');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Pincode');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->name);
                                $sheet->cell('C'.$i, $value->email);
                                $sheet->cell('D'.$i, $value->mobile);
                                $sheet->cell('E'.$i, $value->district_name);
                                $sheet->cell('F'.$i, $value->block_name);
                                $sheet->cell('G'.$i, $value->address_line_one,$value->address_line_two );
                                $sheet->cell('H'.$i, $value->pincode);
                                
                            }
                        }
                    });
                })->download('xlsx');
            }
        }



        if ($request->has('viewdata')) {
            //all listed
            if ($request->exportlist == 'all') {
                $userData = $userData;
                //dd($userData);

            }

            if ($request->exportlist == 'state') {

                $innerquery = User::whereIn('users.role_id', [9])->orderBy('id', 'DESC');
                $innerquery->where('users.state_id', '=', Input::get('state_name'));
                $innerquery->where('users.role_id',9);
                $userData1  =  $innerquery->paginate(10);
                $userData = $userData1;

            }

            if ($request->exportlist == 'district') {
                $innerquery = User::whereIn('users.role_id', [9])->orderBy('id', 'DESC');
                $innerquery->where('users.district', '=', Input::get('district_name'));
                $innerquery->leftjoin('states', 'users.state_id', '=', 'states.id')
                ->leftjoin('cities', 'users.district', '=', 'cities.id')
                ->leftJoin('blocks','users.block','=','blocks.id')
                ->leftjoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->select('users.*', 'cities.name as district_name','states.name as state_name', 'addresses.pincode', 'addresses.address_line_one', 'addresses.address_line_two',
                         'blocks.name as block_name');
                $userData1  =  $innerquery->paginate(10);

                $userData = $userData1;
               // dd($userData);

            }
        }

        $states     = DB::table('states')->where('id', '=', 39)->get();
        $cities = DB::table('cities')
            ->where('state_id', '=', 39)
            ->where('is_district', 1)
            ->select('name', 'id', 'state_id')
            ->get();


        return view('induser.index', ['userData' => $userData, 'stateList'=>$states,'cities'=>$cities]);
    }


    public function blockUser($id, $status)
    {
        $id =  decrypt($id);
        $userStatus = User::where('id', $id)->first();
        if ($userStatus) {
            $input['isActive'] = $status;

            if ($status == 0) {
                $block_status = 1;
            } else {
                $block_status = 0;
            }



            $input['is_blocked_byadmin'] = $block_status;
            $updated = $userStatus->update($input);

            return redirect()->back()->withErrors(['msg', 'The Message']);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
