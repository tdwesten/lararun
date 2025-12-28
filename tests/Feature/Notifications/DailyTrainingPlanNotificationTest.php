<?php

use App\Models\DailyRecommendation;
use App\Models\User;
use App\Notifications\DailyTrainingPlanNotification;

it('uses the correct locale and translated subject for English users', function () {
    $user = User::factory()->create(['locale' => 'en']);
    $recommendation = DailyRecommendation::factory()->create([
        'user_id' => $user->id,
        'title' => 'Easy Run',
    ]);

    $notification = new DailyTrainingPlanNotification($recommendation);
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Your training plan for today: Easy Run');
});

it('uses the correct locale and translated subject for Dutch users', function () {
    $user = User::factory()->create(['locale' => 'nl']);
    $recommendation = DailyRecommendation::factory()->create([
        'user_id' => $user->id,
        'title' => 'Herstelloop',
    ]);

    $notification = new DailyTrainingPlanNotification($recommendation);
    $mail = $notification->toMail($user);

    // Check translated subject from lang/nl.json: "Your training plan for today: :title" -> "Je trainingsplan voor vandaag: :title"
    expect($mail->subject)->toBe('Je trainingsplan voor vandaag: Herstelloop');
});
