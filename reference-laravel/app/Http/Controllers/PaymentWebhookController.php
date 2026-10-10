<?php
namespace App\Http\Controllers;

use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/webhooks/payments/{provider}   (provider: mtn | airtel | schoolpay)
 *
 * Each aggregator names its fields differently. Adjust normalise() and the
 * signature check once you have the real rules from your provider.
 */
class PaymentWebhookController extends Controller
{
    private const METHODS = ['mtn' => 'MTN MoMo', 'airtel' => 'Airtel Money', 'schoolpay' => 'SchoolPay'];

    public function __invoke(Request $request, string $provider, BillingService $billing): JsonResponse
    {
        abort_unless(isset(self::METHODS[$provider]), 404);

        // 1. Verify the signature on the raw body (secret set in .env per provider)
        $secret   = config("payments.$provider.secret");
        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);
        if (! $secret || ! hash_equals($expected, (string) $request->header('X-Signature'))) {
            Log::warning('Webhook bad signature', ['provider' => $provider, 'ip' => $request->ip()]);
            return response()->json(['status' => 'rejected'], 401);
        }

        // 2. Normalise the provider payload
        $p = $this->normalise($request->all());
        if (! $p['reference'] || $p['amount'] <= 0) {
            return response()->json(['status' => 'invalid'], 422);
        }

        // 3. Idempotent: already have this transaction? Acknowledge and stop
        $seen = DB::table('payments')->where('reference', $p['reference'])->exists()
             || DB::table('unmatched_payments')->where('reference', $p['reference'])->exists();
        if ($seen) {
            return response()->json(['status' => 'duplicate']);
        }

        // 4. Match the student by payment code
        $student = DB::table('students')->where('code', strtoupper(trim((string) $p['code'])))->first();

        if (! $student) {
            DB::table('unmatched_payments')->insert([
                'provider' => $provider, 'reference' => $p['reference'], 'student_code' => $p['code'],
                'amount' => $p['amount'], 'raw' => json_encode($request->all()),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return response()->json(['status' => 'held']);
        }

        // 5. Post the payment and compute the new balance
        $term = DB::table('terms')->where('is_current', true)->firstOrFail();
        DB::transaction(fn () => $billing->recordPayment($student, $p['amount'], self::METHODS[$provider], $p['reference']));
        $balance = $billing->balance($student, $term);

        \App\Support\Audit::log('Payment received', $p['reference'].' · UGX '.$p['amount'].' · '.$student->code.' · '.$provider);

        // 6. Queue the confirmation SMS here (add your SMS gateway job)
        // SendReceiptSms::dispatch($student->id, $p['amount'], $balance);

        return response()->json(['status' => 'posted', 'balance' => $balance]);
    }

    private function normalise(array $d): array
    {
        // Placeholder field names: replace with the provider's real ones.
        return [
            'reference' => $d['transaction_id'] ?? $d['txn_id'] ?? null,
            'code'      => $d['student_code'] ?? $d['payment_reference'] ?? $d['external_id'] ?? null,
            'amount'    => (int) ($d['amount'] ?? 0),
        ];
    }
}
