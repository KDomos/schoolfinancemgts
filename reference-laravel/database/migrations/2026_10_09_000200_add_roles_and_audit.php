<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Least privilege by default: a user with no role set can only read.
        Schema::table('users', function (Blueprint $t) {
            $t->string('role', 20)->default('Auditor')->after('email'); // Bursar | Director | Deputy director | Auditor
        });

        Schema::create('audit_log', function (Blueprint $t) {
            $t->id();
            $t->timestamp('at')->useCurrent();
            $t->foreignId('user_id')->nullable();
            $t->string('role', 20)->nullable();
            $t->string('action');
            $t->text('detail')->nullable();
            $t->string('ip', 45)->nullable();
        });

        // Append-only: the database itself refuses edits and deletes of audit rows.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::unprepared("CREATE TRIGGER audit_no_update BEFORE UPDATE ON audit_log FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_log is append-only'");
            DB::unprepared("CREATE TRIGGER audit_no_delete BEFORE DELETE ON audit_log FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_log is append-only'");
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_no_update');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_no_delete');
        }
        Schema::dropIfExists('audit_log');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
    }
};
