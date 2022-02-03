<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_masters', function (Blueprint $table) {
            $table->increments('id');

            $table->string('user_id');
            $table->string('material_id');
            $table->string('price');
            $table->string('qty');
            
            $table->string('localname_en');
            $table->string('localname_kn');

            $table->string('l');
            $table->string('w');
            $table->string('h');
            $table->string('weight');
            $table->string('vol');
            $table->string('image_1');
            $table->string('image_2');
            $table->string('image_3');
            $table->string('image_4');
            $table->string('image_5');
            $table->string('video_url');

            $table->string('des_en');
            $table->string('des_kn');
            
            $table->string('is_draft');
            $table->string('template_id');
            $table->string('is_active');
            
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_masters');
    }
}
