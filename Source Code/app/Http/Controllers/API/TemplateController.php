<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\ProductTemplate;
use Validator;

class TemplateController extends Controller
{
    /**
     * Get Template
     */
    public function gettemplate(Request $request) {

        $language = $request->header('language');


        $templatename = 'name_en  as name';
        // $templatedescription = 'description_en as description';

        if($language == 'hi') {
            $templatename = 'name_kn  as name';
            // $templatedescription = 'description_kn as description';

        }

        $rules = [
            'categoryId'      => 'required',
            'subcategoryId'   => 'required',
            'materialId'      => 'required'
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => false , 'statusCode' =>400);
            $response['message'] = $validator->messages()->first();
            return response()->json($response);
        }

        $template = ProductTemplate::where(['category_id' => $request->categoryId, 'subcategory_id' => $request->subcategoryId, 'material_id' => $request->materialId ,'status'=>1])
            ->select($templatename, 'id', 'description_en', 'description_kn', 'length','no_measurement', 'length_unit','width', 'width_unit','height','height_unit', 'weight','weight_unit', 'volume','volume_unit')->get();
            if($language == 'hi'){
                $queryStatus    = "टेम्पलेट नहीं मिला";
            }else{
                $queryStatus    = "No template found!";
            }
        
        $statusCode     = 400;
        $status         = false;

        $response   = array( 'status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus );

        if(count($template) > 0) {

            $queryStatus    = "All Template!";
            $statusCode     = 200;
            $status         = true;

            $response   = array( 'status' => $status , 'statusCode' =>$statusCode, 'message'=> $queryStatus, 'data' => ['template' => $template] );
        }


        return response()->json($response, 201);

    }
}
