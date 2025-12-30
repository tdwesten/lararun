<?php

namespace App\Http\Controllers;

use App\Models\DailyRecommendation;
use App\Models\WorkoutFeedback;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WorkoutFeedbackController extends Controller
{
    use AuthorizesRequests;

    /**
     * Store workout feedback.
     */
    public function store(Request $request, DailyRecommendation $recommendation): JsonResponse
    {
        $this->authorize('view', $recommendation);

        $validated = $request->validate([
            'status' => 'required|in:completed,skipped,partially_completed',
            'difficulty_rating' => 'nullable|integer|min:1|max:5',
            'enjoyment_rating' => 'nullable|integer|min:1|max:5',
            'notes' => 'nullable|string|max:1000',
        ]);

        $feedback = WorkoutFeedback::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'daily_recommendation_id' => $recommendation->id,
            ],
            $validated
        );

        return response()->json([
            'message' => 'Feedback saved successfully',
            'feedback' => $feedback,
        ]);
    }
}
