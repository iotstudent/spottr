<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait HandlesApiExceptions
{
    public function handleApiException(\Throwable $e, string $context = 'Error')
    {
        Log::error($context, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => app()->environment('production') ? 'Internal Server Error' : $e->getMessage(),
        ], 500);
    }

    public function handleNotFound(string $resource = 'Resource')
    {
        return response()->json([
            'status' => 'error',
            'message' => "$resource not found"
        ], 404);
    }
}
