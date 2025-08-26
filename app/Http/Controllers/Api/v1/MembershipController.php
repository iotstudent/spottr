<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\User;
use App\Models\Membership;

class MembershipController extends Controller
{
    use HandlesApiExceptions;


    public function index(Request $request)
    {
        $authUser = auth()->user();

       $query = Membership::query();

        // Conditional eager loading
        if (in_array($authUser->role, ['admin', 'super_admin'])) {
            $query->with(['corporate', 'seller']);
        } elseif ($authUser->role === 'corporate') {
            $query->with('seller');
            $query->where('corporate_id', $authUser->id);
        } elseif ($authUser->isIndividual() && $authUser->profile->type === 'seller') {
            $query->with('corporate');
            $query->where('seller_id', $authUser->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('initiated_by')) {
            $query->where('initiated_by', $request->initiated_by);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $memberships = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Memberships fetched successfully',
            'data' => $memberships->items(),
            'pagination' => [
                'current_page' => $memberships->currentPage(),
                'next_page_url' => $memberships->nextPageUrl(),
                'prev_page_url' => $memberships->previousPageUrl(),
                'total' => $memberships->total(),
                'per_page' => $memberships->perPage(),
            ]
        ], 200);
    }



    public function invite(Request $request)
    {
        $request->validate([
            'seller_id' => 'required|uuid|exists:users,id',
        ]);

        $corporate = auth()->user();

        if (!$corporate->isCorporate()) {
            return response()->json(['status' => 'error', 'message' => 'Only corporates can invite sellers.'], 403);
        }

        try {

            $seller = User::findOrFail($request->seller_id);

            if (!$seller->isIndividual() || $seller->profile->type !== 'seller') {
                return response()->json(['status' => 'error', 'message' => 'Invalid seller account.'], 400);
            }

            $existing = Membership::where('corporate_id', $corporate->id)
                ->where('seller_id', $seller->id)
                ->whereIn('status', ['pending', 'accepted'])
                ->first();

            if ($existing) {
                return response()->json(['status' => 'error', 'message' => 'Membership or invitation already exists.'], 400);
            }

            Mail::send('emails.membershipinvitation', ['name' => $seller->individualProfile->first_name . $seller->individualProfile->last_name,'company_name' => $corporate->corporateProfile->company_name], function ($message) use ($seller) {
                $message->to($seller->email);
                $message->subject('Membership Invitation');
            });

            $membership = Membership::create([
                'corporate_id' => $corporate->id,
                'seller_id' => $seller->id,
                'status' => 'pending',
                'initiated_by' => 'corporate',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Invitation sent successfully.',
                'data' => $membership,
            ], 201);

        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Seller');
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to send invitation');
        }
    }

    public function revoke($id)
    {
        $authUser = auth()->user();

        try {
            $membership = Membership::findOrFail($id);

            if (
                !(
                    ($authUser->isCorporate() && $membership->corporate_id === $authUser->id) ||
                    ($authUser->isIndividual() && $authUser->profile->type === 'seller' && $membership->seller_id === $authUser->id)
                )
            ) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
            }

            if ($membership->status !== 'pending' ) {

                return response()->json(['status' => 'error', 'message' => 'Only pending memberships can be revoked.'], 400);
            }

            $membership->update(['status' => 'revoked']);

            return response()->json([
                'status' => 'success',
                'message' => 'Membership revoked successfully.',
                'data' => $membership,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Membership');
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to revoke membership');
        }
    }

    public function removeMembership($membershipId)
    {
        try {
            $user = auth()->user();

            $membership = Membership::findOrFail($membershipId);

            // Check if the logged-in user is either the seller or the corporate that owns the membership
            if ($membership->seller_id !== $user->id && $membership->corporate_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to remove this membership.'
                ], 403);
            }

            $membership->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Membership removed successfully.'
            ], 200);

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to remove membership');
        }
    }



    public function apply(Request $request)
    {
        $request->validate([
            'corporate_id' => 'required|uuid|exists:users,id',
        ]);

        $seller = auth()->user();

        if (!$seller->isIndividual() || $seller->profile->type !== 'seller') {
            return response()->json(['status' => 'error', 'message' => 'Only sellers can apply for membership.'], 403);
        }

        try {
            $corporate = User::findOrFail($request->corporate_id);

            if (!$corporate->isCorporate()) {
                return response()->json(['status' => 'error', 'message' => 'Invalid corporate account.'], 400);
            }

            $existing = Membership::where('corporate_id', $corporate->id)
                ->where('seller_id', $seller->id)
                ->whereIn('status', ['pending', 'accepted'])
                ->first();

            if ($existing) {
                return response()->json(['status' => 'error', 'message' => 'Membership request already exists.'], 400);
            }

            $membership = Membership::create([
                'corporate_id' => $corporate->id,
                'seller_id' => $seller->id,
                'status' => 'pending',
                'initiated_by' => 'seller',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Application sent successfully.',
                'data' => $membership,
            ], 201);

        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Corporate');
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to submit application');
        }
    }

    public function respond(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accepted,rejected',
        ]);

        $authUser = auth()->user();

        try {
            $membership = Membership::findOrFail($id);

            if (
                !(
                    ($authUser->isCorporate() && $membership->corporate_id === $authUser->id) ||
                    ($authUser->isIndividual() && $authUser->profile->type === 'seller' && $membership->seller_id === $authUser->id)
                )
            ) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
            }

            if ($membership->status !== 'pending') {
                return response()->json(['status' => 'error', 'message' => 'Only pending memberships can be updated.'], 400);
            }

            $membership->update([
                'status' => $request->status,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Membership {$request->status} successfully.",
                'data' => $membership,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Membership');
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to respond to membership');
        }
    }





}
