<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track SMS.ir owner-alert outcomes per booking (no queue worker).
 * cPanel: also run database/upgrades/2026_11_12_booking_owner_sms.sql via phpMyAdmin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'owner_sms_status')) {
                // null = not attempted; pending = claim in flight; accepted|rejected|uncertain = final.
                $table->string('owner_sms_status', 16)->nullable()->after('ip_hash');
            }
            if (! Schema::hasColumn('bookings', 'owner_sms_provider_message_id')) {
                $table->string('owner_sms_provider_message_id', 64)->nullable()->after('owner_sms_status');
            }
            if (! Schema::hasColumn('bookings', 'owner_sms_error')) {
                $table->string('owner_sms_error', 255)->nullable()->after('owner_sms_provider_message_id');
            }
            if (! Schema::hasColumn('bookings', 'owner_sms_sent_at')) {
                $table->timestamp('owner_sms_sent_at')->nullable()->after('owner_sms_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $cols = ['owner_sms_status', 'owner_sms_provider_message_id', 'owner_sms_error', 'owner_sms_sent_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
