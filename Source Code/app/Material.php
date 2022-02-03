<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Material extends Model
{
    //

    use Notifiable;

    protected $fillable = [
        'image', 'name_en', 'name_kn', 'admin_id', 'category_id','subcategory_id','image','is_active'
    ];

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    public function subcategory()
    {
        return $this->hasOne(Category::class, 'id', 'subcategory_id');
    }

}
