<?php

namespace App\Filament\Resources\ProjectRequests\Pages;

use App\Filament\Resources\ProjectRequests\ProjectRequestResource;
use App\Models\ProjectRequestAttachment;
use Filament\Resources\Pages\EditRecord;

class EditProjectRequest extends EditRecord
{
    protected static string $resource = ProjectRequestResource::class;

    protected function afterSave(): void
    {
        $files = $this->data['attachment_files'] ?? [];

        foreach ($files as $filePath) {
            $exists = $this->record->attachments()
                ->where('file_path', $filePath)
                ->exists();

            if (! $exists) {
                ProjectRequestAttachment::create([
                    'project_request_id' => $this->record->id,
                    'file_path' => $filePath,
                    'file_name' => basename($filePath),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }
    }
}
