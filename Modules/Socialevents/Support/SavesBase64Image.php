<?php

namespace Modules\Socialevents\Support;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait SavesBase64Image
{
    protected function saveBase64Image(string $base64Image, string $destination, string $fileName): ?string
    {
        try {
            if (! Storage::disk('public')->exists($destination)) {
                Storage::disk('public')->makeDirectory($destination, 0755, true);
            }

            $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            if ($fileData === false) {
                return null;
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'img');
            file_put_contents($tempFile, $fileData);
            $mime = mime_content_type($tempFile) ?: 'image/png';
            $extension = str_replace('image/', '', $mime);

            $path = Storage::disk('public')->putFileAs(
                $destination,
                new File($tempFile),
                $fileName.'.'.$extension
            );

            @unlink($tempFile);

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function saveBase64ImageAsUploaded(string $base64Image, string $destination, string $fileName): ?string
    {
        try {
            if (! Storage::exists($destination)) {
                Storage::makeDirectory($destination, 0755, true);
            }

            $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            if ($fileData === false) {
                return null;
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'img');
            file_put_contents($tempFile, $fileData);
            $mime = mime_content_type($tempFile) ?: 'image/png';

            $file = new UploadedFile(realpath($tempFile), uniqid('', true).'.'.str_replace('image/', '', $mime), $mime, null, true);

            return Storage::disk('public')->putFileAs($destination, $file, $fileName);
        } catch (\Throwable) {
            return null;
        }
    }
}
