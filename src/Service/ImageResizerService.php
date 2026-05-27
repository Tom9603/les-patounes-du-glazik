<?php

namespace App\Service;

class ImageResizerService
{
    /**
     * Crops to a centered square, resizes to $size x $size, saves as JPEG.
     * Supports: JPEG, PNG, WEBP, GIF, BMP.
     */
    public function resizeToSquare(string $sourcePath, string $destPath, int $size = 300, int $quality = 85): bool
    {
        $info = @getimagesize($sourcePath);
        if (!$info) {
            return false;
        }

        $source = match ($info['mime']) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($sourcePath),
            'image/png'               => imagecreatefrompng($sourcePath),
            'image/webp'              => imagecreatefromwebp($sourcePath),
            'image/gif'               => imagecreatefromgif($sourcePath),
            'image/bmp'               => imagecreatefrombmp($sourcePath),
            default                   => null,
        };

        if (!$source) {
            return false;
        }

        [$w, $h] = [(int) $info[0], (int) $info[1]];
        $min = min($w, $h);
        $srcX = (int)(($w - $min) / 2);
        $srcY = (int)(($h - $min) / 2);

        $dest = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($dest, 255, 255, 255);
        imagefill($dest, 0, 0, $white);

        imagecopyresampled($dest, $source, 0, 0, $srcX, $srcY, $size, $size, $min, $min);
        imagejpeg($dest, $destPath, $quality);

        imagedestroy($source);
        imagedestroy($dest);

        return true;
    }
}
