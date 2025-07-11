<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\HandlesApiExceptions;
use App\Services\FlutterwaveService;


class BankAccountController extends Controller
{
    use HandlesApiExceptions;

    public function getBanks(FlutterwaveService $flutterwaveService)
    {
        $banks = $flutterwaveService->fetchBanks();
        return response()->json($banks);
    }


    public function verifyBankAccount(Request $request, FlutterwaveService $flutterwaveService)
    {
        $request->validate([
            'account_number' => 'required|string',
            'bank_code' => 'required|string',
        ]);


        $verification = $flutterwaveService->verifyBankAccount(
            $request->account_number,
            $request->bank_code
        );

        if ($verification['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $verification['message'],
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Bank account verified successfully',
            'data' => $verification['data'],
        ],200);
    }

    public function store(Request $request, FlutterwaveService $flutterwaveService)
    {



        $request->validate([
            'bank_code' => 'required|string',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
            'bank_name' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $result = $flutterwaveService->createBeneficiary([
                        'account_bank' => $request->bank_code,
                        'account_number' => $request->account_number,
                        'beneficiary_name' => $request->account_name,
                        'currency' => 'NGN',
                        'bank_name' => $request->bank_name,
                    ]);

            if (!$result['status'] || $result['status'] !== 'success') {
                throw new \Exception($result['message'] ?? 'Failed to create beneficiary');
            }

            $beneficiary = $result['data'];

            $account = BankAccount::create([
                'user_id' => auth()->id(),
                'benefit_id' => $beneficiary['id'],
                'bank_code' => $beneficiary['bank_code'],
                'bank_name' => $beneficiary['bank_name'],
                'account_number' => $beneficiary['account_number'],
                'account_name' => $beneficiary['full_name'],
                'is_default' => false,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bank account created successfully',
                'data' => $account,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to create bank account');
        }
    }

    public function index()
    {
        $user = auth()->user();

        $accounts = BankAccount::where('user_id', $user->id)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Bank accounts fetched successfully',
            'data' => $accounts
        ], 200);
    }

    public function getByUser($userId)
    {
        $authUser = auth()->user();

        if (!in_array($authUser->role, ['admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $accounts = BankAccount::where('user_id', $userId)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Bank accounts fetched successfully',
            'data' => $accounts
        ], 200);
    }

    public function setDefault($id)
    {
        $user = auth()->user();

        DB::beginTransaction();

        try {
            $account = BankAccount::where('user_id', $user->id)->findOrFail($id);


            BankAccount::where('user_id', $user->id)->where('is_default', true)->update(['is_default' => false]);


            $account->is_default = true;
            $account->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Default bank account set successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Bank Account');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to set default bank account');
        }
    }

   

    public function destroy($id, FlutterwaveService $flutterwaveService)
    {
        $user = auth()->user();

        DB::beginTransaction();

        try {
            $account = BankAccount::where('user_id', $user->id)->findOrFail($id);

            $deleteResult = $flutterwaveService->deleteBeneficiary($account->benefit_id);

            if ($deleteResult['status'] !== 'success') {
                throw new \Exception($deleteResult['message'] ?? 'Failed to delete beneficiary from Flutterwave');
            }

            $wasDefault = $account->is_default;
            $account->delete();

            if ($wasDefault) {
                $oldest = BankAccount::where('user_id', $user->id)->oldest()->first();
                if ($oldest) {
                    $oldest->is_default = true;
                    $oldest->save();
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bank account deleted successfully '
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Bank Account');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to delete bank account');
        }
    }



}
