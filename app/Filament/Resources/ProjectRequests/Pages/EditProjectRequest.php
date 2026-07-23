<?php

namespace App\Filament\Resources\ProjectRequests\Pages;

use App\Filament\Resources\ProjectRequests\ProjectRequestResource;
use App\Models\ProjectRequestAttachment;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditProjectRequest extends EditRecord
{
    protected static string $resource = ProjectRequestResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['attachment_files'] = $this->record->attachments()
            ->pluck('file_path')
            ->all();

        return $data;
    }

    protected function afterSave(): void
    {
        $files = $this->data['attachment_files'] ?? [];

        // Remove attachments the user deleted in the form.
        $toDelete = $this->record->attachments()
            ->whereNotIn('file_path', $files)
            ->get();

        foreach ($toDelete as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
        }

        // Add newly uploaded files.
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
