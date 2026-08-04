<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        {{-- Pending approvals --}}
        @php($pending = $this->getPendingInvites())
        <div class="lg:col-span-1 space-y-4">
            <x-filament::section>
                <x-slot name="heading">Pending approvals</x-slot>
                <x-slot name="description">Invitations that need your approval.</x-slot>

                @forelse ($pending as $invite)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <p class="font-medium text-gray-950 dark:text-white">{{ $invite->title }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            from {{ $invite->owner?->name }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $invite->start_at?->format('D, d M Y H:i') }}
                        </p>
                        <div class="mt-2">
                            <x-filament::button
                                size="xs"
                                wire:click="mountAction('respond', { schedule: {{ $invite->id }} })"
                            >
                                Review
                            </x-filament::button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nothing awaiting your approval.</p>
                @endforelse
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Legend</x-slot>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-3 w-3 rounded-full" style="background:#2563eb"></span>
                        My / accepted events
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-3 w-3 rounded-full" style="background:#9ca3af"></span>
                        Awaiting my approval
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Calendar --}}
        <div class="lg:col-span-3">
            <x-filament::section>
                <div id="schedule-calendar" wire:ignore></div>
            </x-filament::section>
        </div>
    </div>

    @assets
        {{-- FullCalendar v6 global bundle injects its own styles; no separate CSS file. --}}
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    @endassets

    @script
    <script>
        const el = document.getElementById('schedule-calendar');

        if (el && ! el.dataset.calendarInit) {
            el.dataset.calendarInit = '1';

            const calendar = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                height: 'auto',
                nowIndicator: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay',
                },
                events: @js(route('schedule.events')),
                dateClick(info) {
                    $wire.mountAction('create', {
                        start: info.allDay ? info.dateStr + ' 09:00:00' : info.dateStr.replace('T', ' ').substring(0, 19),
                        allDay: info.allDay,
                    });
                },
                eventClick(info) {
                    info.jsEvent.preventDefault();
                    const p = info.event.extendedProps;
                    if (p.mine) {
                        $wire.mountAction('edit', { schedule: info.event.id });
                    } else if (p.pending) {
                        $wire.mountAction('respond', { schedule: info.event.id });
                    } else {
                        $wire.mountAction('view', { schedule: info.event.id });
                    }
                },
            });

            calendar.render();

            $wire.on('schedule-updated', () => calendar.refetchEvents());
        }
    </script>
    @endscript
</x-filament-panels::page>
