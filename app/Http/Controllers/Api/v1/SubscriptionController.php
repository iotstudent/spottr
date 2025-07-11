<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    use HandlesApiExceptions;


    public function index(Request $request)
    {
        $query = Subscription::with(['user', 'subscriptionPlan']);

        $user = auth()->user();


        if (!in_array($user->role, ['admin', 'super_admin'])) {
            $query->where('user_id', $user->id);
        }


        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('subscription_plan_id')) {
            $query->where('subscription_plan_id', $request->subscription_plan_id);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $subscriptions = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Subscriptions fetched successfully',
            'data' => $subscriptions->items(),
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'next_page_url' => $subscriptions->nextPageUrl(),
                'prev_page_url' => $subscriptions->previousPageUrl(),
                'total' => $subscriptions->total(),
                'per_page' => $subscriptions->perPage(),
            ]
        ], 200);
    }


    public function store(Request $request)
    {
        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'duration_months' => 'required|integer|min:1',
        ]);

        $user = auth()->user();
        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

        // Calculate total amount
        $totalAmount = $plan->amount * $request->duration_months;

        // Check Wallet Balance
        if ($user->fiat_wallet < $totalAmount) {
            return response()->json(['error' => 'Insufficient wallet balance'], 400);
        }

        // Deduct from wallet
        $user->decrement('fiat_wallet', $totalAmount);

        // Check for active subscription
        $currentSubscription = Subscription::where('user_id', $user->id)->where('is_active', true)->first();

        $startDate = now();
        if ($currentSubscription) {
            $startDate = Carbon::parse($currentSubscription->end_date)->addDay();
        }

        $durationDays = $request->duration_months * 30;
        $endDate = $startDate->copy()->addDays($durationDays);

         // Log Transaction
       $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => 'debit',
            'format' => 'fiat',
            'purpose' => 'subscription',
            'amount' => $totalAmount,
            'status' => 'successful',
        ]);

        // Create Subscription
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'transaction_id' => $transaction->id,
            'duration_months' => $request->duration_months,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => !$currentSubscription,
        ]);


        return response()->json([
            'status' => 'success',
            'message' => 'Subscription created successfully',
            'data' => $subscription,
        ]);
    }


}
