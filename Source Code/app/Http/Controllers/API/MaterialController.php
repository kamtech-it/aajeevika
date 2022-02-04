<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Material;
use Validator;

class MaterialController extends Controller
{
    /**
     * Get Material
     */
    public function getmaterial(Request $request) {

        $language = $request->header('language');


        $materialname = 'name_en  as name';
 

        if($language == 'hi') {
            $materialname = 'name_kn  as name';
        }

        $validator = Validator::make($request->all(), [
            'subcategoryId'        => 'required',   
            
            ]);
        
        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        $materials = Material::where('subcategory_id', $request->subcategoryId)->where('is_active',1)->select($materialname, 'id', 'image')->get();

        $queryStatus    = "All Material.";
        $statusCode     = 200;
        $status         = true; 
        

        $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => [ 'material' => $materials ]);

        $materialLength = count($materials);


        if($materialLength == 0) {
 
            $queryStatus    = "Sub Category not Found!";
            $statusCode     = 400;
            $status         = false; 
            
            $response   = array('status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus);
        }

        return response()->json($response, 201);   

    }
}
