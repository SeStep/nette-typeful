<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\Service;

use League\Flysystem\Filesystem;
use Nette\Http\FileUpload;
use PHPUnit\Framework\TestCase;
use SeStep\NetteTypeful\TestUtils\Traits\FileResourceTest;
use SeStep\NetteTypeful\TestUtils\Traits\TestingStorage;
use SeStep\NetteTypeful\Types\FileDelete;
use SeStep\NetteTypeful\Types\FileType;
use SeStep\Typeful\Entity\GenericDescriptor;
use SeStep\Typeful\Entity\Property;
use SeStep\Typeful\Service\EntityDescriptorRegistry;
use SeStep\Typeful\Service\TypeRegistry;
use SeStep\Typeful\TestDoubles\DummyEntity;
use SeStep\Typeful\TestDoubles\DummyTypefulRepository;
use SeStep\Typeful\TestDoubles\RegistryFactory;

class TypefulRepositoryTest extends TestCase
{
    use TestingStorage;
    use FileResourceTest;

    /** @var Filesystem */
    private $previewImageStorage;
    /** @var EntityDescriptorRegistry */
    private $entityRegistry;
    /** @var TypeRegistry  */
    private $typeRegistry;

    protected function setUp(): void
    {
        if (!isset($this->previewImageStorage)) {
            $this->previewImageStorage = $this->getStorage(self::class);
        }

        $this->clearStorage($this->previewImageStorage);

        if (!isset($this->typeRegistry)) {
            $this->typeRegistry = RegistryFactory::createTypeRegistry([
                'file' => new FileType(),
            ]);
        }
    }


    private function createTestInstance(): DummyTypefulRepository
    {
        $descriptor = new GenericDescriptor([
            'previewImage' => new Property('file', [
                'storage' => $this->previewImageStorage,
                'preferredName' => '!os',
            ]),
        ]);

        return new DummyTypefulRepository($descriptor, $this->typeRegistry);
    }

    public function testCreate()
    {
        $dummy = $this->createTestInstance();
        $dummy->createNewFromTypefulData([
            'os' => 'rasp',
            'previewImage' => $this->createFileUpload('dog_tmp.png', 'dog.png'),
        ]);

        $created = $dummy->getCreated()[0];

        self::assertEquals([
            'previewImage' => 'rasp.png'
        ], $created);

        self::assertTrue($this->previewImageStorage->has('rasp.png'), "File 'rasp.png' should exist");
        self::assertCount(1, $this->previewImageStorage->listContents());
    }

    public function testUpdateNoChanges()
    {
        $dummy = $this->createTestInstance();

        $entity = new DummyEntity();
        $entity->previewImage = 'asd';
        $entity->setAssignGroup('update');

        $dummy->updateWithTypefulData($entity, [
            'fileUpload' => new FileUpload([]),
        ]);

        self::assertCount(0, $dummy->getUpdated());

        self::assertEquals([], $entity->getAssigns());
    }

    public function testUpdateDeleteFile()
    {
        $file = $this->createFileUpload('dog_tmp.png');
        $this->previewImageStorage->write('ubuntu.png', $file->getContents());

        if (count($this->previewImageStorage->listContents()) !== 1 || !$this->previewImageStorage->has('ubuntu.png')) {
            $this->fail("Could not initialize context");
        }
        $entity = new DummyEntity();
        $entity->previewImage = 'ubuntu.png';
        $entity->os = 'ubuntu';
        $entity->version = 20;

        $dummy = $this->createTestInstance();
        $dummy->updateWithTypefulData($entity, [
            'previewImage' => new FileDelete('ubuntu.png'),
        ]);

        $updated = $dummy->getUpdated();

        self::assertCount(1, $updated);
        self::assertEquals([
            'previewImage' => null,
        ], $updated[0]);
        self::assertFalse($this->previewImageStorage->has('ubuntu.png'), "Storage should not contain deleted file");
    }
}
