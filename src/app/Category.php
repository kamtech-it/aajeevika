<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Category extends Model
{
    use Notifiable;

    protected $fillable = [
        'image', 'name_en', 'name_kn', 'admin_id', 'parent_id','slug', 'is_active'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];



    public function subcategory(){
        return $this->hasMany(Category::class,'parent_id','id');
    }


    public function products()
    {
        return $this->hasMany(ProductMaster::class, 'subcategoryId', 'id')->where('is_active','=', 1);

    }




}
