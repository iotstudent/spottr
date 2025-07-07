<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\HandlesApiExceptions;
use App\Http\Requests\StoreProductReqRequest;
use App\Http\Requests\ApproveProductReqRequest;
use App\Http\Requests\RejectProductReqRequest;
use Illuminate\Http\Request;
use App\Models\ProductRequest;
use App\Models\Product;


class ProductRequestController extends Controller
{
    use HandlesApiExceptions;


    public function index(Request $request)
    {
        $query = ProductRequest::query();

        $user = auth()->user();


        if ($user && $user->isIndividual() && $user->individualProfile && $user->individualProfile->type === 'seller') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('sub_category')) {
            $query->where('sub_category_id', $request->sub_category);
        }

        if ($request->has('is_approved')) {
            $query->where('is_approved', filter_var($request->is_approved, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $requests = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Product requests fetched successfully',
            'data' => $requests->items(),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'next_page_url' => $requests->nextPageUrl(),
                'prev_page_url' => $requests->previousPageUrl(),
                'total' => $requests->total(),
                'per_page' => $requests->perPage(),
            ]
        ], 200);
    }


    public function store(StoreProductReqRequest $request)
    {

        $data = $request->validated();

        DB::beginTransaction();

        try {

            $user = auth()->user();

            if ($user->role === 'individual' && $user->individualProfile) {
                $data['user_id'] = $user->id;
            }


            foreach (['product_image_1', 'product_image_2', 'product_image_3', 'product_image_4'] as $field) {
                if ($request->hasFile($field)) {
                    $data[$field] = $request->file($field)->store('product_images', 'public');
                }
            }

            $product = ProductRequest::create($data);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Product request received successfully',
                'data' => $product
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Product request failed');
        }
    }


    public function approve(ApproveProductReqRequest $request, $id)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $productRequest = ProductRequest::findOrFail($id);

            if ($productRequest->is_approved === true) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product request already approved.'
                ], 400);
            }


            $productRequest->update([
                'is_approved' => true,
                'admin_comment' => 'Approved',
            ]);


            $productData = $productRequest->toArray();

            unset($productData['id'], $productData['user_id'], $productData['is_approved'], $productData['admin_comment'], $productData['created_at'], $productData['updated_at']);

            $productData['brand_id'] = $data['brand_id'] ?? null;
            $productData['corporate_profile_id'] = $data['corporate_profile_id'];
            $productData['created_by_admin'] = true;

            $product = Product::create($productData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Product request approved and product created successfully.',
                'data' => $product
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Product Request');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to approve product request');
        }
    }


    public function reject(RejectProductReqRequest $request, $id)
    {
        $request->validated();

        DB::beginTransaction();

        try {
            $productRequest = ProductRequest::findOrFail($id);

            if ($productRequest->is_approved === false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product request already rejected.'
                ], 400);
            }

            $productRequest->update([
                'is_approved' => false,
                'admin_comment' => $request->comment,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Product request rejected successfully.',
                'data' => $productRequest
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Product Request');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to reject product request');
        }
    }


}
