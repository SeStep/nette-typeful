<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\Components;

use Nette\Localization\ITranslator;
use SeStep\Typeful\Service\EntityDescriptorRegistry;
use Ublaboo\DataGrid\DataGrid;

class EntityGridFactory
{
    /** @var EntityDescriptorRegistry */
    private $entityDescriptorRegistry;

    /** @var ITranslator */
    private $translator;

    public function __construct(EntityDescriptorRegistry $entityDescriptorRegistry)
    {
        $this->entityDescriptorRegistry = $entityDescriptorRegistry;
    }

    public function setTranslator(ITranslator $translator)
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
            $grid->addColumnText($name, $entity->getPropertyFullName($name));
        }

        if ($this->translator) {
            $grid->setTranslator($this->translator);
        }

        return $grid;
    }
}
