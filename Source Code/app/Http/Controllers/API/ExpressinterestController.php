<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Expressinterest;
use App\Expressinterestitem;
use App\User;
use App\Rating;
use App\IndRating;
use App\ProductMaster;
use App\Order;
use App\Address;
use App\Country;
use App\State;
use App\City;
use App\Location;
use App\OrderItem;
use App\IndividualInterest;
use App\IndividualInterestList;
use App\IndCategory;
use App\IndProductMaster;
use App\IndOrder;
use App\IndOrderItem;
use App\Favorite;
use App\CollectionCenter;
use App\CertificateType;
use App\GrievanceIssueType;
use App\Grievance;
use App\GrievanceMessage;
use App\Survey;

use Illuminate\Support\Facades\Input;
use DB;

class ExpressinterestController extends Controller
{
    public function expressinterest(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        
        $addinterest = Expressinterest::create([
            'seller_id' => $request->seller_id,
            'user_id'   => $user->id,
            'message'   => $request->message,
            'otp'       => rand(1111, 9999),
            //'interest_Id'=> $intId 
        ]);
        //update interest id
        $str_result = str_pad($addinterest->id, 5, "0", STR_PAD_LEFT);
            $interShowId = 'INT'.$str_result;
            $addinterest->update(['interest_Id' => $interShowId]);


        foreach ($request->products as $value) {
            // echo "<pre>"; print_r($value['product_id']); die("check");
            $data = [
                'express_id' => $addinterest->id,
                'product_id' => $value['product_id'],
                'quantity'   => $value['quantity'] 
            ];

            // echo "<pre>"; print_r($data); die("check");

            Expressinterestitem::create($data);
        }
         //send push notification to buyer...
                $sellerData = User::where('id',$request->seller_id)->first();
                $notifyMsg1['id'] = $sellerData->id;
                $notifyMsg1['title'] = 'Order';
                $notifyMsg1['type'] = 'buyer_interest';
                $notifyMsg1['interest_id'] = $addinterest->id;
                if($sellerData->language =='hi'){
                    $notifyMsg1['message']="नमस्ते $sellerData->organization_name, $user->name ने आपके उत्पादों में अपनी रुचि व्यक्त की है।";
                }else{
                    $notifyMsg1['message']="Hi $sellerData->organization_name, $user->name has expressed his interest in your products.";
                }
                //$notifyMsg1['message']="seller order";
                
        if($sellerData->devicetoken){
        $this->sendPushNotification(array($sellerData->devicetoken),$notifyMsg1, '1');
        }
        if($language == 'hi'){
            $queryStatus    = "दिलचस्पी सफलतापूर्वक जोड़ी गई";
        }else{
            $queryStatus = "Interest Added Successfully";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus);
        return response()->json($response, 201);
    } 

    public function userinterest(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        // , 'items', 'items.product'   
        $allInterest = Expressinterest::with('seller', 'buyer', 'items', 'items.product')->where(['user_id'=> $user->id, 'order_status'=> 'interest'])->orderBy('id', 'desc')->select('id', 'seller_id', 'interest_Id','user_id','otp','created_at')->get();
        if(count($allInterest) > 0) {
            if($language == 'hi'){
                $queryStatus    = "दिलचस्पी सफलतापूर्वक जोड़ी गई";
            }else{
                $queryStatus = "Interest Added Successfully";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'user_interest' => $allInterest
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "दिलचस्पी नहीं देखी गई";
            }else{
                $queryStatus = "Interest not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
        }

        return response()->json($response, 201);   
    }

    public function sellerinterest(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        $allInterest = Expressinterest::with('items', 'items.product', 'buyer')->where(['seller_id'=> $user->id, 'order_status'=> 'interest'])->select('seller_id', 'user_id', 'message', 'id', 'interest_Id')->orderBy('id', 'desc')->get();
        if(count($allInterest) > 0) {
            if($language == 'hi'){
                $queryStatus    = "दिलचस्पी सफलतापूर्वक जोड़ी गई";
            }else{
                $queryStatus = "Interest Added Successfully";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'seller_interest' => $allInterest
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "दिलचस्पी नहीं देखी गई";
            }else{
                $queryStatus = "Interest not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> "Interest not found");
        }

        return response()->json($response, 201);   
    }

    public function orderlist(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        $orderType = $request->input('order_type');
        if($orderType == 'pending'){
            $whereIn = ['pending','received'];
        }elseif($orderType == 'delivered'){
            $whereIn =['delivered'];
        }else{
            $whereIn =['pending'];
        }
        
        if($user->role_id == 1){
        $allOrder = Order::with('items', 'seller:id,name,email,mobile,organization_name,profileImage', 'buyer:id,name,email,mobile', 'items.product:id,price,price_unit,qty,localname_en')->where(['user_id'=> $user->id])->whereIn('order_status',$whereIn)->select('id','order_status','mode_of_delivery','order_id_d','seller_id', 'user_id', 'message', 'id', 'interest_id', 'otp','created_at')->latest()->get();
        
        }else{
            $allOrder = Order::with('items', 'seller:id,name,email,mobile,organization_name,profileImage', 'buyer:id,name,email,mobile', 'items.product:id,price,price_unit,qty,localname_en')->where(['seller_id'=> $user->id])->whereIn('order_status',$whereIn)->select('id','order_status','mode_of_delivery','order_id_d','seller_id', 'user_id', 'message', 'id', 'interest_id', 'otp','created_at')->latest()->get();

        }
        if(count($allOrder) > 0) {
            if($language == 'hi'){
                $queryStatus    = "आदेश सूची";
            }else{
                $queryStatus = "Order List";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'order_list' => $allOrder
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "आदेश नहीं मिला";
            }else{
                $queryStatus = "Order not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
        }

        return response()->json($response, 201);   
    }
    public function interestlist(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        //print_r($user);die;
        if($user->role_id == 1){
            $allInterest = Expressinterest::with('items', 'seller', 'buyer', 'items.product')->where(['user_id'=> $user->id, 'order_status'=> 'interest'])->select('seller_id', 'user_id', 'message', 'id', 'interest_Id', 'otp', 'order_id')->orderBy('id', 'desc')->get();

        }else{
            $allInterest = Expressinterest::with('items', 'seller', 'buyer', 'items.product')->where(['seller_id'=> $user->id, 'order_status'=> 'interest'])->select('seller_id', 'user_id', 'message', 'id', 'interest_Id', 'otp', 'order_id')->orderBy('id', 'desc')->get();

        }
        if(count($allInterest) > 0) {
            if($language == 'hi'){
                $queryStatus    = "दिलचस्पी की सूची";
            }else{
                $queryStatus = "Iterest List";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'interest_list' => $allInterest
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "दिलचस्पी नहीं मिली";
            }else{
                $queryStatus = "Interest not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
        }

        return response()->json($response, 201);   
    }
    public function convertinterest(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $intId = substr(str_shuffle($str_result),0, 8);

        $update = Expressinterest::where(['id'=> $request->interestId])->update(['order_status' => 'order', 'order_Id' => $intId]);
        if($language == 'hi'){
            $queryStatus    = "दिलचस्पी का विवरण";
        }else{
            $queryStatus = "Interest converted to order";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus);
        if(!$update) {
            $response   = array('status' => 'true' , 'statusCode' =>400, 'message'=> "Failed to convert interest to order");

        }
        return response()->json($response, 201);   

    }

    public function getinterestbyid(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        $productname = 'localname_en as name';
        $queryStatus    = "Interest Detail";
        if ($language == 'hi') {
            $productname = 'localname_kn  as name';
            $queryStatus    = "दिलचस्पी का विवरण";
        }


        $allInterest = Expressinterest::with('items', 'items.product:id,price,price_unit,qty,'.$productname, 'buyer', 'seller')->where(['id' => $request->interestId, 'order_status'=> 'interest'])->select('seller_id', 'user_id', 'message', 'id', 'interest_Id', 'created_at', 'order_Id')->orderBy('id', 'desc')->get();
        if(count($allInterest) > 0) {
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'seller_interest' => $allInterest
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "दिलचस्पी नहीं मिली";
            }else{
                $queryStatus = "Interest not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
        }

        return response()->json($response, 201);   
    }

    public function getorderbyid(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        $productname = 'localname_en as name';
        $queryStatus = "Order Details";
        if ($language == 'hi') {
            $productname = 'localname_kn  as name';
            $queryStatus = "आदेश विवरण";
        }
        

        if($user->role_id == 3 || $user->role_id == 2 || $user->role_id == 7 || $user->role_id == 8 || $user->role_id == 10){
            $allOrder = Order::with('items', 'interest:id,interest_Id','items.product:id,price,price_unit,qty,'.$productname, 'buyer:id,name,email,mobile,profileImage', 'seller:id,name,email,mobile,organization_name,profileImage','sellerRating','buyerRating')->where(['id' => $request->orderId])->select('id','order_id_d','order_status','mode_of_delivery','seller_id', 'user_id', 'message', 'otp', 'interest_id','updated_at', 'created_at')->latest()->get();
        }else{
            $allOrder = Order::with('items', 'interest:id,interest_Id','items.product:id,price,price_unit,qty,'.$productname, 'seller:id,name,email,mobile,organization_name,profileImage','sellerRating','buyerRating')->where(['id' => $request->orderId])->select('id','order_id_d','order_status','seller_id', 'user_id', 'message', 'otp', 'mode_of_delivery','interest_id','updated_at', 'created_at')->latest()->get();
        }
        if(count($allOrder) > 0) {
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'all_order' => $allOrder
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "आदेश नहीं मिला";
            }else{
                $queryStatus = "Order not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> "Order not found");
        }

        return response()->json($response, 201);   
    }

    public function updateproduct(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        //Expressinterestitem::where('express_id', $request->interestId)->delete();

        foreach ($request->products as $value) {
            // echo "<pre>"; print_r($value['product_id']); die("check");

            $data = [
                // 'express_id' => $request->interestId,
                'product_id' => $value['product_id'],
                'quantity'   => $value['quantity']   
            ];

            // echo "<pre>"; print_r($data); die("check");

            Expressinterestitem::where('express_id', $request->interestId)->update($data);
        }
        if($language == 'hi'){
            $queryStatus    = "दिलचस्पी सफलतापूर्वक जोड़ी गई";
        }else{
            $queryStatus = "Interest Added Successfully";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus);
        return response()->json($response, 201);

    }

    public function addRating(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        $input = $request->all();
        $input['review_by_user'] = $user->id;
        $input['type'] = $request->type;
        $input['order_id'] = $request->order_id;
        //add type
        Rating::create($input);
        if($language == 'hi'){
            $queryStatus    = "रेटिंग सफलतापूर्वक जोड़ी गई";
        }else{
            $queryStatus = "Rating Added Successfully";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus);
        return response()->json($response, 201);
    }

    public function getsells(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        $allInterest = Expressinterest::with('items', 'seller', 'buyer', 'items.product')->where(['seller_id'=> $user->id, 'order_status'=> 'order'])->select('seller_id', 'user_id', 'message', 'id', 'interest_Id', 'order_id')->orderBy('id', 'desc')->paginate(20);

        $p = json_encode($allInterest);
        $final = json_decode($p);
        $pagination = [
            'current_page' => $final->current_page,
            'last_page' => $final->last_page,
            

        ];
        // echo "<pre>"; print_r($final); die("check");

        if(count($allInterest) > 0) {
            if($language == 'hi'){
                $queryStatus    = "आदेश सूची";
            }else{
                $queryStatus = "Order List";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'allSells' => $final->data,
                'pagination' => $pagination
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "आदेश नहीं मिला";
            }else{
                $queryStatus = "Order not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=>  $queryStatus);
        }

        return response()->json($response, 201);  
    }

    public function getreviews(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        $ratings = Rating::where('user_id', $user->id)->with('getreviews')->paginate(20);
        $p = json_encode($ratings);
        $final = json_decode($p);
        $pagination = [
            'current_page' => $final->current_page,
            'last_page' => $final->last_page,
            

        ];
        // echo "<pre>"; print_r($final); die("check");

        if(count($ratings) > 0) {
            if($language == 'hi'){
                $queryStatus    = "रेटिंग सूची";
            }else{
                $queryStatus = "Rating List";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'ratings' => $final->data,
                'pagination' => $pagination
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "रेटिंग नहीं मिली";
            }else{
                $queryStatus = "Rating not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
        }

        return response()->json($response, 201);  
    }
    public function getSellerReviews(Request $request) {
        $sellerId = $request->seller_id;
        $language = $request->header('language');
        $ratings = Rating::where('user_id', $sellerId)->with('getreviews')->paginate(20);
        $p = json_encode($ratings);
        $final = json_decode($p);
        $pagination = [
            'current_page' => $final->current_page,
            'last_page' => $final->last_page,
            

        ];
        // echo "<pre>"; print_r($final); die("check");

        if(count($ratings) > 0) {
            if($language == 'hi'){
                $queryStatus    = "रेटिंग सूची";
            }else{
                $queryStatus = "Rating List";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'ratings' => $final->data,
                'pagination' => $pagination
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "रेटिंग नहीं मिली";
            }else{
                $queryStatus = "Rating not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
        }

        return response()->json($response, 201);  
    }
    public function getsellerproduct(Request $request) {
        $user = $request->user;
        $language = $request->header('language');


        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';
        $populartitle = 'Popular Products';
        $recentlytitle = 'Recently Added';


        if ($language == 'hi') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
            $populartitle = 'ಜನಪ್ರಿಯ ಉತ್ಪನ್ನಗಳು';
            $recentlytitle = 'ಇತ್ತೀಚೆಗೆ ಸೇರಿಸಲಾಗಿದೆ';
        }
        $recentlyproduct = ProductMaster::where(['is_active' => 1, 'is_draft' => 0, 'user_id' => $request->sellerId])->orderBy('id', 'desc')->select($productname, 'price', 'id','price_unit', 'qty','image_1', 'template_id')->with('template:id,'.$templatename)->paginate(20);
        $p = json_encode($recentlyproduct);
        $final = json_decode($p);
        $pagination = [
            'current_page' => $final->current_page,
            'last_page' => $final->last_page,
            

        ];

       

        if(count($recentlyproduct) > 0) {
            if($language == 'hi'){
                $queryStatus    = "सभी उत्पादों की सूची";
            }else{
                $queryStatus = "All Product List";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'seller_data' => $final->data,
                'pagination' => $pagination
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "सभी उत्पादों की सूची";
            }else{
                $queryStatus = "Product not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
        }

        return response()->json($response, 201);  
    }
    
    public function addNewSale(Request $request) {

        $user = $request->user;
        $language = $request->header('language');
       
        $orderData['interest_id'] = $request->interestId;
        $orderData['user_id'] = $request->user_id; //buyer id
        $orderData['seller_id'] = $user->id;
        $orderData['otp'] = '1234';
        $orderData['mode_of_delivery'] = $request->mode_of_delivery;
        
        $orderData['sale_date'] = $request->sale_date;
        if($request->mode_of_delivery == 0){
            $orderData['delivery_status'] = 'delivered';
            $orderData['order_status'] = 'delivered';
        }
        if($request->mode_of_delivery == 1){
            $orderData['delivery_status'] = 'pending';
            $orderData['order_status'] = 'pending';
            $orderData['collection_center_id'] = $request->collection_center_id;
           // $collectionCenter = CollectionCenter::where(['status' => 0,'block_id'=>$user->block])->first();
           
        }
        
        $orderCreate = Order::create($orderData);
        if($orderCreate){
            //update order id
            $str_result = str_pad($orderCreate->id, 5, "0", STR_PAD_LEFT);
            $orderShowId = 'ORD'.$str_result;
            $orderCreate->update(['order_id_d' => $orderShowId]);
            //update product....
            foreach ($request->products as $value) {
                $productData = [
                    'order_id' => $orderCreate->id,
                    'product_id' => $value['product_id'],
                    'quantity'   => $value['quantity'], 
                    'product_price'   => $value['product_price']   
                ];
                $chkOrderItem = OrderItem::where('order_id',$orderCreate->id)->where('product_id',$value['product_id'])->first();
                if(!$chkOrderItem){
                    OrderItem::create($productData);
                }
                
            }


            $input = $request->all();
            $input['review_by_user'] = $user->id;
            $input['order_id'] = $orderCreate->id;
            //add rating
            Rating::create($input);
            //send push notification to buyer...
            $buyerData = User::where('id',$request->user_id)->first();
            
                $notifyMsg1['id'] = $request->user_id;
                $notifyMsg1['title'] = 'Order';
                $notifyMsg1['type'] = 'seller_order';
                $notifyMsg1['order_id'] = $orderCreate->id;
                if($request->mode_of_delivery == 1){
                    if($buyerData->language =='hi'){
                        $notifyMsg1['message']="नमस्ते $buyerData->name, $user->organization_name has been created your order. Please collect it from collection centre।";
                    }else{
                        $notifyMsg1['message']="Hi $buyerData->name, $user->organization_name has been created your order. Please collect it from collection centre";
                    }
                }else{
                    if($buyerData->language =='hi'){
                        $notifyMsg1['message']="नमस्ते $buyerData->name, $user->organization_name ने आपके नाम से एक ऑर्डर बनाया है। कृपया अपने अनुभव को रेट करें।";
                    }else{
                        $notifyMsg1['message']="Hi $buyerData->name, $user->organization_name has created an order under your name. Rate your experience now";
                    }
                }
               
                //$notifyMsg1['message']="order";
            if($buyerData->devicetoken){
             $this->sendPushNotification(array($buyerData->devicetoken),$notifyMsg1, '1');
            }
            //send notification to collection center users
            if($request->mode_of_delivery == 1){
                $centerUser = User::where('isActive',1)->where('collection_center_id',$request->collection_center_id)->get();
                if(count($centerUser) > 0){
                    foreach($centerUser as $cUser){
                        $notifyMsg['id'] = $cUser->id;
                        $notifyMsg['title'] = 'Order';
                        $notifyMsg['type'] = 'seller_order';
                        $notifyMsg['order_id'] = $orderCreate->id;
                       
                            if($cUser->language =='hi'){
                                $notifyMsg['message']="नमस्ते $cUser->name, $user->organization_name आपके संग्रह केंद्र के लिए एक आदेश बनाया गया है";
                            }else{
                                $notifyMsg['message']="Hi $cUser->name, $user->organization_name has been created a order for your collection centre";
                            }
                       
                        if($cUser->devicetoken){
                            $this->sendPushNotification(array($cUser->devicetoken),$notifyMsg, '1');
                        }
                    }
                }
                
                  
        }
            if($language == 'hi'){
                $queryStatus    = "आदेश सफलतापूर्वक जोड़ा गया";
            }else{
                $queryStatus = "Order Added Successfully";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus,'data'=>['id'=>$orderCreate->id]);
            return response()->json($response, 201);
        }else{
            
            $response   = array('status' => 'true' , 'statusCode' =>400, 'message'=> "Failed to convert interest to order");
            return response()->json($response, 201);
        }
    
        
        
    }
    //******************************************CLF ************************* */
    public function getClfIndOrderList(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        $allOrder = IndOrder::with('GetIndividual:id,name,email,mobile')->where(['seller_id'=> $user->id])->select('id','order_id_d','seller_id', 'user_id', 'message', 'id', 'otp','sale_date','created_at')->latest()->get();
        
       
        if(count($allOrder) > 0) {
            if($language == 'hi'){
                $queryStatus    = "आदेश सूची";
            }else{
                $queryStatus = "Order List";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'order_list' => $allOrder
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "आदेश नहीं मिला";
            }else{
                $queryStatus = "Order not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
        }
    
        return response()->json($response, 201);   
    }
    public function getClfIndOrderDetails(Request $request) {
        $user = $request->user;
        $orderId = $request->input('order_id');
        $language = $request->header('language');
        $productname = 'name_en as name_en';

    if ($language == 'hi') {
        $productname = 'name_hi  as name_en';
       
    }
        $allOrder = IndOrder::with('indItems','GetIndividual:id,name,email,mobile,profileImage','getClf:id,name,email,mobile,profileImage','indItems.Indproduct:id,'.$productname.',price_unit','clfRating','indRating')->where(['id'=> $orderId])->select('id','order_id_d','seller_id', 'user_id', 'message', 'id', 'otp','sale_date','created_at')->latest()->first();

       
        if($allOrder) {
            if($language == 'hi'){
                $queryStatus    = "आदेश सूची";
            }else{
                $queryStatus = "Order Details";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'order_details' => $allOrder
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "आदेश नहीं मिला";
            }else{
                $queryStatus = "Order not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
        }
    
        return response()->json($response, 201);   
    }
    public function getIndividualUserList(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        $userData = [];
        $indUser  = User::where('isActive',1)->where('role_id',9)->where('block',$user->block)->with('address_registerd')->get();
        if(count($indUser) > 0){
            foreach($indUser as $indu){
                $ratingStarAvg = DB::table('ind_ratings')->where('user_id', $indu->id)->avg('rating');
                
                $userData[] = ['id'=>$indu->id,'name'=>$indu->name,'organization_name'=>$indu->organization_name,'mobile'=>$indu->mobile,'email'=>$indu->email,'ratingAvgStar' => $ratingStarAvg,'address_line_one'=>$indu->address_registerd->address_line_one,'address_line_two'=>$indu->address_registerd->address_line_two,'block'=>$indu->address_registerd->getBlock->name,'district'=>$indu->address_registerd->getDistrict->name,'state'=>$indu->address_registerd->getState->name,'pincode'=>$indu->address_registerd->pincode];
            }
        }
       
        if(count($userData) > 0) {
            if($language == 'hi'){
                $queryStatus    = "आदेश सूची";
            }else{
                $queryStatus = "Order List";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'individual_list' => $userData
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "आदेश नहीं मिला";
            }else{
                $queryStatus = "Order not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
        }
    
        return response()->json($response, 201);   
    }
    //clf gives ratign to individual
    public function addIndRating(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        $input = $request->all();
        $input['review_by_user'] = $user->id;
        $input['type'] = 'clf';
        $input['order_id'] = $request->order_id;
        //add type
        IndRating::create($input);
        //send push notification to buyer...
        $individualData = User::where('id',$request->user_id)->first();
        $notifyMsg1['id'] = $individualData->id;
        $notifyMsg1['title'] = 'Order';
        $notifyMsg1['type'] = 'individual_order';
        $notifyMsg1['order_id'] = $request->order_id;
        if($individualData->language =='hi'){
            $notifyMsg1['message']="नमस्ते $individualData->name, $user->organization_name ने आपके साथ अपने अनुभव का मूल्यांकन किया है।";
        }else{
            $notifyMsg1['message']="Hi $individualData->name, $user->organization_name has rated his experience with you.";
        }
        if($individualData->devicetoken){
            $this->sendPushNotification(array($individualData->devicetoken),$notifyMsg1, '1');
           }
        if($language == 'hi'){
            $queryStatus    = "रेटिंग सफलतापूर्वक जोड़ी गई";
        }else{
            $queryStatus = "Rating Added Successfully";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus);
        return response()->json($response, 201);
    }
    public function getCollectionCenterList(Request $request) {
        $user = $request->user;
        $language = $request->header('language');
        $name = 'name_en  as name';
        if ($language == 'hi') {
            $name = 'name_hi  as name';
           
        }
        $collectionCenter = CollectionCenter::where(['status' => 0,'block_id'=>$user->block])->select($name, 'id')->get();
        if(count($collectionCenter) > 0)  {
            if($language == 'hi'){
                $queryStatus    = "सभी उत्पादों की सूची";
            }else{
                $queryStatus = "collection center";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                'collectionCenter' => $collectionCenter,
            ]);
        }else{
            if($language == 'hi'){
                $queryStatus    = "Not found";
            }else{
                $queryStatus = "Not found";
            }
            $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
        }
    
        return response()->json($response, 201);  
    }
//*********************************************SHG Individual API ************************************/
public function getIndividualInterestList(Request $request) {
    $language = $request->header('language');
    $name = 'name_en  as name';
    if ($language == 'hi') {
        $name = 'name_hi  as name';
       
    }
    $interestList = IndividualInterestList::where(['status' => 0])->orderBy('id', 'desc')->select($name, 'id','image')->get();
   
    if(count($interestList) > 0) {
        if($language == 'hi'){
            $queryStatus    = "सभी उत्पादों की सूची";
        }else{
            $queryStatus = "All Interest List";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
            'interestList' => $interestList,
        ]);
    }else{
        if($language == 'hi'){
            $queryStatus    = "सभी उत्पादों की सूची";
        }else{
            $queryStatus = "Interest not found";
        }
        $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
    }

    return response()->json($response, 201);  
}

public function addIndividualInterest(Request $request) {

    $language = $request->header('language');
    $listid = $request->interest_list_id;
    if($listid){
        $listId = explode(',',$listid);
        if(count($listId) > 0){
            foreach($listId as $lid){
                $orderCreate = IndividualInterest::firstOrNew([
                    'user_id' => $request->user_id,
                    'individual_interest_list_id' => $lid,
                ])->save();
            }
        }
        
    }
    
        if($language == 'hi'){
            $queryStatus    = "आदेश सफलतापूर्वक जोड़ा गया";
        }else{
            $queryStatus = "Interest Added Successfully";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus);
        return response()->json($response, 201);
}

public function individualHome(Request $request) {

    $language = $request->header('language');
    $userId = $request->user_id;
    //$user = $request->user;
    //user according to distance
    //$activeData = $request->shgartisanId;
    $getMyInterest  = IndividualInterest::where('user_id',$userId)->get();
    $interIds = [];
    $userData = [];
    foreach ($getMyInterest as $key => $value) {
        $interIds [] = $value->individual_interest_list_id;
        $getMatching = IndividualInterest::whereIn('individual_interest_list_id',$interIds)->where('user_id','!=',$userId)->get();
        
       if(count($getMatching) > 0){
        foreach($getMatching as $val){
                $userData[] = User::select('id','name','organization_name','profileImage')->where('id',$val->user_id)->first();
            }
       }
    }
    //To get favorite list
    $favuser= Favorite::where('user_id',$userId)->where('status',1)->get();
    $favUser =[];
    if(count($favuser) > 0){
        foreach($favuser as $fav){
            $favUser[] = User::select('id','name','organization_name','profileImage')->where('id',$fav->seller_id)->where('role_id',9)->first();
        }
    }
    
    
        if($language == 'hi'){
            $queryStatus    = "आदेश सफलतापूर्वक जोड़ा गया";
        }else{
            $queryStatus = "Interest Added Successfully";
        }
        $data = ['matchingUser'=>array_values(array_unique($userData)),'favUser'=> $favUser];
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, 'data'=>$data);
        return response()->json($response, 201);
}


public function getIndProduct(Request $request) {
    $user = $request->user;
    $language = $request->header('language');
    $keyword = $request->input('search');
    $catname = 'name_en  as name';
    $productname = 'name_en as name';

    if ($language == 'hi') {
        $catname = 'name_hi  as name';
        $productname = 'name_hi  as name';
       
    }
    $allproduct = IndCategory::select($catname,'id')->with('indProducts:id,status,price_unit,image,cat_id,'.$productname);
    if ($request->has('search')) {
        $search = Input::get('search');
        $allproduct =   IndCategory::select($catname,'id')->with('indProducts:id,status,price_unit,image,cat_id,'.$productname)->whereHas(
            'indProducts', function($q) use ($search) {
               // $q->select(['id','status','name_en']);
              $q->where('name_en', "like", "%" . $search . "%");
                  
            })
            ->orWhere('name_en', 'LIKE', '%'.$search.'%');
             
    }

   $product = $allproduct->paginate(20);
    $p = json_encode($product);
    $final = json_decode($p);
    $pagination = [
        'current_page' => $final->current_page,
        'last_page' => $final->last_page,

    ];

    if(count($product) > 0) {
        if($language == 'hi'){
            $queryStatus    = "सभी उत्पादों की सूची";
        }else{
            $queryStatus = "All Product List";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
            'catProduct' => $final->data,
            'pagination' => $pagination
        ]);
    }else{
        if($language == 'hi'){
            $queryStatus    = "सभी उत्पादों की सूची";
        }else{
            $queryStatus = "Product not found";
        }
        $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
    }

    return response()->json($response, 201);  
}
public function addIndSale(Request $request) {

    $user = $request->user;
    $language = $request->header('language');
    
    $orderData['user_id'] = $user->id;
    $orderData['seller_id'] = $request->user_id;//CLF id
    $orderData['otp'] = '1234';
    $orderData['mode_of_delivery'] = 'self';
    $orderData['delivery_status'] = 'pending';
    $orderData['order_status'] = 'pendind';
    $orderData['sale_date'] = $request->sale_date;

    $orderCreate = IndOrder::create($orderData);
    if($orderCreate){
        //update order id
        $str_result = str_pad($orderCreate->id, 5, "0", STR_PAD_LEFT);
        $orderShowId = 'INDORD'.$str_result;
        $orderCreate->update(['order_id_d' => $orderShowId]);
        //update product....
        foreach ($request->products as $value) {
            $productData = [
                'order_id' => $orderCreate->id,
                'product_id' => $value['product_id'],
                'quantity'   => $value['quantity']   
            ];
            $chkOrderItem = IndOrderItem::where('order_id',$orderCreate->id)->where('product_id',$value['product_id'])->first();
            if(!$chkOrderItem){
                IndOrderItem::create($productData);
            }
            
        }


        $input = $request->all();
        $input['review_by_user'] = $user->id;
        $input['order_id'] = $orderCreate->id;
        $input['type'] = 'individual';
        
        //add rating
        IndRating::create($input);

        //send push notification to CLF...
        $clfData = User::where('id',$request->user_id)->first();
        
            $notifyMsg1['id'] = $request->user_id;
            $notifyMsg1['title'] = 'Order';
            $notifyMsg1['type'] = 'individual_order';
            $notifyMsg1['order_id'] = $orderCreate->id;
            if($clfData->language =='hi'){
                $notifyMsg1['message']="नमस्ते $clfData->organization_name, $user->name ने आपके नाम से एक ऑर्डर बनाया है। कृपया अपने अनुभव को रेट करें।";
            }else{
                $notifyMsg1['message']="Hi $clfData->organization_name, $user->name has  created an order under your name. Rate your experience now";
            }
            //$notifyMsg1['message']="order";
        if($clfData->devicetoken){
         $this->sendPushNotification(array($clfData->devicetoken),$notifyMsg1, '1');
        }
        if($language == 'hi'){
            $queryStatus    = "आदेश सफलतापूर्वक जोड़ा गया";
        }else{
            $queryStatus = "Order Added Successfully";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus,'data'=>['id'=>$orderCreate->id]);
        return response()->json($response, 201);
    }else{
        
        $response   = array('status' => 'true' , 'statusCode' =>400, 'message'=> "Failed to convert interest to order");
        return response()->json($response, 201);
    }
    
}
public function IndOrderList(Request $request) {
    $user = $request->user;
    $language = $request->header('language');
   // print_r($user);
    $allOrder = IndOrder::with('indItems', 'getClf:id,name,email,mobile,organization_name,profileImage', 'GetIndividual:id,name,email,mobile', 'indItems.Indproduct:id,name_en')->where(['user_id'=> $user->id])->select('id','order_id_d','seller_id', 'user_id', 'message', 'id', 'otp','created_at')->latest()->get();
    
   
    if(count($allOrder) > 0) {
        if($language == 'hi'){
            $queryStatus    = "आदेश सूची";
        }else{
            $queryStatus = "Order List";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
            'order_list' => $allOrder
        ]);
    }else{
        if($language == 'hi'){
            $queryStatus    = "आदेश नहीं मिला";
        }else{
            $queryStatus = "Order not found";
        }
        $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
    }

    return response()->json($response, 201);   
}
//not in use
public function getIndOrderDetails(Request $request) {
    $user = $request->user;
    $orderId = $request->input('order_id');
    $language = $request->header('language');
   // $catname = 'name_en  as name';
    $productname = 'name_en as name_en';

    if ($language == 'hi') {
        $productname = 'name_hi  as name_en';
       
    }
    $allOrder = IndOrder::with('indItems','getClf:id,name,email,mobile,profileImage','GetIndividual:id,name,email,mobile,profileImage','indItems.Indproduct:id,'.$productname.',price_unit','clfRating','indRating')->where(['id'=> $orderId])->select('id','order_id_d','seller_id', 'user_id', 'message', 'id', 'otp','sale_date','created_at')->latest()->first();
  
    if($allOrder) {
        if($language == 'hi'){
            $queryStatus    = "आदेश सूची";
        }else{
            $queryStatus = "Order Details";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
            'order_details' => $allOrder
        ]);
    }else{
        if($language == 'hi'){
            $queryStatus    = "आदेश नहीं मिला";
        }else{
            $queryStatus = "Order not found";
        }
        $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
    }

    return response()->json($response, 201);   
}

public function indGetCLF(Request $request) {
    $user = $request->user;
    $language = $request->header('language');
    $clfUser = User::select('id','name','email','mobile','organization_name')->where('role_id',2)->where('block',$user->block)->where('isActive',1)->get();    
   
    if(count($clfUser) > 0) {
        if($language == 'hi'){
            $queryStatus    = "सूची";
        }else{
            $queryStatus = "CLF user List";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
            'CLF_list' => $clfUser
        ]);
    }else{
        if($language == 'hi'){
            $queryStatus    = "नहीं मिला";
        }else{
            $queryStatus = "User not found";
        }
        $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
    }

    return response()->json($response, 201);   
}
public function indGetReviews(Request $request) {
    $userId = $request->input('user_id');
    $language = $request->header('language');
    $ratings = IndRating::where('user_id', $userId)->with('getreviews')->paginate(20);
    $p = json_encode($ratings);
    $final = json_decode($p);
    $pagination = [
        'current_page' => $final->current_page,
        'last_page' => $final->last_page,
        

    ];

    if(count($ratings) > 0) {
        if($language == 'hi'){
            $queryStatus    = "रेटिंग सूची";
        }else{
            $queryStatus = "Rating List";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
            'ratings' => $final->data,
            'pagination' => $pagination
        ]);
    }else{
        if($language == 'hi'){
            $queryStatus    = "रेटिंग नहीं मिली";
        }else{
            $queryStatus = "Rating not found";
        }
        $response   = array('status' => 'false' , 'statusCode' =>400, 'message'=> $queryStatus);
    }

    return response()->json($response, 201);  
}

public function addFav(Request $request) {

    $language = $request->header('language');
    $user = $request->user;
   
                $fav = Favorite::updateOrCreate([
                    'user_id' => $user->id,
                    'seller_id' => $request->seller_id,
                   
                ],[ 'status' => $request->status]);   //0 unfollow ,1=follow
        
    
    
        if($language == 'hi'){
            $queryStatus    = "Favorite सफलतापूर्वक जोड़ा गया";
        }else{
            $queryStatus = "Favorite Added Successfully";
        }
        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus);
        return response()->json($response, 201);
}

public function indGetDetails(Request $request)
{
    $apitoken = $request->header('apitoken');
    $language = $request->header('language');
    $individual_id = $request->input('individual_id');
    $loginUser = $request->user;
    $user = User::where(['id'=> $individual_id])->select('id','name', 'email', 'mobile', 'profileImage', 'role_id' ,'organization_name')->first();

    if ($user && $apitoken) {
        if($language == 'hi'){
            $queryStatus    = "उपयोगकर्ता की जानकारी। ";
        }else{
            $queryStatus    = "User Detail.";
        }
        
        $statusCode     = 200;
        $status         = true;

        // Get User Address
        $personal   = null;
        $office     = null;
        $registered = null;
        $countrylName = 'name  as name';
        $stateLname = 'name as name';
        $distLname = 'name as name';
        $blockLname = 'name  as name';
        if ($language == 'hi') {
            $countrylName = 'name_kn  as name';
            $stateLname = 'name_kn  as name';
            $distLname = 'name_kn  as name';
            $blockLname = 'name_kn  as name';
        }
        

        $ratingCount = IndRating::where('user_id', $user->id)->count();
        $ratingStarAvg = DB::table('ind_ratings')->where('user_id', $user->id)->avg('rating');
        $rating = [
            'reviewCount' => $ratingCount,
            'ratingAvgStar' => $ratingStarAvg
        ];
        // echo "<pre>"; print_r($user); die("check");
        $user['selectInterest'] = false;
        $intData = [];
        if($user->role_id  == 9){
            $checkIndividualInterest =IndividualInterest::where('user_id',$user->id)->first();
            if($checkIndividualInterest){
                $user['selectInterest'] = true;
            }
            //get selected interest
            $individualInterest =IndividualInterest::select('id','user_id','individual_interest_list_id')->where('user_id',$user->id)->with('indInterest')->get();
            foreach($individualInterest as $ival){
                $intData[] =['id'=>$ival->id,'name_en'=>$ival->indInterest->name_en,'name_hi'=>$ival->indInterest->name_hi,'image'=>$ival->indInterest->image];
            }
            
        }
        $favStatus = 0;
        $favuser= Favorite::select('status')->where('user_id',$loginUser->id)->where('seller_id',$user->id)->first();
        if($favuser){
            $favStatus = $favuser->status;
        }
        //address

            if ($user->is_document_added == 1) {
                $documentstatus = Documents::where('user_id', $user->id)->select('is_adhar_verify','pancard_file','brn_file','adhar_card_front_file','adhar_card_back_file', 'is_pan_verify', 'is_brn_verify','is_aadhar_added','is_pan_added','is_brn_added')->first();

                $user['is_adhar_verify'] = $documentstatus->is_adhar_verify;
                $user['is_aadhar_added']   = $documentstatus->is_aadhar_added;
                $user['adhar_card_front_file']   = $documentstatus->adhar_card_front_file;
                $user['adhar_card_back_file']   = $documentstatus->adhar_card_back_file;
               
            }

            $registered = Address::where(['user_id' => $user->id, 'address_type' => 'registered'])->first();

            if ($registered) {
                $countryName = Country::where('id', $registered->country)->select('id', $countrylName)->first();
                $stateName   = State::where('id', $registered->state)->select('id', $stateLname)->first();
                $district    = City::where('id', $registered->district)->select('id',$distLname)->first();
                $blockData   = DB::table('blocks')->select('id',$blockLname)->where('id', $registered->block)->first();
                $registered = [
                    'id'               => $registered->id,
                    'address_line_one' => $registered->address_line_one,
                    'address_line_two' => $registered->address_line_two,
                    'pincode'          => $registered->pincode,
                    'block'            => $blockData->name,
                    'blockId'            => $blockData->id,
                    'countryId'        => $countryName->id,
                    'stateId'          => $stateName->id,
                    'districtId'       => $district->id,
                    'country'          => $countryName->name,
                    'state'            => $stateName->name,
                    'district'         => $district->name
                ];
            }

           
        $address = [
            'registered' => $registered
        ];

        $location = Location::where('user_id', $user->id)->select('id as locationId', 'lat', 'log')->first();

        $user->lat = null;
        $user->log = null;

        if ($location) {
            $user->lat = $location->lat;
            $user->log = $location->log;
        }
        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => [
            'user' => $user,  'address' =>$address,'rating' => $rating, 'individualInterest' => $intData, 'favStatus' => $favStatus
        ] );

        return response()->json($response, 201);
    } else {
        if($language == 'hi'){
            $queryStatus    = "कृपया सही एपीआई टाकन बतायें";
        }else{
            $queryStatus    = "Please provide valid api token.";
        }
        
        $statusCode     = 400;
        $status         = false;

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

        return response()->json($response, 201);
    }
}


//*****************************Cllection Center Apis */
public function getCollectionCenterOrder(Request $request) {
    try{
        $language = $request->header('language');
        $userId = $request->input('user_id');
        $centerId = $request->input('collection_center_id');
        $orderType = $request->input('order_type');
        $allOrder = [];
        if($centerId){
            $allOrder = Order::with('items', 'seller:id,name,organization_name', 'items.product:id,image_1')->where(['order_status' => $orderType,'collection_center_id' => $centerId])->select('id','order_id_d','seller_id','collection_center_id', 'user_id','id', 'interest_id', 'created_at')->latest()->get();
            if(count($allOrder)> 0){
                if($language == 'hi'){
                    $queryStatus    = "Order List";
                }else{
                    $queryStatus = "Order List";
                }
                $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                    'order_list' => $allOrder ]);
                return response()->json($response, 201);
            }else{
                if($language == 'hi'){
                    $queryStatus    = "आदेश नहीं मिला";
                }else{
                    $queryStatus = "Order not found";
                }
                $response   = array('status' => 'false' , 'statusCode' =>200, 'message'=> $queryStatus);
                return response()->json($response, 201);
            }
        }
            if($language == 'hi'){
                $queryStatus    = "Collection center id is required";
            }else{
                $queryStatus = "Collection center id is required";
            }
            $response   = array('status' => 'false' , 'statusCode' =>200, 'message'=> $queryStatus);
            return response()->json($response, 201);
    } catch (Throwable $e) {
        report($e);

        return false;
    }
   
}
public function updateOrderStatus(Request $request) {
    try{
        $language = $request->header('language');
        $orderId = $request->input('order_id');
        $orderStatus = $request->input('order_status');
        $orderData = Order::where('id',$orderId)->first();
        if($orderStatus == 'delivered'){
            $otp = $request->input('buyer_otp');
            $chkOtp = Order::where('id',$orderId)->where('otp',$otp)->first();
            if($chkOtp){
                $updateStatus = Order::where('id',$orderId)->update(['order_status'=>'delivered']);
                //send push notification to buyer...
                    $buyerData = User::where('id',$orderData->user_id)->first();
                    $notifyMsg1['id'] = $buyerData->id;
                    $notifyMsg1['title'] = 'Order';
                    $notifyMsg1['type'] = 'seller_order';
                    $notifyMsg1['order_id'] = $orderData->id;
                        if($buyerData->language =='hi'){
                            $notifyMsg1['message']="नमस्ते $buyerData->name, आपका ऑर्डर सफलतापूर्वक डिलीवर कर दिया गया है।";
                        }else{
                            $notifyMsg1['message']="Hi $buyerData->name, Your order has been delivered successfully.";
                        }
                    if($buyerData->devicetoken){
                        $this->sendPushNotification(array($buyerData->devicetoken),$notifyMsg1, '1');
                    }

                            //to seller
                        $sellerData = User::where('id',$orderData->seller_id)->first();
                        $notifyMsg['id'] = $sellerData->id;
                        $notifyMsg['title'] = 'Order';
                        $notifyMsg['type'] = 'seller_order';
                        $notifyMsg['order_id'] = $orderData->id;
                            if($sellerData->language =='hi'){
                                $notifyMsg['message']="नमस्ते $sellerData->name, आपका ऑर्डर डिलीवरी के लिए बाहर है।।";
                            }else{
                                $notifyMsg['message']="Hi $sellerData->name, Your order is out for delivery.";
                            }
                        if($sellerData->devicetoken){
                            $this->sendPushNotification(array($sellerData->devicetoken),$notifyMsg, '1');
                        }
            }else{
                if($language == 'hi'){
                    $queryStatus    = "Invalid Otp";
                }else{
                    $queryStatus    = "Invalid Otp.";
                }
                $statusCode     = 400;
                $status         = false;
                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );
                return response()->json($response, 201);
            }
        }elseif($orderStatus == 'received'){
            $updateStatus = Order::where('id',$orderId)->update(['order_status'=>'received']);
            
            //send push notification to buyer...
            $buyerData = User::where('id',$orderData->user_id)->first();
                $notifyMsg1['id'] = $buyerData->id;
                $notifyMsg1['title'] = 'Order';
                $notifyMsg1['type'] = 'seller_order';
                $notifyMsg1['order_id'] = $orderData->id;
                    if($buyerData->language =='hi'){
                        $notifyMsg1['message']="नमस्ते $buyerData->name, आपका ऑर्डर कलेक्शन सेंटर पर पहुंच गया है। कृपया इसे इकट्ठा करें";
                    }else{
                        $notifyMsg1['message']="Hi $buyerData->name, Your order has been arrive at collection centre. Please collect it";
                    }
                if($buyerData->devicetoken){
                    $this->sendPushNotification(array($buyerData->devicetoken),$notifyMsg1, '1');
                }

                //to seller
                //send push notification to buyer...
            $sellerData = User::where('id',$orderData->seller_id)->first();
            $notifyMsg['id'] = $sellerData->id;
            $notifyMsg['title'] = 'Order';
            $notifyMsg['type'] = 'seller_order';
            $notifyMsg['order_id'] = $orderData->id;
                if($sellerData->language =='hi'){
                    $notifyMsg['message']="नमस्ते $sellerData->name, आपका ऑर्डर आपके कलेक्शन सेंटर को मिल गया है।";
                }else{
                    $notifyMsg['message']="Hi $sellerData->name,  Your order has been received by your collection centre.";
                }
            if($sellerData->devicetoken){
                $this->sendPushNotification(array($sellerData->devicetoken),$notifyMsg, '1');
            }
        }
                    
            if($language == 'hi'){
                $queryStatus    = "आदेश की स्थिति सफलतापूर्वक अपडेट की गई";
            }else{
                $queryStatus = "Order status updated successfully";
            }
            $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus);
            return response()->json($response, 201);
    } catch (Throwable $e) {
        report($e);

        return false;
    }
   
}
///---------product certificate -------

public function getCertificateTypeList(Request $request) {
    try{
        $language = $request->header('language');
        $name = 'name_en  as name';
        if ($language == 'hi') {
            $name = 'name_hi  as name';
           
        }
            $typeList = CertificateType::select('id',$name)->get();
                if($language == 'hi'){
                    $queryStatus    = "Type List";
                }else{
                    $queryStatus = "Type List";
                }
                $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                    'type_list' => $typeList ]);
                return response()->json($response, 201);
           
        
        
    } catch (Throwable $e) {
        report($e);

        return false;
    }
   
}
public function getGrievanceTypeList(Request $request) {
    try{
        $language = $request->header('language');
        $name = 'title_en  as name';
        if ($language == 'hi') {
            $name = 'title_hi  as name';
           
        }
            $typeList = GrievanceIssueType::select('id',$name)->get();
                if($language == 'hi'){
                    $queryStatus    = "Type List";
                }else{
                    $queryStatus = "Type List";
                }
                $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                    'grievance_list' => $typeList ]);
                return response()->json($response, 201);
    } catch (Throwable $e) {
        report($e);
        return false;
    }
}
public function addGrievanceTicket(Request $request) {
    try{
        $language = $request->header('language');
        $userId = $request->user_id;
        $addData['user_id'] =  $userId;
        $addData['issue_type_id'] = $request->issue_type_id;
        $addData['message'] = $request->concern;
        $addGrievance = Grievance::create($addData);
            $str_result = str_pad(1000+$addGrievance->id, 5, "0", STR_PAD_LEFT);
        $grShowId = 'TICK'.$str_result;
        $addGrievance->update(['ticket_id' => $grShowId]);
                if($language == 'hi'){
                    $queryStatus    = "सफलतापूर्वक जोड़ा गया";
                }else{
                    $queryStatus = "Added successfully";
                }
                $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus);
                return response()->json($response, 201);
        
    } catch (Throwable $e) {
        report($e);

        return false;
    }
   
}
public function addGrievanceMessage(Request $request) {
    try{
        $language = $request->header('language');
        
        $addData['grievance_id'] = $request->grievance_id;
        $addData['message'] = $request->message;
        $addData['type'] = 'by_user';
        $chkStatus = Grievance::where('id',$request->grievance_id)->first();
        if($chkStatus){
            if($chkStatus->status == 0){ //if open
                $addGrievance = GrievanceMessage::create($addData);
                $messageDate= date('y-m-d H:i:s');
                Grievance::where('id',$request->grievance_id)->update(['last_message_date' => $messageDate]);
                        if($language == 'hi'){
                            $queryStatus    = "सफलतापूर्वक जोड़ा गया";
                        }else{
                            $queryStatus = "Added successfully";
                        }
                        $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus);
                        return response()->json($response, 201);
            }else{
                if($language == 'hi'){
                    $queryStatus    = "इस टिकट को बंद के रूप में चिह्नित किया गया है। यदि आपकी समस्या का समाधान नहीं होता है, तो एक नया टिकट खोलें।";
                }else{
                    $queryStatus = "This ticket has been marked closed. In case if your issue is not resolved, open a new ticket.";
                }
                $response   = array('status' => 'false' , 'statusCode' =>200, 'message'=> $queryStatus);
                return response()->json($response, 201);
            }
           
        }
       
        
    } catch (Throwable $e) {
        report($e);
        return false;
    }
   
}
public function getGrievanceTicketList(Request $request) {
    try{
        $language = $request->header('language');
        $userId  = $request->user_id;
        $name = 'title_en  as name';
        if ($language == 'hi') {
            $name = 'title_hi  as name';
           
        }
        //select('id',$name)->s
            $typeList = Grievance::where('user_id',$userId)->latest()->get();
            if(count($typeList) > 0){
                
                if($language == 'hi'){
                    $queryStatus    = "टिकट सूची";
                }else{
                    $queryStatus = "Ticket List";
                }
                $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                    'ticket_list' => $typeList ]);
                return response()->json($response, 201);
            }else{
                if($language == 'hi'){
                    $queryStatus    = "रिकॉर्ड नहीं मिला";
                }else{
                    $queryStatus = "Record not found";
                }
                $response   = array('status' => 'false' , 'statusCode' =>200, 'message'=> $queryStatus);
                return response()->json($response, 201);
            }
               
    } catch (Throwable $e) {
        report($e);
        return false;
    }
}
public function getTicketChatList(Request $request) {
    try{
        $language = $request->header('language');
        $userId  = $request->user_id;
        $grievance_id = $request->grievance_id;
        $name = 'title_en  as name';
        if ($language == 'hi') {
            $name = 'title_hi  as name';
           
        }
        //select('id',$name)->s
            $typeList = Grievance::select('id','user_id','ticket_id','issue_type_id','message','status')->with('getIssue:id,'.$name,'getMessage')->where('user_id',$userId)->where('id',$grievance_id)->get();
            if(count($typeList) > 0){
                
                if($language == 'hi'){
                    $queryStatus    = "टिकट चैट सूची";
                }else{
                    $queryStatus = "Ticket chat List";
                }
                $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                    'ticket_chat_list' => $typeList ]);
                return response()->json($response, 201);
            }else{
                if($language == 'hi'){
                    $queryStatus    = "रिकॉर्ड नहीं मिला";
                }else{
                    $queryStatus = "Record not found";
                }
                $response   = array('status' => 'false' , 'statusCode' =>200, 'message'=> $queryStatus);
                return response()->json($response, 201);
            }
               
    } catch (Throwable $e) {
        report($e);
        return false;
    }
}
public function getSurveyList(Request $request) {
    try{
        $language = $request->header('language');
        $name = 'title_en  as name';
        if ($language == 'hi') {
            $name = 'title_hi  as name';
           
        }
            $surveyList = Survey::select('id','message','google_url','start_date','end_date')->get();
                if($language == 'hi'){
                    $queryStatus    = "सर्वेक्षण सूची";
                }else{
                    $queryStatus = "Survey List";
                }
                $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> $queryStatus, "data" => [
                    'survey_list' => $surveyList ]);
                return response()->json($response, 201);
    } catch (Throwable $e) {
        report($e);
        return false;
    }
}
public function addPiceTestingScript(Request $request) {
    try{
        
            $productItem = OrderItem::get();
            foreach($productItem as $val){
                
                $productPrice = ProductMaster::where('id',$val->product_id)->first();
                //echo $productPrice->price;
                $updateprice = OrderItem::where('id',$val->id)->update(['product_price'=>$productPrice->price]);
            }
                $response   = array('status' => 'true' , 'statusCode' =>200, 'message'=> 'ok');
                return response()->json($response, 201);
    } catch (Throwable $e) {
        report($e);
        return false;
    }
}
    //notification function
    public function sendPushNotification(array $device_tokens,array $msg, $device_type)
	{
			//$FIREBASE_API_KEY="AAAAMRPYmqU:APA91bH91L6cHaEAJlboAuNPMC-B06qc1dS1RnDtZA7xFS165w1A25E8F-ZqeP0AHQjCGKZz5Cg3617KR-QuLMxVgMNUhnLejvJY1EVnwXU7C8jxD10DViSnaeCW9jOd4rY9p2j_zmFG";
			$FIREBASE_API_KEY="AAAAoztDo6Y:APA91bE5S04pwIRsw_IeQFBPVnZ79ugI076sRHYq8CKwb8DA3FNrMfLoLpN2SAATrpzQnn2tkL1sGAKUicp4bKaQ0PplnIZNLlW9AfL37PbL5hF9_y4fWOvp4MRmXM1fsdaNasVVpyD9";
			// prep the bundle
			$test['aps']['mutable-content']=1;
			if($device_type==1){
				$fields = array
					(
					'registration_ids' => $device_tokens,
					'data' => $msg,
					//'apns'=>$test,
					);	
			}elseif($device_type==2){
				$fields = array
					(
					'registration_ids' => $device_tokens,
					'notification' => $msg,
					//"category"=>'SECRET',
					"mutable_content"=>true,
					
					);
			}
			$headers = array
			(
			'Authorization: key=' . $FIREBASE_API_KEY,
			'Content-Type: application/json',
			//'apns'=>$test,
			);

			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers);
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true);
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($fields));
			$result[] = curl_exec($ch );
			$info = curl_getinfo($ch);
			curl_close( $ch );
			//print_r($result);
			return $result;
    }

    
}
