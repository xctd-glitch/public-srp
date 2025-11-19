<?php
declare(strict_types=1);

// Autoloader for multiple namespaces
spl_autoload_register(function (string $class): void {
    $baseDir = __DIR__ . '/';

    // Support both SRP\ and direct namespace (Models\, Controllers\, etc)
    $namespaces = [
        'SRP\\' => $baseDir,
        '' => $baseDir  // Direct namespace (Models\, Controllers\, Config\, etc)
    ];

    foreach ($namespaces as $prefix => $dir) {
        $len = strlen($prefix);

        // Check if class uses this prefix
        if ($prefix === '' || strncmp($prefix, $class, $len) === 0) {
            $relativeClass = $prefix === '' ? $class : substr($class, $len);
            $file = $dir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});

// Load environment variables using correct namespace
SRP\Config\Environment::load();
