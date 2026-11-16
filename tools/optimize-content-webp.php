<?php

/**
 * Optimize deployable content rasters to WebP under 100 KB.
 * Run from repo root: php tools/optimize-content-webp.php
 *
 * Keeps icon PNGs/ICO and tiny Rhye UI chrome PNGs untouched.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

if (! function_exists('imagewebp')) {
    fwrite(STDERR, "PHP GD with WebP support is required.\n");
    exit(1);
}

$root = dirname(__DIR__);
$maxBytes = 100_000;
$report = [
    'converted' => [],
    'optimized' => [],
    'skipped' => [],
    'failed' => [],
    'before_bytes' => 0,
    'after_bytes' => 0,
];

$skipNamePatterns = [
    // App / PWA / browser icon integration (must stay PNG/ICO)
    '#(^|/)(android-icon-|apple-icon-|favicon-|ms-icon-|favicon\.png$)#i',
    // Tiny Rhye/PhotoSwipe UI chrome referenced by vendor CSS
    '#(^|/)(default-skin\.png|select_black\.png)$#i',
];

$roots = [
    $root.DIRECTORY_SEPARATOR.'assets',
    $root.DIRECTORY_SEPARATOR.'cms'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'assets',
    $root.DIRECTORY_SEPARATOR.'cms'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'media',
];

$files = [];
foreach ($roots as $dir) {
    if (! is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if (! $file->isFile()) {
            continue;
        }
        $ext = strtolower($file->getExtension());
        if (! in_array($ext, ['webp', 'jpg', 'jpeg', 'png', 'gif'], true)) {
            continue;
        }
        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        if (str_contains($rel, '/fonts/')) {
            continue;
        }
        $skip = false;
        foreach ($skipNamePatterns as $pattern) {
            if (preg_match($pattern, $rel)) {
                $skip = true;
                break;
            }
        }
        if ($skip) {
            $report['skipped'][] = $rel.' (icon/ui exception)';
            continue;
        }
        $files[] = $file->getPathname();
    }
}

sort($files);

foreach ($files as $path) {
    $before = filesize($path) ?: 0;
    $report['before_bytes'] += $before;
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    try {
        $result = optimizeFile($path, $maxBytes);
        $afterPath = $result['path'];
        $after = filesize($afterPath) ?: 0;
        $report['after_bytes'] += $after;
        $entry = sprintf(
            '%s -> %s (%s → %s bytes, q=%s, scale=%s)',
            $rel,
            str_replace('\\', '/', substr($afterPath, strlen($root) + 1)),
            $before,
            $after,
            $result['quality'],
            $result['scale']
        );
        if ($result['converted']) {
            $report['converted'][] = $entry;
            if ($afterPath !== $path && is_file($path)) {
                unlink($path);
            }
        } else {
            $report['optimized'][] = $entry;
        }
    } catch (Throwable $e) {
        $report['failed'][] = $rel.': '.$e->getMessage();
        $report['after_bytes'] += $before;
    }
}

// Sync root assets <-> cms/public/assets for mirrored trees after optimization.
syncMirror(
    $root.DIRECTORY_SEPARATOR.'assets',
    $root.DIRECTORY_SEPARATOR.'cms'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'assets'
);

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

function optimizeFile(string $path, int $maxBytes): array
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $img = loadImage($path, $ext);
    if ($img === false) {
        throw new RuntimeException('unable to decode');
    }

    if (function_exists('imagepalettetotruecolor')) {
        @imagepalettetotruecolor($img);
    }
    if (function_exists('imagealphablending')) {
        imagealphablending($img, true);
    }
    if (function_exists('imagesavealpha')) {
        imagesavealpha($img, true);
    }

    $width = imagesx($img);
    $height = imagesy($img);
    $targetPath = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $path) ?: ($path.'.webp');
    $converted = ! str_ends_with(strtolower($path), '.webp');

    $quality = 82;
    $scale = 1.0;
    $best = null;

    for ($attempt = 0; $attempt < 24; $attempt++) {
        $work = $img;
        $w = $width;
        $h = $height;
        if ($scale < 0.999) {
            $w = max(1, (int) round($width * $scale));
            $h = max(1, (int) round($height * $scale));
            $work = imagecreatetruecolor($w, $h);
            imagealphablending($work, false);
            imagesavealpha($work, true);
            $transparent = imagecolorallocatealpha($work, 0, 0, 0, 127);
            imagefilledrectangle($work, 0, 0, $w, $h, $transparent);
            imagealphablending($work, true);
            imagecopyresampled($work, $img, 0, 0, 0, 0, $w, $h, $width, $height);
            imagesavealpha($work, true);
        }

        ob_start();
        if (! imagewebp($work, null, $quality)) {
            if ($work !== $img) {
                imagedestroy($work);
            }
            ob_end_clean();
            throw new RuntimeException('imagewebp failed');
        }
        $blob = ob_get_clean();
        if ($work !== $img) {
            imagedestroy($work);
        }
        if ($blob === false || $blob === '') {
            throw new RuntimeException('empty webp output');
        }

        $best = $blob;
        if (strlen($blob) < $maxBytes) {
            break;
        }

        if ($quality > 40) {
            $quality -= 6;
        } else {
            $scale *= 0.88;
            $quality = max(35, $quality);
        }
    }

    imagedestroy($img);

    if ($best === null) {
        throw new RuntimeException('no output');
    }

    if (! file_put_contents($targetPath, $best)) {
        throw new RuntimeException('write failed');
    }

    return [
        'path' => $targetPath,
        'converted' => $converted || $targetPath !== $path,
        'quality' => $quality,
        'scale' => round($scale, 3),
    ];
}

function loadImage(string $path, string $ext)
{
    return match ($ext) {
        'webp' => @imagecreatefromwebp($path),
        'jpg', 'jpeg' => @imagecreatefromjpeg($path),
        'png' => @imagecreatefrompng($path),
        'gif' => @imagecreatefromgif($path),
        default => false,
    };
}

function syncMirror(string $srcRoot, string $dstRoot): void
{
    if (! is_dir($srcRoot) || ! is_dir($dstRoot)) {
        return;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $rel = substr($file->getPathname(), strlen($srcRoot) + 1);
        $dst = $dstRoot.DIRECTORY_SEPARATOR.$rel;
        if (! is_dir(dirname($dst))) {
            mkdir(dirname($dst), 0755, true);
        }
        $srcHash = hash_file('sha1', $file->getPathname());
        $dstHash = is_file($dst) ? hash_file('sha1', $dst) : null;
        if ($srcHash !== $dstHash) {
            copy($file->getPathname(), $dst);
        }
    }
}
