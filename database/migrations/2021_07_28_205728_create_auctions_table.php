<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuctionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->string("vehicle_id");
            $table->string("firebase_id")->nullable();
            $table->string("vehicle_title");
            $table->string("lister_name");
            $table->string("lister_id");
            $table->string("sell_type");
            $table->integer("final_price");
            $table->string("retail_value");
            $table->string("vehicle_start_data");
            $table->string("vehicle_start_time");
            $table->string("auction_owner")->nullable();
            $table->string("auction_owner_id")->nullable();
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
        Schema::dropIfExists('auctions');
    }
}
