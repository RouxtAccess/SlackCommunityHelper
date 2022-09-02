<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->references('id')->on('workspaces');
            $table->string('channel_id')->index();
            $table->string('message')->nullable();

            $table->boolean('allow_list_top_level_enabled')->default(false);
            $table->json('allow_list_top_level')->nullable();
            $table->json('deny_list_top_level')->nullable();

            $table->boolean('allow_list_thread_enabled')->default(false);
            $table->json('allow_list_thread')->nullable();
            $table->json('deny_list_thread')->nullable();
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
        Schema::dropIfExists('channel_rules');
    }
}
