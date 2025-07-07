<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\StoreIndustryRequest;
use App\Http\Requests\UpdateIndustryRequest;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use App\Models\Industry;

class IndustryController extends Controller
{
     use HandlesApiExceptions;

    public function index(Request $request)
    {
        $query = Industry::query();

        if ($search = $request->query('search')) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $industries = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => "Industries fetched successfully",
            'data' => $industries->items(),
            'pagination' => [
                    'prev_page_url' =>  $industries->previousPageUrl(),
                    'next_page_url' =>  $industries->nextPageUrl(),
                    'current_page' =>   $industries->currentPage(),
                    'total' =>          $industries->total(),
                ]
        ],200);
    }

    public function show($id)
    {

       try {

            $industry = Industry::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Instrudy fetched successfully',
                'data' => $industry
            ], 200);
       } catch (ModelNotFoundException $e) {

            return $this->handleNotFound('Industry');

        }

    }

    public function store(StoreIndustryRequest $request)
    {

        $data = $request->validated();

        DB::beginTransaction();

        try {

            $industry = Industry::create($data);
            DB::commit();
            return response()->json(['status' => 'success','message'=>'Industry created successfully' ,'data' => $industry], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Industry creation failed');
        }
    }

    public function update(UpdateIndustryRequest $request,$id)
    {

       $data = $request->validated();

        DB::beginTransaction();

        try {

            $industry = Industry::findOrFail($id);
            $industry->update($data);
            DB::commit();
            return response()->json(['status' => 'success','message'=>'Industry updated successfully' ,'data' => $industry], 200);


         } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Industry');

        } catch (\Exception $e) {
            DB::rollBack();
           return $this->handleApiException($e, 'Industry update failed');
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $industry = Industry::findOrFail($id);
            $industry->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Industry deleted successfully'], 200);
       } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->handleNotFound('Industy');

        } catch (\Exception $e) {
            DB::rollBack();
           return $this->handleApiException($e, 'Industry deletion failed');
        }
    }
}
