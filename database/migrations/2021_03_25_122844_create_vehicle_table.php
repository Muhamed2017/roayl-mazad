<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehicleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicle', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('listed_by')->default('user');
            $table->string('vehicle_title');
            $table->string('vehicle_vin');
            $table->string('primary_damage')->nullable();
            $table->integer('odometer');
            $table->text('special_notes')->nullable();
            $table->string('retail_value')->nullable();
            $table->string('transmission');
            $table->tinyInteger('keys');
            $table->string('fuel');
            $table->string('engine_type')->nullable();
            $table->integer('vat_added');
            $table->integer('selender');
            $table->string('sell_type');
            $table->string('drive');

            $table->string('model')->nullable();
            $table->string('color')->nullable();
            $table->string('category')->nullable();
            $table->integer('year')->nullable();
            $table->string('company')->nullable();
            $table->string('starts_at_date')->nullable();
            $table->string('starts_at_time')->nullable();
            $table->tinyInteger('featured')->default(0);
            $table->string('published')->default('pending');
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
        Schema::dropIfExists('vehicle');
    }
}
