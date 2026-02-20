<?php

declare(strict_types=1);

final class PageController
{
    public static function calendar(): void
    {
        servePage('calendar.html');
    }

    public static function login(): void
    {
        servePage('login.html');
    }

    public static function register(): void
    {
        servePage('register.html');
    }

    public static function verifyEmail(): void
    {
        servePage('verify-email.html');
    }

    public static function forgotPassword(): void
    {
        servePage('forgot-password.html');
    }

    public static function resetPassword(): void
    {
        servePage('reset-password.html');
    }
}
