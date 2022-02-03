<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->integer('mobile')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->tinyInteger('is_email_verified')->default(0);
            $table->string('password');
            $table->string('profileImage')->nullable();
            $table->string('role_id')->default(1);
            $table->string('title')->nullable();
            $table->integer('district')->nullable();

            $table->string('api_token', 60)->unique()->nullable();
            
            $table->tinyInteger('isActive')->default(0);
            
            $table->tinyInteger('is_document_added')->default(0);
            $table->tinyInteger('is_document_verified')->default(0);
            $table->tinyInteger('is_address_added')->default(0);
            $table->tinyInteger('is_promotional_mail')->default(0);

            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
