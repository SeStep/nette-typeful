<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\TestUtils;

use Nette\DI\Container;
use PHPUnit\Framework\TestCase;

class ContainerInitializerExtensionTest extends TestCase
{
    private static $invocations;

    public static function initializeNoArg()
    {
        if (!isset(self::$invocations[__FUNCTION__])) {
            self::$invocations[__FUNCTION__] = [];
        }
        self::$invocations[__FUNCTION__][] = [];
    }

    public static function initializeContainerArg(Container $container)
    {
        if (!isset(self::$invocations[__FUNCTION__])) {
            self::$invocations[__FUNCTION__] = [];
        }
        self::$invocations[__FUNCTION__][] = ['_container' => $container];
    }

    public static function initializeTestName(string $className, string $testMethod)
    {
        if (!isset(self::$invocations[__FUNCTION__])) {
            self::$invocations[__FUNCTION__] = [];
        }
        self::$invocations[__FUNCTION__][] = ['className' => $className, 'testMethod' => $testMethod];
    }

    protected function setUp(): void
    {
        self::$invocations = [];
    }

    /**
     * @param string $testMethod
     * @param bool $useCallback
     *
     * @testWith ["initializeNoArg", false]
     *           ["initializeNoArg", true]
     *           ["initializeContainerArg", false]
     *           ["initializeContainerArg", true]
     *           ["initializeTestName", false]
     *           ["initializeTestName", true]
     */
    public function testCallInitializer(string $testMethod, bool $useCallback)
    {
        if (!empty(self::$invocations)) {
            self::fail("Invocations was not empty");
        }

        $initializer = ['name' => $testMethod];
        if ($useCallback) {
            $initializer['callback'] = self::class . "::$testMethod";
        }
        $initExtension = new ContainerInitializerExtension(new DummyContainer(), [$initializer]);
        $initExtension->initialize(__CLASS__, $testMethod);

        self::assertCount(1, self::$invocations);
        self::assertArrayHasKey($testMethod, self::$invocations);
        self::assertCount(1, self::$invocations[$testMethod]);
    }
}
