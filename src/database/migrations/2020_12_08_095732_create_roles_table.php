<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('role_name');
            // $table->integer('created_by');
            $table->timestamps();
        });
        DB::table('roles')->insert([ 'role_name' => 'Users']);
        DB::table('roles')->insert([ 'role_name' => 'Artisan']);
        DB::table('roles')->insert([ 'role_name' => 'SHG']);
        DB::table('roles')->insert([ 'role_name' => 'Sub Admin']);
        DB::table('roles')->insert([ 'role_name' => 'Super Admin']);


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
}
