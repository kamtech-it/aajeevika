<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use File;

use Auth;
use DB;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UploadTrait;



use App\Http\Controllers\Controller;

use App\ProductMaster;
use App\ProductTemplate;

use App\Otphistory;
use App\Category;
use App\User;
use App\Material;
use App\PopularProduct;
use App\Address;
use App\Country;
use App\State;
use App\City;
use App\PopupManager;
use App\Notification;
use App\Pincode;

use Illuminate\Support\Facades\Storage;
use App\Banner;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Excel;
use App\RolePermission;
use App\Permission;
use Session;

class AjaxController extends Controller
{
    public function index()
    {
        $msg = "This is a simple message.";
        return response()->json(array('msg'=> $msg), 200);
    }

    public function get_subcats(Request $request)
    {
        $categoryData = Category::where(['parent_id' => $request->parent_id])->where('is_active',1)->get();
        return response()->json(array('data'=> $categoryData), 200);
    }

    public function get_material(Request $request)
    {
        $materialData = Material::where(['subcategory_id' => $request->id])->get();
        return response()->json(array('data'=> $materialData), 200);
    }

    public function get_products(Request $request)
    {
        $productData = ProductMaster::where(['is_active'=>1, 'is_draft'=>0, 'categoryId' => $request->categoryId, 'subcategoryId'=>$request->subcategoryId])->get();
        return response()->json(array('data'=> $productData), 200);
    }

    public function get_allcategories()
    {
        $categoryData = Category::where(['parent_id' => 0])->where('is_active',1)->get();
        return response()->json(array('data'=> $categoryData), 200);
    }

    public function searchhome(Request $request)
    {
        $langauge = $request->session()->get('weblangauge');
        if (!$langauge) {
            $langauge = 'en';
        }
        $query = 'localname_en';
        $templatequery = 'name_en';
        $productname = 'localname_en as name';
        $categoryname = 'name_en as name';
        $categoryquery = 'name_en';
        $templatename = 'name_en as name';

        if ($langauge == 'kn') {
            $query = 'localname_kn';
            $templatequery = 'name_kn';

            $productname = 'localname_kn as name';

            $categoryquery = 'name_kn';

            $categoryname = 'name_kn as name';
            $templatename = 'name_kn as name';
        }

        $rules = [
            'keyword' => 'required'
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        $keyword = $request->keyword;

        $category = Category::where($categoryquery, 'LIKE', "%{$keyword}%")->where([ 'is_active' => 1, 'parent_id' => 0])->select($categoryname, 'id', 'image', 'slug')->paginate(15);



        $categoryData = json_encode($category);
        $categoryData = json_Decode($categoryData);

        $parentCategory = $categoryData->data;


        $parentCat = [];

        foreach ($parentCategory as $value) {
            $parentCat[] = [
                'catId'     => $value->id,
                'catName'   => $value->name,
                'catImage'  => $value->image,
                'catSlug' => $value->slug,
                'type'      => 'parentCategory'
            ];
        }


        $shgArtisanIds = ProductMaster::where(['is_draft' => 0, 'is_active' => 1])->groupBy('user_id')->pluck('user_id');

        if (count($request->shgartisanId) > 0) {
            $allUserId = $request->shgartisanId;

            $shgArtisanIds = ProductMaster::whereIn('user_id', $allUserId)->where(['is_draft' => 0, 'is_active' => 1])->groupBy('user_id')->pluck('user_id');
        }

        // echo "<pre>"; print_r($shgArtisanIds); die("check");
        // 'role_id' => [ 2,3 ],
        $artisanshg = User::whereIn('id', $shgArtisanIds)->where('name', 'LIKE', "%{$keyword}%")->select('name', 'id', 'profileImage')->paginate(15);

        $artisanData = json_encode($artisanshg);
        $artisanData = json_Decode($artisanData);

        $artisanData = $artisanData->data;

        $artisanArr = [];

        foreach ($artisanData as $value) {
            $artisanArr[] = [
                'artisanshgId'      => $value->id,
                'artisanshgName'    => $value->name,
                'profileImage'      => $value->profileImage,
                'type'              => 'artisanshg'
            ];
        }

        // Search in Product Template


        $templateIds = ProductTemplate::where('status', 1)->where($templatequery, 'LIKE', "%{$keyword}%")->pluck('id');

        $ids = json_encode($templateIds);
        $ids = json_Decode($ids);
        
        
        
        $products = ProductMaster::with('template:id,'.$templatename)
        ->where(['is_draft' => 0, 'is_active' => 1])
        ->where(function ($query1) use ($keyword, $query, $ids) {
            $query1->where($query, 'LIKE', '%'.$keyword.'%');
            $query1->orWhere('id', '=', $keyword);
            $query1->where(['is_draft' => 0, 'is_active' => 1]);
            $query1->orWhereIn('template_id', $ids);
        })
        
        ->select($productname, 'subcategoryId', 'price', 'id', 'image_1', 'template_id', 'is_active')
        ->paginate(15);
        




        if (count($request->shgartisanId) > 0) {
            $allUserId = $request->shgartisanId;
            $products = ProductMaster::with('template:id,'.$templatename)
            ->where(['is_draft' => 0, 'is_active' => 1])
            ->where(function ($query1) use ($keyword, $query ,$ids) {
                $query1->where($query, 'LIKE', '%'.$keyword.'%');
                $query1->orWhere('id', '=', $keyword);
                $query1->where(['is_draft' => 0, 'is_active' => 1]);
                $query1->orWhereIn('template_id', $ids);
            })
            // ->orWhereIn('template_id', $ids)
            ->select($productname, 'subcategoryId', 'price', 'id', 'image_1', 'template_id', 'is_active')
            ->paginate(15);
        }

        $productData = json_encode($products);
        $productData = json_Decode($productData);

        $paginationData = [
            'current_page' => $productData->current_page,
            'last_page'    => $productData->last_page,
            'per_page'     => $productData->per_page
        ];
        $productData = $productData->data;

        $productArr = [];
        foreach ($productData as $value) {
            $productArr[] = [
                'productId'      => $value->id,
                'productName'    => $value->name,
                'price'          => $value->price,
                'image_1'        => $value->image_1,
                'subcategoryId'  => $value->subcategoryId,
                'template'       => $value->template,
                'type'           => 'product'
            ];
        }





        $queryStatus    = "No Product found.";
        $statusCode     = 400;
        $status         = false;

        // $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        // if(count($products) > 0) {
        $queryStatus    = "All Products.";
        $statusCode     = 200;
        $status         = true;

        $product = [
                'type' => 'product',
                'data' => $productArr
            ];

        if (count($product['data']) == 0) {
            $recentlyproduct = [];
        }

        $artisan = [
                'type' => 'artisanshg',
                'data' => $artisanArr
            ];

        if (count($artisan['data']) == 0) {
            $artisan = [];
        }

        $maincat = [
                'type' => 'parentCategory',
                'data' => $parentCat
            ];

        if (count($maincat['data']) == 0) {
            $maincat = [];
        }

        $searchresult = [$product, $artisan,  $maincat];

        $newData = [];
        foreach ($searchresult as $value) {
            if ($value) {
                $newData[] = $value;
            }
        }

        $html = "";
        foreach ($newData as $item) {

           

            if ($item['type'] == "product" && !empty($item['data'])) {
                $html .= "<div class='lehangas-outer enterprise-outer'><div class='container'><div class='product-heading'><div class='row align-items-center'><div class='col-md-12'><h2>Products</h2></div></div></div><div class='lehangas-product'><div class='row'>";


                foreach ($item['data'] as $product) {
                    $html .= "<div class='col-md-12'><div class='lehangas-product-inner d-flex align-items-center'><div class='lehanga-img'>";
                    $html .="<a href='".url('product')."/".encrypt($product['productId'])."'>";
                    $html .="<img src='".asset($product['image_1'])."' alt='lehanga-img1' /></a>";
                    $html .="</div><div class='lehanga-right'>";
                    $html .="<a href='".url('product')."/".encrypt($product['productId'])."'>";
                    $html .="<p class='item-info'>".$product['template']->name."<br>(".$product['productName'].")</p><p>Pdx ID-". sprintf("%'.06d\n", $product['productId'])."</p></a>";
                    $html .="<p><strong>₹".$product['price']."</strong></p></div></div></div>";
                }
                $html .="</div></div></div></div>";
            }




            if ($item['type'] == "artisanshg") {
                //dd($item['data']);
                $html .= "<div class='lehangas-outer enterprise-outer'><div class='container'><div class='product-heading'><div class='row align-items-center'><div class='col-md-12'><h2>Shg/Artisans</h2></div></div></div><div class='lehangas-product'><div class='row'>";


                foreach ($item['data'] as $shg) {
                    $html .= "<div class='col-md-12'><div class='lehangas-product-inner d-flex align-items-center'><div class='lehanga-img'>";
                    if ($shg['profileImage'] != null) {
                        $html .="<a href='".url('shgstrisan')."/".encrypt($shg['artisanshgId'])."'><img src='".asset($shg['profileImage'])."' alt='lehanga-img1' /></a>";
                    } else {
                        $html .="<a href='".url('shgstrisan')."/".encrypt($shg['artisanshgId'])."'><img src='".asset('assets/images/urs-img.png')."' alt='lehanga-img1' /></a>";
                    }
                    $html .="</div><div class='lehanga-right'>";
                    $html .="<a href='".url('shgstrisan')."/".encrypt($shg['artisanshgId'])."'>";
                    $html .="<p class='item-info'>".$shg['artisanshgName']."</p></a>";
                    $html .="</div></div></div>";
                }
                $html .="</div></div></div></div>";
            }
            if ($item['type'] == "parentCategory") {
                $html .= "<div class='lehangas-outer enterprise-outer'><div class='container'><div class='product-heading'><div class='row align-items-center'><div class='col-md-12'><h2>Categories</h2></div></div></div><div class='lehangas-product'><div class='row'>";


                foreach ($item['data'] as $product) {
                    $html .= "<div class='col-md-12'><div class='lehangas-product-inner d-flex align-items-center'><div class='lehanga-img'>";
                    $html .="<a href='".url('category')."/".$product['catSlug']."'>";
                    $html .="<img src='".asset($product['catImage'])."' alt='lehanga-img1' /></a>";
                    $html .="</div><div class='lehanga-right'>";
                    $html .="<a href='".url('category')."/".$product['catSlug']."'>";
                    $html .="<p class='item-info'>".$product['catName']."</p></a>";
                    $html .="</div></div></div>";
                }
                $html .="</div></div></div></div>";
            }
        }


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [
                                    // 'products' => $products
                                        'pagination' => $paginationData,
                                        'searchresult' => $newData,
                                        'html' => $html

                                    ]);
        // }


        return response()->json($response, 201);
    }



    public function getallproduct(Request $request)
    {
        $language = $request->session()->get('weblangauge');
        $user = $request->session()->get('user');
        $shgid = $request->session()->get('shgid');
        // dd($shgid);

        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($language == 'kn') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }

        // ->select('id', 'localname_en', 'localname_kn', 'price', 'image_1')


        //changes for geo location

        // $alluser = ProductMaster::where(['is_active' => 1, 'is_draft' => 0])->select('user_id')->groupBy('user_id')->orderBy('user_id', 'desc')->paginate(10);

        // $users = [];
        // foreach ($alluser as $value) {
        //     $users[] = $value->user_id;
        // }


        if (count($request->shgartisanId) > 0) {
            $allUserId = $request->shgartisanId;
            $alluser = ProductMaster::whereIn('user_id', $allUserId)->where(['is_active' => 1, 'is_draft' => 0])->select('user_id')->groupBy('user_id')->orderBy('user_id', 'desc')->paginate(10);

            $users = [];
            foreach ($alluser as $value) {
                $users[] = $value->user_id;
            }
        } else {
            $alluser = ProductMaster::where(['is_active' => 1, 'is_draft' => 0])->select('user_id')->groupBy('user_id')->orderBy('user_id', 'desc')->paginate(10);

            $users = [];
            foreach ($alluser as $value) {
                $users[] = $value->user_id;
            }
        }


        $shgproduct = User::wherein('id', $users)->whereNotIn('id', $shgid)->where('isActive', 1)->orderBy('id', 'desc')->get();
        // ->has('shgproduct', '>',0)->with('shgproduct')->select('id', 'name', 'title')->orderBy('id', 'desc')->get();



        $getData = json_encode($alluser);
        $getData = json_Decode($getData);

        $pagination['currentPage'] = $getData->current_page;
        $pagination['per_page']    = $getData->per_page;
        $pagination['last_page']            = $getData->last_page;

        // echo "<pre>"; print_r($shgproduct);
        // die("check");


        $filteredCollection = $shgproduct->filter(function ($store) {
            return $store->shgproduct->count() > 0;
        });

        /**\
         * New Product According to new query
         */

        $allProduct = [];
        $i = 0;
        foreach ($shgproduct as $key => $value) {
            $store = [];
            $productdata = [];
            // $store['title'] = $value->title;
            $store['name']  = $value->name;
            $store['type']  = 'shg-artisan';
            $store['id']  = $value->id;



            $product = ProductMaster::where(['user_id' => $value->id, 'is_active' => 1, 'is_draft' => 0])->select('id', 'image_1', 'price', 'template_id', 'localname_kn', 'localname_en')->limit(3)->get();



            foreach ($product as $item) {
                $p = [];
                $p['id']        = $item->id;
                $p['image_1']   = $item->image_1;
                $p['price']     = $item->price;
                $p['template_id'] = $item->template_id;

                $template = ProductTemplate::where('id', $item->template_id)->select('id', $templatename)->first();

                $p['template']    = $template;

                if ($language == 'kn') {
                    $p['name']      = $item->localname_kn;
                } else {
                    $p['name']      = $item->localname_en;
                }
                $productdata[] = $p;
            }
            $store['data']  = $productdata;
            $allProduct[] = $store;

            $i++;
        }


        /*
        End
        */

        $shguser = User::wherein('role_id', [ 2, 3 ])->whereNotIn('id', $shgid)->select('title', 'id')->where('isActive', 1)->get();

        $pageData = [];

        foreach ($shguser as $page) {
            $check = ProductMaster::where(['is_active' => 1, 'is_draft' => 0, 'user_id' => $page->id])->first();
            if ($check) {
                $pageData[] = $page;
            }
        }

        $totalpage = ceil(count($pageData) / 1);

        $shgArr = [];
        // $store= [];

        foreach ($shguser as $key => $value) {
            $store= [];
            $productCheck = ProductMaster::where(['is_active' => 1, 'is_draft' => 0, 'user_id' => $value->id])->select($productname, 'price', 'id', 'image_1')->take(3)->get();


            if (count($productCheck) > 0) {
                $store['title'] = $value->title;
                $store['type'] = 'shg-artisan';
                $store['id'] = $value->id;
                $store['data'] = $productCheck;
                $shgArr[] = $store;
            }
        }

        // echo "<pre>"; print_r(count($shgArr[1]['data'])); die("check");
        $products = [];
        if (count($shgArr) > 0) {
            // $shgArr = $shgArr[0];
            foreach ($shgArr as $value) {
                $products[] = $value;
            }
        }

        $queryStatus    = "Product not found.";
        $statusCode     = 200;
        $status         = true;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                            );

        if (count($shgArr) > 0) {
            $queryStatus    = "AllProduct.";
            $statusCode     = 200;
            $status         = true;

            // $datashg = $this->paginate($shgArr);

            $html = "";
            $count = count($allProduct);
            foreach ($allProduct as $item) {
                $html .= "<div class='lehangas-outer enterprise-outer'><div class='container'><div class='product-heading'><div class='row align-items-center'><div class='col-sm-8 col-8'><h2>".$item['name']."</h2></div><div class='col-sm-4 col-4 text-right'><a href='".url('shgstrisan/' . $item['id'])."' class='btn'>View More</a></div></div></div><div class='lehangas-product'><div class='row'>";
                foreach ($item['data'] as $product) {
                    $html .= "<div class='col-12 col-sm-6 col-md-4'><div class='lehangas-product-inner d-flex align-items-center'><div class='lehanga-img'>";
                    $html .="<a href='".url('product')."/".encrypt($product['id'])."'>";
                    $html .="<img src='".asset($product['image_1'])."' alt='lehanga-img1' /></a>";
                    $html .="</div><div class='lehanga-right'>";
                    $html .="<a href='".url('product')."/".encrypt($product['id'])."'>";
                    $html .="<p class='item-info'>".$product['template']['name']."<br>(".$product['name'].")</p><p>Pdx ID-".sprintf("%'.06d\n", $product['id'])."</p></a>";
                    $html .="<p><strong>₹".$product['price']."</strong></p></div></div></div>";
                }
                $html .="</div></div></div></div><div class='border-design'></div>";
            }


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [
                                            'pagination'   => $pagination,
                                            'data'    => $allProduct,
                                            'html' => $html,
                                            'count'  => $count
                                        ]);
        }

        return response()->json($response, 201);
    }

    public function changeroletype(Request $request)
    {
        $updateuserstatus = User::where('id', Auth::user()->id)->update([ 'role_id' => $request->role_id ]);
        return response()->json(array('status'=>'done'), 200);
    }

    public function checkpincode(Request $request)
    {
        $pincheck = Pincode::where(['pin_code' => $request->pin_code])->first();
        if ($pincheck == null) {
            return response()->json(array('status'=>false), 200);
        } else {
            return response()->json(array('status'=>true), 200);
        }
    }


    public function getstate(Request $request)
    {
        $response = array();
        $country_id = $request->country_id;

        $states = DB::table('states')
                    ->where('country_id', '=', $country_id)
                    ->get();
        $response['status'] = true;
        $response['statusCode'] = 200 ;
        $response['message'] = "success";
        $response['data'] = ['states' => $states];
        return response()->json($response, 201);
    }


    public function resendotp(Request $request)
    {
        $rules = [
            'mobile'     => 'required',
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400 );
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        if ($request->type == 'updatemobile') {
            $checkmobile = User::where(['mobile' => $request->mobile, 'isActive' => 1])->first();
            if ($checkmobile) {
                $response = array('status' => false , 'statusCode' =>400 );
                $response['message'] = "Mobile number already exists";
                return response()->json($response);
            }
        }

        $otp            = rand(1111, 9999);
        $otp            = 1234;



        $queryStatus;

        $otpUpdateStatus = Otphistory::where(['mobile_no'=> $request->mobile ])->update(['status' => 0]);



        $otpStatus = Otphistory::create(['mobile_no' => $request->mobile, 'otp' => $otp ]);

        $this->sendotp($request->mobile, $otp);

        $queryStatus = "OTP Sent.";
        $statusCode = 200;
        $status = true;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,  'data' => [ 'otp' => $otp] );

        return response()->json($response, 201);
    }

    /**
     * Send OTP
     */

    public function sendotp($mobile, $otp)
    {
        $username="Mobile_1-KSLKAR";
        $password="kslkar@1234";
        $senderid="KSLKAR";
        $message="Please verify your mobile using this OTP " .$otp;
        // $messageUnicode="à¤®à¥‹à¤¬à¤¾à¤‡à¤²à¤¸à¥‡à¤µà¤¾à¤®à¥‡à¤‚à¤†à¤ªà¤•à¤¾à¤¸à¥à¤µà¤¾à¤—à¤¤à¤¹à¥ˆ "; //message content in unicode
        $mobileno = $mobile; //if single sms need to be send use mobileno keyword
        // $mobileNos= "9587777762,8766068415"; //if bulk sms need to send use mobileNos as keyword and mobile number seperated by commas as value
        $deptSecureKey= "b36befee-c007-4749-b6da-d8a0d6ca5e41"; //departsecure key for encryption of message...
        $encryp_password=sha1(trim($password));


        $key=hash('sha512', trim($username).trim($senderid).trim($message).trim($deptSecureKey));

        $url = "https://msdgweb.mgov.gov.in/esms/sendsmsrequestDLT";

        $data = array(
            "username"        => trim($username),
            "password"        => trim($encryp_password),
            "senderid"        => trim($senderid),
            "content"         => trim($message),
            "smsservicetype"  =>"otpmsg",
            "mobileno"        =>trim($mobileno),
            "key"             => trim($key)
         );

        $fields = '';

        foreach ($data as $key => $value) {
            $fields .= $key . '=' . urlencode($value) . '&';
        }
        rtrim($fields, '&');
        $post = curl_init();
        //curl_setopt($post, CURLOPT_SSLVERSION, 5); // uncomment for systems supporting TLSv1.1 only
         curl_setopt($post, CURLOPT_SSLVERSION, 6); // use for systems supporting TLSv1.2 or comment the line
         curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($post, CURLOPT_URL, $url);
        curl_setopt($post, CURLOPT_POST, count($data));
        curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($post); //result from mobile seva server
     echo $result; //output from server displayed
     die;
        curl_close($post);
    }


    public function exportMasterReport(Request $request)
    {
        $query = ProductMaster::with('category', 'subcategory', 'material', 'template', 'user.address.district', 'user.district', 'user.address.state')
        ->where('is_active', '=', '1')->where('is_draft', '=', '0')->get();


        $exportArray = array();


        // die('asdf');

        $data = $query;
        return Excel::create('MasterReport', function ($excel) use ($data) {
            $excel->sheet('mySheet', function ($sheet) use ($data) {
                $sheet->cell('A1', function ($cell) {
                    $cell->setValue('User ID');
                });
                $sheet->cell('B1', function ($cell) {
                    $cell->setValue('Name');
                });
                $sheet->cell('C1', function ($cell) {
                    $cell->setValue('Type');
                });
                $sheet->cell('D1', function ($cell) {
                    $cell->setValue('Mobile');
                });
                $sheet->cell('E1', function ($cell) {
                    $cell->setValue('Email');
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
                    $cell->setValue('Category');
                });

                $sheet->cell('K1', function ($cell) {
                    $cell->setValue('Sub Category');
                });


                $sheet->cell('L1', function ($cell) {
                    $cell->setValue('Template Name');
                });

                $sheet->cell('M1', function ($cell) {
                    $cell->setValue('Product Name');
                });
                $sheet->cell('N1', function ($cell) {
                    $cell->setValue('Price');
                });

                $sheet->cell('O1', function ($cell) {
                    $cell->setValue('Quantity');
                });
                $sheet->cell('P1', function ($cell) {
                    $cell->setValue('Unit');
                });
                $sheet->cell('Q1', function ($cell) {
                    $cell->setValue('Product Type');
                });


                if (!empty($data)) {
                    foreach ($data as $key => $value) {
                        $i= $key+2;
                        $sheet->cell('A'.$i, $value['user']['id']);
                        $sheet->cell('B'.$i, $value['user']['name']);

                        if ($value['user']['role_id'] == 2) {
                            $sheet->cell('C'.$i, "CLF");
                        } else {
                            $sheet->cell('C'.$i, "SHG Enterprise");
                        }



                        $sheet->cell('D'.$i, $value['user']['mobile']);
                        $sheet->cell('E'.$i, $value['user']['email']);


                        $address = Address::where(['user_id' => $value['user']['id'], 'address_type' => 'registered'])->first();


                        $district = City::where('id', $address['district'])->first();



                        $sheet->cell('F'.$i, $district['name']);
                        $sheet->cell('G'.$i, $value['user']['userBlock']['name']);




                        $sheet->cell('H'.$i, $address['address_line_one']);
                        $sheet->cell('I'.$i, $address['pincode']);









                        $sheet->cell('J'.$i, $value['category']['name_en']);
                        $sheet->cell('K'.$i, $value['subcategory']['name_en']);
                        $sheet->cell('L'.$i, $value['template']['name_en']);
                        $sheet->cell('M'.$i, $value['localname_en']);
                        $sheet->cell('N'.$i, $value['price']);
                        $sheet->cell('O'.$i, $value['qty']);
                        $sheet->cell('P'.$i, $value['price_unit']);
                        $sheet->cell('Q'.$i, $value['material']['name_en']);
                    }
                }
            });
        })->download('xlsx');
    }

    /** 
     * Get Blocks on district change
     * user  document verify form
     */
    public function getBlockByCityId(Request $request){

        $language = Session::get('weblangauge');
       
        $name = 'name';
        if($language == 'kn') {
            $name = 'name_kn as name';
        }
        $response = array();
        $html = "";
        $city_id = $request->city_id;
        $cities = DB::table('blocks')
            ->where('city_id', '=', $city_id)
            ->where('status', 0)
            ->select($name, 'id', 'city_id')
            ->get();
        
        foreach($cities as $block){
            $html .= '<option value="'.$block->id.'"> '.$block->name.'</option>';
        }
        $response['status'] = true;
        $response['statusCode'] = 200 ;
        $response['message'] = "success";
        $response['html'] = $html;
        return response()->json($response, 201);
    }

}
