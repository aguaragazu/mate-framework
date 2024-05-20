<?php

namespace Mate\File;

class Filesystem
{
    private $basePath;

    public function __construct($basePath = null)
    {
        $this->basePath = $basePath ?: base_path();
    }

    /**
     * Check if a file exists.
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        $fullPath = $this->normalizePath($path);
        return file_exists($fullPath);
    }

    /**
     * Get the contents of a file.
     *
     * @param string $path
     * @return string|false
     */
    public function get(string $path): string|false
    {
        $fullPath = $this->normalizePath($path);
        if (!$this->exists($fullPath)) {
            return false;
        }

        return file_get_contents($fullPath);
    }

    /**
     * Put the contents of a string into a file.
     *
     * @param string $path
     * @param string $contents
     * @param bool $lock = true
     * @return bool
     */
    public function put(string $path, string $contents, bool $lock = true): bool
    {
        $fullPath = $this->normalizePath($path);
        $directory = dirname($fullPath);

        if (!$this->exists($directory)) {
            $this->makeDirectory($directory);
        }

        return file_put_contents($fullPath, $contents, $lock);
    }

    /**
     * Delete a file.
     *
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        $fullPath = $this->normalizePath($path);
        return unlink($fullPath);
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int $mode = 0777
     * @param bool $recursive = false
     * @return bool
     */
    public function makeDirectory(string $path, int $mode = 0777, bool $recursive = false): bool
    {
        $fullPath = $this->normalizePath($path);
        return mkdir($fullPath, $mode, $recursive);
    }

    /**
     * Delete a directory.
     *
     * @param string $directory
     * @param bool $recursive = false
     * @return bool
     */
    public function deleteDirectory(string $directory, bool $recursive = false): bool
    {
        $fullPath = $this->normalizePath($directory);
        return rmdir($fullPath, $recursive);
    }

    /**
     * Scan a directory for files and directories.
     *
     * @param string $directory
     * @param array $options = []
     * @return array
     */
    public function scandir(string $directory, array $options = []): array
    {
        $fullPath = $this->normalizePath($directory);
        $contents = scandir($fullPath, 0, $options);

        // Filter out . and .. directories
        $filteredContents = [];
        foreach ($contents as $file) {
            if ($file !== '.' && $file !== '..') {
                $filteredContents[] = $file;
            }
        }

        return $filteredContents;
    }

    /**
     * Get the contents of a directory as an array of files.
     *
     * @param string $directory
     * @param array $options = []
     * @return array
     */
    public function files(string $directory, array $options = []): array
    {
        $files = [];
        $contents = $this->scandir($directory, $options);

        foreach ($contents as $file) {
            $filePath = $directory . '/' . $file;

            if ($this->isFile($filePath)) {
                $files[] = $filePath;
            }
        }

        return $files;
    }

    /**
     * Get the contents of a directory as an array of directories.
     *
     * @param string $directory
     * @param array $options = []
     * @return array
     */
    public function directories(string $directory, array $options = []): array
    {
        $directories = [];
        $contents = $this->scandir($directory, $options);

        foreach ($contents as $file) {
            $filePath = $directory . '/' . $file;

            if ($this->isDirectory($filePath)) {
                $directories[] = $filePath;
            }
        }

        return $directories;
    }

    /**
     * Check if a path is a file.
     *
     * @param string $path
     * @return bool
     */
    public function isFile(string $path): bool
    {
        $fullPath = $this->normalizePath($path);
        return is_file($fullPath);
    }

    /**
     * Check if a path is a directory.
     *
     * @param string $path
     * @return bool
     */
    public function isDirectory(string $path): bool
    {
        $fullPath = $this->normalizePath($path);
        return is_dir($fullPath);
    }

    /**
     * Get the file size in bytes.
     *
     * @param string $path
     * @return int|false
     */
    public function size(string $path): int|false
    {
        $fullPath = $this->normalizePath($path);
        if (!$this->exists($fullPath)) {
            return false;
        }

        return filesize($fullPath);
    }

    /**
     * Get the last modified time of a file.
     *
     * @param string $path
     * @return int|false
     */
    public function lastModified(string $path): int|false
    {
        $fullPath = $this->normalizePath($path);
        if (!$this->exists($fullPath)) {
            return false;
        }

        return filemtime($fullPath);
    }

    /**
     * Copy a file from one location to another.
     *
     * @param string $source
     * @param string $destination
     * @param bool $overwrite = false
     * @return bool
     */
    public function copy(string $source, string $destination, bool $overwrite = false): bool
    {
        $fullPathSource = $this->normalizePath($source);
        $fullPathDestination = $this->normalizePath($destination);

        if (!$this->exists($fullPathSource)) {
            return false;
        }

        if ($this->exists($fullPathDestination) && !$overwrite) {
            return false;
        }

        return copy($fullPathSource, $fullPathDestination);
    }

    /**
     * Move a file from one location to another.
     *
     * @param string $source
     * @param string $destination
     * @param bool $overwrite = false
     * @return bool
     */
    public function move(string $source, string $destination, bool $overwrite = false): bool
    {
        $fullPathSource = $this->normalizePath($source);
        $fullPathDestination = $this->normalizePath($destination);

        if (!$this->exists($fullPathSource)) {
            return false;
        }

        if ($this->exists($fullPathDestination) && !$overwrite) {
            return false;
        }

        return rename($fullPathSource, $fullPathDestination);
    }

    /**
     * Get the absolute path for a given path.
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        return realpath($this->basePath . '/' . trim($path, '/'));
    }
}