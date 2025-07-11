<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;

class SubscriptionPlanController extends Controller
{
    use HandlesApiExceptions;

    public function index(Request $request)
    {
        $query = SubscriptionPlan::with('features');


        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }


        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $plans = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription plans fetched successfully',
            'data' => $plans->items(),
            'pagination' => [
                'current_page' => $plans->currentPage(),
                'next_page_url' => $plans->nextPageUrl(),
                'prev_page_url' => $plans->previousPageUrl(),
                'total' => $plans->total(),
                'per_page' => $plans->perPage(),
            ]
        ], 200);
    }

    public function show($id)
    {
        try {
            $plan = SubscriptionPlan::with('features')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Subscription plan fetched successfully',
                'data' => $plan
            ], 200);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Subscription Plan');
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
        ]);

        DB::beginTransaction();

        try {
            $data = $request->only(['name', 'amount']);

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('subscription_plan_images', 'public');
            }

            $plan = SubscriptionPlan::create($data);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Subscription plan created successfully', 'data' => $plan], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Subscription plan creation failed');
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $plan = SubscriptionPlan::findOrFail($id);
            $plan->update($request->only(['name','amount']));

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Subscription plan updated successfully', 'data' => $plan], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Subscription Plan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Subscription plan update failed');
        }
    }

    public function uploadImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png|max:10240',
        ]);

        try {
            $plan = SubscriptionPlan::findOrFail($id);

            if ($plan->image && Storage::disk('public')->exists($plan->getRawOriginal('image'))) {
                Storage::disk('public')->delete($plan->getRawOriginal('image'));
            }

            $plan->image = $request->file('image')->store('subscription_plan_images', 'public');
            $plan->save();

            return response()->json(['status' => 'success', 'message' => 'Image uploaded successfully', 'data' => $plan], 200);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Subscription Plan');
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Image upload failed');
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $plan = SubscriptionPlan::findOrFail($id);
            $plan->delete();

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Subscription plan deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Subscription Plan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Subscription plan deletion failed');
        }
    }

    public function toggleActivation($id)
    {
        DB::beginTransaction();

        try {
            $plan = SubscriptionPlan::findOrFail($id);
            $plan->is_active = !$plan->is_active;
            $plan->save();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => $plan->is_active ? 'Subscription plan activated' : 'Subscription plan deactivated',
                'data' => $plan,
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Subscription Plan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to toggle activation status');
        }
    }

    public function addFeature(Request $request, $planId)
    {
        $request->validate([
            'feature' => 'required|string',
            'value' => 'nullable|string',
        ]);

        try {
            $plan = SubscriptionPlan::findOrFail($planId);

            $feature = $plan->features()->create([
                'feature' => $request->feature,
                'value' => $request->value,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Feature added successfully',
                'data' => $feature
            ], 200);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Subscription Plan');
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to add feature');
        }
    }

    public function removeFeature($planId, $featureId)
    {
        try {
            $plan = SubscriptionPlan::findOrFail($planId);
            $feature = $plan->features()->findOrFail($featureId);

            $feature->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Feature removed successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Feature or Subscription Plan');
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to remove feature');
        }
    }


}
