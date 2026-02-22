<?php

namespace App\Http\Controllers;

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
}
