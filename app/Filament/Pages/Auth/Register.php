<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Filament\Auth\Pages\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    /**
     * New self-registered accounts always start as a plain "member" with no
     * division. An admin later assigns the division and any additional roles.
     */
    protected function handleRegistration(array $data): Model
    {
        /** @var User $user */
        $user = User::create($data);

        $user->assignRole('member');

        return $user;
    }

    /**
     * Replace Filament's default verification LINK with a 6-digit OTP code.
     * Called by the base register() right after the user is created.
     */
    protected function sendEmailVerificationNotification(Model $user): void
    {
        if (! $user instanceof User || $user->hasVerifiedEmail()) {
            return;
        }

        $otp = $user->generateEmailOtp();

        $user->notify(new EmailOtpNotification($otp));
    }
}
