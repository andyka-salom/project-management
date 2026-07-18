<?php

namespace App\Filament\Resources\ProjectRequests\Pages;

use App\Filament\Resources\ProjectRequests\ProjectRequestResource;
use App\Models\ProjectRequestAttachment;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectRequest extends CreateRecord
{
    protected static string $resource = ProjectRequestResource::class;

    protected function afterCreate(): void
    {
        $files = $this->data['attachment_files'] ?? [];

        foreach ($files as $filePath) {
            ProjectRequestAttachment::create([
                'project_request_id' => $this->record->id,
                'file_path' => $filePath,
                'file_name' => basename($filePath),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }
}
