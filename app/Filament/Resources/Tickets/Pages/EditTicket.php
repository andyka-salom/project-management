<?php

namespace App\Filament\Resources\Tickets\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Project;
use App\Models\TicketAttachment;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle assignees validation before saving
        if (!empty($data['assignees']) && !empty($data['project_id'])) {
            $project = Project::find($data['project_id']);
            
            if ($project) {
                $validAssignees = [];
                $invalidAssignees = [];
                
                foreach ($data['assignees'] as $userId) {
                    $isMember = $project->members()->where('users.id', $userId)->exists();
                    
                    if ($isMember) {
                        $validAssignees[] = $userId;
                    } else {
                        $invalidAssignees[] = $userId;
                    }
                }
                
                // Update data with only valid assignees
                $data['assignees'] = $validAssignees;
                
                // Show warning if some users were invalid
                if (!empty($invalidAssignees)) {
                    Notification::make()
                        ->warning()
                        ->title('Some assignees removed')
                        ->body('Some selected users are not members of this project and have been removed from assignees.')
                        ->send();
                }
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (isset($this->data['assignees']) && is_array($this->data['assignees'])) {
            $this->record->assignees()->sync($this->data['assignees']);
        }

        $files = $this->data['attachment_files'] ?? [];
        foreach ($files as $filePath) {
            $exists = $this->record->attachments()->where('file_path', $filePath)->exists();
            if (!$exists) {
                TicketAttachment::create([
                    'ticket_id' => $this->record->id,
                    'file_path' => $filePath,
                    'file_name' => basename($filePath),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Ticket updated')
            ->body('The ticket has been updated successfully.');
    }
}