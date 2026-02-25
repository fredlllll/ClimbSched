<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function calendar(): View
    {
        return view('pages.calendar');
    }

    public function login(): View
    {
        return view('pages.login');
    }

    public function register(): View
    {
        return view('pages.register');
    }

    public function forgotPassword(): View
    {
        return view('pages.forgot-password');
    }

    public function resetPassword(): View
    {
        return view('pages.reset-password');
    }

    public function verifyEmail(): View
    {
        return view('pages.verify-email');
    }

    public function createEvent(): View
    {
        return view('pages.create-event');
    }


    public function eventDetails(Request $request, int $id): View
    {
        return view('pages.event-details', ['eventId' => $id]);
    }
}


