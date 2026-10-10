<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('year');
            $t->unsignedTinyInteger('number');          // 1, 2 or 3
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable();
            $t->boolean('is_current')->default(false);
            $t->unique(['year', 'number']);
        });

        Schema::create('students', function (Blueprint $t) {
            $t->id();
            $t->string('code', 12)->unique();           // payment code, e.g. SF260001
            $t->string('name');
            $t->string('class_name')->index();          // e.g. "P.6 Blue"
            $t->enum('type', ['Day', 'Boarding']);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        // Flat fee structure: class_name null = all classes, term_number null = all terms
        Schema::create('fee_items', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('amount');           // UGX, whole shillings
            $t->string('class_name')->nullable();
            $t->unsignedTinyInteger('term_number')->nullable();
            $t->boolean('boarding_only')->default(false);
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained();
            $t->foreignId('term_id')->constrained();
            $t->unsignedBigInteger('amount');
            $t->enum('method', ['Cash', 'Bank slip', 'Cheque', 'MTN MoMo', 'Airtel Money', 'SchoolPay']);
            $t->string('reference')->nullable()->unique(); // gateway transaction id blocks duplicates
            $t->date('received_on');
            $t->foreignId('recorded_by')->nullable();      // user id; null for webhook
            $t->timestamps();
        });

        // Gateway payments with an unknown student code, held for the bursar
        Schema::create('unmatched_payments', function (Blueprint $t) {
            $t->id();
            $t->string('provider');
            $t->string('reference')->unique();
            $t->string('student_code')->nullable();
            $t->unsignedBigInteger('amount');
            $t->json('raw');
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->date('spent_on');
            $t->enum('category', ['Catering', 'Transport', 'Utilities', 'Stationery', 'Maintenance', 'Administration']);
            $t->string('note')->nullable();
            $t->unsignedBigInteger('amount');
            $t->enum('account', ['Cash', 'Bank', 'Mobile money']);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['expenses', 'unmatched_payments', 'payments', 'fee_items', 'students', 'terms'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
