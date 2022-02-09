<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use File;
use App\ProductMaster;
use App\PopularProduct;
use App\Category;
use Auth;
use App\RolePermission;
use App\Permission;

class PopularProductController extends Controller
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
            if (!in_array('/admin/popularproducts', $permission)) {
                return redirect('admin');
            }
            return $next($request);
        });
    }
    public function index()
    {
        $productData = PopularProduct::with('product.template')->get();
        return view('popular.index', ['productData' => $productData]);
    }

    public function create()
    {
        $categoryData = Category::where(['parent_id' => 0, 'is_active' => 1])->get();
        return view('popular.add', ['categoryData'=>$categoryData]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id'=>'unique:popular_products',
        ]);
        if ($validator->fails()) {
            return redirect('admin/addpopular')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $input = array();
            $input['product_id'] = $request->product_id;
            $input['admin_id'] = Auth::user()->id;

            $popularProducts = PopularProduct::create($input);
            return redirect('admin/popularproducts');
        }
    }

    public function show($id)
    {
    }

    public function edit($id)
    {
    }

    public function update(Request $request, $id)
    {
    }

    public function removepopular($id)
    {   
        $id = decrypt($id);

        $pp     = PopularProduct::where('id', $id)->first();
        $pops   = $pp->delete();
        return redirect()->back();
    }
}
