<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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

    public function allcountries(Request $request){

        $language = $request->header('language');
        $apptype = $request->header('app-type');
        

        $name = 'name';
       
        if($language == 'hi') {
            $name = 'name_kn as name';
        }



        $response = array();
        
        if($apptype == 'Admin') {
            if($language == 'hi') {
            
                $name = 'name_kn as name';
            }
            $countries = DB::table('countries')
            ->select($name, 'id', 'sortname', 'phonecode')
            ->get();
        }else{
            $countries = DB::table('countries')
            ->select($name, 'id', 'sortname', 'phonecode')
            ->get();
        }
       
        
        $response['status'] = true;
        $response['statusCode'] = 200 ;
        $response['message'] = "success";
        $response['data'] = ['country' => $countries];

        return response()->json($response, 201);
    }

    public function getStateByCid(Request $request){

      

        $language = $request->header('language');
        $apptype = $request->header('app-type');
       
        $name = 'name';
       



        $response = array();
        $country_id = $request->country_id;
        if($language == 'hi') {
            $name = 'name_kn as name';
        }

        if($apptype == 'Admin') {
            if($language == 'hi') {
            
                $name = 'name_kn as name';
            }
    
            $states = DB::table('states')
            ->select('id', $name, 'country_id')
            ->where('country_id', '=', $country_id)
            ->get();
        }else{
            $states = DB::table('states')
                    ->select('id', $name, 'country_id')
                    ->where('country_id', '=', $country_id)
                    ->get();
        }



        $response['status'] = true;
        $response['statusCode'] = 200 ;
        $response['message'] = "success";
        $response['data'] = ['states' => $states];
        return response()->json($response, 201);
    }


    public function getCityBySid(Request $request){

        $language = $request->header('language');
        $apptype = $request->header('app-type');
       
        $name = 'name';

        $response = array();
        $state_id = $request->state_id;
        if($language == 'hi') {
            $name = 'name_kn as name';
        }
        if($apptype == 'Admin') {
            if($language == 'hi') {
            
                $name = 'name_kn as name';
            }
    
            $cities = DB::table('cities')
            ->where('state_id', '=', $state_id)
            ->where('is_district', 1)
            ->select($name, 'id', 'state_id')
            ->get();
        }else{
            $cities = DB::table('cities')
            ->where('state_id', '=', $state_id)
            ->where('is_district', 1)
            ->select($name, 'id', 'state_id')
            ->get();
        }
       

        $response['status'] = true;
        $response['statusCode'] = 200 ;
        $response['message'] = "success";
        $response['data'] = ['district'=>$cities];
        return response()->json($response, 201);
    }


    public function getCityBySidd(Request $request){

        $language = $request->header('language');
        $apptype = $request->header('app-type');
       
        $name = 'name';

        $response = array();
        $state_id = $request->state_id;
        if($language == 'hi') {
            $name = 'name_kn as name';
        }
        if($apptype == 'Admin') {
            if($language == 'hi') {
            
                $name = 'name_kn as name';
            }
    
            $cities = DB::table('cities')
            ->where('state_id', '=', $state_id)
            ->where('is_district', 1)
            ->select($name, 'id', 'state_id')
            ->get();
        }else{
            $cities = DB::table('cities')
            ->where('state_id', '=', $state_id)
            ->where('is_district', 1)
            ->select($name, 'id', 'state_id')
            ->get();
        }
       

        $response['status'] = true;
        $response['statusCode'] = 200 ;
        $response['message'] = "success";
        $response['data'] = ['district'=>$cities];
        return response()->json($response, 201);
    }



    public function getBlockByDid(Request $request){

        $language = $request->header('language');
        $apptype = $request->header('app-type');
       
        $name = 'name';
        if($language == 'hi') {
            $name = 'name_kn as name';
        }
        $response = array();
        $city_id = $request->city_id;

       
            $cities = DB::table('blocks')
            ->where('city_id', '=', $city_id)
            ->where('status', 0)
            ->select($name, 'id', 'city_id')
            ->get();
        
       

        $response['status'] = true;
        $response['statusCode'] = 200 ;
        $response['message'] = "success";
        $response['data'] = ['block'=>$cities];
        return response()->json($response, 201);
    }
}
