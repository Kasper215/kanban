<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePushSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->string('endpoint')->unique();
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('push_subscriptions');
    }

}
