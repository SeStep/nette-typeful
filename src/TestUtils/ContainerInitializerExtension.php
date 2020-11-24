<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\TestUtils;

use InvalidArgumentException;
use Nette\DI\Container;
use Nette\InvalidStateException;
use ReflectionMethod;

class ContainerInitializerExtension extends InitializerExtension
{
    /** @var Container */
    private $container;

    /** @var array[] */
    private $initializers;

    /**
     * @param Container|callable $containerInitializer
     * @param array $initializers
     */
    public function __construct($containerInitializer, array $initializers = [])
    {
        $this->container = $containerInitializer;
        $this->initializers = $initializers;
    }

    public function initialize(string $className, ?string $testMethod): void
    {
        foreach ($this->initializers as $initializer) {
            $initializerName = $initializer['name'];

            if ($this->isInitialized("$className-$initializerName")
                || $this->isInitialized("$className::$testMethod-$initializerName")
            ) {
                continue;
            }

            $result = $this->runInitializer($initializer, $className, $testMethod);
            if ($result === false) {
                continue;
            }
            if ($result === null) {
                $result = "$className-$testMethod";
            }

            if (is_string($result)) {
                $this->markInitialized("$result-$initializerName");
            } else {
                trigger_error("Unknown initializer return type");
            }
        }
        $this->markInitialized($className);
    }

    private function runInitializer($initializer, string $className, ?string $testMethod)
    {
        if (isset($initializer['callback'])) {
            $callback = $initializer['callback'];
            if (is_string($callback) && strpos($callback, '::') !== false) {
                $callback = explode('::', $callback);
            }
        } else {
            $callback = [$className, $initializer['name']];
        }
        if (!is_array($callback) || !class_exists($callback[0])) {
            throw new InvalidArgumentException("Invalid initializer[name] given: { $initializer[name] }");
        }

        if (!is_callable($callback)) {
            return false;
        }

        $reflection = new ReflectionMethod(...$callback);
        $args = [];
        foreach ($reflection->getParameters() as $parameter) {
            if ($parameter->name === "className") {
                $args["className"] = $className;
            } elseif ($parameter->name === "testMethod") {
                $args["testMethod"] = $testMethod;
            }
        }

        return $this->getContainer()->callMethod($callback, $args);
    }

    private function getContainer(): Container
    {
        if (!($this->container instanceof Container)) {
            if (!is_callable($this->container)) {
                throw new InvalidStateException("Unknown container initializer given");
            }
            $this->container = call_user_func($this->container);
        }

        return $this->container;
    }
}
