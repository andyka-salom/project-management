<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Livewire\ExternalLogin;
use App\Livewire\ExternalDashboard;
use App\Http\Controllers\Auth\GoogleController;

Route::get('/', function () {
    return redirect('/admin/login');
});

// FullCalendar event source for the Schedule page. Returns only the current
// user's own schedules plus schedules they have accepted, within the requested
// date window. Pending invites are returned greyed so they stand out.
Route::get('/admin/schedule/events', function (Request $request) {
    $user = Auth::user();
    $start = $request->query('start');
    $end = $request->query('end');

    $schedules = Schedule::query()
        ->with(['owner', 'participants' => fn ($q) => $q->where('users.id', $user->id)])
        ->where(function ($q) use ($user) {
            $q->where('owner_id', $user->id)
                ->orWhereHas('participants', function ($p) use ($user) {
                    $p->where('users.id', $user->id)
                        ->whereIn('schedule_user.status', ['accepted', 'pending']);
                });
        })
        // Overlap with [start, end): start_at < end AND (end_at ?? start_at) >= start.
        ->when($end, fn ($q) => $q->where('start_at', '<', $end))
        ->when($start, fn ($q) => $q->where(function ($w) use ($start) {
            $w->where('end_at', '>=', $start)
                ->orWhere(fn ($x) => $x->whereNull('end_at')->where('start_at', '>=', $start));
        }))
        ->get();

    $events = $schedules->map(function (Schedule $schedule) use ($user) {
        $mine = $schedule->owner_id === $user->id;
        $pivot = $schedule->participants->firstWhere('id', $user->id)?->pivot;
        $pending = ! $mine && $pivot && $pivot->status === 'pending';

        return [
            'id' => $schedule->id,
            'title' => $schedule->title,
            'start' => optional($schedule->start_at)->toIso8601String(),
            'end' => optional($schedule->end_at)->toIso8601String(),
            'allDay' => (bool) $schedule->all_day,
            'color' => $pending ? '#9ca3af' : ($schedule->color ?: '#2563eb'),
            'extendedProps' => [
                'pending' => $pending,
                'mine' => $mine,
                'owner' => $schedule->owner?->name,
                'shared' => (bool) $schedule->is_shared,
            ],
        ];
    });

    return response()->json($events);
})->middleware(['web', 'auth'])->name('schedule.events');

// Google Authentication Routes
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// External Dashboard Routes
Route::prefix('external')->name('external.')->group(function () {
    Route::get('/{token}', ExternalLogin::class)->name('login');
    Route::get('/{token}/dashboard', ExternalDashboard::class)->name('dashboard');
});
