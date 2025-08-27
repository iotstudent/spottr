<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use App\Models\Membership;
use App\Models\DistributionList;
use App\Models\DistributionListMember;

class DistributionListController extends Controller
{
    use HandlesApiExceptions;


    public function index(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser->isCorporate()) {
            return response()->json(['status' => 'error', 'message' => 'Only corporates can access distribution lists.'], 403);
        }

        $query = DistributionList::where('corporate_id', $authUser->id);


        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $lists = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Distribution lists fetched successfully',
            'data' => $lists->items(),
            'pagination' => [
                'current_page' => $lists->currentPage(),
                'next_page_url' => $lists->nextPageUrl(),
                'prev_page_url' => $lists->previousPageUrl(),
                'total' => $lists->total(),
                'per_page' => $lists->perPage(),
            ]
        ], 200);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable',
            'type' => 'nullable|string'
        ]);

        $authUser = auth()->user();

        if (!$authUser->isCorporate()) {
            return response()->json(['status' => 'error', 'message' => 'Only corporates can create distribution lists.'], 403);
        }

        try {
            $list = DistributionList::create([
                'corporate_id' => $authUser->id,
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Distribution list created successfully.',
                'data' => $list,
            ], 201);

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to create distribution list');
        }
    }

    public function show($id)
    {
        $authUser = auth()->user();

        try {
            $list = DistributionList::with(['members.member.seller'])->where('corporate_id', $authUser->id)->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Distribution list fetched successfully.',
                'data' => $list,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Distribution List');
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to fetch distribution list');
        }
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|required|string',
            'description' => 'sometimes|string',
            'type' => 'sometimes|required|string'
        ]);

        $authUser = auth()->user();

        try {
            $list = DistributionList::where('corporate_id', $authUser->id)->findOrFail($id);

            $list->update($request->only(['name', 'description', 'type']));

            return response()->json([
                'status' => 'success',
                'message' => 'Distribution list updated successfully.',
                'data' => $list,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Distribution List');
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to update distribution list');
        }
    }


    public function destroy($id)
    {
        $authUser = auth()->user();

        try {
            $list = DistributionList::where('corporate_id', $authUser->id)->findOrFail($id);
            $list->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Distribution list deleted successfully.',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Distribution List');
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to delete distribution list');
        }
    }


   public function addMembers(Request $request, $listId)
{
    $request->validate([
        'member_ids' => 'required|array|min:1',
        'member_ids.*' => 'uuid|distinct'
    ]);

    $authUser = auth()->user();

    if (!$authUser->isCorporate()) {
        return response()->json(['status' => 'error', 'message' => 'Only corporates can add members to distribution lists.'], 403);
    }

    DB::beginTransaction();
    try {
        $list = DistributionList::where('corporate_id', $authUser->id)->findOrFail($listId);


        $validMembershipIds = Membership::where('corporate_id', $authUser->id)
            ->whereIn('id', $request->member_ids)
            ->where('status', 'accepted')
            ->pluck('id')
            ->toArray();

        if (empty($validMembershipIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No valid members found to add.'
            ], 422);
        }


        $existingMembers = DistributionListMember::where('distribution_list_id', $list->id)
            ->whereIn('member_id', $validMembershipIds)
            ->pluck('member_id')
            ->toArray();


        $newMembers = array_diff($validMembershipIds, $existingMembers);

        if (empty($newMembers)) {
            return response()->json([
                'status' => 'error',
                'message' => 'All provided members are already in this list.'
            ], 422);
        }

        foreach ($newMembers as $membershipId) {
            DistributionListMember::create([
                'distribution_list_id' => $list->id,
                'member_id' => $membershipId
            ]);
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Members added successfully.',
        ], 201);

    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return $this->handleNotFound('Distribution List');
    } catch (\Exception $e) {
        DB::rollBack();
        return $this->handleApiException($e, 'Failed to add members to distribution list');
    }
}


public function removeMembers(Request $request, $listId)
{
    $request->validate([
        'member_ids' => 'required|array|min:1',
        'member_ids.*' => 'uuid|distinct'
    ]);

    $authUser = auth()->user();

    if (!$authUser->isCorporate()) {
        return response()->json(['status' => 'error', 'message' => 'Only corporates can remove members from distribution lists.'], 403);
    }

    DB::beginTransaction();
    try {
        $list = DistributionList::where('corporate_id', $authUser->id)->findOrFail($listId);


      $validMembershipIds = Membership::where('corporate_id', $authUser->id)
        ->whereIn('id', $request->member_ids)
        ->where('status', 'accepted')
        ->pluck('id')
        ->toArray();

        if (empty($validMembershipIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No valid members found to remove.'
            ], 422);
        }

        $deletedCount = DistributionListMember::where('distribution_list_id', $list->id)
            ->whereIn('member_id', $validMembershipIds)
            ->delete();

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => "{$deletedCount} members removed successfully."
        ], 200);

    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return $this->handleNotFound('Distribution List');
    } catch (\Exception $e) {
        DB::rollBack();
        return $this->handleApiException($e, 'Failed to remove members from distribution list');
    }
}




}
