<x-filament-panels::page.simple>
    <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
        Kami telah mengirim kode verifikasi 6 digit ke
        <span class="font-medium text-gray-950 dark:text-white">
            {{ filament()->auth()->user()?->getEmailForVerification() }}
        </span>.
    </p>

    <form wire:submit.prevent="verify" class="mt-4 space-y-4">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full">
            Verifikasi
        </x-filament::button>
    </form>

    <div class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
        Tidak menerima kode?
        <button type="button" wire:click="resendOtp"
            class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
            Kirim ulang
        </button>
    </div>
</x-filament-panels::page.simple>
