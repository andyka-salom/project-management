<div class="space-y-3 text-sm">
    <div>
        <span class="font-medium text-gray-950 dark:text-white">When:</span>
        {{ $schedule->start_at?->format('D, d M Y H:i') }}
        @if ($schedule->end_at)
            &ndash; {{ $schedule->end_at->format('D, d M Y H:i') }}
        @endif
        @if ($schedule->all_day)
            <span class="text-gray-500">(all day)</span>
        @endif
    </div>

    <div>
        <span class="font-medium text-gray-950 dark:text-white">Organizer:</span>
        {{ $schedule->owner?->name }}
    </div>

    @if ($schedule->location)
        <div>
            <span class="font-medium text-gray-950 dark:text-white">Location:</span>
            {{ $schedule->location }}
        </div>
    @endif

    @if ($schedule->description)
        <div>
            <span class="font-medium text-gray-950 dark:text-white">Description:</span>
            <p class="whitespace-pre-line text-gray-700 dark:text-gray-300">{{ $schedule->description }}</p>
        </div>
    @endif

    @if ($schedule->is_shared)
        <div>
            <span class="font-medium text-gray-950 dark:text-white">Participants:</span>
            <ul class="mt-1 space-y-1">
                @foreach ($schedule->participants as $participant)
                    @continue($participant->pivot->is_organizer)
                    <li class="flex items-center justify-between">
                        <span>{{ $participant->name }}</span>
                        <x-filament::badge
                            :color="match ($participant->pivot->status) {
                                'accepted' => 'success',
                                'declined' => 'danger',
                                default => 'warning',
                            }"
                        >
                            {{ ucfirst($participant->pivot->status) }}
                        </x-filament::badge>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
