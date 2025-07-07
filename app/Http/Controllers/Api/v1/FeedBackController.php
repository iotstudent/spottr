<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\StoreFeedbackRequest;
use Illuminate\Support\Facades\Storage;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use App\Models\Feedback;


class FeedBackController extends Controller
{

    use HandlesApiExceptions;

    public function index(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $query = Feedback::query()->with('user');


        if ($search = $request->query('search')) {
            $query->where('description', 'LIKE', '%' . $search . '%');
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->query('category'));
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $feedbacks = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => "Feedback fetched successfully",
            'data' => $feedbacks->items(),
            'pagination' => [
                'prev_page_url' => $feedbacks->previousPageUrl(),
                'next_page_url' => $feedbacks->nextPageUrl(),
                'current_page' => $feedbacks->currentPage(),
                'total' => $feedbacks->total(),
            ]
        ], 200);
    }

    public function store(StoreFeedbackRequest $request)
    {
        try {
            $user = auth()->user();

            $data = $request->validated();
            $data['user_id'] = $user->id;

            if ($request->hasFile('screen_shot')) {
                $data['screen_shot'] = $request->file('screen_shot')->store('feedback_screenshots', 'public');
            }

            $feedback = Feedback::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback submitted successfully',
                'data' => $feedback
            ], 200);

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to submit feedback');
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        try {
            $feedback = Feedback::findOrFail($id);

            if ($feedback->screen_shot) {
                Storage::disk('public')->delete(str_replace(url('storage') . '/', '', $feedback->screen_shot));
            }

            $feedback->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback deleted successfully'
            ],200);

        } catch (ModelNotFoundException $e) {
            return $this->handleNotFound('Feedback');
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Failed to delete feedback');
        }
    }

}
