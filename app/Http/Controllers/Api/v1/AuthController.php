<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Traits\HandlesApiExceptions;
use \Illuminate\Support\Facades\Mail;
use \Illuminate\Support\Facades\DB;
use App\Http\Requests\registerCorporateRequest;
use App\Http\Requests\registerIndividualRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Requests\resendVerificationCodeRequest;
use App\Http\Requests\signInRequest;
use App\Http\Requests\forgotPasswordRequest;
use App\Http\Requests\verifyResetOTPRequest;
use App\Http\Requests\resetPasswordRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\support\Str;
use App\Models\User;
use App\Models\CorporateProfile;
use App\Models\IndividualProfile;
use Carbon\Carbon;



class AuthController extends Controller
{

    use HandlesApiExceptions;


    public function registerCorporateUser(registerCorporateRequest $request){
        $request->validated();

        DB::beginTransaction();

        try {
             $otp = $this->generateOtp(6, true);
            $verification_expiry = now()->addHours(24);

            $picPath = $request->file('pic')->store('profile_images', 'public');
            $kycPath = $request->file('kyc_doc')->store('kyc_documents', 'public');


            $user = User::create([
                'user_name' => $this->generateUniqueUsername($request->company_name),
                'role' => 'corporate',
                'email' => $request->email,
                'phone' => $request->phone,
                'pic' => $picPath,
                'password' => bcrypt($request->password),
                'verification_code' => $otp,
                'verification_expiry' => $verification_expiry,
                'is_active' => true,
            ]);


            $corporate = CorporateProfile::create([
                'user_id' => $user->id,
                'company_name' => $request->company_name,
                'company_address' => $request->company_address,
                'company_description' => $request->company_description,
                'industry_id' => $request->industry_id,
                'kyc_doc' => $kycPath,
            ]);

            DB::commit();


            Mail::send('emails.verification', [
                'name' => $corporate->company_name,
                'otp' => $otp,
                'email' => $user->email
            ], function($message) use ($user) {
                $message->to($user->email);
                $message->subject('Verification Mail');
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Corporate account created successfully. A verification code has been sent to ' . $user->email,
                'data' => [
                    'user_name' => $user->user_name,
                    'type' => $user->role,
                    'status' => $user->is_active,
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Corporate registration failed');
        }
    }

    public function registerIndividualUser(registerIndividualRequest $request){
        $request->validated();
        DB::beginTransaction();

        try {
           $otp = $this->generateOtp(6, true);
            $verification_expiry = now()->addHours(24);

            $user = User::create([
                'user_name' => $this->generateUniqueUsername($request->email),
                'role' => 'individual',
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'verification_code' => $otp,
                'verification_expiry' => $verification_expiry,
            ]);

            IndividualProfile::create([
                'user_id' => $user->id,
                'type' => $request->type,
                'verification_level' => '0',
            ]);

            DB::commit();


            Mail::send('emails.verification', [
                'name' => $user->user_name,
                'otp' => $otp,
                'email' => $user->email
            ], function($message) use ($user) {
                $message->to($user->email);
                $message->subject('Verification Mail');
            });


            return response()->json([
                'status' => 'success',
                'message' => 'Individual account created successfully. A verification code has been sent to ' . $user->email,
                'data' =>$user
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Individual registration failed');
        }

    }

    public function verifyEmail(VerifyEmailRequest $request){

        $request->validated();

        DB::beginTransaction();

        try {

            $user = User::where('verification_code', $request->verification_code)->first();

            if(!$user){

                return response()->json(['status' => 'error', 'message' => 'wrong verifcation code'], 400);
            }

            if(now()->isBefore(Carbon::parse($user->verification_expiry))){

                $user->email_verified_at = now();
                $user->verification_code = null;
                $user->verification_expiry = null;
                $user->save();

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Email Successfully Verified',
                ],200);

            }else{

                return response()->json(['status' => 'error', 'message' => 'verification code expired.click on resend', 'data' => ['user_email'=>$user->email]], 400);
            }


        } catch (\Exception $e) {
            DB::rollback();
           return $this->handleApiException($e, 'resen verification Failed');
        }
    }

    public function resendVerificationCode(resendVerificationCodeRequest $request){

        $data = $request->validated();

        DB::beginTransaction();

        try {

            $user = User::where('email', $data['email'])->first();

            if($user){

                if ($user->email_verified_at !== null) {

                    return response()->json(['status' => 'error', 'message' => 'Your email has been verified already'], 400);
                }

                $verification_code = $this->generateOtp(6, true);
                $verification_expiry = now()->addHours(24);
                $user->update(["verification_code" =>  $verification_code,"verification_expiry" =>  $verification_expiry,]);
                DB::commit();

                //Send verification email here
                Mail::send('emails.verification', ['name' => $request->name,'otp' => $verification_code,'email'=>$request->email], function($message) use($request){
                    $message->to($request->email);
                    $message->subject('Verification Mail');
                });

                return response()->json(['status' => 'success', 'message' => 'A verification code has been sent to ' .
                $user->email . '. Check your spam if you can\'t find it. The link expires in 24 hours'], 200);

            }else{

                return response()->json(['status' => 'error', 'message' => 'This Email is not registered with us'], 403);
            }

        }catch(\Exception $e){
            DB::rollback();
             return $this->handleApiException($e, 'resen verification Failed');
        }
    }

    public function signIn(signInRequest $request){


        $data = $request->validated();

        try {

            $user = User::withTrashed()->whereEmail($data['email'])->first();


            if (!$user){
                return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 400);
            }

            if (!Hash::check($data['password'], $user->password)) {
                return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 400);
            }


            if (!$user->is_active){
                return response()->json(['status' => 'error', 'message' => 'Account Suspended'], 400);
            }

            if ($user->role != "admin" &&  $user->role != "super_admin" ){

                if (!$user->email_verified_at){
                    return response()->json(['status' => 'error', 'message' => 'Your account is yet to be verified'], 400);
                }
            }

            if ($user->trashed()) {
                if ($user->deletion_scheduled_at && now()->lessThan($user->deletion_scheduled_at)) {
                    $user->restore();
                    $user->deletion_scheduled_at = null;
                    $user->save();
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Account permanently deleted'], 403);
                }
            }


            $token = $user->createToken('token')->plainTextToken;
            return response()->json(['status' => 'success', 'message' => 'Signin successfull', 'token' => $token,'data' => $user], 200);

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Signin Failed');
        }

    }

    public function adminSignIn(signInRequest $request)
    {
        $data = $request->validated();

        try {
            $user = User::withTrashed()->whereEmail($data['email'])->first();

            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 400);
            }

            if (!Hash::check($data['password'], $user->password)) {
                return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 400);
            }

            // Ensure only admin or super_admin can login here
            if (!in_array($user->role, ['admin', 'super_admin'])) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized access'], 403);
            }

            if (!$user->is_active) {
                return response()->json(['status' => 'error', 'message' => 'Account Suspended'], 400);
            }

            // Handle soft-deleted accounts
            if ($user->trashed()) {
                if ($user->deletion_scheduled_at && now()->lessThan($user->deletion_scheduled_at)) {
                    $user->restore();
                    $user->deletion_scheduled_at = null;
                    $user->save();
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Account permanently deleted'], 403);
                }
            }

            $token = $user->createToken('token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Admin signin successful',
                'token' => $token,
                'data' => $user
            ], 200);

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Admin Signin Failed');
        }
    }


    public function forgotPassword(forgotPasswordRequest $request){

        try {

            $data = $request->validated();

            DB::beginTransaction();

            $user = User::whereEmail($data['email'])->first();

            if($user)
            {
                $user_check = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

                if($user_check){

                    $otp = $this->generateOtp(6, true);
                    DB::table('password_reset_tokens')->where(['email' =>  $data['email']])->update([
                        'token' => $otp,
                        'created_at' => Carbon::now(),
                        'expired_at' => now()->addMinutes(30)
                      ]);
                      DB::commit();

                }else{

                     $otp = $this->generateOtp(6, true);
                    DB::table('password_reset_tokens')->insert([
                        'email' => $request->email,
                        'token' => $otp,
                        'created_at' => Carbon::now(),
                        'expired_at' => now()->addMinutes(30)
                      ]);
                      DB::commit();
                }

                //Send verification email here
                Mail::send('emails.resetpassword', ['otp' => $otp,'email'=>$request->email], function($message) use($request){
                    $message->to($request->email);
                    $message->subject('Password Reset OTP');
                });

                return response()->json(['status' => 'success', 'message' => 'An OTP has been sent to your Email address'], 200);

            }else{

                return response()->json(['status' => 'error', 'message' => 'This Email address is not registered with us '], 404);
            }


        }catch(\Exception $e){
            DB::rollback();
            return $this->handleApiException($e, 'Forgot password Failed');
        }
    }

    public function verifyResetOTP(verifyResetOTPRequest $request){

        try {

            $request->validated();

            $user_check = DB::table('password_reset_tokens')->where([['token', $request->otp]])->first();

            if(!$user_check){
                return response()->json(['status' => 'error', 'message' => 'Invalid OTP'], 400);
            }else{

                if(now()->isBefore(Carbon::parse($user_check->expired_at))){

                    $user = User::where('email', $user_check->email)->first();
                    return response()->json(['status' => 'success', 'message' => 'OTP correct', 'data' => ['user_id' => $user->id,]], 200);

                }else{

                    return response()->json(['status' => 'error', 'message' => 'OTP expired'], 400);
                }
            }

        }catch(\Exception $e){
            return $this->handleApiException($e, 'verify Reset Password OTP Failed');
        }
    }

    public function resetPassword(resetPasswordRequest $request){

        try {

            $request->validated();

            $user = User::where('id', $request->user_id)->first();

            if(!$user){
                return response()->json(['status' => 'error', 'message' => 'User does not exist'], 400);
            }else{

                $user->update(['password' => bcrypt($request->password)]);
                DB::table('password_reset_tokens')->where('email',$user->email)->delete();
                return response()->json(['status' => 'success', 'message' => 'Password reset successfully'], 200);
            }

        }catch(\Exception $e){
            return $this->handleApiException($e, 'Reset Password Failed');
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


    protected function generateOtp($length = 6, $numeric = false)
    {
        return $numeric
            ? (string) mt_rand(10 ** ($length - 1), (10 ** $length) - 1)
            : strtoupper(Str::random($length));
    }




}
