<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Category;
use Validator;

class CategoryController extends Controller
{

/**
 * Get category 
 * */    
    public function getcategory(Request $request) {

        $language = $request->header('language');


        $catname = 'name_en  as name';
 

        if($language == 'hi') {
            $catname = 'name_kn  as name';

        }

        $allCategory = Category::select('id', $catname, 'image')->where(['parent_id' => 0, 'is_active' => 1])->get();
        

        $queryStatus    = "All Category.";
        $statusCode     = 200;
        $status         = true; 
        

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => [ 'category' => $allCategory ]);

        if(!$allCategory) {
            if($language == 'hi'){
                $queryStatus    = "श्रेणी नहीं मिली";
            }else{
                $queryStatus    = "Category not Found!";
            }

            
            $statusCode     = 400;
            $status         = false; 
            
            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
        }

        return response()->json($response, 201);

    }

/**
 * 
 * Get Sub category
 * */    
    public function getsubcategory(Request $request) {

        $language = $request->header('language');


        $catname = 'name_en  as name';
 

        if($language == 'hi') {
            $catname = 'name_kn  as name';
        }

        $validator = Validator::make($request->all(), [
            'id'        => 'required|exists:categories',   
            
            ]);
        
        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }


        $parentId = $request->id;

        $allCategory = Category::select('id', $catname, 'image')->where('parent_id', $parentId)->where('is_active',1)->get();
        

        $queryStatus    = "All Sub Category.";
        $statusCode     = 200;
        $status         = true; 
        

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => [ 'category' => $allCategory ]);

        $catLength = count($allCategory);


        if($catLength == 0) {
            if($language == 'hi'){
                $queryStatus    = "उप श्रेणी नहीं मिली";
            }else{
                $queryStatus    = "Sub Category not Found!";
            }
            
            $statusCode     = 400;
            $status         = false; 
            
            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
        }

        return response()->json($response, 201);   
    }

    /**
     * get category
     */
    // public function getcategory(Request $request) {

    //     /** Start Category */
    //     $categories = Category::where('parent_id', 0)->select($catname, 'image')->get();

    //     $queryStatus    = "All Category.";
    //     $statusCode     = 200;
    //     $status         = true; 
        

    //     $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 
    //     'data' => [ 'categories' => $categories]);

    //     if(count($banner) == 0) {
    //         $queryStatus    = "categories not found.";
    //         $statusCode     = 400;
    //         $status         = false; 
            
    
    //         $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
    //     }

    //     return response()->json($response, 201);
    // }
}
