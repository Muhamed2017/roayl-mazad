<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();

            $table->integer('imageable_id');
            $table->string('imageable_type');

            // $table->string('img_public_id');

            $table->string('img_url');

            $table->string('img_width');
            $table->string('img_height');

            $table->string('img_bytes');

            $table->string('format');
            $table->string('original_filename');

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
        Schema::dropIfExists('images');
    }
}
