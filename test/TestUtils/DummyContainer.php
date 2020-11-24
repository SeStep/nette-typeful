<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\TestUtils;

use Nette\DI\Container;

class DummyContainer extends Container
{
    private $instances;

    public function __construct(array $instances = [])
    {
        parent::__construct();
        $instances['testContainer'] = $this;
        $this->instances = $instances;
        foreach ($instances as $name => $instance) {
            $types = array_merge(
                [get_class($instance)],
                class_parents($instance),
                class_implements($instance),
            );
            foreach ($types as $type) {
                if (!isset($this->wiring[$type])) {
                    $this->wiring[$type] = [
                        0 => [],
                    ];
                }
                $this->wiring[$type][0][] = $name;
            }
        }
    }

    public function getService(string $name)
    {
        if (!isset($this->instances[$name])) {
            throw new \InvalidArgumentException("Service of name '$name' not registered");
        }

        return $this->instances[$name];
    }
}
