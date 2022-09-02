<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThreadRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('thread_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->references('id')->on('workspaces');
            $table->string('channel_id')->index();
            $table->string('message')->nullable();
            $table->string('timestamp')->nullable()->index();
            $table->string('message_link')->nullable();

            $table->boolean('allow_list_enabled')->default(false);
            $table->json('allow_list')->nullable();
            $table->json('deny_list')->nullable();
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
        Schema::dropIfExists('thread_rules');
    }
}
