<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use App\Models\Brand;

class BrandController extends Controller
{

    use HandlesApiExceptions;

    public function index(Request $request)
    {

        $query = Brand::with('category','products.category', 'products.subcategory');

        $user = auth()->user();


        if ($user && $user->role === 'corporate' && $user->corporateProfile) {
            $query->where('corporate_profile_id', $user->corporateProfile->id);
        }

        if ($search = $request->query('search')) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        if ($category = $request->query('category')) {
            $query->where('category_id', $category);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $brands = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => "Brands fetched successfully",
            'data' => $brands->items(),
            'pagination' => [
                    'prev_page_url' =>  $brands->previousPageUrl(),
                    'next_page_url' =>  $brands->nextPageUrl(),
                    'current_page' =>   $brands->currentPage(),
                    'total' =>          $brands->total(),
                ]
        ],200);
    }

    public function show($id)
    {

       try {

             $brand = Brand::with('category')->findOrFail($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Brand fetched successfully',
                'data' => $brand
            ], 200);
       } catch (ModelNotFoundException $e) {

            return $this->handleNotFound('Brand');

        }

    }

    public function store(StoreBrandRequest $request)
    {

         $data = $request->validated();

        DB::beginTransaction();

        try {

            $user = auth()->user();


            if ($user->role === 'admin' || $user->role === 'super_admin') {

                $data['created_by_admin'] = true;

                 $data['corporate_profile_id'] = null;

            } elseif ($user->role === 'corporate' && $user->corporateProfile) {

                $data['corporate_profile_id'] = $user->corporateProfile->id;
            }


            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('brand_images', 'public');
            }

            $brand = Brand::create($data);
            DB::commit();
            return response()->json(['status' => 'success','message'=>'Brand created successfully' ,'data' => $brand], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Brand creation failed');
        }
    }


    public function update(UpdateBrandRequest $request, $id)
    {
        $user = auth()->user();

        DB::beginTransaction();

        try {
            $brand = Brand::findOrFail($id);


            if (in_array($user->role, ['admin', 'super_admin'])) {
                if (!$brand->created_by_admin) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Admins can only update brands created by admin.'
                    ], 403);
                }
            }

            elseif ($user->role === 'corporate') {
                if ($brand->corporate_profile_id !== $user->corporateProfile->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only update your own brands.'
                    ], 403);
                }
            }
            else {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
            }

            $data = $request->validated();

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('brand_images', 'public');
            }

            $brand->update($data);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Brand updated successfully.',
                'data' => $brand
            ], 200);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Brand');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Brand update failed');
        }
    }


    public function destroy($id)
    {
        $user = auth()->user();

        DB::beginTransaction();

        try {
            $brand = Brand::findOrFail($id);


            if (in_array($user->role, ['admin', 'super_admin'])) {
                if (!$brand->created_by_admin) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Admins can only delete brands created by admin.'
                    ], 403);
                }
            }

            elseif ($user->role === 'corporate') {
                if ($brand->corporate_profile_id !== $user->corporateProfile->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only delete your own brands.'
                    ], 403);
                }
            }
            else {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
            }

            $brand->delete();
            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Brand deleted successfully'], 200);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Brand');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Brand deletion failed');
        }
    }




}
