<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\Forms;

use InvalidArgumentException;
use Nette\Forms\Form;
use Nette\Forms\Controls\TextArea;
use Nette\Forms\Controls\TextInput;
use PHPUnit\Framework\TestCase;
use SeStep\NetteTypeful\TestUtils\PropertyFactory;
use SeStep\Typeful\TestDoubles\RegistryFactory;

class EntityFormPopulatorTest extends TestCase
{
    private function createEntityFormPopulator()
    {
        return new EntityFormPopulator(
            RegistryFactory::createEntityRegistry(),
            PropertyFactory::createControlFactory(RegistryFactory::createTypeRegistry()),
        );
    }

    public function testFillForm()
    {
        $entityFormFactory = $this->createEntityFormPopulator();

        $form = new Form();
        $entityFormFactory->fillFromReflection($form, 'furniture');
        
        $controls = iterator_to_array($form->getControls());

        self::assertCount(3, $controls);

        self::assertInstanceOf(TextInput::class, $controls['class']);
        self::assertEquals('text', $controls['class']->control->type );

        self::assertInstanceOf(TextInput::class, $controls['legCount']);
        self::assertEquals('numeric', $controls['legCount']->control->type);

        self::assertInstanceOf(TextArea::class, $controls['description']);
    }

    public function testFillFormMissingProperty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nonExistingProperty');

        $entityFormFactory = $this->createEntityFormPopulator();

        $form = new Form();
        $entityFormFactory->fillFromReflection($form, 'furniture', ['nonExistingProperty']);
    }
}
