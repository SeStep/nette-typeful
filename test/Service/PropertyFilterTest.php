<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\Service;

use PHPUnit\Framework\TestCase;
use SeStep\Typeful\Entity\GenericDescriptor;
use SeStep\Typeful\Entity\Property;
use SeStep\Typeful\Service\EntityDescriptorRegistry;
use SeStep\Typeful\Service\ValueRenderer;
use SeStep\Typeful\TestDoubles\RegistryFactory;
use SeStep\Typeful\Types\NumberType;

class PropertyFilterTest extends TestCase
{
    public function testRenderNumberDefaults()
    {
        $filter = $this->createTestedInstance();

        $radius = $filter->displayEntityProperty(66.6, 'flower', 'blossomRadius');
        self::assertEquals(66.6, $radius);
    }

    public function testRenderNumberWithOptions()
    {
        $radius = $this->createTestedInstance()->displayEntityProperty(66.6, 'flower', 'blossomRadius', [
            'precision' => 0,
        ]);
        self::assertEquals(67, $radius);
    }

    private function createTestedInstance(): PropertyFilter
    {
        $typeRegistry = RegistryFactory::createTypeRegistry([
            'number' => new NumberType(),
        ]);
        $entityRegistry = new EntityDescriptorRegistry([
            'flower' => new GenericDescriptor([
                'petalCount' => new Property('int'),
                'blossomRadius' => new Property('number', ['precision' => 1]),
            ], 'flora')
        ]);
        
        return new PropertyFilter($typeRegistry, $entityRegistry, new ValueRenderer());
    }
}
