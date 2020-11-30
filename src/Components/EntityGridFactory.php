<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\Components;

use Nette\Localization\Translator;
use SeStep\Typeful\Service\EntityDescriptorRegistry;
use SeStep\Typeful\Service\TypeRegistry;
use SeStep\Typeful\Service\ValueRenderer;
use Ublaboo\DataGrid\DataGrid;

class EntityGridFactory
{
    /** @var EntityDescriptorRegistry */
    private $entityDescriptorRegistry;
    /** @var TypeRegistry */
    private $typeRegistry;
    /** @var ValueRenderer */
    private $valueRenderer;

    /** @var Translator */
    private $translator;

    public function __construct(
        EntityDescriptorRegistry $entityDescriptorRegistry,
        TypeRegistry $typeRegistry,
        ValueRenderer $valueRenderer
    ) {
        $this->entityDescriptorRegistry = $entityDescriptorRegistry;
        $this->typeRegistry = $typeRegistry;
        $this->valueRenderer = $valueRenderer;
    }

    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function create(string $entityName, array $properties = []): DataGrid
    {
        $entity = $this->entityDescriptorRegistry->getEntityDescriptor($entityName, true);
        $entityProperties = $entity->getProperties();
        if ($properties) {
            $entityProperties = array_intersect_key($entityProperties, array_flip($properties));
        }

        $grid = new DataGrid();
        foreach ($entityProperties as $name => $property) {
            $column = $grid->addColumnText($name, $entity->getPropertyFullName($name));

            $type = $this->typeRegistry->getType($property->getType());
            $options = $property->getOptions();

            $column->setRenderer(function ($entity) use ($type, $options, $name) {
                return $this->valueRenderer->render($entity->$name, $type, $options);
            });
        }

        if ($this->translator) {
            $grid->setTranslator($this->translator);
        }

        return $grid;
    }
}
