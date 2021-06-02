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
            $table->string('vehicle_vrn');
            $table->string('primary_damage');
            $table->string('odometer');
            $table->text('notes');
            $table->string('retail_value');
            $table->string('category');
            $table->string('secondary_damage');
            $table->string('color');
            $table->string('transmission');
            $table->string('fuel');
            $table->string('engine_type');
            $table->string('body_style');
            $table->integer('vat_added');
            $table->string('sell_type');
            $table->string('drive');
            $table->string('keys');
            $table->string('state');
            $table->string('model');
            $table->integer('year');
            $table->string('company');
            $table->string('starts_at_date');
            $table->string('is_finished');
            $table->boolean('featured')->default(false);
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
