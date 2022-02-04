<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
use App\Rating;
use App\Favorite;
use App\ProductCertification;
use Helper;

use DB;
use Illuminate\Support\Facades\Storage;
use App\Banner;
use App\Location;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;
use App\Mail\Enquiry;

class ProductController extends Controller
{
    /**
     * Add Product
     */
    public function addproduct(Request $request)
    {
        $user = $request->user;
        $language = $request->header('language');
        $rules = [

            'material_id'    => 'required',
            'qty'            => 'required',
            'localname_en'   => 'required',
            'localname_kn'   => 'required',
            'categoryId'     => 'required',
            'subcategoryId'  => 'required',
            'is_draft'       => 'required',
            'template_id'    => 'required',
            'price'          => 'required',

        ];

        if ($request->is_draft == 0) {
            $rules = [

                'image_1'        => 'required',
                'des_en'         => 'required',
                'des_kn'         => 'required',
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        if ($request->is_draft == 0) {
            $template = ProductTemplate::where('id', $request->template_id)
            ->select('length', 'width', 'height', 'volume', 'weight','no_measurement')
            ->first();

           
        }
        
        
        $input = $request->input();
        $input['user_id'] = $request->user->id;
        unset($input['user']);

        // Check for save for draft

        $product = ProductMaster::where(['user_id' => $input['user_id'], 'is_draft' => 1])->first();


        if ($product && ($input['is_draft'] == 1)) {
            $response = array('status' => false , 'statusCode' => 400);
            $response['message'] = "You have already product in your draft, Please add or remove from draft.";
            return response()->json($response);
        }

            if($request->template_id == -1){
                $queryStatus    = "Template not found.";
                $statusCode     = 401;
                $status         = false;
                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
                return response()->json($response, 201);
            }
            if($request->material_id == -1){
                $queryStatus    = "Material not found.";
                $statusCode     = 401;
                $status         = false;
                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
                return response()->json($response, 201);
            }
            //chk category
            $chkCat = Category::where('id',$request->categoryId)->where('is_active', 1)->first();
            if(empty($chkCat)){
                $queryStatus    = "Category not found.";
                $statusCode     = 401;
                $status         = false;
                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
                return response()->json($response, 201);
            }
            //chk sub category....
            $chkSubCat = Category::where('parent_id',$request->categoryId)->where('id',$request->subcategoryId)->where('is_active', 1)->first();
            if(empty($chkSubCat)){
                $queryStatus    = "Sub Category not found.";
                $statusCode     = 401;
                $status         = false;
                $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
                return response()->json($response, 201);
            }
        /** Upload Images */

        if ($request->file('image_1')) {
            $image_file_1 = $request->file('image_1');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_1_image = date('YmdHis') . rand(111, 9999). "productimage1." . $image_file_1->getClientOriginalExtension();
            $aa = $image_file_1->move($folder, $image_file_1_image);

            $image_file_1_image_name = $image_file_1_image;
            $image_file_1_image = 'images/product/'.$user->id.'/'.$image_file_1_image_name;

            $input['image_1'] = $image_file_1_image;
        }
        /** Upload Image 2 */

        if ($request->file('image_2')) {
            $image_file_2 = $request->file('image_2');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_2_image = date('YmdHis') . rand(111, 9999). "productimage2." . $image_file_2->getClientOriginalExtension();
            $aa = $image_file_2->move($folder, $image_file_2_image);

            $image_file_2_image_name = $image_file_2_image;
            $image_file_2_image = 'images/product/'.$user->id.'/'.$image_file_2_image_name;

            $input['image_2'] = $image_file_2_image;
        }


        /** Upload Image 3 */

        if ($request->file('image_3')) {
            $image_file_3 = $request->file('image_3');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_3_image = date('YmdHis') . rand(111, 9999). "productimage3." . $image_file_3->getClientOriginalExtension();
            $aa = $image_file_3->move($folder, $image_file_3_image);

            $image_file_3_image_name = $image_file_3_image;
            $image_file_3_image = 'images/product/'.$user->id.'/'.$image_file_3_image_name;

            $input['image_3'] = $image_file_3_image;
        }


        /** Upload Image 4 */

        if ($request->file('image_4')) {
            $image_file_4 = $request->file('image_4');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_4_image = date('YmdHis') . rand(111, 9999). "productimage4." . $image_file_4->getClientOriginalExtension();
            $aa = $image_file_4->move($folder, $image_file_4_image);

            $image_file_4_image_name = $image_file_4_image;
            $image_file_4_image = 'images/product/'.$user->id.'/'.$image_file_4_image_name;

            $input['image_4'] = $image_file_4_image;
        }

        /** Upload Image 5 */

        if ($request->file('image_5')) {
            $image_file_5 = $request->file('image_5');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_5_image = date('YmdHis') . rand(111, 9999)."productimage5." . $image_file_5->getClientOriginalExtension();
            $aa = $image_file_5->move($folder, $image_file_5_image);

            $image_file_5_image_name = $image_file_5_image;
            $image_file_5_image = 'images/product/'.$user->id.'/'.$image_file_5_image_name;

            $input['image_5'] = $image_file_5_image;
        }

        $addProduct = ProductMaster::create($input);
        $str_result = str_pad($addProduct->id, 5, "0", STR_PAD_LEFT);
        $productShowId = 'PDX'.$str_result;
        $addProduct->update(['product_id_d' => $productShowId]);
        if ($addProduct) {
            //add certificate
            if ($request->file('certificate_image_1')) {
                $image_file_1 = $request->file('certificate_image_1');
                $folder = public_path('images/product/certificate/' . $user->id . '/');
    
                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }
    
                $image_file_1_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_1->getClientOriginalExtension();
                $aa = $image_file_1->move($folder, $image_file_1_cerf);
    
                $image_file_1_image_name = $image_file_1_cerf;
                $image_file_1_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_1_image_name;
    
                $certificateData['certificate_image_1'] = $image_file_1_cerf;
                $certificateData['certificate_type_1'] = $request->certificate_type_1;
            }
    
            if ($request->file('certificate_image_2')) {
                $image_file_2 = $request->file('certificate_image_2');
                $folder = public_path('images/product/certificate/' . $user->id . '/');
    
                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }
    
                $image_file_2_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_2->getClientOriginalExtension();
                $aa = $image_file_2->move($folder, $image_file_2_cerf);
    
                $image_file_2_image_name = $image_file_2_cerf;
                $image_file_2_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_2_image_name;
    
                $certificateData['certificate_image_2'] = $image_file_2_cerf;
                $certificateData['certificate_type_2'] = $request->certificate_type_2;
            }
            if ($request->file('certificate_image_3')) {
                $image_file_3 = $request->file('certificate_image_3');
                $folder = public_path('images/product/certificate/' . $user->id . '/');
    
                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }
    
                $image_file_3_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_3->getClientOriginalExtension();
                $aa = $image_file_3->move($folder, $image_file_3_cerf);
    
                $image_file_3_image_name = $image_file_3_cerf;
                $image_file_3_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_3_image_name;
    
                $certificateData['certificate_image_3'] = $image_file_3_cerf;
                $certificateData['certificate_type_3'] = $request->certificate_type_3;
            }
            if ($request->file('certificate_image_4')) {
                $image_file_4 = $request->file('certificate_image_4');
                $folder = public_path('images/product/certificate/' . $user->id . '/');
    
                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }
    
                $image_file_4_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_4->getClientOriginalExtension();
                $aa = $image_file_4->move($folder, $image_file_4_cerf);
    
                $image_file_4_image_name = $image_file_4_cerf;
                $image_file_4_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_4_image_name;
    
                $certificateData['certificate_image_4'] = $image_file_4_cerf;
                $certificateData['certificate_type_4'] = $request->certificate_type_4;
            }
            if ($request->file('certificate_image_5')) {
                $image_file_5 = $request->file('certificate_image_5');
                $folder = public_path('images/product/certificate/' . $user->id . '/');
    
                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }
    
                $image_file_5_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_5->getClientOriginalExtension();
                $aa = $image_file_5->move($folder, $image_file_5_cerf);
    
                $image_file_5_image_name = $image_file_5_cerf;
                $image_file_5_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_5_image_name;
    
                $certificateData['certificate_image_5'] = $image_file_5_cerf;
                $certificateData['certificate_type_5'] = $request->certificate_type_5;
            }
            if ($request->file('certificate_image_6')) {
                $image_file_6 = $request->file('certificate_image_6');
                $folder = public_path('images/product/certificate/' . $user->id . '/');
    
                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }
    
                $image_file_6_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_6->getClientOriginalExtension();
                $aa = $image_file_6->move($folder, $image_file_6_cerf);
    
                $image_file_6_image_name = $image_file_6_cerf;
                $image_file_6_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_6_image_name;
    
                $certificateData['certificate_image_6'] = $image_file_6_cerf;
                $certificateData['certificate_type_6'] = $request->certificate_type_6;
            }
            if ($request->file('certificate_image_7')) {
                $image_file_7 = $request->file('certificate_image_7');
                $folder = public_path('images/product/certificate/' . $user->id . '/');
    
                if (!Storage::exists($folder)) {
                    Storage::makeDirectory($folder, 0775, true, true);
                }
    
                $image_file_7_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_7->getClientOriginalExtension();
                $aa = $image_file_7->move($folder, $image_file_7_cerf);
    
                $image_file_7_image_name = $image_file_7_cerf;
                $image_file_7_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_7_image_name;
    
                $certificateData['certificate_image_7'] = $image_file_7_cerf;
                $certificateData['certificate_type_7'] = $request->certificate_type_7;
            }
            if($request->file('certificate_image_1') || $request->file('certificate_image_2') || $request->file('certificate_image_3') || $request->file('certificate_image_4') || $request->file('certificate_image_5') || $request->file('certificate_image_6') || $request->file('certificate_image_7')){
                $certificateData['product_id'] = $addProduct->id;
                $chkAlreadyCer = ProductCertification::where('product_id',$addProduct->id)->first();
                if($chkAlreadyCer){
                     $chkAlreadyCer->update($certificateData);
                }else{
                    $insertData = ProductCertification::create($certificateData);
    
                }
               
            }
            if($language == 'hi'){
                $queryStatus    = "उत्पाद जोड़ा गया सफलतापूर्वक ";
            }else{
                $queryStatus    = "Product added successfully";
            }
            
            $statusCode     = 200;
            $status         = true;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
        } else {
            $queryStatus    = "Failed to add product.";
            $statusCode     = 401;
            $status         = false;

            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
        }

        return response()->json($response, 201);
    }

    /**
     * Get product
     */
    public function getproduct(Request $request)
    {
        $user = $request->user;

        $language = $request->header('language');

        $categoryIds = ProductMaster::where(['user_id' => $user->id, 'is_active' => 1, 'is_draft' => 0])->distinct('categoryId')->select('categoryId')->get();

        $catArr = [];

        foreach ($categoryIds as $cat) {
            $catArr[] = $cat->categoryId;
        }

        $categoryDetail = Category::wherein('id', $catArr)->get();

        $subcategoryId = Category::wherein('parent_id', $catArr)->get();

        $products = ProductMaster::wherein('categoryId', $catArr)->where('user_id', $user->id)->get();

        $productArr = [];
        foreach ($categoryDetail as $key => $category) {
            if ($language == 'en') {
                $productArr[$key]['categoryName'] = $category->name_en;
            }
            if ($language == 'hi') {
                $productArr[$key]['categoryName'] = $category->name_kn;
            }
            foreach ($subcategoryId as $key1 => $subcategory) {
                if ($language == 'en') {
                    $productArr[$key]['subCategoryName'] = $subcategory->name_en;
                }
                if ($language == 'hi') {
                    $productArr[$key]['subCategoryName'] = $subcategory->name_kn;
                }

                $productArr[$key]['product'] = ProductMaster::where(['user_id' => $user->id, 'is_active' => 1, 'is_draft' => 0])->get();
            }
        }


        echo "<pre>";
        print_r($productArr);
        die("check");
    }

    /**
     * get draft product
     */
    public function getdraftproduct(Request $request)
    {
        $user = $request->user;

        $language = $request->header('language');


        $descriptionname = 'des_en  as description';
        $productname = 'localname_en as name';

        if ($language == 'hi') {
            $descriptionname = 'des_kn  as description';
            $productname = 'localname_kn  as name';
        }
        $draftproduct = ProductMaster::where(['user_id' => $user->id, 'is_draft' => 1])->first();
        $queryStatus    = "No product found in draft.";
        $statusCode     = 401;
        $status         = false;

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
        // && $checkcategory
        if ($draftproduct) {
            $queryStatus    = "Draft product.";
            $statusCode     = 200;
            $status         = true;


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => [
                'draftproduct' => $draftproduct
             ]);
        }

        return response()->json($response, 201);
    }

    /**
     * Remove Draft Product
     */
    public function removedraftproduct(Request $request)
    {
        $user = $request->user;

        $rules = [
            'productId' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }


        $removedraftproduct = ProductMaster::where(['id' => $request->productId, 'is_active' => 1, 'is_draft' => 1])->update(['is_active' => 0, 'is_draft' => 0]);

        $queryStatus    = "Draft product removed.";
        $statusCode     = 200;
        $status         = true;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        return response()->json($response, 201);
    }

    /**
     * Remove Product
     */

    public function deleteproduct(Request $request)
    {
        $user = $request->user;

        $rules = [
            'productId' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }



        //$removeProduct = ProductMaster::where(['id' => $request->productId, 'user_id' => $user->id, 'is_active' => 1, 'is_draft' => 0])->update(['is_active' => 0]);
        $removeProduct = ProductMaster::where(['id' => $request->productId, 'user_id' => $user->id, 'is_active' => 1, 'is_draft' => 0])->delete();


        if ($removeProduct) {
            $queryStatus    = "Product removed.";
            $statusCode     = 200;
            $status         = true;
        } else {
            $queryStatus    = "Failed to removed product.";
            $statusCode     = 200;
            $status         = true;
        }



        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        return response()->json($response, 201);
    }


    /**
     * Update Draft Product
     */

    public function updatedraftproduct(Request $request)
    {
        $user = $request->user;
        $language = $request->header('language');
        // $rules = [
        //     'material_id'    => 'required',
        //     'price'          => 'required',
        //     'qty'            => 'required',
        //     'localname_en'   => 'required',
        //     'localname_kn'   => 'required',
        //     'productId'      => 'required',
        //     'categoryId'     => 'required',
        //     'subcategoryId'  => 'required',
        //     'des_en'         => 'required',
        //     'des_kn'         => 'required',
        //     'is_draft'       => 'required',
        //     'template_id'    => 'required'
        // ];

        $rules = [

            'material_id'    => 'required',
            'qty'            => 'required',
            'localname_en'   => 'required',
            'localname_kn'   => 'required',
            'categoryId'     => 'required',
            'subcategoryId'  => 'required',
            'is_draft'       => 'required',
            'template_id'    => 'required',
            'price'          => 'required'

        ];

        if ($request->is_draft == 0) {
            $productDetail = ProductMaster::where('id', $request->productId)->select('image_1')->first();

            if (!$productDetail->image_1) {
                $rules = [

                    'image_1'        => 'required'
                ];
            }

            $rules = [
                'des_en'         => 'required',
                'des_kn'         => 'required',
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        if ($request->is_draft == 0) {
            $template = ProductTemplate::where('id', $request->template_id)
            ->select('length', 'width', 'height', 'volume', 'weight','no_measurement')
            ->first();
            /*
            if ($template->length == 'on') {
                $dimensionRule['length'] = 'required';
                $dimensionRule['length_unit'] = 'required';
            }
            if ($template->width == 'on') {
                $dimensionRule['width'] = 'required';
                $dimensionRule['width_unit'] = 'required';
            }
            if ($template->height == 'on') {
                $dimensionRule['height'] = 'required';
                $dimensionRule['height_unit'] = 'required';
            }
            if ($template->volume == 'on') {
                $dimensionRule['volume'] = 'required';
                $dimensionRule['vol_unit'] = 'required';
            }
            if ($template->weight == 'on') {
                $dimensionRule['weight'] = 'required';
                $dimensionRule['weight_unit'] = 'required';
            }
            */

            // $validator = Validator::make($request->all(), $dimensionRule);

            // if ($validator->fails()) {
            //     $response = array('status' => false , 'statusCode' =>400);
            //     $response['message'] = $validator->messages()->first();
            //     return response()->json($response);
            // }
        }
        //chk category
        if($request->template_id == -1){
            $queryStatus    = "Template not found.";
            $statusCode     = 401;
            $status         = false;
            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
            return response()->json($response, 201);
        }
        if($request->material_id == -1){
            $queryStatus    = "Material not found.";
            $statusCode     = 401;
            $status         = false;
            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
            return response()->json($response, 201);
        }
        //chk category
        $chkCat = Category::where('id',$request->categoryId)->where('is_active', 1)->first();
        if(empty($chkCat)){
            $queryStatus    = "Category not found.";
            $statusCode     = 401;
            $status         = false;
            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
            return response()->json($response, 201);
        }
        //chk sub category....
        $chkSubCat = Category::where('parent_id',$request->categoryId)->where('id',$request->subcategoryId)->where('is_active', 1)->first();
        if(empty($chkSubCat)){
            $queryStatus    = "Sub Category not found.";
            $statusCode     = 401;
            $status         = false;
            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
            return response()->json($response, 201);
        }
        $input = $request->input();
        // if($request->is_draft ==) {

        // }
        // $input['is_draft'] = 1;
        // $input['user_id'] = $request->user->id;
        unset($input['user']);
        unset($input['productId']);
        unset($input['shgartisanId']);
        unset($input['certificate_image_1']);
        unset($input['certificate_image_2']);
        unset($input['certificate_image_3']);
        unset($input['certificate_image_4']);
        unset($input['certificate_image_5']);
        unset($input['certificate_image_6']);
        unset($input['certificate_image_7']);
        unset($input['certificate_status_1']);
        unset($input['certificate_status_2']);
        unset($input['certificate_status_3']);
        unset($input['certificate_status_4']);
        unset($input['certificate_status_5']);
        unset($input['certificate_status_6']);
        unset($input['certificate_status_7']);
        unset($input['certificate_type_1']);
        unset($input['certificate_type_2']);
        unset($input['certificate_type_3']);
        unset($input['certificate_type_4']);
        unset($input['certificate_type_5']);
        unset($input['certificate_type_6']);
        unset($input['certificate_type_7']);

        /** Upload Images */

        if ($request->file('image_1')) {
            $image_file_1 = $request->file('image_1');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_1_image = date('YmdHis') . rand(111, 9999). "productimage1." . $image_file_1->getClientOriginalExtension();
            $aa = $image_file_1->move($folder, $image_file_1_image);

            $image_file_1_image_name = $image_file_1_image;
            $image_file_1_image = 'images/product/'.$user->id.'/'.$image_file_1_image_name;

            $input['image_1'] = $image_file_1_image;
        }


        /** Upload Image 2 */

        if ($request->file('image_2')) {
            $image_file_2 = $request->file('image_2');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_2_image = date('YmdHis') . rand(111, 9999). "productimage2." . $image_file_2->getClientOriginalExtension();
            $aa = $image_file_2->move($folder, $image_file_2_image);

            $image_file_2_image_name = $image_file_2_image;
            $image_file_2_image = 'images/product/'.$user->id.'/'.$image_file_2_image_name;

            $input['image_2'] = $image_file_2_image;
        }


        /** Upload Image 3 */

        if ($request->file('image_3')) {
            $image_file_3 = $request->file('image_3');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_3_image = date('YmdHis') . rand(111, 9999). "productimage3." . $image_file_3->getClientOriginalExtension();
            $aa = $image_file_3->move($folder, $image_file_3_image);

            $image_file_3_image_name = $image_file_3_image;
            $image_file_3_image = 'images/product/'.$user->id.'/'.$image_file_3_image_name;

            $input['image_3'] = $image_file_3_image;
        }


        /** Upload Image 4 */

        if ($request->file('image_4')) {
            $image_file_4 = $request->file('image_4');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_4_image = date('YmdHis') . rand(111, 9999). "productimage4." . $image_file_4->getClientOriginalExtension();
            $aa = $image_file_4->move($folder, $image_file_4_image);

            $image_file_4_image_name = $image_file_4_image;
            $image_file_4_image = 'images/product/'.$user->id.'/'.$image_file_4_image_name;

            $input['image_4'] = $image_file_4_image;
        }

        /** Upload Image 5 */

        if ($request->file('image_5')) {
            $image_file_5 = $request->file('image_5');
            $folder = public_path('images/product/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_5_image = date('YmdHis') . rand(111, 9999)."productimage5." . $image_file_5->getClientOriginalExtension();
            $aa = $image_file_5->move($folder, $image_file_5_image);

            $image_file_5_image_name = $image_file_5_image;
            $image_file_5_image = 'images/product/'.$user->id.'/'.$image_file_5_image_name;

            $input['image_5'] = $image_file_5_image;
        }

        if ($request->image_1) {
            if (!$request->file('image_1')) {
                $input['image_1'] = $request->image_1;
            }
        } else {
            $input['image_1'] = null;
        }

        if ($request->image_2) {
            if (!$request->file('image_2')) {
                $input['image_2'] = $request->image_2;
            }
        } else {
            $input['image_2'] = null;
        }

        if ($request->image_3) {
            if (!$request->file('image_3')) {
                $input['image_3'] = $request->image_3;
            }
        } else {
            $input['image_3'] = null;
        }

        if ($request->image_4) {
            if (!$request->file('image_4')) {
                $input['image_4'] = $request->image_4;
            }
        } else {
            $input['image_4'] = null;
        }

        if ($request->image_5) {
            if (!$request->file('image_5')) {
                $input['image_5'] = $request->image_5;
            }
        } else {
            $input['image_5'] = null;
        }

        // echo "<pre>";  print_r($input); die("check");

        $updatedraftproduct = ProductMaster::where('id', $request->productId)->update($input);

        //add certificate
        if ($request->file('certificate_image_1')) {
            $image_file_1 = $request->file('certificate_image_1');
            $folder = public_path('images/product/certificate/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_1_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_1->getClientOriginalExtension();
            $aa = $image_file_1->move($folder, $image_file_1_cerf);

            $image_file_1_image_name = $image_file_1_cerf;
            $image_file_1_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_1_image_name;

            $certificateData['certificate_image_1'] = $image_file_1_cerf;
            $certificateData['certificate_status_1'] = 0;
            
        }

        if ($request->file('certificate_image_2')) {
            $image_file_2 = $request->file('certificate_image_2');
            $folder = public_path('images/product/certificate/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_2_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_2->getClientOriginalExtension();
            $aa = $image_file_2->move($folder, $image_file_2_cerf);

            $image_file_2_image_name = $image_file_2_cerf;
            $image_file_2_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_2_image_name;

            $certificateData['certificate_image_2'] = $image_file_2_cerf;
            $certificateData['certificate_status_2'] = 0;  
        }
        if ($request->file('certificate_image_3')) {
            $image_file_3 = $request->file('certificate_image_3');
            $folder = public_path('images/product/certificate/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_3_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_3->getClientOriginalExtension();
            $aa = $image_file_3->move($folder, $image_file_3_cerf);

            $image_file_3_image_name = $image_file_3_cerf;
            $image_file_3_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_3_image_name;

            $certificateData['certificate_image_3'] = $image_file_3_cerf;
            $certificateData['certificate_status_3'] = 0;
        }
        if ($request->file('certificate_image_4')) {
            $image_file_4 = $request->file('certificate_image_4');
            $folder = public_path('images/product/certificate/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_4_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_4->getClientOriginalExtension();
            $aa = $image_file_4->move($folder, $image_file_4_cerf);

            $image_file_4_image_name = $image_file_4_cerf;
            $image_file_4_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_4_image_name;

            $certificateData['certificate_image_4'] = $image_file_4_cerf;
            $certificateData['certificate_status_4'] = 0;
        }
        if ($request->file('certificate_image_5')) {
            $image_file_5 = $request->file('certificate_image_5');
            $folder = public_path('images/product/certificate/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_5_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_5->getClientOriginalExtension();
            $aa = $image_file_5->move($folder, $image_file_5_cerf);

            $image_file_5_image_name = $image_file_5_cerf;
            $image_file_5_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_5_image_name;

            $certificateData['certificate_image_5'] = $image_file_5_cerf;
            $certificateData['certificate_status_5'] = 0;
            
        }
        if ($request->file('certificate_image_6')) {
            $image_file_6 = $request->file('certificate_image_6');
            $folder = public_path('images/product/certificate/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_6_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_6->getClientOriginalExtension();
            $aa = $image_file_6->move($folder, $image_file_6_cerf);

            $image_file_6_image_name = $image_file_6_cerf;
            $image_file_6_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_6_image_name;

            $certificateData['certificate_image_6'] = $image_file_6_cerf;
            $certificateData['certificate_status_6'] = 0;
            
        }
        if ($request->file('certificate_image_7')) {
            $image_file_7 = $request->file('certificate_image_7');
            $folder = public_path('images/product/certificate/' . $user->id . '/');

            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder, 0775, true, true);
            }

            $image_file_7_cerf = date('YmdHis') . rand(111, 9999). "certificate1." . $image_file_7->getClientOriginalExtension();
            $aa = $image_file_7->move($folder, $image_file_7_cerf);

            $image_file_7_image_name = $image_file_7_cerf;
            $image_file_7_cerf = 'images/product/certificate/'.$user->id.'/'.$image_file_7_image_name;

            $certificateData['certificate_image_7'] = $image_file_7_cerf;
            $certificateData['certificate_status_7'] = 0;
            
        }
        $certificateData['certificate_type_1'] = $request->certificate_type_1;
        $certificateData['certificate_type_2'] = $request->certificate_type_2;
        $certificateData['certificate_type_3'] = $request->certificate_type_3;
        $certificateData['certificate_type_4'] = $request->certificate_type_4;
        $certificateData['certificate_type_5'] = $request->certificate_type_5;
        $certificateData['certificate_type_6'] = $request->certificate_type_6;
        $certificateData['certificate_type_7'] = $request->certificate_type_7;
        //echo $request->certificate_type_6;die;
        if ($request->certificate_image_1) {
            if (!$request->file('certificate_image_1')) {
                $certificateData['certificate_image_1'] = $request->certificate_image_1;
            }
        } else {
            $certificateData['certificate_image_1'] = null;
            $certificateData['certificate_status_1'] = 0;
            
        }

        if ($request->certificate_image_2) {
            if (!$request->file('certificate_image_2')) {
                $certificateData['certificate_image_2'] = $request->certificate_image_2;
            }
        } else {
            $certificateData['certificate_image_2'] = null;
            $certificateData['certificate_status_2'] = 0;
            
        }
        if ($request->certificate_image_3) {
            if (!$request->file('certificate_image_3')) {
                $certificateData['certificate_image_3'] = $request->certificate_image_3;
            }
        } else {
            $certificateData['certificate_image_3'] = null;
            $certificateData['certificate_status_3'] = 0;
            
        }
        if ($request->certificate_image_4) {
            if (!$request->file('certificate_image_4')) {
                $certificateData['certificate_image_4'] = $request->certificate_image_4;
            }
        } else {
            $certificateData['certificate_image_4'] = null;
            $certificateData['certificate_status_4'] = 0;
            
        }
        if ($request->certificate_image_5) {
            if (!$request->file('certificate_image_5')) {
                $certificateData['certificate_image_5'] = $request->certificate_image_5;
            }
        } else {
            $certificateData['certificate_image_5'] = null;
            $certificateData['certificate_status_5'] = 0;
            
        }
        if ($request->certificate_image_6) {
            if (!$request->file('certificate_image_6')) {
                $certificateData['certificate_image_6'] = $request->certificate_image_6;
            }
        } else {
            $certificateData['certificate_image_6'] = null;
            $certificateData['certificate_status_6'] = 0;
            
        }
        if ($request->certificate_image_7) {
            if (!$request->file('certificate_image_7')) {
                $certificateData['certificate_image_7'] = $request->certificate_image_7;
            }
        } else {
            $certificateData['certificate_image_7'] = null;
            $certificateData['certificate_status_7'] = 0;
            
        }

            $certificateData['product_id'] = $request->productId;
            //print_r($certificateData);die();
            $chkAlreadyCer = ProductCertification::where('product_id',$request->productId)->first();
            if($chkAlreadyCer){
                 $chkAlreadyCer->update($certificateData);
            }else{
                if($request->file('certificate_image_1') || $request->file('certificate_image_2') || $request->file('certificate_image_3') || $request->file('certificate_image_4') || $request->file('certificate_image_5') || $request->file('certificate_image_6') || $request->file('certificate_image_7')){
                    $insertData = ProductCertification::create($certificateData);

                }

            }
           
        
        if($language == 'hi'){
            $queryStatus    = "उत्पाद अपडेट सफल";
        }else{
            $queryStatus    = "Product updated successfully.";
        }
        
        $statusCode     = 200;
        $status         = true;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus
        );

        return response()->json($response, 201);
    }

    /**
     * get banner
     */

    public function getbanner(Request $request)
    {
        $language = $request->header('language');
        /** Start Banner */
        $banner = Banner::where('status', 1)->orderBy('id', 'DESC')->select('id', 'image', 'action')->take(5)->get();
        $queryStatus    = "All Banner.";
        $statusCode     = 200;
        $status         = true;
        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
        'data' => [ 'banner' => $banner]);

        if (count($banner) == 0) {
            if($language == 'hi'){
                $queryStatus    = "बैनर नहीं मिला";
            }else{
                $queryStatus    = "Banner not found.";
            }
            
            $statusCode     = 400;
            $status         = false;


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
        }

        return response()->json($response, 201);
    }

    /**
     * User Home Screen
     */
    public function getuserhome(Request $request)
    {
        
        $language = $request->header('language');
        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';
        $populartitle = 'Popular Seller';
        $recentlytitle = 'Recently Added';


        if ($language == 'hi') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
            $populartitle = 'लोकप्रिय विक्रेता';
            $recentlytitle = 'हाल ही में जोड़ा';
        }
        $apitoken = $request->header('apitoken');
        if($apitoken){
            $userByToken = User::where(['api_token'=> $apitoken])->first();
            User::where('id', $userByToken->id)->update(['language' => $language]);
        }
        $popularproduct = DB::select("SELECT user_id, AVG(rating) as rating FROM `ratings` where `type`='buyer' GROUP BY user_id ORDER BY rating Limit 5");
        $popularArr = [];
        foreach ($popularproduct as $value) {
            $popularArr[] = $value->user_id;
        }

        // $popularproducts = ProductMaster::wherein('user_id', $popularArr)->where(['is_active' => 1, 'is_draft' => 0])->with('template:id,'.$templatename)->select($productname, 'price', 'id', 'image_1', 'template_id')->orderBy('id', 'desc')->get();

        $sellerData = User::whereIn('id', $popularArr)->select('id','name', 'email', 'organization_name','mobile', 'profileImage')->get();

        $popularSeller = [];
        foreach($sellerData as $value) {
            $ratingCount = Rating::where('user_id', $value->id)->count();
            $ratingStarAvg = DB::table('ratings')->where('user_id', $value->id)->avg('rating');
            $rating = [
                'reviewCount' => $ratingCount,
                'ratingAvgStar' => $ratingStarAvg
            ];
            $final = [
                'rating' => $rating,
                'id' => $value->id,
                'name' => $value->organization_name,
                'email' => $value->email,
                'mobile' => $value->mobile,
                'profileImage' => $value->profileImage
            ];
            $popularSeller[] = $final;

        }
        $popularproducts =  $popularSeller;

        // echo "<pre>"; print_r($popularproducts); die("check");
        /** Start SHG / Artisan Product */
        /**
         * 16 Jan 2021
         *
         */

        //  Changes For Geo Coding For SHG & Artisan on 10 feb

        $alluser = ProductMaster::where(['is_active' => 1, 'is_draft' => 0])->select('user_id')->groupBy('user_id')->orderBy('user_id', 'desc')->limit(3)->get();

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

        // $shgproduct = User::wherein('id', $users)->where('isActive', 1)->get();
        // dd($users);

        $allProduct = [];
        // foreach ($users as $value) {
        //     echo $value;
        //     $userData[] = User::where('id', $value)->where('isActive', 1)->first();
        // }
        
        //die('sdf');
        // dd($userData);
        foreach ($users as $value) {
            //echo $value;
            $userData = User::where('id', $value)->where('isActive', 1)->first();

            //dd($userData);

            if ($userData != null) {
                $store = [];
                $productdata = [];
                $store['title'] = $userData->title;
                $store['name']  = $userData->organization_name;
                $store['type']  = 'shg-artisan';
                $store['id']  = $userData->id;

                $product = ProductMaster::where(['user_id' => $userData->id, 'is_active' => 1, 'is_draft' => 0])->select('id','product_id_d', 'image_1', 'price', 'template_id', 'localname_kn', 'localname_en')->limit(5)->get();

                foreach ($product as $item) {
                    $p = [];
                    $p['id']        = $item->id;
                    $p['product_id_d']  = $item->product_id_d;
                    $p['image_1']   = $item->image_1;
                    $p['price']     = $item->price;
                    $p['template_id'] = $item->template_id;

                    $template = ProductTemplate::where('id', $item->template_id)->select('id', $templatename)->first();

                    $p['template']    = $template;


                    if ($language == 'hi') {
                        $p['name']      = $item->localname_kn;
                    } else {
                        $p['name']      = $item->localname_en;
                    }
                    $productdata[] = $p;
                }
                $store['data']  = $productdata;
                $allProduct[] = $store;
            }
        }

        // echo "<pre>"; print_r($allProduct); die("check");

        /**
         * End
         */

        $shguser = User::wherein('role_id', [ 2, 3, 7, 8])->select('title', 'id')->where('isActive', 1)->take(5)->get();

        $shgArr = [];

        foreach ($shguser as $key => $value) {
            $productCheck = ProductMaster::where(['is_active' => 1, 'is_draft' => 0, 'user_id' => $value->id])->select($productname, 'price', 'id', 'product_id_d','image_1')->take(5)->get();


            if (count($productCheck) > 0) {
                $shgData = [
                    // 'title' => $value->title,
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

        $recentlyproduct = ProductMaster::where(['is_active' => 1, 'is_draft' => 0])->orderBy('id', 'desc')->select($productname, 'price', 'id','product_id_d', 'image_1', 'template_id','user_id')->with('template:id,'.$templatename,'user:id,organization_name')->take(5)->get();

        if (count($request->shgartisanId) > 0) {
            $allUserId = $request->shgartisanId;

            $recentlyproduct = ProductMaster::whereIn('user_id', $allUserId)->where(['is_active' => 1, 'is_draft' => 0])->orderBy('id', 'desc')->select($productname, 'price', 'id','product_id_d', 'image_1', 'template_id','user_id')->with('template:id,'.$templatename,'user:id,organization_name')->take(5)->get();
        }





        /** Start SHG / Artisan Product */

        $lengthshg = count($shguser);
        if ($lengthshg > 0) {
            $position = $lengthshg - 1;

            if ($shguser[$position]->id) {
                $shgId = $shguser[$position]->id;

                $shguser = User::wherein('role_id', [ 2, 3, 7, 8 ])->select('title', 'id')->where('isActive', 1)->where('id', '>', $shgId)->take(2)->get();

                $shg2ndArr = [];

                foreach ($shguser as $key => $value) {
                    $productCheck = ProductMaster::where(['is_active' => 1, 'is_draft' => 0, 'user_id' => $value->id])->select($productname, 'price', 'id', 'product_id_d','image_1')->take(5)->get();

                    if (count($productCheck) > 0) {
                        // $shg2ndArr[$key]['title'] = $value->title;
                        $shg2ndArr[$key]['products'] = $productCheck;
                    }
                }
            }
        }


        if($language == 'hi'){
            $queryStatus    = "होम स्क्रीन";
        }else{
            $queryStatus    = "Home Screen.";
        }

        
        $statusCode     = 200;
        $status         = true;



        $popularproduct = [
            'type' => 'popular',
            'title'=> $populartitle,
            // 'id'   => 1,
            'data' => $popularproducts
        ];

        if (count($popularproduct['data']) == 0) {
            $popularproduct = [];
        }

        $recentlyproduct = [
            'type' => 'recently',
            'title'=> $recentlytitle,
            // 'id'   => 1,
            'data' => $recentlyproduct
        ];

        if (count($recentlyproduct['data']) == 0) {
            $recentlyproduct = [];
        }

        $data = [

                'shgartisans'       => $shgArr

        ];

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




        $checkData = [ $popularproduct, $shgproduct1, $shgproduct2, $recentlyproduct, $shgproduct3 ];
        $newData = [];
        foreach ($checkData as $value) {
            if ($value) {
                $newData[] = $value;
            }
        }

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                            'data' => [ 'data' => $newData]);

        return response()->json($response, 201);
    }

    /**
     * view all popular product
     */
    public function viewallpopularproduct(Request $request, $id = null)
    {
        $language = $request->header('language');


        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($language == 'hi') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }

        /** Start Popular Product */
        // $popularproduct = PopularProduct::where('status', 1)->select('product_id')->paginate(10);
        
        $popularproduct = DB::select("SELECT user_id, AVG(rating) as rating FROM `ratings` where `type`='buyer' GROUP BY user_id ORDER BY rating");

        // $getData = json_encode($popularproduct);
        // $getData = json_Decode($getData);

        // $paginationData = [
        //     'current_page' => $getData->current_page,
        //     'last_page'    => $getData->last_page,
        //     'per_page'     => $getData->per_page
        // ];



        $popularArr = [];
        foreach ($popularproduct as $value) {
            $popularArr[] = $value->user_id;
        }

        // $popularproducts = ProductMaster::wherein('id', $popularArr)->select($productname, 'price', 'id', 'image_1', 'template_id')->with('template:id,'.$templatename)->get();

        $sellerData = User::whereIn('id', $popularArr)->select('id','name','organization_name', 'email', 'mobile', 'profileImage')->paginate(20);
    
        $getData = json_encode($sellerData);
        $getData = json_Decode($getData);

        $paginationData = [
            'current_page' => $getData->current_page,
            'last_page'    => $getData->last_page,
            'per_page'     => $getData->per_page
        ];

        $popularSeller = [];
        foreach($sellerData as $value) {
            $ratingCount = Rating::where('user_id', $value->id)->count();
            $ratingStarAvg = DB::table('ratings')->where('user_id', $value->id)->avg('rating');
            $rating = [
                'reviewCount' => $ratingCount,
                'ratingAvgStar' => $ratingStarAvg
            ];
            $final = [
                'rating' => $rating,
                'id' => $value->id,
                'name' => $value->organization_name,
                'email' => $value->email,
                'mobile' => $value->mobile,
                'profileImage' => $value->profileImage
            ];
            $popularSeller[] = $final;

        }

        $popularproducts =  $popularSeller;
        if($language == 'hi'){
            $queryStatus    = "लोकप्रिय उत्पाद";
        }else{
            $queryStatus    = "Popular product.";
        }
        
        $statusCode     = 200;
        $status         = true;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                            'data' => [
                                        'pagination'        => $paginationData,
                                        'popularproduct'    => $popularproducts
                                    ]);

        return response()->json($response, 201);
    }
    /**
     * view all recently product
     */
    public function viewallrecentlyproduct(Request $request, $id = null)
    {
        $language = $request->header('language');


        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($language == 'hi') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }

        $recentlyproduct = ProductMaster::where(['is_active' => 1, 'is_draft' => 0])->orderBy('id', 'desc')->select($productname, 'price', 'id','product_id_d', 'image_1', 'template_id','user_id')->with('template:id,'.$templatename,'user:id,organization_name')->paginate(20);
        //print_r($recentlyproduct);die;
        $getData = json_encode($recentlyproduct);
        $getData = json_Decode($getData);

        $paginationData = [
            'current_page' => $getData->current_page,
            'last_page'    => $getData->last_page,
            'per_page'     => $getData->per_page
        ];
        if($language == 'hi'){
            $queryStatus    = "नये उत्पाद";
        }else{
            $queryStatus    = "Recently Product.";
        }
        
        $statusCode     = 200;
        $status         = true;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                            'data' => [
                                        'pagination'         => $paginationData,
                                        'recentlyproduct'    => $getData->data
                                    ]);

        return response()->json($response, 201);
    }

    /**
     * view product
     */
    public function viewproduct(Request $request)
    {
        $language = $request->header('language');


        $productname = 'localname_en as name';
        $templatename = 'name_en as templatename';
        $descriptionname = 'des_en as description';
        $materialname = 'name_en as materialname';
        if ($language == 'hi') {
            // $catname = 'name_kn  as name';
            $descriptionname = 'des_kn as description';
            $productname = 'localname_kn  as name';
            $materialname = 'name_kn as materialname';
            $templatename = 'name_kn as templatename';
        }

        $rules = [
            'productId' => 'required'
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        $productdetail = ProductMaster::where('id', $request->productId)->select('id', 'product_id_d','price', 'user_id as artisanid', 'material_id', 'categoryId', 'subcategoryId', 'qty', 'length', 'width', 'height', 'vol', 'weight','no_measurement', 'length_unit', 'width_unit', 'height_unit', 'weight_unit', 'price_unit','vol_unit', 'image_1', 'image_2', 'image_3', 'image_4', 'image_5', 'video_url', 'localname_kn', 'template_id', 'localname_en', 'des_en', 'des_kn', $productname, $descriptionname)->get();
        if($language == 'hi'){
            $queryStatus    = "उत्पाद नहीं मिला";
        }else{
            $queryStatus    = "Product detail not found.";
        }
        $statusCode     = 400;
        $status         = false;

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        if (count($productdetail) > 0) {
            $template_name = ProductTemplate::where('id', $productdetail[0]->template_id)->select($templatename)->first();

            $artisanData = User::where('id', $productdetail[0]->artisanid)->select('name', 'title')->first();

            $material = Material::where('id', $productdetail[0]->material_id)->select($materialname, 'name_kn', 'name_en')->first();
            $productdetail[0]['materialname'] = $material->materialname;
            $productdetail[0]['material_kn'] = $material->name_kn;

            $productdetail[0]['artisanshgname'] = $artisanData->name;
            $productdetail[0]['artisanshgtitle'] = $artisanData->title;
            $productdetail[0]['template_name'] = $template_name->templatename;

            //added by nadeem
            $catName = Category::select('id','name_en','name_kn')->where('id',$productdetail[0]['categoryId'])->first();
            $SubCatName = Category::select('id','name_en','name_kn')->where('id',$productdetail[0]['subcategoryId'])->first();
            //get product certificate
            $certificateData = ProductCertification::where('product_id',$productdetail[0]['id'])->first();
            if($certificateData){
                if($certificateData->certificate_type_1 > 0){
                    $certificateData['certificate_type_name_1'] = Helper::getCertificateTypeById($certificateData->certificate_type_1, $language)->name;
                }else{
                    $certificateData['certificate_type_name_1'] = NULL;
                }
                if($certificateData->certificate_type_2 > 0){
                    $certificateData['certificate_type_name_2'] = Helper::getCertificateTypeById($certificateData->certificate_type_2, $language)->name;
                }else{
                    $certificateData['certificate_type_name_2'] = NULL;
                }
                if($certificateData->certificate_type_3 > 0){
                    $certificateData['certificate_type_name_3'] = Helper::getCertificateTypeById($certificateData->certificate_type_3, $language)->name;
                }else{
                    $certificateData['certificate_type_name_3'] = NULL;
                }
                if($certificateData->certificate_type_4 > 0){
                    $certificateData['certificate_type_name_4'] = Helper::getCertificateTypeById($certificateData->certificate_type_4, $language)->name;
                }else{
                    $certificateData['certificate_type_name_4'] = NULL;
                }
                if($certificateData->certificate_type_5 > 0){
                    $certificateData['certificate_type_name_5'] = Helper::getCertificateTypeById($certificateData->certificate_type_5, $language)->name;
                }else{
                    $certificateData['certificate_type_name_5'] = NULL;
                }
                if($certificateData->certificate_type_6 > 0){
                    $certificateData['certificate_type_name_6'] = Helper::getCertificateTypeById($certificateData->certificate_type_6, $language)->name;
                }else{
                    $certificateData['certificate_type_name_6'] = NULL;
                }
                if($certificateData->certificate_type_7 > 0){
                    $certificateData['certificate_type_name_7'] = Helper::getCertificateTypeById($certificateData->certificate_type_7, $language)->name;
                }else{
                    $certificateData['certificate_type_name_7'] = NULL;
                }
                $productdetail[0]['certificate_data'] = $certificateData; 
            }else{
                $productdetail[0]['certificate_data'] = NULL;
            }
            $productdetail[0]['catName'] = $language=='hi' ? $catName->name_kn : $catName->name_en;
            $productdetail[0]['SubCatName'] = $language=='hi' ? $SubCatName->name_kn : $SubCatName->name_en;
            $queryStatus    = "Product Detail.";
            $statusCode     = 200;
            $status         = true;


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [
                                            'productdetail'    => $productdetail[0]
                                        ]);
        }




        return response()->json($response, 201);
    }

    /**
     * view subcategory product
     */
    public function viewsubcategoryproduct(Request $request)
    {
        $language = $request->header('language');

        $catname = 'name_en  as name';
        $templatename = 'name_en as name';

        $productname = 'localname_en as name';
        if ($language == 'hi') {
            $catname = 'name_kn  as name';
            $templatename = 'name_kn as name';
            $productname = 'localname_kn  as name';
        }

        $rules = [
            'subcategoryId' => 'required'
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        $recentlyproduct = ProductMaster::where(['is_active' => 1, 'is_draft' => 0, 'subcategoryId' => $request->subcategoryId])->select($productname, 'price', 'id', 'product_id_d','image_1', 'template_id','user_id')->with('template:id,'.$templatename,'user:id,organization_name')->paginate(20);

        $getData = json_encode($recentlyproduct);
        $getData = json_Decode($getData);

        $paginationData = [
            'current_page' => $getData->current_page,
            'last_page'    => $getData->last_page,
            'per_page'     => $getData->per_page
        ];
        if($language == 'hi'){
            $queryStatus    = "उत्पाद नहीं मिला";
        }else{
            $queryStatus    = "Product not found.";
        }
       
        $statusCode     = 400;
        $status         = false;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        if (count($recentlyproduct) > 0) {
            $queryStatus    = "AllProduct.";
            $statusCode     = 200;
            $status         = true;


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [
                                            'pagination'  => $paginationData,
                                            'products'    => $getData->data
                                        ]);
        }


        return response()->json($response, 201);
    }

    /**
     * view category product
     */
    public function viewcategoryproduct(Request $request)
    {
        $language = $request->header('language');
        $catname = 'name_en  as name';
        $templatename = 'name_en as name';
        $productname = 'localname_en as name';
        if ($language == 'hi') {
            $catname = 'name_kn  as name';

            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }

        $rules = [
            'categoryId' => 'required'
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }


        $categoryDetail = Category::where('parent_id', 0)->where('id', $request->categoryId)->select('id', $catname)->get();

        /**
         * 20 jan 2021
         *
         */
        $productdata = ProductMaster::where(['categoryId' => $request->categoryId, 'is_active' => 1, 'is_draft' => 0])->select('subcategoryId')->groupBy('subcategoryId')->orderBy('subcategoryId', 'desc')->paginate(500);

        // echo "<pre>"; print_r($request->shgartisanId); die("check");

        if (count($request->shgartisanId) > 0) {
            $allUserId = $request->shgartisanId;

            $productdata = ProductMaster::whereIn('user_id', $allUserId)->where(['categoryId' => $request->categoryId, 'is_active' => 1, 'is_draft' => 0])->select('subcategoryId')->groupBy('subcategoryId')->orderBy('subcategoryId', 'desc')->paginate(20);
        }

        $getData = json_encode($productdata);
        $getData = json_Decode($getData);

        $paginationData = [
             'current_page' => $getData->current_page,
             'last_page'    => $getData->last_page,
             'per_page'     => $getData->per_page
         ];

        $allsubcatetgory = [];

        foreach ($getData->data as $key => $value) {
            $subcategory = Category::where('id', $value->subcategoryId)-> select('id', $catname)->first();

            $productdata = ProductMaster::where([ 'is_active' => 1, 'is_draft' => 0, 'categoryId' => $request->categoryId, 'subcategoryId' => $value->subcategoryId])->select($productname,'user_id', 'subcategoryId', 'price', 'id','product_id_d', 'image_1', 'template_id')->take(5)->orderBy('id', 'desc')->get();

            $productArr['categoryname'] = $categoryDetail[0]->name;
            $productArr['subcategory'] = $subcategory->name;
            $productArr['subcategoryId'] = $subcategory->id;

            $allProduct = [];
            foreach ($productdata as $item) {
                $template = ProductTemplate::where('id', $item->template_id)->select('id', $templatename)->first();
                $product = $item;
                $sellerName = User::where('id',$item->user_id)->select('id','name','organization_name')->first();
                $allProduct[] = [
                    'template' => $template,
                    'name'     => $item->name,
                    'subcategoryId' => $item->subcategoryId,
                    'price'    => $item->price,
                    'id'       => $item->id,
                    'product_id_d' => $item->product_id_d,
                    'image_1'  => $item->image_1,
                    'template_id' => $item->template_id,
                    'seller_name' => $sellerName->organization_name,
                ];
            }

            $productArr['product'] = $allProduct;

            $allsubcatetgory[] = $productArr;
        }


        if($language == 'hi'){
            $queryStatus    = "उत्पाद नहीं मिला";
        }else{
            $queryStatus    = "No products found.";
        }
       

        
        $statusCode     = 400;
        $status         = false;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        if (count($allsubcatetgory) > 0) {
            $queryStatus    = "AllProduct.";
            $statusCode     = 200;
            $status         = true;


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [
                                            'pagination'  => $paginationData,
                                            'products'    => $allsubcatetgory
                                        ]);
        }


        return response()->json($response, 201);
    }

    /**
     * get shg artisan home
     */
    public function getshgartisanhome(Request $request)
    {
        $user = $request->user;

        $language = $request->header('language');

        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';


        if ($language == 'hi') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }
        User::where('id', $user->id)->update(['language' => $language]);
        $categoryIds = ProductMaster::where(['user_id' => $user->id, 'is_active' => 1, 'is_draft' => 0])->groupBy('categoryId')->select('categoryId')->paginate(500);

        // echo "Total Count : "; echo count($categoryIds); die("check");

        $getData = json_encode($categoryIds);
        $getData = json_Decode($getData);

        $paginationData = [
            'current_page' => $getData->current_page,
            'last_page'    => $getData->last_page,
            'per_page'     => $getData->per_page
        ];


        // echo "Category Current Page : "; print_r($getData); die("check");

        $catArr = [];

        foreach ($categoryIds as $cat) {
            $catArr[] = $cat->categoryId;
        }

        $categoryDetail = Category::wherein('id', $catArr)->select('id', $catname)->get();

        $allProduct = [];

        foreach ($categoryDetail as $key=> $cat) {
            $allProduct[$key]['categoryId'] = $cat->id;
            $allProduct[$key]['categoryName'] = $cat->name;
            $allProduct[$key]['categoryName'] = $cat->name;

            $subcategoryId = Category::where('parent_id', $cat->id)->select('id', $catname)->get();

            $sub = [];
            foreach ($subcategoryId as $subcat) {
                $products = ProductMaster::where('subcategoryId', $subcat->id)->where('is_active', 1)->where('is_draft', 0)->where('user_id', $user->id)->with('template:id,'.$templatename)->select($productname, 'price', 'id', 'image_1', 'template_id','product_id_d')->take(5)->get();

                if (count($products) > 0) {
                    $sub[] = [
                        'subCategoryId'     => $subcat->id,
                        'subCategoryName'   => $subcat->name,
                        'products'          => $products
                    ];
                }
            }

            $allProduct[$key]['subCategories'] = $sub;
        }

        // $allProduct['current_page'] = $categoryIds['current_page'];

        if($language == 'hi'){
            $queryStatus    = "उत्पाद नहीं मिला";
        }else{
            $queryStatus    = "No products found.";
        }
       
        $statusCode     = 400;
        $status         = false;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        if (count($allProduct) > 0) {
            $queryStatus    = "AllProduct.";
            $statusCode     = 200;
            $status         = true;


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [
                                            'categories' => $paginationData,
                                            'products'    => $allProduct
                                        ]);
        }


        return response()->json($response, 201);
    }

    /**
     * get all product
     */




    public function getallproduct(Request $request)
    {
        $language = $request->header('language');


        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($language == 'hi') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }

        /**
         * Changes for geo location
         */


        if (count($request->shgartisanId) > 0) {
            $allUserId = $request->shgartisanId;
            $alluser = ProductMaster::whereIn('user_id', $allUserId)->where(['is_active' => 1, 'is_draft' => 0])->select('user_id')->groupBy('user_id')->orderBy('user_id', 'desc')->paginate(20);

            $users = [];
            foreach ($alluser as $value) {
                $users[] = $value->user_id;
            }
        } else {
            $alluser = ProductMaster::where(['is_active' => 1, 'is_draft' => 0])->select('user_id')->groupBy('user_id')->orderBy('user_id', 'desc')->paginate(20);

            $users = [];
            foreach ($alluser as $value) {
                $users[] = $value->user_id;
            }
        }

        $shgproduct = User::wherein('id', $users)->where('isActive', 1)->orderBy('id', 'desc')->get();
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
            $store['name']  = $value->organization_name;
            $store['type']  = 'shg-artisan';
            $store['id']  = $value->id;



            $product = ProductMaster::where(['user_id' => $value->id, 'is_active' => 1, 'is_draft' => 0])->select('id','product_id_d', 'image_1', 'price', 'template_id', 'localname_kn', 'localname_en')->limit(5)->get();



            foreach ($product as $item) {
                $p = [];
                $p['id']        = $item->id;
                $p['product_id_d']  = $item->product_id_d;
                $p['image_1']   = $item->image_1;
                $p['price']     = $item->price;
                $p['template_id'] = $item->template_id;

                $template = ProductTemplate::where('id', $item->template_id)->select('id', $templatename)->first();

                $p['template']    = $template;

                if ($language == 'hi') {
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

        $shguser = User::wherein('role_id', [ 2, 3, 7, 8 ])->select('title', 'id')->where('isActive', 1)->get();

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
            $productCheck = ProductMaster::where(['is_active' => 1, 'is_draft' => 0, 'user_id' => $value->id])->select($productname, 'price', 'id','product_id_d', 'image_1')->take(5)->get();


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
        if($language == 'hi'){
            $queryStatus    = "उत्पाद नहीं मिला";
        }else{
            $queryStatus    = "Product not found.";
        }
       
        
        $statusCode     = 200;
        $status         = true;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                            );

        if (count($shgArr) > 0) {
            $queryStatus    = "AllProduct.";
            $statusCode     = 200;
            $status         = true;

            // $datashg = $this->paginate($shgArr);


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [
                                            'pagination'   => $pagination,
                                            'data'    => $allProduct
                                        ]);
        }

        return response()->json($response, 201);
    }


    /**
     * get all product
     */

    public function paginate($items, $perPage = 1, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }


    /**
     * view artisan shg profile
     */
    public function viewartisanshgprofile(Request $request)
    {
        $apitoken = $request->header('apitoken');
        $language = $request->header('language');

        $user = User::where('api_token', $apitoken)->select('id', 'name', 'title', 'profileImage', 'mobile', 'email')->first();

        $catname = 'name_en  as name';
        $productname = 'localname_en as name';

        if ($language == 'hi') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
        }

        $rules = [
            'artisanshgid' => 'required'
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }
        // echo "<pre>"; print_r($user); die("check");

        if (!$user) {
            
            $user = User::where('id', $request->artisanshgid)->select('id', 'name','organization_name', 'title', 'profileImage')->first();
            $data = ['user' => $user];
        }
        if ($user) {
            $favuser = Favorite::where('user_id',$user->id)->where('seller_id',$request->artisanshgid)->first();
            if($favuser){
                $favoriteStatus = $favuser->status;
            }else{
                $favoriteStatus =0;
            }
            $address = Address::where(['user_id' => $request->artisanshgid, 'address_type' => 'registered'])->first();
            $country = Country::where('id', $address->country)->first();
            $state = State::where('id', $address->state)->first();
            $district = City::where('id', $address->district)->first();
            $address['country'] = $country->name;
            $address['state'] = $state->name;
            $address['district'] = $district->name;
            $userdata = User::where('id', $request->artisanshgid)->select('id', 'name','organization_name', 'title', 'profileImage', 'email', 'mobile')->first();

            $data = ['user' => $userdata, 'address' => $address, 'favoriteStatus' =>$favoriteStatus];
        }

        //print_r($data);die;
        $categoryIds = ProductMaster::where(['user_id' => $request->artisanshgid, 'is_active' => 1, 'is_draft' => 0])->distinct('categoryId')->select('categoryId')->get();

        $catArr = [];

        foreach ($categoryIds as $cat) {
            $catArr[] = $cat->categoryId;
        }

        $categoryDetail = Category::wherein('id', $catArr)->where('is_active', 1)->select('id', $catname)->get();

        $allProduct = [];

        foreach ($categoryDetail as $key=> $cat) {
            $subcategoryId = Category::where('parent_id', $cat->id)->select('id', $catname)->first();
            $products = ProductMaster::where('categoryId', $cat->id)->where('user_id', $request->artisanshgid)->select($productname, 'price', 'id', 'image_1')->take(5)->get();

            $allProduct[$key]['categoryId'] = $cat->id;
            $allProduct[$key]['categoryName'] = $cat->name;
            $allProduct[$key]['categoryName'] = $cat->name;
            $allProduct[$key]['subCategoryId'] = $subcategoryId->id;
            $allProduct[$key]['subCategoryName'] = $subcategoryId->name;

            $allProduct[$key]['products'] = $products;
        }

        $ratingCount = Rating::where('user_id', $request->artisanshgid)->count();
        $ratingStarAvg = DB::table('ratings')->where('user_id', $request->artisanshgid)->avg('rating');
        if($ratingStarAvg){
            $ratingStarAvg = $ratingStarAvg;
        }else{
            $ratingStarAvg = 0;
        }
        $rating = [
            'reviewCount' => $ratingCount,
            'ratingAvgStar' => $ratingStarAvg
        ];

        $data['rating'] = $rating;
        //print_r($allProduct);die;
        if($language == 'hi'){
            $queryStatus    = "डाटा नहीं मिला";
        }else{
            $queryStatus    = "No Data found.";
        }
        
        $statusCode     = 400;
        $status         = false;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        //if (count($allProduct) > 0) {
            $queryStatus    = "User profile with product.";
            $statusCode     = 200;
            $status         = true;


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => $data);
       // }


        return response()->json($response, 201);
    }

    /**
     * get popup
     */
    public function getpopup(Request $request)
    {
        $user = $request->user;
        $langauge = $request->header('language');

        if (!$langauge) {
            $langauge = 'en';
        }



        $popup = PopupManager::where(['status' => 1, 'language' => $langauge])->select('id', 'action', 'background_image', 'description', 'title')->first();

        $queryStatus    = "No popup found.";
        $statusCode     = 400;
        $status         = false;

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        if ($popup) {
            $queryStatus    = "Popup.";
            $statusCode     = 200;
            $status         = true;


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [ 'popup' => $popup ]);
        }


        return response()->json($response, 201);
    }

    /**
     * get notification
     */
    public function getnotification(Request $request)
    {
        $user = $request->user;
        $langauge = $request->header('language');

        if (!$langauge) {
            $langauge = 'en';
        }
        $userRegDate = $user->created_at;
        $notification = Notification::where(['language' => $langauge, 'role_id' => $user->role_id, 'status'=>1])->select('id', 'title', 'body', 'image', 'role_id', 'language','created_at')->where('created_at', '>=',$userRegDate)->orderBy('id', 'desc')->paginate(10);

        $getData = json_encode($notification);
        $getData = json_decode($getData);

        $paginationData = [
            'current_page' => $getData->current_page,
            'last_page'    => $getData->last_page,
            'per_page'     => $getData->per_page
        ];
        if($langauge == 'hi'){
            $queryStatus    = "नोटिफिकेशन नहीं मिला";
        }else{
            $queryStatus    = "No notification found.";
        }
        
        $statusCode     = 400;
        $status         = false;

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        if (count($notification) > 0) {
            $queryStatus    = "All Notification.";
            $statusCode     = 200;
            $status         = true;


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [ 'pagination' => $paginationData, 'getnotification' => $getData->data ]);
        }


        return response()->json($response, 201);
    }

    /**
     * search Product
     */
    public function search(Request $request)
    {
        $langauge = $request->header('language');

        if (!$langauge) {
            $langauge = 'en';
        }
        $query = 'localname_en';
        $templatequery = 'name_en';
        $productname = 'localname_en as name';
        $categoryname = 'name_en as name';
        $categoryquery = 'name_en';
        $templatename = 'name_en as name';

        if ($langauge == 'hi') {
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
        // ->orWhere('id', $keyword)
        $category = Category::where($categoryquery, 'LIKE', "%{$keyword}%")->where([ 'is_active' => 1, 'parent_id' => 0])->select($categoryname, 'id', 'image')->paginate(500);



        $categoryData = json_encode($category);
        $categoryData = json_Decode($categoryData);

        $parentCategory = $categoryData->data;


        $parentCat = [];

        foreach ($parentCategory as $value) {
            $parentCat[] = [
                'catId'     => $value->id,
                'catName'   => $value->name,
                'catImage'  => $value->image,
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
        // ->orWhere('id', $keyword)
        $artisanshg = User::whereIn('id', $shgArtisanIds)->where('name', 'LIKE', "%{$keyword}%")->select('name','organization_name', 'id', 'profileImage')->paginate(500);

        $artisanData = json_encode($artisanshg);
        $artisanData = json_Decode($artisanData);

        $artisanData = $artisanData->data;

        $artisanArr = [];

        foreach ($artisanData as $value) {
            $artisanArr[] = [
                'artisanshgId'      => $value->id,
                'artisanshgName'    => $value->name,
                'artisanshgOrgName'    => $value->organization_name,
                'profileImage'      => $value->profileImage,
                'type'              => 'artisanshg'
            ];
        }

        // Search in Product Template

        // ->orWhere('id', $keyword)
        $templateIds = ProductTemplate::where('status', 1)->where($templatequery, 'LIKE', "%{$keyword}%")->pluck('id');

        $ids = json_encode($templateIds);
        $ids = json_Decode($ids);




        // $products = ProductMaster::where($query, 'LIKE', "%{$keyword}%")->where(['is_draft' => 0, 'is_active' => 1])->orWhereIn('template_id', $ids)->orWhere('id', $keyword)->select($productname, 'subcategoryId', 'price', 'id', 'image_1', 'template_id')->with('template:id,'.$templatename)->paginate(15);


        // if(count($request->shgartisanId) > 0) {
        //     $allUserId = $request->shgartisanId;

        //     $products = ProductMaster::where($query, 'LIKE', "%{$keyword}%")->where(['is_draft' => 0, 'is_active' => 1])->orWhereIn('template_id', $ids)->orWhere('id', $keyword)->select($productname, 'subcategoryId', 'price', 'id', 'image_1', 'template_id')->with('template:id,'.$templatename)->paginate(15);

        // }



        $products = ProductMaster::with('template:id,'.$templatename)
        ->where(['is_draft' => 0, 'is_active' => 1])
        ->where(function ($query1) use ($keyword, $query) {
            $query1->where($query, 'LIKE', '%'.$keyword.'%');
            $query1->orWhere('product_id_d', 'LIKE', '%'.$keyword.'%');
        })
        ->orWhereIn('template_id', $ids)
        ->select($productname, 'subcategoryId', 'price', 'id', 'product_id_d','image_1', 'template_id', 'is_active')
        ->paginate(500);
        
        if (count($request->shgartisanId) > 0) {
            $allUserId = $request->shgartisanId;
            $products = ProductMaster::with('template:id,'.$templatename)
            ->where(['is_draft' => 0, 'is_active' => 1])
            ->where(function ($query1) use ($keyword, $query) {
                $query1->where($query, 'LIKE', '%'.$keyword.'%');
                $query1->orWhere('product_id_d', 'LIKE', '%'.$keyword.'%');
            })
            ->orWhereIn('template_id', $ids)
            ->select($productname, 'subcategoryId', 'price', 'id', 'product_id_d','image_1', 'template_id', 'is_active')
            ->paginate(500);
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
                'product_id_d'      => $value->product_id_d,
                'productName'    => $value->name,
                'price'          => $value->price,
                'image_1'        => $value->image_1,
                'subcategoryId'  => $value->subcategoryId,
                'template'       => $value->template,
                'type'           => 'product'
            ];
        }

        if($langauge == 'hi'){
            $queryStatus    = "उत्पाद नहीं मिला";
        }else{
            $queryStatus    = "No Product found.";
        }



        
        $statusCode     = 400;
        $status         = false;

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

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


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [
                                    // 'products' => $products
                                        'pagination' => $paginationData,
                                        'searchresult' => $newData

                                    ]);
        // }


        return response()->json($response, 201);
    }

    /**
     * search shg
     */
    public function searchshg(Request $request)
    {
        $user = $request->user;
        $langauge = $request->header('language');

        if (!$langauge) {
            $langauge = 'en';
        }
        $query = 'localname_en';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($langauge == 'hi') {
            $query = 'localname_kn';
            $productname = 'localname_kn as name';
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




        $products = ProductMaster::where($query, 'LIKE', "%{$keyword}%")->where(['is_draft' => 0, 'is_active' => 1])->where('user_id', $user->id)->with('template:id,'.$templatename)->select($productname, 'subcategoryId', 'price', 'id','product_id_d', 'image_1', 'template_id')->paginate(5);
        if($langauge == 'hi'){
            $queryStatus    = "उत्पाद नहीं मिला";
        }else{
            $queryStatus    = "No Product found.";
        }

       
        $statusCode     = 400;
        $status         = false;

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

         if(count($products) > 0) {
        $queryStatus    = "All Products.";
        $statusCode     = 200;
        $status         = true;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [ 'products' => $products ]);
         }


        return response()->json($response, 201);
    }

    /**
     * view artisan shg product
     */

    public function viewartisanshgproduct(Request $request)
    {
        $language = $request->header('language');

        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($language == 'hi') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }
        $rules = [
            'artisanshgid' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        // $categoryIds = ProductMaster::where(['user_id' => $request->artisanshgid, 'is_active' => 1, 'is_draft' => 0])->distinct('categoryId')->select('categoryId')->get();

        $categoryIds = ProductMaster::where(['user_id' => $request->artisanshgid, 'is_active' => 1, 'is_draft' => 0])->groupBy('categoryId')->select('categoryId')->paginate(500);



        $getData = json_encode($categoryIds);
        $getData = json_Decode($getData);

        $paginationData = [
            'current_page' => $getData->current_page,
            'last_page'    => $getData->last_page,
            'per_page'     => $getData->per_page
        ];


        $catArr = [];

        foreach ($getData->data as $cat) {
            $catArr[] = $cat->categoryId;
        }

        $categoryDetail = Category::wherein('id', $catArr)->select('id', $catname)->get();

        $allProduct = [];

        foreach ($categoryDetail as $key=> $cat) {
            $allProduct[$key]['categoryId'] = $cat->id;
            $allProduct[$key]['categoryName'] = $cat->name;
            $allProduct[$key]['categoryName'] = $cat->name;

            $subcategoryId = Category::where('parent_id', $cat->id)->select('id', $catname)->get();

            $sub = [];
            foreach ($subcategoryId as $subcat) {
                $products = ProductMaster::where('subcategoryId', $subcat->id)->where('is_active', 1)->where('is_draft', 0)->where('user_id', $request->artisanshgid)->with('template:id,'.$templatename,'user:id,organization_name')->select($productname, 'price', 'user_id','id', 'product_id_d','image_1', 'template_id')->take(5)->get();

                if (count($products) > 0) {
                    $sub[] = [
                        'subCategoryId'     => $subcat->id,
                        'subCategoryName'   => $subcat->name,
                        'products'          => $products
                    ];
                }
            }

            $allProduct[$key]['subCategories'] = $sub;
        }
        if($language == 'hi'){
            $queryStatus    = "उत्पाद नहीं मिला";
        }else{
            $queryStatus    = "No Product found.";
        }

       
        $statusCode     = 400;
        $status         = false;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        if (count($allProduct) > 0) {
            $queryStatus    = "AllProduct.";
            $statusCode     = 200;
            $status         = true;


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [
                                            'pagination'  => $paginationData,
                                            'products'    => $allProduct
                                        ]);
        }


        return response()->json($response, 201);
    }

    /**
     * view artisan category product
     */
    public function viewsartisancategoryproduct(Request $request)
    {
        $language = $request->header('language');

        $catname = 'name_en  as name';
        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($language == 'hi') {
            $catname = 'name_kn  as name';
            $productname = 'localname_kn  as name';
            $templatename = 'name_kn as name';
        }

        $rules = [
            'artisanshgid' => 'required',
            // 'categoryId'   => 'required',
            'subcategoryId'=> 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        $subcategory = Category::where('id', $request->subcategoryId)->select('id', $catname, 'parent_id')->first();

        $category = Category::where('id', $subcategory->parent_id)->select('id', $catname)->first();



        $allProduct = ProductMaster::where(['subcategoryId' => $request->subcategoryId, 'user_id' => $request->artisanshgid, 'is_active' => 1, 'is_draft' => 0])->with('template:id,'.$templatename,'user:id,organization_name')->select($productname, 'price', 'id', 'image_1', 'template_id','product_id_d')->paginate(20);


        $getData = json_encode($allProduct);
        $getData = json_Decode($getData);

        $paginationData = [
            'current_page' => $getData->current_page,
            'last_page'    => $getData->last_page,
            'per_page'     => $getData->per_page
        ];

        if($language == 'hi'){
            $queryStatus    = "उत्पाद नहीं मिला";
        }else{
            $queryStatus    = "No Product found.";
        }

       
        $statusCode     = 400;
        $status         = false;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);

        if (count($allProduct) > 0) {
            $queryStatus    = "AllProduct.";
            $statusCode     = 200;
            $status         = true;


            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                                'data' => [
                                            'subcategoryId' => $subcategory->id,
                                            'subcategoryName' => $subcategory->name,
                                            'categoryId'      => $category->id,
                                            'categoryName'    => $category->name,
                                            'pagination'      => $paginationData,
                                            'products'    => $getData->data
                                        ]);
        }


        return response()->json($response, 201);
    }


    /**
     * enquiry
     */
    public function enquiry(Request $request)
    {
        $user = $request->user;

        $language = $request->header('language');


        $productname = 'localname_en as name';
        $templatename = 'name_en as name';

        if ($language == 'hi') {
            $productname = 'localname_kn as name';
            $templatename = 'name_kn as name';
        }

        $rules = [
            'message' => 'required',
            'productId' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        $allProduct = ProductMaster::where('id', $request->productId)->with('template:id,'.$templatename)->select($productname, 'price', 'id', 'image_1', 'template_id')->first();

        $data['user'] = $user;
        $data['product'] = $allProduct;

        // Mail::to($user->email)->send(new Enquiry($data));


        // die("check");
        if($language == 'hi'){
            $queryStatus    = "पूछताछ सफलतापूर्वक भेजी गई";
        }else{
            $queryStatus    = "Enquiry Sent Successfully.";
        }
        
        $statusCode     = 200;
        $status         = true;


        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus,
                            'data' => []);

        return response()->json($response, 201);
    }
}
