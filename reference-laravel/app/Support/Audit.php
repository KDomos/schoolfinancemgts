<?php
namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Audit
{
    public static function log(string $action, ?string $detail = null): void
    {
        $u = Auth::user();
        DB::table('audit_log')->insert([
            'user_id' => $u?->id,
            'role'    => $u?->role,
            'action'  => $action,
            'detail'  => $detail,
            'ip'      => request()->ip(),
        ]);
    }
}
