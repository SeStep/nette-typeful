<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\TestUtils;

use PHPUnit\Runner\BeforeTestHook;

abstract class InitializerExtension implements BeforeTestHook
{
    private $initialized = [];

    final public function executeBeforeTest(string $test): void
    {
        $classDelimiter = mb_strpos($test, '::');
        $testClass = mb_substr($test, 0, $classDelimiter);
        $testMethod = mb_substr($testClass, $classDelimiter + 2);

        $this->initialize($testClass, $testMethod);
    }

    protected abstract function initialize(string $className, ?string $testMethod): void;

    protected final function isInitialized(string $subject): bool
    {
        return $this->initialized[$subject] ?? false;
    }

    protected final function markInitialized(string $subject): void
    {
        $this->initialized[$subject] = true;
    }
}
