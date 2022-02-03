<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductMaster extends Model
{
    protected $fillable = [
        'material_id',
        'price',
        'qty',
        'localname_en' ,
        'localname_kn' ,
        'image_1',
        'image_2',
        'image_3',
        'image_4',
        'image_5',
        'video_url',
        'des_en',
        'des_kn',
        'is_draft',
        'is_deleted',
        'template_id',
        'user_id',
        'length',
        'width',
        'height',
        'vol',
        'length_unit',
        'width_unit',
        'height_unit',
        'vol_unit',
        'categoryId',
        'subcategoryId',
        'weight',
        'weight_unit',
        'is_active',
        'price_unit',
        'is_product_added',
        'product_id_d'

    ];

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'categoryId');
    }

    public function subcategory()
    {
        return $this->hasOne(Category::class, 'id', 'subcategoryId');
    }


    public function material()
    {
        return $this->hasOne(Material::class, 'id', 'material_id');
    }

    public function template()
    {
        return $this->hasOne(ProductTemplate::class, 'id', 'template_id');
    }




    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function users(){
        return $this->belongsTo(User::class);
    }
    public function popular()
    {
        return $this->hasOne(PopularProduct::class, 'product_id', 'id');
    }


    public function materials()
    {
        return $this->belongsTo(Material::class);
    }

    public function shgproduct()
    {
        $data = $this->hasMany(ProductMaster::class)->where(['is_active'=> 1,'is_draft' => 0])->take(5);

            return $data;

    }
    public function getCertificate()
    {
        return $this->hasOne(ProductCertification::class,'product_id' ,'id');
    }

}
