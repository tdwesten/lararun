<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailEntryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailEntryController extends Controller
{
    /**
     * Show the email entry form.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->email) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('auth/email-entry');
    }

    /**
     * Store the user's email and send verification.
     */
    public function store(EmailEntryRequest $request): RedirectResponse
    {
        $request->user()->update([
            'email' => $request->email,
        ]);

        $request->user()->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
