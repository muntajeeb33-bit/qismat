<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class ProfilePhotoProcessor
{
    public function process(UploadedFile $file): array
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('Image processing is temporarily unavailable.');
        }

        $source = file_get_contents($file->getRealPath());
        $image = $source === false ? false : imagecreatefromstring($source);
        if ($image === false) {
            throw new RuntimeException('The uploaded photo could not be processed.');
        }

        try {
            $image = $this->orientJpeg($image, $file);
            $mimeType = $file->getMimeType();
            $extension = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => throw new RuntimeException('The uploaded photo type is not supported.'),
            };

            ob_start();
            $written = match ($extension) {
                'jpg' => imagejpeg($image, null, 88),
                'png' => imagepng($image, null, 8),
                'webp' => function_exists('imagewebp') && imagewebp($image, null, 88),
            };
            $contents = ob_get_clean();

            if (! $written || ! is_string($contents)) {
                throw new RuntimeException('The uploaded photo could not be encoded.');
            }

            return [
                'contents' => $contents,
                'extension' => $extension,
                'mime_type' => $mimeType,
                'width' => imagesx($image),
                'height' => imagesy($image),
                'size_bytes' => strlen($contents),
            ];
        } finally {
            imagedestroy($image);
        }
    }

    private function orientJpeg(\GdImage $image, UploadedFile $file): \GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
        switch ($orientation) {
            case 2:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $image = $this->rotate($image, 180);
                break;
            case 4:
                imageflip($image, IMG_FLIP_VERTICAL);
                break;
            case 5:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                $image = $this->rotate($image, -90);
                break;
            case 6:
                $image = $this->rotate($image, -90);
                break;
            case 7:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                $image = $this->rotate($image, 90);
                break;
            case 8:
                $image = $this->rotate($image, 90);
                break;
        }

        return $image;
    }

    private function rotate(\GdImage $image, int $angle): \GdImage
    {
        $rotated = imagerotate($image, $angle, 0);
        if ($rotated === false) {
            throw new RuntimeException('The uploaded photo orientation could not be normalized.');
        }
        imagedestroy($image);

        return $rotated;
    }
}
