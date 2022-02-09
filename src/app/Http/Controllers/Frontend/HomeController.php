<?php

namespace App\Http\Controllers\Forntend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Banner;
use Validator;
use App\ProductMaster;
use App\ProductTemplate;
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
use DB;
use Auth;
use App\Otphistory;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

use GuzzleHttp\Client as GuzzleClient;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private $headers;

    public function __construct()
    {
        $this->headers = [
            'Content-Type' => 'application/json',
            'app-key' => 'laravelUNDP'
        ];
    }

    public function index(Request $request)
    {

        // echo "asdf";
        // die;
        $language = $request->session()->get('weblangauge');

        if ($language == "") {
            $language = 'en';
        }

        $user = $request->session()->get('user');
        $catname = 'name_en  as name';
        $templatename = 'name_en as name';
        $productname = 'localname_en as name';

        if ($language == 'kn') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }
        $banner      = Banner::where('status', 1)->orderBy('id', 'DESC')->select('id', 'image', 'action')->take(5)->get();
        $midCategory = Category::select('id', $catname, 'image', 'slug')->where(['parent_id' => 0, 'is_active' => 1])->take(7)->get();
        $popularproduct = PopularProduct::where('status', 1)->select('id', 'product_id')->orderBy('id', 'DESC')->take(6)->get();

        // dd( $popularproduct);

        $popularArr = [];
        foreach ($popularproduct as $value) {
            $popularArr[] = $value->product_id;
        }

        // dd( $popularArr);

        $popularproducts = ProductMaster::wherein('id', $popularArr)
        ->where(['is_active' => 1, 'is_draft' => 0])
        ->with('template:id,'.$templatename)
        ->select($productname, 'price', 'id', 'image_1', 'template_id')
        ->get();


        /** Start SHG / Artisan Product */
        /**
         * 16 Jan 2021
         *
         */
        $alluser = ProductMaster::where(['is_active' => 1, 'is_draft' => 0])
                    ->select('user_id')->groupBy('user_id')->orderBy('user_id', 'desc')->limit(3)->get();
        $users = [];

        foreach ($alluser as $value) {
            $users[] = $value->user_id;
        }

        if (count($request->shgartisanId) > 0) {
            $activeData = $request->shgartisanId;
            if (isset($activeData[0])) {
                $users[0] = $activeData[0];
            }
            if (isset($activeData[1])) {
                $users[1] = $activeData[1];
            }
            if (isset($activeData[2])) {
                $users[2] = $activeData[2];
            }
        }

        $shgproduct = User::wherein('id', $users)->where('isActive', 1)->orderBy('id', 'desc')->get();



        // $shgproduct = User::wherein('id', $users)->where('isActive', 1)->has('shgproduct')->with('shgproduct')->select('id', 'name', 'title')->orderBy('id', 'desc')->get();

        // $filteredCollection = $shgproduct->filter(function($store) {
        //     return $store->shgproduct->count() > 0;
        // });




        $allProduct = [];
        foreach ($shgproduct as $value) {
            $store = [];
            $productdata = [];
            $store['title'] = $value->title;
            $store['name']  = $value->name;
            $store['type']  = 'shg-artisan';
            $store['id']  = $value->id;

            $product = ProductMaster::where(['user_id' => $value->id, 'is_active' => 1, 'is_draft' => 0])
                    ->select('id', 'image_1', 'price', 'template_id', 'localname_kn', 'localname_en')->limit(3)->get();

            foreach ($product as $item) {
                $p = [];
                $p['id']        = $item->id;
                $p['image_1']   = $item->image_1;
                $p['price']     = $item->price;
                $p['template_id'] = $item->template_id;
                $template = ProductTemplate::where('id', $item->template_id)->select('id', $templatename)->first();
                $p['template']    = $template;
                if ($language == 'kn') {
                    $p['name']  = $item->localname_kn;
                } else {
                    $p['name']  = $item->localname_en;
                }
                $productdata[] = $p;
            }
            $store['data']  = $productdata;
            $allProduct[] = $store;
        }



        // echo "<pre>"; print_r($allProduct); die("check");

        /**
         * End
         */

        $shguser = User::wherein('role_id', [ 2, 3 ])->select('title', 'id')->where('isActive', 1)->take(5)->get();

        $shgArr = [];

        foreach ($shguser as $key => $value) {
            $productCheck = ProductMaster::where(['is_active' => 1, 'is_draft' => 0, 'user_id' => $value->id])->select($productname, 'price', 'id', 'image_1')->take(3)->get();


            if (count($productCheck) > 0) {
                $shgData = [
                    'title' => $value->title,
                    'type'  => 'shg-artisan',
                    'id'    => $value->id,
                    'data'  => $productCheck
                ];
                $shgArr[] = $shgData;
            }
        }

        // $shg = array_values($shgArr)



        if (count($shgArr) > 0) {
            $shgArr = $shgArr[0];
        }




        /** Start Recently Product */
        $recentlyproduct = ProductMaster::where(['is_active' => 1, 'is_draft' => 0])->orderBy('id', 'desc')->select($productname, 'price', 'id', 'image_1', 'template_id')->with('template:id,'.$templatename)->take(5)->get();


        /** Start SHG / Artisan Product */

        $lengthshg = count($shguser);
        if ($lengthshg > 0) {
            $position = $lengthshg - 1;

            if ($shguser[$position]->id) {
                $shgId = $shguser[$position]->id;

                $shguser = User::wherein('role_id', [ 2, 3 ])->select('title', 'id')
                ->where('isActive', 1)->where('id', '>', $shgId)->take(2)->get();

                $shg2ndArr = [];

                foreach ($shguser as $key => $value) {
                    $productCheck = ProductMaster::where(['is_active' => 1, 'is_draft' => 0, 'user_id' => $value->id])->select($productname, 'price', 'id', 'image_1')->take(3)->get();

                    if (count($productCheck) > 0) {
                        $shg2ndArr[$key]['title'] = $value->title;
                        $shg2ndArr[$key]['products'] = $productCheck;
                    }
                }
            }
        }




        $queryStatus    = "Home Screen.";
        $statusCode     = 200;
        $status         = true;

        // dd($popularproducts);

        $popularproduct = [
            'type' => 'popular',
            'title'=> 'Popular Products',
            // 'id'   => 1,
            'data' => $popularproducts
        ];

        if (count($popularproduct['data']) == 0) {
            $popularproduct = [];
        }

        $recentlyproduct = [
            'type' => 'recently',
            'title'=> 'Recently Added',
            // 'id'   => 1,
            'data' => $recentlyproduct
        ];

        if (count($recentlyproduct['data']) == 0) {
            $recentlyproduct = [];
        }

        $data = [

                'shgartisans'       => $shgArr

        ];


        // dd($allProduct);

        $countofshg = count($allProduct);
        $shgproduct1 = [];
        if ($countofshg > 0) {
            $shgproduct1 = $allProduct[0];
        }
        $shgproduct2 = [];
        if ($countofshg > 1) {
            $shgproduct2 = $allProduct[1];
        }

        $shgproduct3 = [];
        if ($countofshg > 2) {
            $shgproduct3 = $allProduct[2];
        }

        $shgids = [];
        foreach ($allProduct as $ids) {
            $shgids[] = $ids['id'] ;
        }
        session(['shgid' => $shgids]);





        $checkData = [ $popularproduct, $shgproduct1, $shgproduct2, $recentlyproduct, $shgproduct3 ];
        $newData = [];
        foreach ($checkData as $value) {
            if ($value) {
                $newData[] = $value;
            }
        }


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                            'data' =>  $newData);



        $popup = PopupManager::where(['status' => 1, 'language' => $language])->select('id', 'action', 'background_image', 'description', 'title')->first();


        return view('frontend.home', ['language'=>$language,'banner'=>$banner, 'midCategory'=> $midCategory,'response'=>$response, 'popup'=>$popup, 'shgids'=>$shgids]);
    }

    public function moreproducts(Request $request)
    {
        $language = $request->session()->get('weblangauge');
        $user = $request->session()->get('user');
        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';
        if ($language == 'kn') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }

        $alluser = ProductMaster::where(['is_active' => 1, 'is_draft' => 0])->select('user_id')->groupBy('user_id')->orderBy('user_id', 'desc')->paginate(10);

        $users = [];
        foreach ($alluser as $value) {
            $users[] = $value->user_id;
        }
        $shgproduct = User::wherein('id', $users)->where('isActive', 1)->orderBy('id', 'desc')->get();
        $getData    = $alluser;
        //$getData    = json_Decode($getData);


        //dd($getData);

        // $pagination['currentPage'] = $getData->current_page;
        // $pagination['per_page']    = $getData->per_page;
        // $pagination['last_page']   = $getData->last_page;

        $filteredCollection = $shgproduct->filter(function ($store) {
            return $store->shgproduct->count() > 0;
        });

        $allProduct = [];
        $i = 0;
        foreach ($shgproduct as $key => $value) {
            $store = [];
            $productdata = [];
            $store['name']  = $value->name;
            $store['type']  = 'shg-artisan';
            $store['id']  = $value->id;
            $product = ProductMaster::where(['user_id' => $value->id, 'is_active' => 1, 'is_draft' => 0])->select('id', 'image_1', 'price', 'template_id', 'localname_kn', 'localname_en')->limit(5)->get();
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
        $shguser = User::wherein('role_id', [ 2, 3 ])->select('title', 'id')->where('isActive', 1)->get();
        $pageData = [];

        foreach ($shguser as $page) {
            $check = ProductMaster::where(['is_active' => 1, 'is_draft' => 0, 'user_id' => $page->id])->first();
            if ($check) {
                $pageData[] = $page;
            }
        }

        $totalpage = ceil(count($pageData) / 1);
        $shgArr = [];
        foreach ($shguser as $key => $value) {
            $store= [];
            $productCheck = ProductMaster::where(['is_active' => 1, 'is_draft' => 0, 'user_id' => $value->id])->select($productname, 'price', 'id', 'image_1')->take(5)->get();
            if (count($productCheck) > 0) {
                $store['title'] = $value->title;
                $store['type'] = 'shg-artisan';
                $store['id'] = $value->id;
                $store['data'] = $productCheck;
                $shgArr[] = $store;
            }
        }
        $products = [];
        if (count($shgArr) > 0) {
            foreach ($shgArr as $value) {
                $products[] = $value;
            }
        }
        $queryStatus    = "Product not found.";
        $statusCode     = 200;
        $status         = true;
        $response       = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        if (count($shgArr) > 0) {
            $queryStatus    = "AllProduct.";
            $statusCode     = 200;
            $status         = true;
            $response       = array(
                'status' => $status , 'statusCode' => $statusCode, 'message'=> $queryStatus,
                'data' => ['data'    => $allProduct ]
            );
        }
        return view('frontend.moreproducts', ['response' => $response ]);
    }


    /**
     * search Product
     */
    public function search(Request $request)
    {
        $langauge = $request->session()->get('weblangauge');

        if (!$langauge) {
            $langauge = 'en';
        }
        $query = 'localname_en';
        $productname = 'localname_en as name';
        $categoryname = 'name_en as name';
        $categoryquery = 'name_en';
        $templatename = 'name_en as name';
        $templatequery = 'name_en';

        if ($langauge == 'kn') {
            $query = 'localname_kn';
            $productname = 'localname_kn as name';

            $categoryquery = 'name_kn';
            $templatequery = 'name_kn';
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
            return redirect()->back();
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
                'catSlug' =>$value->slug,
                'type'      => 'parentCategory'
            ];
        }



        $shgArtisanIds = ProductMaster::where(['is_draft' => 0, 'is_active' => 1])->groupBy('user_id')->pluck('user_id');

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



        // $products = ProductMaster::where($query, 'LIKE', "%{$keyword}%")->where(['is_draft' => 0, 'is_active' => 1])->select($productname, 'subcategoryId', 'price', 'id', 'image_1', 'template_id')->with('template:id,'.$templatename)->paginate(15);

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

        if (count($products) > 0) {
            $product = [
                'type' => 'product',
                'data' => $productArr
            ];

            if (count($product['data']) == 0) {
                $recentlyproduct = [];
            }
        } else {
            $product = [];
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


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [
                                    // 'products' => $products
                                        'pagination' => $paginationData,
                                        'searchresult' => $newData

                                    ]);
        // }


        // dd($newData);
        return view('frontend.searchresult', ['result'=>$newData]);
    }

    public function login()
    {
        //$language = $request->session()->get('weblangauge');
        return view('frontend.login');
    }

    public function doLogin(Request $request)
    {
        $client = new GuzzleClient([
            'headers' => $this->headers
        ]);

        if (is_numeric($request->get('email'))) {
            $paramArr['form_params']['mobile']  = $request->email;
        }
        if (filter_var($request->get('email'), FILTER_VALIDATE_EMAIL)) {
            $paramArr['form_params']['email'] = $request->email;
        }

        $paramArr['form_params']['password'] = $request->password;
        $r = $client->request('POST', url('api/login'), $paramArr);
        $response = $r->getBody()->getContents();

        $responseArray = json_decode($response);


        if ($responseArray->status == false) {
            return redirect()->back()
            ->withInput()
            ->withErrors([$responseArray->message]);
        } else {
            session_start();
            $_SESSION['user'] = $responseArray->data->user;

            $userdata = $_SESSION['user'];

            if (($userdata->role_id == 1) && ($userdata->is_otp_verified == 1)) {
                return redirect('/');
            }
            if (($userdata->is_otp_verified == 1) && (($userdata->role_id == 2) || ($userdata->role_id == 3))) {
                $is_verified = User::where('id', $userdata->id)->select('is_document_verified')->first();

                if (($userdata->is_document_added == 1) && ($is_verified->is_document_verified == 1)) {
                    return redirect('/');
                } else {
                    // Add Redirect Routes here for document upload
                    return redirect('/');
                }
            }
        }
    }

    public function forgetpassword()
    {
        return view('frontend.forget');
    }

    public function forgetpass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile'     => 'required|exists:users',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }
        $user = User::where('mobile', $request->mobile)->first();
        $otp  = rand(1111, 9999);
        $this->sendotp($request->mobile, $otp);
        $otpStatus = Otphistory::create(['mobile_no' => $request->mobile, 'otp' => $otp ]);

        //dd($user, $otpStatus);
        $encrypted = Crypt::encryptString($request->mobile);
        return redirect('/verifyotp/'.$encrypted);
    }

    /**
     * Send OTP
     */



    public function expressintrest(Request $request)
    {
        $id = decrypt($request->productId);
        $language = $request->session()->get('weblangauge');
        $user =  Auth::user();
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($language == 'kn') {
            $productname = 'localname_kn as name';
            $templatename = 'name_kn as name';
        }

        $allProduct = ProductMaster::where('id', $id)->with('template:id,'.$templatename)->select($productname, 'price', 'id', 'image_1', 'template_id')->first();

        $data['user'] = $user;
        $data['product'] = $allProduct;

        $usermobile = Auth::user()->mobile;
        $artisanmobile = $request->shgartisanMobile;

        // $usermobile = '8890203788';
        // $artisanmobile = '7976398332';
        //dd($allProduct->template->name);



        $thankyou_message = "Thank you for showing your interest in PDX- ".sprintf("%'.06d\n", $allProduct->id)."- ".$allProduct->template->name."( ".$allProduct->name." )";
        $this->enqmsg($usermobile, $thankyou_message);

        $enq_message = Auth::user()->name." (".Auth::user()->mobile.") has shown interest in your product PDX- ".sprintf("%'.06d\n", $allProduct->id)."- ".$allProduct->template->name."(".$allProduct->name.")  with message: ".$request->message;
        $this->enqmsg2($artisanmobile, $enq_message);

        return redirect()->back();
    }
    public function enqmsg($mobile, $otp)
    {
        $username="Mobile_1-KSLKAR";
        $password="kslkar@1234";
        $senderid="KSLKAR";
        $message=$otp;
        // $messageUnicode="à¤®à¥‹à¤¬à¤¾à¤‡à¤²à¤¸à¥‡à¤µà¤¾à¤®à¥‡à¤‚à¤†à¤ªà¤•à¤¾à¤¸à¥à¤µà¤¾à¤—à¤¤à¤¹à¥ˆ "; //message content in unicode
        $mobileno = $mobile; //if single sms need to be send use mobileno keyword
        // $mobileNos= "9587777762,8766068415"; //if bulk sms need to send use mobileNos as keyword and mobile number seperated by commas as value
        $deptSecureKey= "b36befee-c007-4749-b6da-d8a0d6ca5e41"; //departsecure key for encryption of message...
        $encryp_password=sha1(trim($password));


        $key=hash('sha512', trim($username).trim($senderid).trim($message).trim($deptSecureKey));

        $url = "https://msdgweb.mgov.gov.in/esms/sendsmsrequest";

        $data = array(
            "username"        => trim($username),
            "password"        => trim($encryp_password),
            "senderid"        => trim($senderid),
            "content"         => trim($message),
            "smsservicetype"  =>"singlemsg",
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
        curl_close($post);
        return true;
    }

    public function enqmsg2($mobile, $otp)
    {
        $username="Mobile_1-KSLKAR";
        $password="kslkar@1234";
        $senderid="KSLKAR";
        $message=$otp;
        // $messageUnicode="à¤®à¥‹à¤¬à¤¾à¤‡à¤²à¤¸à¥‡à¤µà¤¾à¤®à¥‡à¤‚à¤†à¤ªà¤•à¤¾à¤¸à¥à¤µà¤¾à¤—à¤¤à¤¹à¥ˆ "; //message content in unicode
        $mobileno = $mobile; //if single sms need to be send use mobileno keyword
        // $mobileNos= "9587777762,8766068415"; //if bulk sms need to send use mobileNos as keyword and mobile number seperated by commas as value
        $deptSecureKey= "b36befee-c007-4749-b6da-d8a0d6ca5e41"; //departsecure key for encryption of message...
        $encryp_password=sha1(trim($password));


        $key=hash('sha512', trim($username).trim($senderid).trim($message).trim($deptSecureKey));

        $url = "https://msdgweb.mgov.gov.in/esms/sendsmsrequest";

        $data = array(
            "username"        => trim($username),
            "password"        => trim($encryp_password),
            "senderid"        => trim($senderid),
            "content"         => trim($message),
            "smsservicetype"  =>"singlemsg",
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
        curl_close($post);
        return true;
    }

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

        $url = "https://msdgweb.mgov.gov.in/esms/sendsmsrequest";

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
        //  echo $result; //output from server displayed
        curl_close($post);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function verifyotp(Request $request, $type)
    {
        if ($request->has('type')) {
            $mobile = Crypt::decryptString($type);
            $type = $request->input('type');
            //$otp = Otphistory::where(['mobile_no'=> $mobile, 'status'=> 1])->first();
            return view('frontend.verifyotp', ['mobile'=>$mobile,'type'=>$type]);
        }

        if ($type == "signup") {
            if (Auth::user()) {
                $mobile = Auth::user()->mobile;
            } else {
                // abort(404);
            }
        } else {
            $mobile = Crypt::decryptString($type);
        }

        //$otp = Otphistory::where(['mobile_no'=> $mobile, 'status'=> 1])->first();

        return view('frontend.verifyotp', ['mobile'=>$mobile,'type'=>$type]);
    }

    public function checkotp(Request $request)
    {
        $mobile = $request->mobile;
        $otp = $request->a. $request->b. $request->c. $request->d;

        $otpCheck = Otphistory::where(['mobile_no' => $request->mobile, 'otp' => $otp, 'status' => 1])->first();


        if ($otpCheck) {
            $updateuserstatus = User::where('mobile', $request->mobile)->update([ 'is_otp_verified' => 1 ]);
            $otpUpdate = Otphistory::where(['mobile_no' => $request->mobile, 'otp' => $otp, 'status' => 1])->update(['status' => 0]);



            if ($request->type == 'signup') {
                if (Auth::user()->role_id == 1) {

                //check for redirect back to previous URL
                    $followuplink = session()->get('url.intended');

                    if ($followuplink == "") {
                        return redirect('/profile');
                    } else {
                        return redirect($followuplink);
                    }
                } else {
                    return redirect('/add_document');
                }
            } elseif ($request->type == 'change') {
                $updateMobile = User::where(['id' => Auth::user()->id])->update(['mobile' => $mobile]);
                return redirect('/profile')->with('message', 'Mobile No Updated !');
            } else {
                $encrypted = Crypt::encryptString($request->mobile);
                $encotp = Crypt::encryptString($otp);

                return redirect('/changepassword/'.$encrypted.'/'.$encotp);
            }
        } else {
            return redirect()->back()->withErrors(['Please enter valid OTP']);
            ;
        }
    }




    public function changepassword($mobile, $otp)
    {
        $mobile = Crypt::decryptString($mobile);
        $otp = Crypt::decryptString($otp);
        return view('frontend.changepassword', ['mobile'=>$mobile,'otp'=>$otp]);
    }

    public function updatepassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile'        => 'required|exists:users',
            'password'      => 'required|min:6|confirmed',
            'otp'           => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
            ->withInput()
            ->withErrors($validator);
        } else {
            $password = Hash::make($request->password);
            $mobile   = $request->mobile;

            $userUpdate = User::where(['mobile' => $mobile ])->update(['mobile' => $mobile, 'password' => $password ]);
            return redirect('/login')->withErrors('Password Changed, Please Login to Continue');
        }
    }

    public function register()
    {
        return view('frontend.register');
    }

    public function categories(Request $request)
    {
        $language = $request->session()->get('weblangauge');
        $catname = 'name_en  as name';
        if ($language == 'kn') {
            $catname = 'name_kn  as name';
        }
        $allCategory = Category::select('id', $catname, 'image', 'slug')->where(['parent_id'=> 0, 'is_active'=>1])->get();


        return view('frontend.categories', ['allCategory'=>$allCategory]);
    }

    public function singleCategory(Request $request, $id)
    {
        $language = $request->session()->get('weblangauge');
        $catname     = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';
        if ($language == 'kn') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }

        $categoryDetail = Category::where('parent_id', 0)->where(['slug'=> $id, 'is_active'=>1])->select('id', $catname, 'slug')->orderBy('id', 'desc')->get();
        $allproduct = [];
        $productArr = [];
        foreach ($categoryDetail as $key => $category) {
            $subcategory = Category::where(['parent_id'=> $category->id,'is_active'=>1])->select('id', $catname, 'slug')->orderBy('id', 'desc')->get();

            foreach ($subcategory as $key1 => $sub) {
                if (count($request->shgartisanId) > 0) {
                    $allUserId = $request->shgartisanId;
                    $productdata = ProductMaster::whereIn('user_id', $allUserId)->with('template')->where(['is_active' => 1, 'is_draft' => 0, 'categoryId' => $category->id, 'subcategoryId' => $sub->id])->select($productname, 'subcategoryId', 'price', 'id', 'image_1', 'template_id')->take(4)->orderBy('id', 'desc')->get();
                } else {
                    $productdata = ProductMaster::with('template')->where(['is_active' => 1, 'is_draft' => 0, 'categoryId' => $category->id, 'subcategoryId' => $sub->id])->select($productname, 'subcategoryId', 'price', 'id', 'image_1', 'template_id')->take(4)->orderBy('id', 'desc')->get();
                }




                if (count($productdata) > 0) {
                    $productArr[$key1]['categoryname']  = $category->name;
                    $productArr[$key1]['slug']  = $category->slug;
                    $productArr[$key1]['subcategory']   = $sub->name;
                    $productArr[$key1]['subslug']   = $sub->slug;
                    $productArr[$key1]['subcategoryId'] = $sub->id;
                    $productArr[$key1]['product']       = $productdata;
                }
            }
        }

        if (count($productArr) > 0) {
            return view('frontend.singlecategory', ['status'=>1,'categoryDetail'=>$productArr,'language'=>$language]);
        } else {
            $categoryDetail = Category::where('parent_id', 0)->where('id', $id)->select('id', $catname)->first();

            return view('frontend.singlecategory', ['status'=>0,'categoryDetail'=>$categoryDetail,'language'=>$language]);
        }
    }

    public function subCategory(Request $request, $id, $subcatid)
    {
        $shg_id = $request->show;
        $language = $request->session()->get('weblangauge');
        $catname     = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';
        if ($language == 'kn') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn  as name';
        }
        $categorydata      = Category::where('slug', $id)->select('id', $catname)->first();
        $subcategorydata   = Category::where('slug', $subcatid)->select('id', $catname)->first();
        $categoryId         = $categorydata->id;
        $subcategoryId      = $subcategorydata->id;
        // $productdata = ProductMaster::with('template')->where(['is_active' => 1, 'is_draft' => 0, 'categoryId' => $categoryId, 'subcategoryId' => $subcategoryId])->select($productname, 'subcategoryId', 'price', 'id', 'image_1')->get();

        if ($request->has('show')) {
            // echo $request->show;
            // die;

            $shg_id = decrypt($request->show);
     
            $user_id = $request->input('id');
            $productdata = ProductMaster::where(['user_id'=>$shg_id, 'is_active' => 1, 'is_draft' => 0, 'subcategoryId' => $subcategoryId])->select($productname, 'price', 'id', 'image_1', 'template_id')->with('template:id,'.$templatename)->get();
            return view('frontend.subcategory', ['categorydata'=>$categorydata, 'subcategorydata'=>$subcategorydata,'productdata'=>$productdata]);
        }
        if ($request->has('id')) {
            $user_id = $request->input('id');
            $productdata = ProductMaster::where(['user_id'=>$user_id, 'is_active' => 1, 'is_draft' => 0, 'subcategoryId' => $subcategoryId])->select($productname, 'price', 'id', 'image_1', 'template_id')->with('template:id,'.$templatename)->orderBy('id', 'desc')->get();
        } else {
            $productdata = ProductMaster::where(['is_active' => 1, 'is_draft' => 0, 'subcategoryId' => $subcategoryId])->select($productname, 'price', 'id', 'image_1', 'template_id')->with('template:id,'.$templatename)->orderBy('id', 'desc')->get();
        }







        return view('frontend.subcategory', ['categorydata'=>$categorydata, 'subcategorydata'=>$subcategorydata,'productdata'=>$productdata]);
    }


    public function viewproduct(Request $request, $id)
    {
        $id = decrypt($id);

        $language       = $request->session()->get('weblangauge');
        $productname    = 'localname_en as name';
        $descriptionname = 'des_en as description';
        $materialname = 'name_en as materialname';
        $templatename = 'name_en as templatename';

        if ($language == 'kn') {
            $descriptionname = 'des_kn as description';
            $productname = 'localname_kn  as name';
            $materialname = 'name_kn as materialname';
            $templatename = 'name_kn as templatename';
        }

        $productdetail = ProductMaster::where(['id'=>$id,'is_active'=>1])->select('id', 'price', 'user_id as artisanid', 'material_id', 'categoryId', 'subcategoryId', 'qty', 'length', 'width', 'height', 'vol', 'weight', 'length_unit', 'width_unit', 'height_unit', 'weight_unit', 'vol_unit', 'image_1', 'image_2', 'image_3', 'image_4', 'image_5', 'template_id', 'video_url', $productname, $descriptionname)->first();


        if ($productdetail) {
            $template_name = ProductTemplate::where('id', $productdetail->template_id)->select($templatename)->first();

            $artisanData = User::where('id', $productdetail->artisanid)->select('name', 'title', 'mobile')->first();
            $material = Material::where('id', $productdetail->material_id)->select($materialname)->first();
            $productdetail['materialname'] = $material->materialname;
            $productdetail['artisanshgname'] = $artisanData->name;
            $productdetail['artisanshgtitle'] = $artisanData->title;
            $productdetail['artisanmobile'] = $artisanData->mobile;

            $productdetail['template_name'] = $template_name->templatename;
        } else {
            abort(404);
        }
        //dd($productdetail);




        return view('frontend.product', ['productdetail'=>$productdetail]);
    }


    public function getshgartisanhome(Request $request, $id)
    {
        // Auth::user()->country;
        // Auth::user()->state;
        // Auth::user()->city;
        // Auth::user()->district;
        

        $checkif = User::where(['id'=> $id, 'isActive'=>1])->select('id', 'name', 'title', 'profileImage', 'email', 'mobile')->first();
        if ($checkif == null) {
            abort(404);
        }





        $language       = $request->session()->get('weblangauge');
        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($language == 'kn') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }

        //check user login or not
        $address = '';

        $address = Address::where(['user_id' => $id, 'address_type' => 'registered'])->first();



        $country = Country::where('id', $address->country)->first();


        $state = State::where('id', $address->state)->first();
        $district = City::where('id', $address->district)->first();

        $address['countryname'] = $country->name;
        $address['statename'] = $state->name;
        $address['districtname'] = $district->name;


        $userdata = User::where('id', $id)->select('id', 'name', 'title', 'profileImage', 'email', 'mobile')->first();


        $categoryIds = ProductMaster::where(['user_id' => $id, 'is_active' => 1, 'is_draft' => 0])->distinct('categoryId')->select('categoryId')->get();

        $catArr = [];

        foreach ($categoryIds as $cat) {
            $catArr[] = $cat->categoryId;
        }

        $categoryDetail = Category::wherein('id', $catArr)->where('is_active', 1)->select('id', $catname, 'slug')->get();

        $allProduct = [];

        foreach ($categoryDetail as $key=> $cat) {
            $subcategoryId = Category::where('parent_id', $cat->id)->where('is_active', 1)->select('id', $catname, 'slug')->get();
            $sub = [];
            foreach ($subcategoryId as $subcat) {
                $products = ProductMaster::where('subcategoryId', $subcat->id)->where('is_active', 1)->where('is_draft', 0)->where('user_id', $id)->with('template:id,'.$templatename)->select($productname, 'price', 'id', 'image_1', 'template_id')->take(4)->get();

                if (count($products) > 0) {
                    $sub[] = [
                        'subCategoryId'     => $subcat->id,
                        'subCategoryName'   => $subcat->name,
                        'subCategoryslug' => $subcat->slug,
                        'products'          => $products
                    ];
                }
            }


            $allProduct[$key]['subCategories'] = $sub;

            $allProduct[$key]['categoryId'] = $cat->id;
            $allProduct[$key]['categoryName'] = $cat->name;
            $allProduct[$key]['categoryName'] = $cat->name;

            $allProduct[$key]['categoryslug'] = $cat->slug;
            //$allProduct[$key]['subCategoryslug'] = $subcategoryId->slug;


            //$allProduct[$key]['subCategoryId'] = $subcategoryId->id;
            //           $allProduct[$key]['subCategoryName'] = $subcategoryId->name;

            $allProduct[$key]['products'] = $products;
        }


        $data = ['user' => $userdata, 'address' => $address,'allProduct'=>$allProduct];
        return view('frontend.shgartisanhome', ['alldetail'=>$data]);
    }


    public function help()
    {
        return view('frontend.help');
    }
    public function privacypolicy()
    {
        return view('frontend.privacypolicy');
    }
    public function terms()
    {
        return view('frontend.terms');
    }



    public function disclaimer()
    {
        return view('frontend.disclaimer');
    }


    public function chat()
    {
        return view('frontend.chat');
    }
    public function popularproduct(Request $request)
    {
        $language       = $request->session()->get('weblangauge');
        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($language == 'kn') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }

        /** Start Popular Product */
        $popularproduct = PopularProduct::where('status', 1)->select('id', 'product_id')->orderBy('id', 'desc')->paginate(10);

        $getData = json_encode($popularproduct);
        $getData = json_Decode($getData);

        $paginationData = [
            'current_page' => $getData->current_page,
            'last_page'    => $getData->last_page,
            'per_page'     => $getData->per_page
        ];



        $popularArr = [];

        foreach ($popularproduct as $value) {
            $popularArr[] = $value->product_id;
        }

        $popularproducts = ProductMaster::wherein('id', $popularArr)->where(['is_active' => 1, 'is_draft' => 0])->select($productname, 'price', 'id', 'image_1', 'template_id')->with('template:id,'.$templatename)->get();



        return view('frontend.popularproduct', ['popularproducts'=>$popularproducts]);
    }


    public function recentproduct(Request $request)
    {
        $language       = $request->session()->get('weblangauge');
        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($language == 'kn') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }

        $recentlyproduct = ProductMaster::where(['is_active' => 1, 'is_draft' => 0])->select($productname, 'price', 'id', 'image_1', 'template_id')->with('template:id,'.$templatename)->orderBy('id', 'DESC')->paginate(10);

        $getData = json_encode($recentlyproduct);
        $getData = json_Decode($getData);

        $paginationData = [
            'current_page' => $getData->current_page,
            'last_page'    => $getData->last_page,
            'per_page'     => $getData->per_page
        ];

        return view('frontend.recent', ['popularproducts'=> $recentlyproduct]);
    }
}
