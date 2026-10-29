<?php

/**
 * Builds a buyer-ready DESTRAWEB ZIP.
 *
 * Usage: php scripts/package.php [output-directory]
 *
 * The archive excludes development dependencies (.env, node_modules, demo
 * screenshots, runtime caches) and keeps the writable/ skeleton the installer
 * expects. Vendor libraries are bundled so the buyer does not need Composer.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$version = '1.0.0';
$config = @file_get_contents($root . '/app/Config/Destra.php') ?: '';
if (preg_match('/public string \$version\s*=\s*\'([^\']+)\'/', $config, $m)) {
    $version = $m[1];
}

$outDir = rtrim($argv[1] ?? ($root . '/dist'), '/\\');
if (! is_dir($outDir) && ! mkdir($outDir, 0755, true)) {
    fwrite(STDERR, "Cannot create output directory: {$outDir}\n");
    exit(1);
}

$name    = 'destra-cms-' . $version;
$zipPath = $outDir . '/' . $name . '.zip';

if (is_file($zipPath)) {
    unlink($zipPath);
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Cannot create {$zipPath}\n");
    exit(1);
}

/**
 * Paths relative to the project root that are never shipped, or that are
 * shipped in a reduced form.
 */
$skipExact = [
    '.env', '.env.example.local', 'writable/installed.lock', 'builds',
    'vibe_images', 'node_modules', 'dist',
];
$skipPrefixes = [
    '.env.', '.git/', '.idea/', '.vscode/', 'vibe_images/', 'dist/',
    'writable/shots/', 'writable/cache/', 'writable/logs/',
    'writable/session/', 'writable/debugbar/', 'writable/uploads/',
    'resources/package.json', 'resources/package-lock.json',
];
$keepInSkipped = [
    'writable/cache/index.html',
    'writable/logs/index.html',
    'writable/session/index.html',
    'writable/debugbar/index.html',
    'writable/uploads/index.html',
    'writable/uploads/.htaccess',
];

$skipExtensions = ['log', 'tmp', 'zip~'];
$skipNames      = ['.DS_Store', 'Thumbs.db', 'desktop.ini'];

$add = static function (string $relative) use ($zip, $root, $name): void {
    $source = $root . '/' . $relative;
    if (is_dir($source)) {
        $zip->addEmptyDir($name . '/' . $relative);

        return;
    }
    $zip->addFile($source, $name . '/' . $relative);
};

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$files = 0;
foreach ($iterator as $item) {
    /** @var SplFileInfo $item */
    $abs = str_replace('\\', '/', $item->getPathname());
    $rel = ltrim(substr($abs, strlen(str_replace('\\', '/', $root))), '/');

    if ($rel === '' || in_array($rel, $skipNames, true)) {
        continue;
    }

    $skip = in_array($rel, $skipExact, true);
    if (! $skip && preg_match('#(^|/)node_modules(/|$)#', $rel)) {
        $skip = true;
    }
    if (! $skip) {
        foreach ($skipPrefixes as $prefix) {
            if (str_starts_with($rel, $prefix)) {
                $skip = true;
                break;
            }
        }
    }

    if ($skip && ! in_array($rel, $keepInSkipped, true)) {
        continue;
    }

    if ($item->isDir()) {
        $zip->addEmptyDir($name . '/' . $rel);

        continue;
    }

    $extension = strtolower($item->getExtension());
    if (in_array($extension, $skipExtensions, true)) {
        continue;
    }

    $zip->addFile($abs, $name . '/' . $rel);
    $files++;
}

// Top-level folder so the archive extracts cleanly on any OS.
$zip->addEmptyDir($name);

$zip->close();

$size = filesize($zipPath);

printf(
    "Built %s\nFiles: %d\nSize: %.1f MB\n",
    $zipPath,
    $files,
    $size / 1048576
);
