<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ThresholdService;
use App\Traits\HandlesApiExceptions;
use App\Models\UserAddress;

class UserAddressController extends Controller
{
    use HandlesApiExceptions;

    protected $thresholdService;

    public function __construct(ThresholdService $thresholdService)
    {
        $this->thresholdService = $thresholdService;
    }

    public function index()
    {
        $user = auth()->user();


        if (in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $addresses = UserAddress::where('user_id', $user->id)->get();

        if ($addresses->isEmpty()) {
            $addresses = $this->createAddresses($user->id);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Wallet addresses fetched successfully',
            'data' => $addresses,
        ], 200);
    }


    protected function createAddresses($userId)
    {
        $user = auth()->user();
        $this->thresholdService->createUserWallets($user);

        return UserAddress::where('user_id', $userId)->get();
    }
}
