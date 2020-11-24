<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\TestUtils\Traits;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Nette\NotImplementedException;
use Nette\Utils\Strings;

trait TestingStorage
{
    /** @var Filesystem */
    private static $rootTestingStorage;
    private static $storageCache = [];

    public static function initializeTestingStorage(Filesystem $filesystem)
    {
        self::$rootTestingStorage = $filesystem;
    }

    protected function getStorage(string $name = ''): Filesystem
    {
        if (!isset(self::$storageCache[$name])) {
            $filesystem = self::$rootTestingStorage;
            if ($name) {
                $subdirectory = Strings::webalize($name);
                if (!$filesystem->has($subdirectory)) {
                    $filesystem->createDir($subdirectory);
                }
                $adapter = $filesystem->getAdapter();
                if (!$adapter instanceof AbstractAdapter) {
                    throw new NotImplementedException("Subdirectory is not implemented for " . get_class($adapter));
                }
                $newAdapter = clone $adapter;
                $newAdapter->setPathPrefix($adapter->getPathPrefix() . $subdirectory);
                $filesystem = new Filesystem($newAdapter);
            }

            self::$storageCache[$name] = $filesystem;
        }

        return self::$storageCache[$name];
    }

    protected function clearStorage(FilesystemInterface $filesystem)
    {
        foreach ($filesystem->listContents() as $file) {
            $filesystem->delete($file['basename']);
        }
    }
}
