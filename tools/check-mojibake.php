<?php

$root = dirname(__DIR__);

$excludedDirectories = [
    '.git',
    'bootstrap/cache',
    'node_modules',
    'public/css/filament',
    'public/fonts',
    'public/js/filament',
    'storage',
    'vendor',
];

$excludedFiles = [
    'tools/check-mojibake.php',
];

$extensions = [
    'blade.php',
    'css',
    'json',
    'js',
    'md',
    'php',
    'vue',
    'yaml',
    'yml',
];

$patterns = [
    '�',
    '銆',
    '锛',
    '璇',
    '鐨',
    '杈',
    '鍟',
    '濡',
    '閫',
    '瀹',
    '搴',
    '鏃',
    '绛',
    '妯',
    '瑙',
    '鍒',
    '鎶',
    '憡',
    '姝',
    '殑',
    '熀',
    '',
];

$directory = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($directory);
$failed = false;

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }

    $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

    if (in_array($relativePath, $excludedFiles, true) || isExcluded($relativePath, $excludedDirectories) || ! hasAllowedExtension($relativePath, $extensions)) {
        continue;
    }

    $contents = file_get_contents($file->getPathname());

    if ($contents === false) {
        continue;
    }

    if (! mb_check_encoding($contents, 'UTF-8')) {
        echo "Non UTF-8 file: {$relativePath}\n";
        $failed = true;

        continue;
    }

    $lines = preg_split('/\R/u', $contents) ?: [];

    foreach ($lines as $index => $line) {
        foreach ($patterns as $pattern) {
            if (str_contains($line, $pattern)) {
                $lineNumber = $index + 1;
                $snippet = trim(mb_substr($line, 0, 160));

                echo "Possible mojibake: {$relativePath}:{$lineNumber}: {$snippet}\n";
                $failed = true;

                break;
            }
        }
    }
}

if ($failed) {
    exit(1);
}

echo "No mojibake detected.\n";

function isExcluded(string $path, array $excludedDirectories): bool
{
    foreach ($excludedDirectories as $directory) {
        if ($path === $directory || str_starts_with($path, $directory.'/')) {
            return true;
        }
    }

    return false;
}

function hasAllowedExtension(string $path, array $extensions): bool
{
    foreach ($extensions as $extension) {
        if (str_ends_with($path, '.'.$extension)) {
            return true;
        }
    }

    return false;
}
