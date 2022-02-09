<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Expressinterest;
use App\Expressinterestitem;
use App\User;
use App\Order;
use App\IndOrder;
use App\IndOrderItem;
use App\OrderItem;
use Illuminate\Support\Facades\Input;
use Excel;


class IndOrderController extends Controller
{
    public function index(Request $request) {

        

        $allorder = IndOrder::with('indItems','getClf','GetIndividual','clfRating','indRating')->latest();
        
        if ($request->has('s')) {
            $search = Input::get('s');
            $allorder =   IndOrder::with('indItems','getClf','GetIndividual','clfRating','indRating')
            ->where('order_id_d', "like", "%" . $search . "%");
                 
        }

        $allorder_export_data  =  $allorder->get();
        $allorder  =  $allorder->paginate(10);
        if ($request->has('exportlist')) {
            if ($request->exportlist == 'all') {
                $data =  $allorder_export_data;
                //print_r($data);die;
               return Excel::create('IndividualOrder', function ($excel) use ($data) {

                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('Order_id_d');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Date of Sale');
                        });
                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Date Of Creation');
                        });
                /*    $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Mode Of Delivery');
                        });
                        */
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Order States');
                        });
                        $sheet->cell('F1', function ($cell) {
                            $cell->setValue('Individual Name');
                        });
                        $sheet->cell('G1', function ($cell) {
                            $cell->setValue('Individual Phone');
                        });
                        $sheet->cell('H1', function ($cell) {
                            $cell->setValue('CLF Name');
                        });
                   /*     $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Type');
                        });*/
                        $sheet->cell('I1', function ($cell) {
                            $cell->setValue('District');
                        });
                        $sheet->cell('J1', function ($cell) {
                            $cell->setValue('Block');
                        });

                        $sheet->cell('K1', function ($cell) {
                            $cell->setValue('CLF Phone');
                        });

                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->order_id_d);
                                $sheet->cell('C'.$i, $value->updated_at);
                                $sheet->cell('D'.$i, $value->created_at);
                             //   $sheet->cell('E'.$i, $value->mode_of_delivery);
                                $sheet->cell('E'.$i, $value->order_status);
                                $sheet->cell('F'.$i, $value->GetIndividual->name);
                                $sheet->cell('G'.$i, $value->GetIndividual->mobile);
                                $sheet->cell('H'.$i, $value->getClf->name);
                                $sheet->cell('I'.$i, $value->getClf->userdistrict->name);
                                $sheet->cell('J'.$i, $value->getClf->userBlock?$value->getClf->userBlock->name : 'NA');
                                $sheet->cell('K'.$i, $value->getClf->mobile);
                            }
                        }
                    });
                })->download('xlsx'); 
                //return Excel::download(new CollectionExport, 'collectionExport.xlsx');

            }

            if ($request->exportlist == 'subcat') {
                $query = DB::table('collection_centers')
                ->select('categories.*');
                $data = $query->get()->toArray();
                return Excel::create('allsubcategoies', function ($excel) use ($data) {
                    $excel->sheet('mySheet', function ($sheet) use ($data) {
                        $sheet->cell('A1', function ($cell) {
                            $cell->setValue('ID');
                        });
                        $sheet->cell('B1', function ($cell) {
                            $cell->setValue('Name English');
                        });
                        $sheet->cell('C1', function ($cell) {
                            $cell->setValue('Name Hindi');
                        });

                        $sheet->cell('D1', function ($cell) {
                            $cell->setValue('Parent ID');
                        });
                        $sheet->cell('E1', function ($cell) {
                            $cell->setValue('Parent  Name');
                        });
                        if (!empty($data)) {
                            foreach ($data as $key => $value) {
                                $i= $key+2;
                                $sheet->cell('A'.$i, $value->id);
                                $sheet->cell('B'.$i, $value->name_en);
                                $sheet->cell('C'.$i, $value->name_kn);
                                $sheet->cell('D'.$i, $value->parent_id);
                                $sheet->cell('E'.$i,  Helper::getCatBySubCat($value->parent_id)->name_en );
                            }
                        }
                    });
                })->download('xlsx');
            }
        }

        

         //echo "<pre>"; print_r($allorder); die("check");
        return view('indorder.index', compact('allorder'));
    }

    public function view($id) {
        $id = decrypt($id);
        $allorder = IndOrder::with('indItems','getClf','GetIndividual','clfRating','indRating')->where(['id' => $id])->latest()->get();
        
        return view('indorder.view', compact('allorder'));

    }
}
