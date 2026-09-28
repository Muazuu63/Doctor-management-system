<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('name_bn')->default('');
            $table->string('degree')->default('');
            $table->string('specialty')->default('');
            $table->string('phone', 30)->unique();
            $table->string('email')->default('');
            $table->unsignedInteger('consultation_fee')->default(500);
            $table->unsignedTinyInteger('slot_minutes')->default(20);
            $table->text('bio')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30)->unique();
            $table->string('pin', 20);
            $table->string('gender', 20)->default('');
            $table->unsignedSmallInteger('age')->default(0);
            $table->string('blood_group', 10)->default('');
            $table->string('address')->default('');
            $table->timestamps();
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedTinyInteger('day_of_week');
            $table->string('start_time', 10);
            $table->string('end_time', 10);
            $table->unsignedTinyInteger('slot_minutes')->default(20);
            $table->unsignedSmallInteger('max_serial')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->date('except_date');
            $table->boolean('is_off')->default(false);
            $table->string('title')->default('');
            $table->string('start_time', 10)->nullable();
            $table->string('end_time', 10)->nullable();
            $table->unsignedTinyInteger('slot_minutes')->default(20);
            $table->unsignedSmallInteger('max_serial')->default(50);
            $table->string('note')->default('');
            $table->timestamps();
            $table->unique(['doctor_id', 'except_date']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('schedule_id')->default(0);
            $table->date('appointment_date');
            $table->unsignedSmallInteger('serial_no');
            $table->string('scheduled_time', 10);
            $table->string('original_time', 10);
            $table->unsignedTinyInteger('slot_minutes')->default(20);
            $table->unsignedSmallInteger('delay_minutes')->default(0);
            $table->string('status', 30)->default('booked');
            $table->string('token', 64)->unique();
            $table->string('notes')->default('');
            $table->boolean('sms_sent')->default(false);
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();
            $table->unique(['appointment_date', 'serial_no', 'doctor_id'], 'uniq_serial');
            $table->index(['appointment_date', 'status']);
        });

        Schema::create('delays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->date('delay_date');
            $table->unsignedSmallInteger('delay_minutes');
            $table->string('reason')->default('');
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->text('complaints')->nullable();
            $table->text('findings')->nullable();
            $table->text('diagnosis')->nullable();
            $table->json('medicines')->nullable();
            $table->text('tests')->nullable();
            $table->text('advice')->nullable();
            $table->string('follow_up_date', 40)->default('');
            $table->timestamps();
        });

        Schema::create('medicine_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('dose', 100)->default('');
            $table->string('duration', 100)->default('');
            $table->string('instruction')->default('');
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 30);
            $table->text('message');
            $table->string('type', 30)->default('info');
            $table->string('status', 30)->default('logged');
            $table->unsignedBigInteger('appointment_id')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('skey', 100)->unique();
            $table->text('svalue')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor', 100)->default('');
            $table->string('action');
            $table->text('detail')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('login_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->string('channel', 20)->default('sms');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_otps');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('medicine_templates');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('delays');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('schedule_exceptions');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('doctors');
    }
};
