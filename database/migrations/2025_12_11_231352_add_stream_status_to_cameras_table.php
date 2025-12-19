<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->string('stream_status')->default('stopped')->after('is_active');
            $table->text('last_error')->nullable()->after('stream_status');
            $table->timestamp('last_stream_check')->nullable()->after('last_error');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn(['stream_status', 'last_error', 'last_stream_check']);
        });
    }
};
