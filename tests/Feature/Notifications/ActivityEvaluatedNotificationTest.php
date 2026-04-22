<?php

use App\Models\Activity;
use App\Models\User;
use App\Notifications\ActivityEvaluatedNotification;

it('uses the activity extended_evaluation as email content', function () {
    $user = User::factory()->create();
    $activity = Activity::withoutEvents(function () use ($user) {
        return Activity::factory()->create([
            'user_id' => $user->id,
            'short_evaluation' => 'Great run! Keep it up.',
            'extended_evaluation' => "# Performance Analysis\n\nSolid aerobic work today.",
        ]);
    });

    $notification = new ActivityEvaluatedNotification($activity);
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe("Your run is ready for review: {$activity->name}");
    expect($mail->viewData['content'])->toBe("# Performance Analysis\n\nSolid aerobic work today.");
    expect($mail->viewData['name'])->toBe($user->name);
});

it('falls back to short_evaluation when extended_evaluation is empty', function () {
    $user = User::factory()->create();
    $activity = Activity::withoutEvents(function () use ($user) {
        return Activity::factory()->create([
            'user_id' => $user->id,
            'short_evaluation' => 'Solid run.',
            'extended_evaluation' => null,
        ]);
    });

    $notification = new ActivityEvaluatedNotification($activity);
    $mail = $notification->toMail($user);

    expect($mail->viewData['content'])->toBe('Solid run.');
});

it('uses the localized subject for Dutch users', function () {
    $user = User::factory()->create(['locale' => 'nl']);
    $activity = Activity::withoutEvents(function () use ($user) {
        return Activity::factory()->create([
            'user_id' => $user->id,
            'name' => 'Avondloop',
            'short_evaluation' => 'Goede loop!',
            'extended_evaluation' => 'Uitgebreide analyse.',
        ]);
    });

    $notification = new ActivityEvaluatedNotification($activity);
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Je hardloopronde staat klaar voor review: Avondloop');
    expect($mail->viewData['content'])->toBe('Uitgebreide analyse.');
});
