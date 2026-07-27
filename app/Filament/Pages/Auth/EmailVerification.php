<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Notifications\EmailOtpNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt as BasePrompt;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Support\Htmlable;

/**
 * OTP-based replacement for Filament's default "click the link" email
 * verification prompt. The user enters the 6-digit code that was mailed to
 * them on registration; on success their email is marked verified and they
 * gain access to the panel (as a member, still without a division).
 */
class EmailVerification extends BasePrompt
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected string $view = 'filament.auth.email-verification';

    public function mount(): void
    {
        // Base handles: redirect away if not logged in or already verified.
        parent::mount();

        $user = $this->getVerifiable();

        // Make sure a valid code is waiting when the user lands here directly
        // (e.g. after re-login) without a pending OTP.
        if ($user instanceof User && ! $user->hasPendingEmailOtp()) {
            $this->sendEmailVerificationNotification($user);
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('otp')
                    ->label('Kode OTP')
                    ->helperText('Masukkan 6 digit kode yang dikirim ke email Anda.')
                    ->required()
                    ->numeric()
                    ->minLength(6)
                    ->maxLength(6)
                    ->autofocus(),
            ])
            ->statePath('data');
    }

    public function verify(): void
    {
        $data = $this->form->getState();

        $user = $this->getVerifiable();

        if (! $user instanceof User || ! $user->verifyEmailOtp((string) $data['otp'])) {
            Notification::make()
                ->title('Kode OTP salah atau sudah kedaluwarsa')
                ->danger()
                ->send();

            return;
        }

        $user->markEmailAsVerified();
        $user->clearEmailOtp();

        event(new Verified($user));

        Notification::make()
            ->title('Email berhasil diverifikasi')
            ->success()
            ->send();

        $this->redirect(Filament::getUrl());
    }

    public function resendOtp(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $this->sendEmailVerificationNotification($this->getVerifiable());

        Notification::make()
            ->title('Kode OTP baru telah dikirim')
            ->success()
            ->send();
    }

    protected function sendEmailVerificationNotification($user): void
    {
        if (! $user instanceof User || $user->hasVerifiedEmail()) {
            return;
        }

        $otp = $user->generateEmailOtp();

        $user->notify(new EmailOtpNotification($otp));
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Verifikasi Email';
    }
}
