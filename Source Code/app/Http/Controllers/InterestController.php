<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Expressinterest;
use App\Expressinterestitem;
use App\Role;
use App\User;
use Excel;
use Illuminate\Support\Facades\Input;
class InterestController extends Controller
{
    public function index(Request $request) {
        $allorder = Expressinterest::with('items', 'buyer','items.product', 'seller', 'seller.userdistrict')->where('order_status', 'interest')->latest();
        if ($request->has('s')) {
            $search = Input::get('s');
            $allorder =   Expressinterest::with('items', 'buyer','items.product', 'seller', 'seller.userdistrict')->where('order_status', 'interest')
                 ->where(function ($query) use ($search) {
                                    $query->where('interest_Id', "like", "%" . $search . "%");
            });
        }
        $allorder_export_data  =  $allorder->get();
        $allorder  =  $allorder->paginate(10);
        if ($request->has('exportlist')) {
            if ($request->exportlist == 'all') {
                $data =  $allorder_export_data;
                //print_r($data);die;
               return Excel::create('Interest_Management', function ($excel) use ($data) {

                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('Interest Id');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Date of Creation');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Buyer Name');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Buyer Phone');
                        });
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('Seller Name');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Type');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('District');
                        });
                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Block');
                        });
                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Seller Phone');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->interest_Id);
                                $sheet->cell('C'.$i, $value->created_at);
                                $sheet->cell('D'.$i, $value->buyer->name);
                                $sheet->cell('E'.$i, $value->buyer->mobile);
                                $sheet->cell('F'.$i, $value->seller->name);
                                $sellerRole = Role::where('id', $value->seller->role_id)->first();
                                $sheet->cell('G'.$i, $sellerRole->role_name);  
                                $sheet->cell('H'.$i, $value->seller->userdistrict->name); 
                                $sheet->cell('I'.$i, $value->seller->userBlock?$value->seller->userBlock->name : 'NA');            
                                $sheet->cell('J'.$i, $value->seller->mobile);  
                                
                                
                            }
                        }
                    });
                })->download('xlsx'); 
                //return Excel::download(new CollectionExport, 'collectionExport.xlsx');

              }
          }


        return view('interest.index', compact('allorder'));
    }

    public function view($id) {
        $id = decrypt($id);
        $allorder = Expressinterest::with('items', 'buyer','items.product', 'seller', 'seller.userdistrict')->where(['id' => $id, 'order_status' => 'interest'])->get();
        return view('interest.view', compact('allorder'));

    }
}
