<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Expressinterest;
use App\Expressinterestitem;
use Excel;
use App\User;
use App\Order;
use App\OrderItem;
use Illuminate\Support\Facades\Input;
class OrderController extends Controller
{
    public function index(Request $request) {
        $allorder = Order::with('items', 'buyer','items.product', 'seller', 'seller.userdistrict','interest')->latest();
        if ($request->has('s')) {
            $search = Input::get('s');
            $allorder =   Order::with('items', 'buyer','items.product', 'seller', 'seller.userdistrict')
            ->whereHas(
                'interest', function($q) use ($search) {
                  $q->where('interest_Id', "like", "%" . $search . "%")
                      ->orWhere('order_id_d', "like", "%" . $search . "%");
                });
                 
        }

        $allorder_export_data  =  $allorder->get();
        $allorder  =  $allorder->paginate(10);
        if ($request->has('exportlist')) {
            if ($request->exportlist == 'all') {
                $data =  $allorder_export_data;
                //print_r($data);die;
               return Excel::create('Order_Management', function ($excel) use ($data) {

                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('Order Id');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Interest Id');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Date of Sale');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Date Of Creation');
                        });
                /*    $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Mode Of Delivery');
                        });
                        */
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('Mode Of Delevery');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Order Value');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('Order Status');
                        });
                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('Buyer Name');
                        });
                   /*     $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Type');
                        });*/
                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Buyer Phone');
                        });
                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('Seller Name');
                        });

                        
                        $sheet->cell('L1', function ($cell) {
                            $cell->setValue('District');
                        });
                        $sheet->cell('M1', function ($cell) {
                            $cell->setValue('Block');
                        });
                        $sheet->cell('N1', function ($cell) {
                            $cell->setValue('Seller Phone');
                        });

                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->order_id_d);
                                $sheet->cell('C'.$i, $value->interest->interest_Id);
                                $sheet->cell('D'.$i, $value->updated_at);
                             //   $sheet->cell('E'.$i, $value->mode_of_delivery);
                                $sheet->cell('E'.$i, $value->created_at);
                                if($value->mode_of_delivery=='1')
                                {
                                    $sheet->cell('F'.$i, 'Collection Center');
                                }else{
                                    $sheet->cell('F'.$i, 'Self');
                                }
                                $ttlPrice = 0;
                                            // if($item->items) {
                                                foreach($value->items as $valuee) {
                                                    $ttlPrice += ($valuee->quantity  *  $value->product_price);
                                                }
                                            // }

                                $sheet->cell('G'.$i, $ttlPrice);
                                $sheet->cell('H'.$i, $value->order_status);
                                $sheet->cell('I'.$i, $value->buyer->name);
                                $sheet->cell('J'.$i, $value->buyer->mobile);
                                $sheet->cell('K'.$i, $value->seller->name);
                                $sheet->cell('L'.$i, $value->seller->userdistrict->name);
                                $sheet->cell('M'.$i, $value->seller->userBlock?$value->seller->userBlock->name : 'NA');
                                $sheet->cell('N'.$i, $value->seller->mobile);
                                
                                
                            }
                        }
                    });
                })->download('xlsx'); 
                //return Excel::download(new CollectionExport, 'collectionExport.xlsx');

              }
          }

         //echo "<pre>"; print_r($allorder[0]->interest); die("check");
        return view('order.index', compact('allorder'));
    }

    public function view($id) {
        $id = decrypt($id);
        $allorder = Order::with('items', 'buyer','items.product', 'seller', 'seller.userdistrict','interest')->where(['id' => $id])->latest()->get();
        return view('order.view', compact('allorder'));

    }
}
