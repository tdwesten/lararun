<?php

use App\Models\Activity;
use App\Models\User;
use App\Notifications\ActivityEvaluatedNotification;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

it('generates email content using AI', function () {
    $prismFake = Prism::fake([
        TextResponseFake::make()->withText('This is the AI generated email content.'),
    ]);

    $user = User::factory()->create();
    $activity = Activity::factory()->create([
        'user_id' => $user->id,
        'short_evaluation' => 'Great run! Keep it up.',
    ]);

    $notification = new ActivityEvaluatedNotification($activity);
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe("Your run is ready for review: {$activity->name}");
    expect($mail->viewData['content'])->toBe('This is the AI generated email content.');
    expect($mail->viewData['name'])->toBe($user->name);

    $prismFake->assertRequest(function ($requests) use ($user) {
        expect($requests)->toHaveCount(1);
        $request = $requests[0];
        $systemPrompts = collect($request->systemPrompts())->map(fn ($p) => $p->content)->implode("\n");
        expect($systemPrompts)->toContain("You are Lararun's expert running coach.");
        expect($systemPrompts)->toContain("Write a brief, personalized email body to the runner {$user->name}");
        expect($systemPrompts)->toContain('Use Markdown for formatting.');
        expect($systemPrompts)->toContain('DO NOT include a \'Subject\' line.');
        expect($request->prompt())->toContain('Activity Summary:');
        expect($request->prompt())->toContain("Coach's Evaluation: Great run! Keep it up.");
    });
});
