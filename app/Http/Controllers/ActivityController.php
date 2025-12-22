<?php

namespace App\Http\Controllers;

use App\Jobs\EnrichActivityWithAiJob;
use App\Models\Activity;
use App\Models\DailyRecommendation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('activities/index', [
            'activities' => $request->user()->activities()->latest('start_date')->paginate(10),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Activity $activity): Response
    {
        if ($activity->user_id !== $request->user()->id) {
            abort(403);
        }

        if (empty($activity->short_evaluation) || empty($activity->extended_evaluation)) {
            EnrichActivityWithAiJob::dispatch($activity);
        }

        $recommendation = DailyRecommendation::where('user_id', $activity->user_id)
            ->whereDate('date', $activity->start_date->toDateString())
            ->first();

        return Inertia::render('activities/show', [
            'activity' => $activity,
            'recommendation' => $recommendation,
        ]);
    }
}
