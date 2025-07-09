<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\HandlesApiExceptions;
use App\Http\Requests\StoreRepresentativeRequest;

use App\Models\Representative;

class RepresentativeController extends Controller
{

    use HandlesApiExceptions;


    public function index()
    {
        $user = auth()->user();

        if (!$user->isCorporate() || !$user->corporateProfile) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. Corporate account required.'], 403);
        }

        $representative = Representative::where('corporate_profile_id', $user->corporateProfile->id)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Representative fetched successfully',
            'data' => $representative
        ],200);
    }


    public function fetchByCorporateRepresentativeProfile($corporateProfileId)
    {
        $user = auth()->user();


        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. Admin access required.'], 403);
        }

        try {
            $representative = Representative::where('corporate_profile_id', $corporateProfileId)->firstOrFail();

            return response()->json([
                'status' => 'success',
                'message' => 'Representative fetched successfully',
                'data' => $representative
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Representative not found for the given corporate profile.'
            ], 404);
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to fetch representative');
        }
    }


    public function store(StoreRepresentativeRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            if (!$user->isCorporate() || !$user->corporateProfile) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized. Corporate account required.'], 403);
            }

            $corporateProfileId = $user->corporateProfile->id;

            $data = $request->validated();

            if ($request->hasFile('pic')) {
                $data['pic'] = $request->file('pic')->store('representative_images', 'public');
            }

            $representative = Representative::updateOrCreate(['corporate_profile_id' => $corporateProfileId],$data);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Representative saved successfully',
                'data' => $representative
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to save representative');
        }
    }

}
