<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductTemplate extends Model
{

    protected $fillable = [

        'name_kn',
        'name_en',
        'description_en',
        'description_kn',
        'subcategory_id',
        'category_id',
        'material_id',
        'height',
        'height_unit',
        'width',
        'width_unit',
        'length',
        'length_unit',
        'volume',
        'volume_unit',
        'weight',
        'weight_unit',
        'admin_id',
        'no_measurement'
    ];

    //

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    public function subcategory()
    {
        return $this->hasOne(Category::class, 'id', 'subcategory_id');
    }

    public function material()
    {
        return $this->hasOne(Material::class, 'id', 'material_id');
    }

}
