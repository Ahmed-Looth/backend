<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('cancel_request_reason')->nullable()->after('status');
            $table->text('cancel_reason')->nullable()->after('cancel_request_reason');
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'cancel_request_reason',
                'cancel_reason',
            ]);
        });
    }
};
