<?php
// autoload.php

class Autoloader
{
    private array $prefixes = [];

    public function __construct(string $configFile)
    {
        if (!file_exists($configFile)) {
            throw new Exception("Config file not found: $configFile");
        }

        $config = json_decode(file_get_contents($configFile), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in config file: " . json_last_error_msg());
        }

        // Load PSR-4 style autoloading
        if (isset($config['autoload']['psr-4'])) {
            foreach ($config['autoload']['psr-4'] as $namespace => $path) {
                $this->addNamespace($namespace, $path);
            }
        }
    }

    public function addNamespace(string $prefix, string $baseDir): void
    {
        // Normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';
        
        // Normalize base directory
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        
        // Initialize array for this prefix if needed
        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }
        
        // Add base directory for this namespace prefix
        $this->prefixes[$prefix][] = $baseDir;
    }

    public function loadClass(string $class): bool
    {
        // Current namespace prefix
        $prefix = $class;

        // Work backwards through namespace to find mapped file
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);

            // Try to load mapped file for this prefix
            if ($this->loadMappedFile($prefix, $relativeClass)) {
                return true;
            }

            // Remove trailing separator for next iteration
            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }

    private function loadMappedFile(string $prefix, string $relativeClass): bool
    {
        if (!isset($this->prefixes[$prefix])) {
            return false;
        }

        foreach ($this->prefixes[$prefix] as $baseDir) {
            // Replace namespace separators with directory separators
            // and append .php
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (file_exists($file)) {
                require $file;
                return true;
            }
        }

        return false;
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }
}

// Initialize and register autoloader
$autoloader = new Autoloader(__DIR__ . '/autoload.json');
$autoloader->register();