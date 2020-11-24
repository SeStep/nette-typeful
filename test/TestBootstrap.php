<?php declare(strict_types=1);

namespace SeStep\NetteTypeful;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use SeStep\NetteTypeful\TestUtils\DummyContainer;

class TestBootstrap
{
    public static function createContainer()
    {
        return new DummyContainer([
            'testingStorage' => new Filesystem(new Local(dirname(__DIR__) . '/test_output')),
        ]);
    }
}
