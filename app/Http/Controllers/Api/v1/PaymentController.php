<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use \Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\HandlesApiExceptions;
use App\Services\ThresholdService;
use App\Services\FlutterwaveService;
use Illuminate\support\Str;
use Illuminate\Http\Request;
use App\Models\WalletTransaction;
use App\Models\Transaction;

class PaymentController extends Controller
{

    use HandlesApiExceptions;

    public function initiateWalletTopUp(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'tx_ref' => 'required|string',
        ]);

        $user = auth()->user();
        $tx_ref = 'spottr-wallet-topup-' . Str::uuid();

        $transaction = WalletTransaction::create([
            'user_id' => $user->id,
            'tx_ref' => $request->tx_ref,
            'provider' =>'flutterwave',
            'type' => 'debit',
            'format' => 'fiat',
            'amount' => $request->amount,
            'payment_status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Transaction initialized successfully',
            'data' => [
                'tx_ref' => $transaction->tx_ref,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
            ],
        ]);
    }

    public function verifyFiatPayment(Request $request, FlutterwaveService $flutterwaveService)
    {
        $transaction_id = $request->query('transaction_id');
        $tx_ref = $request->query('tx_ref');

        if (!$transaction_id || !$tx_ref) {
            return response()->json(['error' => 'Missing transaction_id or tx_ref'], 400);
        }

        $verification = $flutterwaveService->verifyTransaction($transaction_id);

        if ($verification['status'] !== 'success') {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment verification failed'
            ], 400);
        }

        $paymentData = $verification['data'];

        // Find wallet transaction using tx_ref
        $walletTransaction = WalletTransaction::where('tx_ref', $tx_ref)->first();

        if (!$walletTransaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        if ($walletTransaction->payment_status !== 'successful') {

            $walletTransaction->update([
               'transaction_id' => $transaction_id,
               'payment_status' => strtolower($paymentData['status']),
               'payment_method' => $paymentData['payment_type'] ?? 'unknown',

            ]);

            $walletTransaction->user->increment('fiat_wallet', $walletTransaction->amount);

            Transaction::create([
                'user_id' => $walletTransaction->user_id,
                'type' => "credit",
                'format' => "fiat",
                'purpose' => "wallet-top-up",
                'amount' => $walletTransaction->amount,
                'status' => 'successful',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Wallet top-up successful',
        ], 200);
    }

    public function verifyCryptoTopUp(Request $request, ThresholdService $thresholdService)
    {
        $payload = $request->all();

        try {
            $result = $thresholdService->processTopUpWebhook($payload);

            if ($result) {
                Transaction::create([
                    'user_id' => $result['user']->id,
                    'type' => 'credit',
                    'format' => 'crypto',
                    'purpose' => 'crypto-wallet-top-up',
                    'amount' => $result['amount'],
                    'status' => 'successful',
                ]);
            }

            return response()->json([
                'status' => $result ? 'success' : 'ignored',
                'message' => $result ? 'Crypto top-up processed.' : 'Webhook event ignored.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Threshold webhook failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed.',
            ], 500);
        }
    }





}
