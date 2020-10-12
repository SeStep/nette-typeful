<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SeStep\Typeful\DI\RegisterTypeful;
use SeStep\Typeful\DI\TypefulExtension;
use SeStep\NetteTypeful\Components;
use SeStep\NetteTypeful\Forms;
use SeStep\NetteTypeful\Latte\PropertyFilter;
use SeStep\NetteTypeful\Service;

class NetteTypefulExtension extends CompilerExtension
{
    use RegisterTypeful;

    public const TAG_TYPE_CONTROL_FACTORY = 'netteTypeful.typeControlFactory';

    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'filters' => Expect::structure([
                'displayEntityProperty' => Expect::string('typefulPropertyValue'),
                'displayPropertyName' => Expect::string('typefulPropertyName'),
            ]),
        ]);
    }

    public function loadConfiguration()
    {
        $this->registerTypefulTypePlugin('netteControlFactory', Expect::mixed(), self::TAG_TYPE_CONTROL_FACTORY);

        $builder = $this->getContainerBuilder();
        $staticConfig = $this->loadFromFile(__DIR__ . '/netteTypefulExtension.neon');
        $config = $this->getConfig();

        $this->registerTypeful($staticConfig['typeful']);
        $this->loadDefinitionsFromConfig([
            'propertyControlFactory' => Forms\PropertyControlFactory::class,
            'formPopulator' => Forms\EntityFormPopulator::class,
            'entityGridFactory' => Components\EntityGridFactory::class,
            'schemaConverter' => Service\SchemaConverter::class,
        ]);

        $builder->addDefinition($this->prefix('propertyFilter'))
            ->setType(PropertyFilter::class);

        if ($builder->hasDefinition('nette.latteFactory')) {
            $this->loadLatteFilters($builder, $config);
        }
    }

    private function loadLatteFilters(ContainerBuilder $builder, $config)
    {
        $filterService = $builder->getDefinition($this->prefix('propertyFilter'));

        $latteFactory = $this->getContainerBuilder()->getDefinition('nette.latteFactory');
        if ($latteFactory instanceof FactoryDefinition) {
            $latteFactory = $latteFactory->getResultDefinition();
        }
        if (!($latteFactory instanceof ServiceDefinition)) {
            throw new InvalidStateException("Could not initialize latte filters on " . get_class($latteFactory));
        }

        foreach ($config->filters as $filterMethod => $registerName) {
            if (!preg_match('/^\w+$/', $registerName)) {
                $paramName = $this->prefix("filters.$filterMethod");
                throw new InvalidArgumentException("Parameter '$paramName' must match `^\w+$` pattern," .
                    " got '$registerName'");
            }

            $latteFactory->addSetup("\$service->addFilter(?, [?, ?])", [
                $registerName,
                $filterService,
                $filterMethod,
            ]);
        }
    }

    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();
        $this->finalizeIntegrationsWithExtensions($builder);

        $controlFactories = [];
        $controlFactoriesDefinitions = $builder->findByTag(self::TAG_TYPE_CONTROL_FACTORY);

        foreach ($controlFactoriesDefinitions as $name => $factory) {
            $definition = $builder->getDefinition($name);
            $typeName = $definition->getTag(TypefulExtension::TAG_TYPE);
            if (!$typeName) {
                throw new InvalidStateException("Service '$name' specifies a control factory but does not"
                    . " look like a type. Please make sure that the tag '" . TypefulExtension::TAG_TYPE . "' exists");
            }
            $controlFactories[$typeName] = $factory;
        }

        $propertyControlFactory = $builder->getDefinition($this->prefix('propertyControlFactory'));
        $propertyControlFactory->setArgument('typeMap', $controlFactories);
    }

    private function finalizeIntegrationsWithExtensions(ContainerBuilder $builder)
    {
        $typefulExtensionArr = $this->compiler->getExtensions(TypefulExtension::class);
        if (!empty($typefulExtensionArr)) {
            $typefulExtensionName = key($typefulExtensionArr);
            $this->initializeFactoriesForBaseTypes($builder, $typefulExtensionName);
        }
    }

    private function initializeFactoriesForBaseTypes(ContainerBuilder $builder, string $typefulExtensionName)
    {
        $builder->getDefinition("$typefulExtensionName.type.$typefulExtensionName.int")
            ->addTag(self::TAG_TYPE_CONTROL_FACTORY, 'SeStep\NetteTypeful\Forms\StandardControlsFactory::createInt');
        $builder->getDefinition("$typefulExtensionName.type.$typefulExtensionName.text")
            ->addTag(self::TAG_TYPE_CONTROL_FACTORY, 'SeStep\NetteTypeful\Forms\StandardControlsFactory::createText');
    }
}
