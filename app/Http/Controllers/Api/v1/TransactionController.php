<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\WalletTransaction;


class TransactionController extends Controller
{

    public function index(Request $request)
    {
        $query = WalletTransaction::with('user');

        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'super_admin'])) {
            $query->where('user_id', $user->id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('format')) {
            $query->where('format', $request->format);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $transactions = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Wallet transactions fetched successfully',
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'next_page_url' => $transactions->nextPageUrl(),
                'prev_page_url' => $transactions->previousPageUrl(),
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
            ]
        ], 200);
    }


    public function indexTransaction(Request $request)
    {
        $query = Transaction::with('user');

        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'super_admin'])) {
            $query->where('user_id', $user->id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('format')) {
            $query->where('format', $request->format);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

         if ($request->has('purpose')) {
            $query->where('purpose', $request->purpose);
        }


        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $transactions = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Transactions fetched successfully',
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'next_page_url' => $transactions->nextPageUrl(),
                'prev_page_url' => $transactions->previousPageUrl(),
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
            ]
        ], 200);
    }



}
