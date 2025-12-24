<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreObjectiveRequest;
use App\Http\Requests\UpdateObjectiveRequest;
use App\Models\Objective;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ObjectiveController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('objectives/index', [
            'objectives' => $request->user()->objectives()->latest()->whereNot('status', 'active')->get(),
            'currentObjective' => $request->user()->currentObjective,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('objectives/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreObjectiveRequest $request): RedirectResponse
    {
        // Enforce ONE active objective
        $request->user()->objectives()->where('status', 'active')->update(['status' => 'abandoned']);

        $request->user()->objectives()->create($request->validated());

        return redirect()->route('objectives.index')
            ->with('success', 'Objective created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Objective $objective): Response
    {
        $this->authorize('view', $objective);

        return Inertia::render('objectives/show', [
            'objective' => $objective->load(['dailyRecommendations' => function ($query) {
                $query->oldest('date')->whereDate('date', '>=', now())->take(7);
            }]),
            'runningStats' => $objective->user->getRunningStats(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Objective $objective): Response
    {
        $this->authorize('update', $objective);

        return Inertia::render('objectives/edit', [
            'objective' => $objective,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateObjectiveRequest $request, Objective $objective): RedirectResponse
    {
        $objective->update($request->validated());

        return redirect()->route('objectives.index')
            ->with('success', 'Objective updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Objective $objective): RedirectResponse
    {
        $this->authorize('delete', $objective);

        $objective->delete();

        return redirect()->route('objectives.index')
            ->with('success', 'Objective deleted successfully.');
    }
}
