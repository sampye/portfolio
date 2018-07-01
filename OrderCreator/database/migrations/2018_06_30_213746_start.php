<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Start extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('vats', function (Blueprint $table){
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('countries', function (Blueprint $table){
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('email')->nullable();
            $table->string('format');
            $table->integer('mail_to')->nullable();
            $table->decimal('total', 7,2);
            $table->integer('country_id')->unsigned();
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->decimal('price', 7,2);
            $table->integer('vat_id')->unsigned();
            $table->integer('divisible');
        });

        Schema::create('country_vat', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('country_id')->unsigned();
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
            $table->integer('vat_id')->unsigned();
            $table->foreign('vat_id')->references('id')->on('vats')->onDelete('cascade');
            $table->integer('amount');
        });

        Schema::create('order_item', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('order_id')->unsigned();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->integer('item_id')->unsigned();
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->decimal('quantity',7,2);
            $table->decimal('vat', 7,2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_item');
        Schema::drop('country_vat');
        Schema::drop('items');
        Schema::drop('orders');
        Schema::drop('countries');
        Schema::drop('vats');
    }
}
