<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\TestDoubles;

use SeStep\NetteTypeful\Forms\PropertyControlFactory;
use SeStep\NetteTypeful\Forms\StandardControlsFactory;
use SeStep\Typeful\Service\TypeRegistry;

abstract class PropertyFactory
{
    public static function createControlFactory(TypeRegistry $typeRegistry): PropertyControlFactory
    {
        return new PropertyControlFactory($typeRegistry, [
            'text' => [StandardControlsFactory::class, 'createText'],
            'int' => [StandardControlsFactory::class, 'createInt'],
        ]);
    }
}
