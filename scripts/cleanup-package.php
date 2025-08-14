<?php
if ($argc < 2) {
    die("Usage: php cleanup-package.php vendor/package-name [Provider\\Class\\Name]\n");
}

$packageName = $argv[1];
$providerClass = $argv[2] ?? null;

$packagesFile = __DIR__ . '/../bootstrap/cache/packages.php';
$servicesFile = __DIR__ . '/../bootstrap/cache/services.php';

// ---- 1. Remove from packages.php ----
if (file_exists($packagesFile)) {
    $packages = require $packagesFile;
    if (isset($packages[$packageName])) {
        unset($packages[$packageName]);
        echo "Removed $packageName from packages.php\n";
    }
    file_put_contents($packagesFile, '<?php return ' . var_export($packages, true) . ';' . PHP_EOL);
}

// ---- 2. Remove from services.php ----
if ($providerClass && file_exists($servicesFile)) {
    $services = require $servicesFile;

    if (isset($services['providers'])) {
        $services['providers'] = array_values(
            array_filter($services['providers'], fn($p) => $p !== $providerClass)
        );
    }
    if (isset($services['eager'])) {
        $services['eager'] = array_values(
            array_filter($services['eager'], fn($p) => $p !== $providerClass)
        );
    }
    if (isset($services['deferred'])) {
        foreach ($services['deferred'] as $key => $val) {
            if ($val === $providerClass) unset($services['deferred'][$key]);
        }
    }
    if (isset($services['when'][$providerClass])) {
        unset($services['when'][$providerClass]);
    }

    file_put_contents($servicesFile, '<?php return ' . var_export($services, true) . ';' . PHP_EOL);
    echo "Removed $providerClass from services.php\n";
}

echo "Cache cleanup done.\n";
