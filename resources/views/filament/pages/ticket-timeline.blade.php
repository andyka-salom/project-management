<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Project Selector --}}
        @if(!$selectedProject)
            <div class="mb-6">
                <x-filament::section>
                    <div class="mb-5">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Select Project
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Choose a project to view its timeline
                        </p>
                    </div>

                    {{-- Search Bar --}}
                    <div class="mb-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" wire:model.live.debounce.300ms="searchProject"
                                placeholder="Search projects by name or prefix..."
                                class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent" />
                            @if($searchProject)
                                <button wire:click="$set('searchProject', '')"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    @if($projects->isEmpty())
                        <div class="flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400">
                            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-1">No Projects Available</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">You don't have access to any projects yet.</p>
                        </div>
                    @elseif($this->filteredProjects->isEmpty())
                        <div class="flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mb-3 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-1">No Projects Found</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Try adjusting your search terms</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                            @foreach($this->filteredProjects as $project)
                                <button wire:click="selectProject({{ $project->id }})"
                                    class="relative p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md transition-all text-left overflow-hidden"
                                    style="border-left: 4px solid {{ $project->color ?? '#6B7280' }};">
                                    {{-- Pin Icon Badge --}}
                                    @if($project->is_pinned)
                                        <div class="absolute top-2 right-2">
                                            <div class="flex items-center justify-center w-6 h-6 rounded-full shadow-sm"
                                                style="background-color: {{ $project->color ?? '#6B7280' }};" title="Pinned Project">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-white"
                                                    viewBox="0 0 24 24" fill="currentColor">
                                                    <path
                                                        d="M16 9V4h1c.55 0 1-.45 1-1s-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h1v5c0 1.66-1.34 3-3 3v2h5.97v7l1 1 1-1v-7H19v-2c-1.66 0-3-1.34-3-3z" />
                                                </svg>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Project Prefix Badge --}}
                                    @if($project->ticket_prefix)
                                        @php
                                            $color = $project->color ?? '#6B7280';
                                            $hex = ltrim($color, '#');
                                            $r = hexdec(substr($hex, 0, 2));
                                            $g = hexdec(substr($hex, 2, 2));
                                            $b = hexdec(substr($hex, 4, 2));
                                            $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                            $textColor = $brightness > 155 ? '#1F2937' : '#FFFFFF';
                                        @endphp
                                        <div class="inline-flex px-2.5 py-1 rounded text-xs font-semibold mb-3"
                                            style="background-color: {{ $color }}; color: {{ $textColor }};">
                                            {{ $project->ticket_prefix }}
                                        </div>
                                    @endif

                                    {{-- Project Name --}}
                                    <h3 class="font-semibold text-base text-gray-900 dark:text-white line-clamp-2">
                                        {{ $project->name }}
                                    </h3>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </x-filament::section>
            </div>
        @else
            {{-- Project Switcher --}}
            <div class="mb-4" x-data="{ open: false }">
                <div class="relative">
                    <button @click="open = !open" @click.away="open = false"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-800 border-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        style="border-color: {{ $selectedProject->color ?? '#D1D5DB' }};">
                        @if($selectedProject->ticket_prefix)
                            @php
                                $color = $selectedProject->color ?? '#6B7280';
                                $hex = ltrim($color, '#');
                                $r = hexdec(substr($hex, 0, 2));
                                $g = hexdec(substr($hex, 2, 2));
                                $b = hexdec(substr($hex, 4, 2));
                                $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                $textColor = $brightness > 155 ? '#1F2937' : '#FFFFFF';
                            @endphp
                            <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                style="background-color: {{ $color }}; color: {{ $textColor }};">
                                {{ $selectedProject->ticket_prefix }}
                            </span>
                        @endif
                        <span>{{ $selectedProject->name }}</span>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    {{-- Dropdown Menu --}}
                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute top-full left-0 mt-2 w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50 max-h-96 overflow-y-auto"
                        style="display: none;">
                        <div class="p-2">
                            <div
                                class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Switch Project
                            </div>
                            @foreach($this->filteredProjects as $project)
                                <button wire:click="selectProject({{ $project->id }})" @click="open = false"
                                    class="w-full flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left {{ $project->id === $selectedProject->id ? 'bg-gray-50 dark:bg-gray-700' : '' }}">
                                    @if($project->is_pinned)
                                        <div class="flex items-center justify-center w-5 h-5 rounded-full shrink-0"
                                            style="background-color: {{ $project->color ?? '#6B7280' }};" title="Pinned">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white" viewBox="0 0 24 24"
                                                fill="currentColor">
                                                <path
                                                    d="M16 9V4h1c.55 0 1-.45 1-1s-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h1v5c0 1.66-1.34 3-3 3v2h5.97v7l1 1 1-1v-7H19v-2c-1.66 0-3-1.34-3-3z" />
                                            </svg>
                                        </div>
                                    @endif
                                    @if($project->ticket_prefix)
                                        @php
                                            $color = $project->color ?? '#6B7280';
                                            $hex = ltrim($color, '#');
                                            $r = hexdec(substr($hex, 0, 2));
                                            $g = hexdec(substr($hex, 2, 2));
                                            $b = hexdec(substr($hex, 4, 2));
                                            $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                            $textColor = $brightness > 155 ? '#1F2937' : '#FFFFFF';
                                        @endphp
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                            style="background-color: {{ $color }}; color: {{ $textColor }};">
                                            {{ $project->ticket_prefix }}
                                        </span>
                                    @endif
                                    <div class="flex-1 min-w-0 text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $project->name }}
                                    </div>
                                    @if($project->id === $selectedProject->id)
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: {{ $project->color ?? '#3B82F6' }};"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($selectedProject)
            <!-- dhtmlxGantt Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Ticket Timeline</h2>
                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Read Only View</span>
                        </div>
                    </div>
                </div>

                <!-- dhtmlxGantt Container -->
                <div class="w-full">
                    @if (count($this->ganttData['data']) > 0)
                        <div id="gantt_here" style="width:100%; height:600px;"></div>
                    @else
                        <div class="flex flex-col items-center justify-center h-64 text-gray-500 gap-4">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2z" />
                            </svg>
                            <h3 class="text-lg font-medium">No tickets with due dates</h3>
                            <p class="text-sm">Add due dates to tickets to see the timeline</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css" type="text/css">
        <link rel="stylesheet" href="{{ asset('css/gantt-timeline.css') }}" type="text/css">
    @endpush

    @push('scripts')
        <script src="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js"></script>
        <script>
            (function() {
                var projectId = '{{ $projectId }}';

                function getGanttData() {
                    return @json($this->ganttData ?? ['data' => [], 'links' => []]);
                }

                function resetGantt() {
                    if (typeof gantt === 'undefined') return;
                    try {
                        if (window._ticketGanttReady) {
                            gantt.clearAll();
                        }
                    } catch(e) {}
                    window._ticketGanttReady = false;

                    var container = document.getElementById('gantt_here');
                    if (container) {
                        container.innerHTML = '';
                    }
                }

                function showErrorMessage(message) {
                    var container = document.getElementById('gantt_here');
                    if (container) {
                        container.innerHTML =
                            '<div class="flex flex-col items-center justify-center h-64 text-gray-500 gap-4">' +
                            '<h3 class="text-lg font-medium">' + (message || 'Error loading timeline') + '</h3>' +
                            '<p class="text-sm">Please refresh the page</p>' +
                            '<button onclick="location.reload()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Refresh Page</button>' +
                            '</div>';
                    }
                }

                function initializeGantt() {
                    try {
                        var ganttData = getGanttData();

                        if (!ganttData.data || ganttData.data.length === 0) {
                            return;
                        }

                        var container = document.getElementById('gantt_here');
                        if (!container) {
                            return;
                        }

                        if (typeof gantt === 'undefined' || !gantt.init) {
                            setTimeout(initSafely, 100);
                            return;
                        }

                        gantt.config.date_format = "%Y-%m-%d %H:%i";
                        gantt.config.xml_date = "%Y-%m-%d %H:%i";

                        gantt.config.scales = [
                            { unit: "month", step: 1, format: "%F %Y" },
                            { unit: "day", step: 1, format: "%j" }
                        ];

                        gantt.config.readonly = true;
                        gantt.config.drag_move = false;
                        gantt.config.drag_resize = false;
                        gantt.config.drag_progress = false;
                        gantt.config.drag_links = false;

                        gantt.config.grid_width = 350;
                        gantt.config.row_height = 40;
                        gantt.config.task_height = 32;
                        gantt.config.bar_height = 24;

                        gantt.config.columns = [
                            { name: "text", label: "Task Name", width: 200, tree: true },
                            { name: "status", label: "Status", width: 100, align: "center" },
                            { name: "duration", label: "Duration", width: 50, align: "center" }
                        ];

                        gantt.templates.task_class = function(start, end, task) {
                            return task.is_overdue ? "overdue" : "";
                        };

                        gantt.templates.tooltip_text = function(start, end, task) {
                            return '<b>Task:</b> ' + task.text + '<br/>' +
                                '<b>Status:</b> ' + task.status + '<br/>' +
                                '<b>Duration:</b> ' + task.duration + ' day(s)<br/>' +
                                '<b>Progress:</b> ' + Math.round(task.progress * 100) + '%<br/>' +
                                '<b>Start:</b> ' + gantt.templates.tooltip_date_format(start) + '<br/>' +
                                '<b>End:</b> ' + gantt.templates.tooltip_date_format(end) +
                                (task.is_overdue ? '<br/><b style="color: #ef4444;">OVERDUE</b>' : '');
                        };

                        resetGantt();
                        gantt.init("gantt_here");
                        window._ticketGanttReady = true;

                        var processedData = {
                            data: ganttData.data.map(function(task) {
                                function convertDate(dateStr) {
                                    if (!dateStr) return dateStr;
                                    try {
                                        var parts = dateStr.split(' ');
                                        var datePart = parts[0];
                                        var timePart = parts[1] || '00:00';
                                        var dmy = datePart.split('-');
                                        return dmy[2] + '-' + dmy[1].padStart(2, '0') + '-' + dmy[0].padStart(2, '0') + ' ' + timePart;
                                    } catch(e) {
                                        return dateStr;
                                    }
                                }
                                var copy = {};
                                for (var k in task) { copy[k] = task[k]; }
                                copy.start_date = convertDate(task.start_date);
                                copy.end_date = convertDate(task.end_date);
                                return copy;
                            }),
                            links: ganttData.links || []
                        };

                        gantt.parse(processedData);

                    } catch (error) {
                        console.error('Error initializing dhtmlxGantt:', error);
                        showErrorMessage(error.message);
                    }
                }

                function initSafely() {
                    var container = document.getElementById('gantt_here');
                    if (!container) return;

                    if (typeof gantt !== 'undefined' && gantt.init) {
                        initializeGantt();
                    } else {
                        setTimeout(initSafely, 100);
                    }
                }

                function setupLivewireListeners() {
                    if (typeof Livewire === 'undefined') return;
                    Livewire.on('refreshData', function() {
                        setTimeout(function() { resetGantt(); initSafely(); }, 100);
                    });
                    Livewire.on('refreshGanttChart', function() {
                        setTimeout(function() { resetGantt(); initSafely(); }, 200);
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        initSafely();
                        if (typeof Livewire !== 'undefined') {
                            setupLivewireListeners();
                        } else {
                            document.addEventListener('livewire:init', setupLivewireListeners);
                        }
                    });
                } else {
                    initSafely();
                    if (typeof Livewire !== 'undefined') {
                        setupLivewireListeners();
                    } else {
                        document.addEventListener('livewire:init', setupLivewireListeners);
                    }
                }

                document.addEventListener('livewire:navigated', function() {
                    var container = document.getElementById('gantt_here');
                    if (container) {
                        resetGantt();
                        setTimeout(initSafely, 50);
                    }
                });
            })();
        </script>
    @endpush
</x-filament-panels::page>
