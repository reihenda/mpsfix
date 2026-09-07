<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\UploadedFile;

trait CompressesTripPhotos
{
    /**
     * Kompres foto (resize + re-encode JPEG) sebelum disimpan ke storage/app/public.
     * Termasuk koreksi orientasi EXIF supaya foto dari HP tidak terbalik/miring.
     */
    private function compressAndStorePhoto(UploadedFile $file, string $folder): string
    {
        $image = imagecreatefromstring(file_get_contents($file->getRealPath()));

        if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'])) {
            $exif = @exif_read_data($file->getRealPath());
            if ($exif && isset($exif['Orientation'])) {
                $image = match ($exif['Orientation']) {
                    3 => imagerotate($image, 180, 0),
                    6 => imagerotate($image, -90, 0),
                    8 => imagerotate($image, 90, 0),
                    default => $image,
                };
            }
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $maxDimension = 1280;

        if ($width > $maxDimension || $height > $maxDimension) {
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $relativePath = $folder . '/' . uniqid('trip_', true) . '.jpg';
        $fullPath = storage_path('app/public/' . $relativePath);

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        imagejpeg($image, $fullPath, 75);
        imagedestroy($image);

        return $relativePath;
    }
}
