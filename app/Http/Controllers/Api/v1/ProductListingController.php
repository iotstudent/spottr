<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\ProductListing;


class ProductListingController extends Controller
{

    use HandlesApiExceptions;


    public function index(Request $request)
    {
        $user = auth()->user();

        $query = ProductListing::query()->with(['product','product.category','product.subcategory','user']);

        if ($user && $user->isIndividual() && $user->profile->type === 'seller') {
            $query->where('user_id', $user->id);
        }


        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->search . '%');
            });
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $listings = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Product listings fetched successfully',
            'data' => $listings->getCollection(),
            'pagination' => [
                'current_page' => $listings->currentPage(),
                'next_page_url' => $listings->nextPageUrl(),
                'prev_page_url' => $listings->previousPageUrl(),
                'total' => $listings->total(),
                'per_page' => $listings->perPage(),
            ]
        ], 200);
    }

    public function show($productLisitingId)
    {
        try{
            $listing = ProductListing::with(['product','product.category','product.subcategory','user'])->findOrFail($productLisitingId);

            return response()->json([
                'status' => 'success',
                'message' => 'Product listings fetched successfully',
                'data' => $listing,

            ], 200);

        }catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Product Listing');
        }
    }



    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user->isIndividual() || $user->profile->type !== 'seller') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only sellers can list products.'
            ], 403);
        }

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'description' => 'nullable|string',
            'seller_unit_price' => 'required|numeric',
            'location' => 'nullable|string',
            'image_one' => 'sometimes|nullable|image|mimes:jpeg,jpg,png|max:10240',
            'image_two' => 'sometimes|nullable|image|mimes:jpeg,jpg,png|max:10240',
            'image_three' => 'sometimes|nullable|image|mimes:jpeg,jpg,png|max:10240',
        ]);

        DB::beginTransaction();

        try {
            $exists = $user->productListings()->where('product_id', $data['product_id'])->exists();

            if ($exists) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have already listed this product.'
                ], 400);
            }

            foreach (['image_one', 'image_two', 'image_three'] as $imageField) {
                if ($request->hasFile($imageField)) {
                    $data[$imageField] = $request->file($imageField)->store('listing_images', 'public');
                }
            }

            $listing = $user->productListings()->create($data);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Product listed successfully.',
                'data' => $listing
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to create product listing.');
        }
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();

        if (!$user->isIndividual() || $user->profile->type !== 'seller') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only sellers can update product listings.'
            ], 403);
        }

        $data = $request->validate([
            'description' => 'nullable|string',
            'seller_unit_price' => 'required|numeric',
        ]);

        DB::beginTransaction();

        try {

            $listing = $user->productListings()->findOrFail($id);
            $listing->update($data);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Product listing updated successfully.',
                'data' => $listing
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Listing not found or unauthorized access.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to update product listing.');
        }
    }

    public function toggleStatus($id)
    {
        $user = auth()->user();

        if (!$user->isIndividual() || $user->profile->type !== 'seller') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only sellers can update product listing status.'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $listing = $user->productListings()->findOrFail($id);

            $listing->is_active = !$listing->is_active;
            $listing->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Product listing status updated successfully.',
                'data' => $listing
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Listing not found or unauthorized access.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to update product listing status.');
        }
    }

    public function updateImage(Request $request, $id)
    {
        $user = auth()->user();

        if (!$user->isIndividual() || $user->profile->type !== 'seller') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only sellers can update listing images.'
            ], 403);
        }

        $data = $request->validate([
            'image_field' => 'required|in:image_one,image_two,image_three',
            'image' => 'required|image|mimes:jpeg,jpg,png|max:10240',
        ]);

        DB::beginTransaction();

        try {
            $listing = $user->productListings()->findOrFail($id);


            $oldImage = $listing->{$data['image_field']};
            if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                Storage::disk('public')->delete($oldImage);
            }


            $newImagePath = $request->file('image')->store('listing_images', 'public');
            $listing->{$data['image_field']} = $newImagePath;
            $listing->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Listing image updated successfully.',
                'data' =>  $listing
            ],200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Listing not found or unauthorized access.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to update listing image.');
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            $listing = $user->productListings()->findOrFail($id);
            $listing->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Product listing deleted successfully.'
            ], 200);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Product listing not found.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Failed to delete product listing.');
        }
    }



}
