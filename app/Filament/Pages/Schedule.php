<?php

namespace App\Filament\Pages;

use App\Models\Schedule as ScheduleModel;
use App\Models\User;
use App\Services\ScheduleService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class Schedule extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Schedule';

    protected static ?string $title = 'Schedule';

    protected static string|\UnitEnum|null $navigationGroup = 'Work';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'schedule';

    protected string $view = 'filament.pages.schedule';

    // Schedule currently open in the edit modal; used by the nested Delete
    // footer action, which does not inherit the parent action's arguments.
    public ?int $activeScheduleId = null;

    public static function getNavigationBadge(): ?string
    {
        $count = Auth::user()?->pendingScheduleInvites()->count() ?? 0;

        return $count > 0 ? (string) $count : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->createAction(),
        ];
    }

    /**
     * Options for the participant picker: everyone except the current user.
     *
     * @return array<int, string>
     */
    protected function participantOptions(): array
    {
        return User::query()
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Shared form used by create + edit.
     *
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    protected function eventFormSchema(): array
    {
        return [
            TextInput::make('title')->required()->maxLength(255),
            Toggle::make('all_day')->label('All day')->live(),
            DateTimePicker::make('start_at')
                ->label('Start')
                ->seconds(false)
                ->required(),
            DateTimePicker::make('end_at')
                ->label('End')
                ->seconds(false)
                ->afterOrEqual('start_at'),
            TextInput::make('location')->maxLength(255),
            Textarea::make('description')->rows(3)->columnSpanFull(),
            ColorPicker::make('color'),
            Select::make('participants')
                ->label('Invite people (shared)')
                ->helperText('Inviting someone higher in the org sends them an approval request; peers and lower are added automatically.')
                ->multiple()
                ->searchable()
                ->preload()
                ->options(fn () => $this->participantOptions())
                ->columnSpanFull(),
        ];
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('New event')
            ->icon('heroicon-o-plus')
            ->modalHeading('New event')
            ->fillForm(fn (array $arguments) => [
                'start_at' => $arguments['start'] ?? now()->format('Y-m-d H:i:s'),
                'end_at' => $arguments['end'] ?? null,
                'all_day' => $arguments['allDay'] ?? false,
            ])
            ->schema($this->eventFormSchema())
            ->action(function (array $data) {
                $service = app(ScheduleService::class);
                $participants = $data['participants'] ?? [];

                $schedule = ScheduleModel::create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'location' => $data['location'] ?? null,
                    'start_at' => $data['start_at'],
                    'end_at' => $data['end_at'] ?? null,
                    'all_day' => $data['all_day'] ?? false,
                    'color' => $data['color'] ?? null,
                    'owner_id' => Auth::id(),
                    'is_shared' => ! empty($participants),
                ]);

                $service->attachOwner($schedule);

                foreach (User::whereIn('id', $participants)->get() as $participant) {
                    $service->attachParticipant($schedule, $participant);
                }

                $this->dispatch('schedule-updated');
            });
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->modalHeading('Edit event')
            ->mountUsing(function (Schema $form, array $arguments) {
                $schedule = ScheduleModel::with('participants')->findOrFail($arguments['schedule']);
                abort_unless($schedule->owner_id === Auth::id(), 403);

                $this->activeScheduleId = $schedule->id;

                $form->fill([
                    'title' => $schedule->title,
                    'description' => $schedule->description,
                    'location' => $schedule->location,
                    'start_at' => $schedule->start_at,
                    'end_at' => $schedule->end_at,
                    'all_day' => $schedule->all_day,
                    'color' => $schedule->color,
                    'participants' => $schedule->participants
                        ->where('pivot.is_organizer', false)
                        ->pluck('id')
                        ->all(),
                ]);
            })
            ->schema($this->eventFormSchema())
            ->extraModalFooterActions([
                Action::make('delete')
                    ->label('Delete')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->cancelParentActions()
                    ->action(function () {
                        $schedule = ScheduleModel::findOrFail($this->activeScheduleId);
                        abort_unless($schedule->owner_id === Auth::id(), 403);
                        $schedule->delete();
                        $this->dispatch('schedule-updated');
                    }),
            ])
            ->action(function (array $data, array $arguments) {
                $schedule = ScheduleModel::findOrFail($arguments['schedule']);
                abort_unless($schedule->owner_id === Auth::id(), 403);

                $participants = $data['participants'] ?? [];

                $schedule->update([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'location' => $data['location'] ?? null,
                    'start_at' => $data['start_at'],
                    'end_at' => $data['end_at'] ?? null,
                    'all_day' => $data['all_day'] ?? false,
                    'color' => $data['color'] ?? null,
                    'is_shared' => ! empty($participants),
                ]);

                // Re-sync participants: keep owner, add new, remove dropped.
                $service = app(ScheduleService::class);
                $keepIds = collect($participants)->push($schedule->owner_id)->all();
                $schedule->participants()
                    ->wherePivot('is_organizer', false)
                    ->whereNotIn('users.id', $keepIds)
                    ->detach();

                foreach (User::whereIn('id', $participants)->get() as $participant) {
                    if (! $schedule->participants()->where('users.id', $participant->id)->exists()) {
                        $service->attachParticipant($schedule, $participant);
                    }
                }

                $this->dispatch('schedule-updated');
            });
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->modalHeading('Event details')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(function (array $arguments) {
                $schedule = ScheduleModel::with(['owner', 'participants'])->findOrFail($arguments['schedule']);

                return new HtmlString(view('filament.pages.schedule-details', [
                    'schedule' => $schedule,
                ])->render());
            });
    }

    public function respondAction(): Action
    {
        return Action::make('respond')
            ->modalHeading('Respond to invitation')
            ->modalSubmitActionLabel('Submit')
            ->mountUsing(function (Schema $form, array $arguments) {
                $form->fill(['decision' => 'accepted']);
            })
            ->schema(function (array $arguments) {
                $schedule = ScheduleModel::with('owner')->findOrFail($arguments['schedule']);

                return [
                    \Filament\Schemas\Components\Text::make(
                        "{$schedule->owner->name} invited you to \"{$schedule->title}\"."
                    ),
                    Radio::make('decision')
                        ->hiddenLabel()
                        ->options([
                            'accepted' => 'Approve — add to my calendar',
                            'declined' => 'Decline',
                        ])
                        ->required(),
                ];
            })
            ->action(function (array $data, array $arguments) {
                $schedule = ScheduleModel::findOrFail($arguments['schedule']);
                $user = Auth::user();

                abort_unless(
                    $schedule->participants()
                        ->where('users.id', $user->id)
                        ->wherePivot('is_organizer', false)
                        ->exists(),
                    403
                );

                app(ScheduleService::class)->respond($schedule, $user, $data['decision']);

                $this->dispatch('schedule-updated');
            });
    }

    /**
     * Invites awaiting the current user's approval, for the sidebar list.
     */
    public function getPendingInvites(): \Illuminate\Support\Collection
    {
        return Auth::user()
            ->pendingScheduleInvites()
            ->with('owner')
            ->get();
    }
}
