<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\Latte;

use SeStep\Typeful\Service\EntityDescriptorRegistry;
use SeStep\Typeful\Service\TypeRegistry;
use SeStep\Typeful\Service\ValueRenderer;

class PropertyFilter
{
    /** @var TypeRegistry */
    private $typeRegistry;
    /** @var EntityDescriptorRegistry */
    private $entityDescriptorRegistry;
    /** @var ValueRenderer */
    private $valueRenderer;

    public function __construct(
        TypeRegistry $typeRegistry,
        EntityDescriptorRegistry $entityDescriptorRegistry,
        ValueRenderer $valueRenderer
    ) {
        $this->typeRegistry = $typeRegistry;
        $this->entityDescriptorRegistry = $entityDescriptorRegistry;
        $this->valueRenderer = $valueRenderer;
    }

    public function displayPropertyName(string $property, string $entityClass = null)
    {
        $descriptor = $this->entityDescriptorRegistry->getEntityDescriptor($entityClass);

        return $descriptor->getPropertyFullName($property);
    }

    public function displayEntityProperty($value, string $entityType, string $propertyName, array $options = [])
    {
        $descriptor = $this->entityDescriptorRegistry->getEntityDescriptor($entityType);
        if (!$descriptor) {
            trigger_error("Entity $entityType not recognized");
            return 'nada';
        }
        $property = $descriptor->getProperty($propertyName);
        $propertyType = $property ? $this->typeRegistry->getType($property->getType()) : null;

        if (!$propertyType) {
            trigger_error("Property '$entityType::$propertyName'' can not be displayed");
            return 'nada';
        }

        $renderOptions = array_merge($property->getOptions(), $options);

        return $this->valueRenderer->render($value, $propertyType, $renderOptions);
    }
}
