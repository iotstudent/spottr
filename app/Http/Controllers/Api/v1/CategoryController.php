<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use App\Models\Category;


class CategoryController extends Controller
{

    use HandlesApiExceptions;

    public function index(Request $request)
    {
        $query = Category::query();

        if ($search = $request->query('search')) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $categories = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => "Categories fetched successfully",
            'data' => $categories->items(),
            'pagination' => [
                    'prev_page_url' =>  $categories->previousPageUrl(),
                    'next_page_url' =>  $categories->nextPageUrl(),
                    'current_page' =>   $categories->currentPage(),
                    'total' =>          $categories->total(),
                ]
        ],200);
    }

    public function show($id)
    {

       try {

            $category = Category::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Category fetched successfully',
                'data' => $category
            ], 200);
       } catch (ModelNotFoundException $e) {

            return $this->handleNotFound('Category');

        }

    }

    public function store(StoreCategoryRequest $request)
    {

        $data = $request->validated();

        DB::beginTransaction();

        try {

            $category= Category::create($data);
            DB::commit();
            return response()->json(['status' => 'success','message'=>'Category created successfully' ,'data' => $category], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Category creation failed');
        }
    }

    public function update(UpdateCategoryRequest $request,$id)
    {

       $data = $request->validated();

        DB::beginTransaction();

        try {

            $category = Category::findOrFail($id);
            $category->update($data);
            DB::commit();
            return response()->json(['status' => 'success','message'=>'Category updated successfully' ,'data' => $category], 200);


         } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Category');

        } catch (\Exception $e) {
            DB::rollBack();
           return $this->handleApiException($e, 'Category update failed');
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $category = Category::findOrFail($id);
            $category->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Category deleted successfully'], 200);
       } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Category');

        } catch (\Exception $e) {
            DB::rollBack();
           return $this->handleApiException($e, 'Category deletion failed');
        }
    }

    
}
