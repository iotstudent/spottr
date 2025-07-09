<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Traits\HandlesApiExceptions;
use App\Http\Requests\updateCorporateRequest;
use App\Http\Requests\userDeactivationRequest;
use App\Http\Requests\changePasswordRequest;
use App\Http\Requests\authorizeUserRequest;
use \Illuminate\Support\Facades\Mail;
use \Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\support\Str;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CorporateProfile;
use App\Models\DeactivationRequest;


class UserController extends Controller
{
    use HandlesApiExceptions;



    public function index(Request $request){

        $authUser = auth()->user();
        $query = User::with(['individualProfile', 'corporateProfile']);


        if (!in_array($authUser->role, ['admin', 'super_admin'])) {
            $query->whereNotIn('role', ['admin', 'super_admin']);
        }


        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('type') && in_array($request->type, ['seller', 'buyer'])) {
            $query->whereHas('individualProfile', function ($q) use ($request) {
                $q->where('type', $request->type);
            });
        }


        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }


        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('user_name', 'LIKE', '%' . $request->search . '%')
                ->orWhere('email', 'LIKE', '%' . $request->search . '%');
            });
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $users = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Users fetched successfully',
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'next_page_url' => $users->nextPageUrl(),
                'prev_page_url' => $users->previousPageUrl(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ]
        ], 200);
    }

    public function getProfile(){
        try {

            $user = auth()->user()->fresh();

            return response()->json([
                'status' => 'success',
                'message' => 'User Fetched successfully',
                'data' => $user
            ], 200);

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to fetch user profile');
        }
    }

    public function updateCorporate(updateCorporateRequest $request){

        $validated = $request->validated();

        DB::beginTransaction();

        try {

            $user = auth()->user();

            $userFields = ['email', 'phone', 'pic'];
            $profileFields = ['company_name', 'company_size', 'company_address', 'company_description', 'industry_id'];


            if ($request->hasFile('pic')) {
                $validated['pic'] = $request->file('pic')->store('profile_images', 'public');
            }


            $userData = array_intersect_key($validated, array_flip($userFields));
            $profileData = array_intersect_key($validated, array_flip($profileFields));

            $user->update($userData);
            $user->corporateProfile()->update($profileData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => $user->fresh(),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Profile update failed');
        }
    }






    public function changePassword(changePasswordRequest  $request){

        try{

            $data = $request->validated();

            $user = User::where('id',auth()->user()->id)->first();

            if($user){

                if (!Hash::check($data['current_password'], $user->password)) {

                    return response()->json(['status' => 'error', 'message' => 'Current password does not match '], 400);

                }else{

                    $user->update(["password" => bcrypt($data['new_password'])]);

                    return response()->json(['status' => 'success', 'message' => 'Password changed successfully'], 200);
                }

            }else{

                return response()->json(['status' => 'error', 'message' => 'User does not exist'], 400);
            }

        }catch (\Exception $e) {

            DB::rollBack();
            return $this->handleApiException($e, 'Update password failed');

        }

    }

    public function authorizeUser(authorizeUserRequest $request){
        $request->validated();

        $user = User::withTrashed()->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 400);
        }

        return response()->json(['status' => 'success', 'message' => 'Authorization successful'], 200);

    }

    public function deactivateAccount(userDeactivationRequest $request){

        $request->validated();

        try {

            $user = auth()->user();


            DB::beginTransaction();

            DeactivationRequest::create([
                'user_id' => $user->id,
                'reason' => $request->reason,
                'comment' => $request->comment,
            ]);


            $user->deletion_scheduled_at = now()->addDays(30);
            $user->save();

            $user->delete();

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Account deletion activated account Will be fully deleted in 30 days.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Account deactivation failed');
        }
    }



    public function validateTransactionPin(Request $request)
    {
        $request->validate([
            'current_transaction_pin' => 'required|string',
        ]);

        $user = auth()->user();


        if (is_null($user->transaction_pin)) {
            return response()->json([
                'status' => 'success',
                'message' => 'No existing transaction PIN. You can set a new transaction PIN directly.',
                 'first_time' => true
            ], 200);
        }


        if ($user->transaction_pin !== $request->current_transaction_pin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Incorrect current transaction PIN.',
            ], 400);
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Transaction PIN is correct. You can now proceed to set a new transaction PIN.',
        ], 200);
    }

    public function generateTransactionPinOtp(Request $request)
    {
        $user = auth()->user();

        $otp = random_int(100000, 999999);
        $user->transaction_pin_otp = $otp;
        $user->save();


        Mail::send('emails.transactionotp', [
            'name' => $user->user_name,
            'otp' => $otp,
            'email' => $user->email
        ], function($message) use ($user) {
            $message->to($user->email);
            $message->subject('Transaction PIN OTP');
        });

        return response()->json([
            'status' => 'success',
            'message' => 'OTP generated and sent to your email address.',
        ]);
    }

    public function confirmTransactionPinChange(Request $request)
    {
        $request->validate([
            'otp' => 'required|string',
            'new_transaction_pin' => 'required|string|min:4|max:6',
        ]);

        $user = auth()->user();

        if ($user->transaction_pin_otp !== $request->otp) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP.'], 400);
        }

        $user->transaction_pin = $request->new_transaction_pin;
        $user->transaction_pin_otp = null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Transaction PIN changed successfully.'
        ],200);
    }






    public function createCorporateAccountByAdmin(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'company_name' => 'required|string',
            'company_address' => 'nullable|string',
            'company_description' => 'required|string',
            'industry_id' => 'nullable|exists:industries,id',
            'pic' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
            'kyc_doc' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:10240',
        ]);

        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();

        try {
            $picPath = $request->hasFile('pic') ? $request->file('pic')->store('profile_images', 'public') : null;
            $kycPath = $request->hasFile('kyc_doc') ? $request->file('kyc_doc')->store('kyc_documents', 'public') : null;

            $newUser = User::create([
                'user_name' => $this->generateUniqueUsername($request->company_name),
                'role' => 'corporate',
                'email' => $request->email,
                'phone' => $request->phone,
                'pic' => $picPath,
                'password' => bcrypt(Str::random(10)),
                'email_verified_at' => now(),
                'is_active' => true,
                'created_by_admin' => true,
            ]);

            $corporateProfile = CorporateProfile::create([
                'user_id' => $newUser->id,
                'company_name' => $request->company_name,
                'company_address' => $request->company_address,
                'company_description' => $request->company_description,
                'industry_id' => $request->industry_id,
                'kyc_doc' => $kycPath,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Corporate account created successfully by admin.',
                'data' => [
                    'user_name' => $newUser->user_name,
                    'email' => $newUser->email,
                    'company_name' => $corporateProfile->company_name,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to create corporate account');
        }
    }

    public function updateCorporateAccountByAdmin(Request $request, $id)
    {
        $request->validate([
            'email' => 'nullable|email|unique:users,email,' . $id,
            'phone' => 'nullable|string',
            'company_name' => 'required|string',
            'company_address' => 'nullable|string',
            'company_description' => 'required|string',
            'industry_id' => 'nullable|exists:industries,id',
            'pic' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
            'kyc_doc' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:10240',
        ]);

        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();

        try {
            $corporateUser = User::where('role', 'corporate')->where('id', $id)->firstOrFail();

            if (!$corporateUser->created_by_admin) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'This corporate account was not created by admin.',
                ], 403);
            }

            // Update User fields
            $userData = [];
            if ($request->filled('email')) {
                $userData['email'] = $request->email;
            }
            if ($request->filled('phone')) {
                $userData['phone'] = $request->phone;
            }
            if ($request->hasFile('pic')) {
                if ($corporateUser->pic && Storage::disk('public')->exists($corporateUser->pic)) {
                    Storage::disk('public')->delete($corporateUser->pic);
                }
                $userData['pic'] = $request->file('pic')->store('profile_images', 'public');
            }

            $corporateUser->update($userData);

            // Update Corporate Profile
            $profile = $corporateUser->corporateProfile;
            $profile->update([
                'company_name' => $request->company_name,
                'company_address' => $request->company_address,
                'company_description' => $request->company_description,
                'industry_id' => $request->industry_id,
            ]);

            if ($request->hasFile('kyc_doc')) {
                if ($profile->kyc_doc && Storage::disk('public')->exists($profile->kyc_doc)) {
                    Storage::disk('public')->delete($profile->kyc_doc);
                }
                $profile->update([
                    'kyc_doc' => $request->file('kyc_doc')->store('kyc_documents', 'public'),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Corporate account updated successfully.',
                'data' => [
                    'user_name' => $corporateUser->user_name,
                    'email' => $corporateUser->email,
                    'company_name' => $profile->company_name,
                ],
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Corporate User');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to update corporate account');
        }
    }

    public function createAdmin(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'role' => 'required|in:admin,super_admin',
            'user_name' => 'required|string|unique:users,user_name',
        ]);

        $user = auth()->user();
        if ($user->role !== 'super_admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. Only super admins can perform this action.'], 403);
        }

        DB::beginTransaction();

        try {
            $randomPassword = Str::random(10);
            $newUser = User::create([
                'user_name' => $request->user_name,
                'role' => $request->role,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => bcrypt($randomPassword),
                'email_verified_at' => now(),
                'is_active' => true,
                'created_by_admin' => true,
            ]);

            DB::commit();


            Mail::send('emails.accountcreation', [
                'name' => $newUser->user_name,
            ], function ($message) use ($newUser) {
                $message->to($newUser->email);
                $message->subject('Your Admin Account Has Been Created');
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Admin account created successfully. Notification have been sent via email.',
                'data' => $newUser
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to create admin account');
        }
    }

    public function UserActivation(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $authUser = auth()->user();


        if (!in_array($authUser->role, ['admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        try {

            $user = User::findOrFail($id);

            $user->is_active = $request->is_active;
            $user->save();

            $message = $user->is_active ? 'User activated successfully.' : 'User deactivated successfully.';

            return response()->json([
                'status' => 'success',
                'message' =>  $message,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('User');
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to update user status');
        }
    }



    protected function generateUniqueUsername($input)
    {

        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $base = Str::slug(explode('@', $input)[0]);
        } else {
            $base = Str::slug($input);
        }

        $username = $base;
        $suffix = 1;

        while (User::where('user_name', $username)->exists()) {
            $username = $base . '-' . $suffix;
            $suffix++;
        }

        return $username;
    }


}
