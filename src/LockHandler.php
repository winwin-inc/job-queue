<?php

namespace winwin\jobQueue;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Borrow from Symfony\Component\Filesystem\LockHandler
 */
class LockHandler
{
    private $file;
    private $heartbeatInterval;
    private $handle;

    /**
     * @param string      $name     The lock name
     * @param int $heartbeatInterval
     * @param string|null $lockPath The directory to store the lock. Default values will use temporary directory
     *
     * @throws IOException If the lock directory could not be created or is not writable
     */
    public function __construct($name, $heartbeatInterval, $lockPath = null)
    {
        $this->heartbeatInterval = $heartbeatInterval;
        $lockPath = $lockPath ?: sys_get_temp_dir();

        if (!is_dir($lockPath)) {
            $fs = new Filesystem();
            $fs->mkdir($lockPath);
        }

        if (!is_writable($lockPath)) {
            throw new IOException(sprintf('The directory "%s" is not writable.', $lockPath), 0, null, $lockPath);
        }

        $this->file = sprintf('%s/sf.%s.%s.lock', $lockPath, preg_replace('/[^a-z0-9\._-]+/i', '-', $name), hash('sha256', $name));
    }

    /**
     * Lock the resource.
     *
     * @param bool $blocking wait until the lock is released
     *
     * @return bool Returns true if the lock was acquired, false otherwise
     *
     * @throws IOException If the lock file could not be created or opened
     */
    public function lock($blocking = false)
    {
        if ($this->handle && $this->isAlive()) {
            return true;
        }

        // Silence error reporting
        set_error_handler(function () {
        });

        if (!$this->handle = fopen($this->file, 'r')) {
            if ($this->handle = fopen($this->file, 'x')) {
                chmod($this->file, 0444);
            } elseif (!$this->handle = fopen($this->file, 'r')) {
                usleep(100); // Give some time for chmod() to complete
                $this->handle = fopen($this->file, 'r');
            }
        }
        restore_error_handler();

        if (!$this->handle) {
            $error = error_get_last();
            throw new IOException($error['message'], 0, null, $this->file);
        }

        // On Windows, even if PHP doc says the contrary, LOCK_NB works, see
        // https://bugs.php.net/54129
        if (!flock($this->handle, LOCK_EX | ($blocking ? 0 : LOCK_NB))) {
            fclose($this->handle);
            $this->handle = null;

            return false;
        }
        touch($this->file);

        return true;
    }

    /**
     * Updates file mtime
     */
    public function heartbeat()
    {
        if ($this->handle && $this->isAlive()) {
            // var_export(['heartbeat', date('c')]);
            touch($this->file);
            return true;
        }
        return false;
    }

    /**
     * Checks the lock alive
     *
     * @return bool
     */
    public function isAlive()
    {
        if (file_exists($this->file)) {
            clearstatcache($this->file);
            $mtime = filemtime($this->file);
            // var_export(['compare', date('c', $mtime), date('c')]);
            return time() - $mtime < $this->heartbeatInterval;
        }
        return false;
    }

    /**
     * Release the resource.
     */
    public function release($force = false)
    {
        if ($this->handle) {
            flock($this->handle, LOCK_UN | LOCK_NB);
            fclose($this->handle);
            $this->handle = null;
        } elseif ($force) {
            unlink($this->file);
        }
    }

    public function getHeartbeatInterval()
    {
        return $this->heartbeatInterval;
    }
}
