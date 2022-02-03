<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductCertification extends Model
{
    protected $fillable = [
        'product_id', 'certificate_image_1', 'certificate_status_1', 'certificate_type_1', 'certificate_image_2', 'certificate_status_2', 'certificate_type_2', 'certificate_image_3', 'certificate_status_3','certificate_type_3', 'certificate_image_4', 'certificate_status_4', 'certificate_type_4', 'certificate_image_5', 'certificate_status_5', 'certificate_type_5' , 'certificate_image_6', 'certificate_status_6', 'certificate_type_6', 'certificate_image_7', 'certificate_status_7','certificate_type_7'
    ];


    public function product() {
        return $this->belongsTo('App\ProductMaster', 'product_id', 'id');
    }
}
