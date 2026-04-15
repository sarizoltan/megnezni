<?php
// ── Kép optimalizálás feltöltéskor ──
function optimizeImage(string $source, string $dest, int $maxW = 800, int $maxH = 450): bool {
    [$w, $h, $type] = getimagesize($source);

    // Arány megtartása
    $ratio  = min($maxW / $w, $maxH / $h, 1.0);
    $newW   = (int)($w * $ratio);
    $newH   = (int)($h * $ratio);

    $src = match($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($source),
        IMAGETYPE_PNG  => imagecreatefrompng($source),
        IMAGETYPE_WEBP => imagecreatefromwebp($source),
        default        => false,
    };
    if (!$src) return false;

    $canvas = imagecreatetruecolor($newW, $newH);
    imagecopyresampled($canvas, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

    // WebP mentés
    $result = imagewebp($canvas, $dest, 82);
    imagedestroy($src);
    imagedestroy($canvas);
    return $result;
}

// Feltöltés után:
$webpPath = $uploadDir . pathinfo($filename, PATHINFO_FILENAME) . '.webp';
optimizeImage($tmpPath, $webpPath);