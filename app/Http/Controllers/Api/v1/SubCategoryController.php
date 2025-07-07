<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\StoreSubCategoryRequest;
use App\Http\Requests\UpdateSubCategoryRequest;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use App\Models\SubCategory;


class SubCategoryController extends Controller
{

    use HandlesApiExceptions;

    public function index(Request $request)
    {
        $query = SubCategory::query();

        if ($search = $request->query('search')) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }


        if ($category = $request->query('category')) {
            $query->where('category_id', $category);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $subcategories = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => "Sub Categories fetched successfully",
            'data' => $subcategories->items(),
            'pagination' => [
                    'prev_page_url' =>  $subcategories->previousPageUrl(),
                    'next_page_url' =>  $subcategories->nextPageUrl(),
                    'current_page' =>   $subcategories->currentPage(),
                    'total' =>          $subcategories->total(),
                ]
        ],200);
    }

    public function show($id)
    {

       try {

            $subcategory = SubCategory::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'message' => 'SubCategory fetched successfully',
                'data' => $subcategory
            ], 200);
       } catch (ModelNotFoundException $e) {

            return $this->handleNotFound('SubCategory');

        }

    }

    public function store(StoreSubCategoryRequest $request)
    {

        $data = $request->validated();

        DB::beginTransaction();

        try {

            $subcategory= SubCategory::create($data);
            DB::commit();
            return response()->json(['status' => 'success','message'=>'SubCategory created successfully' ,'data' => $subcategory], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'SubCategory creation failed');
        }
    }

    public function update(UpdateSubCategoryRequest $request,$id)
    {

       $data = $request->validated();

        DB::beginTransaction();

        try {

            $subcategory = SubCategory::findOrFail($id);
            $subcategory->update($data);
            DB::commit();
            return response()->json(['status' => 'success','message'=>'SubCategory updated successfully' ,'data' => $subcategory], 200);


         } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('SubCategory');

        } catch (\Exception $e) {
            DB::rollBack();
           return $this->handleApiException($e, 'SubCategory update failed');
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $subcategory = SubCategory::findOrFail($id);
            $subcategory->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'SubCategory deleted successfully'], 200);
       } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('SubCategory');

        } catch (\Exception $e) {
            DB::rollBack();
           return $this->handleApiException($e, 'SubCategory deletion failed');
        }
    }


}
