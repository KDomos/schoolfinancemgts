<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class BillingService
{
    /** Fee items that apply to one student in one term. */
    public function items(object $student, int $termNumber)
    {
        return DB::table('fee_items')
            ->where(fn ($q) => $q->whereNull('class_name')->orWhere('class_name', $student->class_name))
            ->where(fn ($q) => $q->whereNull('term_number')->orWhere('term_number', $termNumber))
            ->when($student->type !== 'Boarding', fn ($q) => $q->where('boarding_only', false))
            ->get();
    }

    public function billed(object $student, object $term): int
    {
        return (int) $this->items($student, $term->number)->sum('amount');
    }

    public function paid(object $student, object $term): int
    {
        return (int) DB::table('payments')
            ->where('student_id', $student->id)->where('term_id', $term->id)->sum('amount');
    }

    public function balance(object $student, object $term): int
    {
        return $this->billed($student, $term) - $this->paid($student, $term);
    }

    /** Defaulters report: students with arrears this term, largest first. */
    public function defaulters(object $term)
    {
        return DB::table('students')->where('active', true)->get()
            ->map(function ($s) use ($term) {
                $b = $this->billed($s, $term);
                $p = $this->paid($s, $term);
                return (object) ['student' => $s, 'billed' => $b, 'paid' => $p, 'arrears' => $b - $p];
            })
            ->filter(fn ($r) => $r->arrears > 0)
            ->sortByDesc('arrears')->values();
    }

    /**
     * Record a payment (manual or gateway) against the current term.
     * A repeated $reference is rejected by the unique index on payments.reference.
     */
    public function recordPayment(object $student, int $amount, string $method, ?string $reference, ?int $userId = null): int
    {
        $term = DB::table('terms')->where('is_current', true)->firstOrFail();

        return DB::table('payments')->insertGetId([
            'student_id'  => $student->id,
            'term_id'     => $term->id,
            'amount'      => $amount,
            'method'      => $method,
            'reference'   => $reference,
            'received_on' => now()->toDateString(),
            'recorded_by' => $userId,
            'created_at'  => now(), 'updated_at' => now(),
        ]);
    }
}
