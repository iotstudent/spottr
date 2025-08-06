<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\HandlesApiExceptions;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductListing;

class ProductController extends Controller
{
    use HandlesApiExceptions;


    public function index(Request $request)
    {
       $query = Product::with('category','subcategory','brand');

        $user = auth()->user();


        if ($user && $user->role === 'corporate' && $user->corporateProfile) {
            $query->where('corporate_profile_id', $user->corporateProfile->id);
        }

        if ($request->has('brand')) {
            $query->where('brand_id', $request->brand);
        }

        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('sub_category')) {
            $query->where('sub_category_id', $request->sub_category);
        }

        if ($request->has('is_available')) {
            $query->where('is_available', filter_var($request->is_available, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $products = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Products fetched successfully',
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'next_page_url' => $products->nextPageUrl(),
                'prev_page_url' => $products->previousPageUrl(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
            ]
        ], 200);
    }

    public function getListingsByProduct($productId)
    {
        try {
            $listings = ProductListing::with('user')->where('product_id', $productId)->get();

            if ($listings->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No listings found for this product.'
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product listings fetched successfully.',
                'data' => $listings
            ], 200);

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'An unexpected error occurred when fetching product listing');
        }

    }

    public function show($id)
    {
        try {
            $product = Product::with('category', 'subcategory')->findOrFail($id);
            $user = auth()->user();

            if ($user && $user->role === 'corporate') {
                if (!$user->corporateProfile || $product->corporate_profile_id !== $user->corporateProfile->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthorized access to product'
                    ], 403);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product fetched successfully',
                'data' => $product
            ], 200);

        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Product');
        }
    }

    public function store(StoreProductRequest $request)
    {

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $user = auth()->user();

             if ($user->role === 'admin' || $user->role === 'super_admin') {

                $data['created_by_admin'] = true;

                $data['corporate_profile_id'] ;

            } elseif ($user->role === 'corporate' && $user->corporateProfile) {

                $data['corporate_profile_id'] = $user->corporateProfile->id;
            }



            foreach (['product_image_one', 'product_image_two', 'product_image_three', 'product_image_four'] as $field) {
                if ($request->hasFile($field)) {
                    $data[$field] = $request->file($field)->store('product_images', 'public');
                }
            }

            $product = Product::create($data);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => $product
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Product creation failed');
        }
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $product = Product::findOrFail($id);

            if (in_array($user->role, ['admin', 'super_admin'])) {
                if (!$product->created_by_admin) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Admins can only update products created by admin.'
                    ], 403);
                }
            } elseif ($user->role === 'corporate') {
                if ($product->corporate_profile_id !== $user->corporateProfile->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only update your own products.'
                    ], 403);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
            }

            foreach (['product_image_one', 'product_image_two', 'product_image_three', 'product_image_four'] as $field) {
                if ($request->hasFile($field)) {
                    if ($product->{$field} && Storage::disk('public')->exists($product->{$field})) {
                        Storage::disk('public')->delete($product->{$field});
                    }
                    $data[$field] = $request->file($field)->store('product_images', 'public');
                }
            }

            $product->update($data);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => $product
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Product');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Product update failed');
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();

        DB::beginTransaction();

        try {
            $product = Product::findOrFail($id);

            if (in_array($user->role, ['admin', 'super_admin'])) {
                if (!$product->created_by_admin) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Admins can only delete products created by admin.'
                    ], 403);
                }
            } elseif ($user->role === 'corporate') {
                if ($product->corporate_profile_id !== $user->corporateProfile->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only delete your own products.'
                    ], 403);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
            }

            $product->delete();
            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Product deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Product');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Product deletion failed');
        }
    }



}
