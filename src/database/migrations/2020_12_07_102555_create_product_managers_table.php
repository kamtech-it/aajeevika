<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductManagersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_managers', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name_en');
            $table->string('name_kn');
            $table->string('des_en');
            $table->string('des_kn');
            
            $table->string('material_id');
            $table->string('measurement');
            





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
        Schema::dropIfExists('product_managers');
    }
}
