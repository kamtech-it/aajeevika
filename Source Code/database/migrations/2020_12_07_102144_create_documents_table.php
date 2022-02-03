<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->increments('id');

            $table->string('user_id')->nullable();

            $table->string('adhar_card_no')->nullable();
            $table->string('adhar_name')->nullable();
            $table->string('adhar_card_file')->nullable();
            $table->string('is_adhar_verify')->nullable();
            $table->string('adhar_dob')->nullable();

            $table->string('pencard_name')->nullable();
            $table->string('pancard_no')->nullable();
            $table->string('pancard_file')->nullable();
            $table->string('pancard_dob')->nullable();
            $table->string('is_pan_verify')->nullable();

            $table->string('brn_no')->nullable();
            $table->string('brn_name')->nullable();
            $table->string('brn_file')->nullable();

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
        Schema::dropIfExists('documents');
    }
}
