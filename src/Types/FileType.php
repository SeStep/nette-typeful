<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\Types;

use League\Flysystem\FilesystemInterface;
use Nette\Http\FileUpload;
use Nette\InvalidArgumentException;
use Nette\Localization\Translator;
use Nette\Utils\Html;
use SeStep\Typeful\Types\OptionallyUpdate;
use SeStep\Typeful\Types\PostStoreCommit;
use SeStep\Typeful\Types\PreStoreNormalize;
use SeStep\Typeful\Types\PropertyType;
use SeStep\Typeful\Types\RendersValue;
use SeStep\Typeful\Types\SerializesValue;
use SeStep\Typeful\Validation\ValidationError;

class FileType implements PropertyType, OptionallyUpdate, PreStoreNormalize, PostStoreCommit, SerializesValue, RendersValue
{
    const TYPE_IMAGE = 'image';

    const FILE_NOT_FOUND = 'typeful.error.fileNotFound';
    const FILE_UPLOAD_ERROR = 'typeful.error.fileUploadError';

    /** @var Translator */
    private $translator;

    public function __construct(Translator $translator = null)
    {
        $this->translator = $translator;
    }

    public function renderValue($value, array $options = [])
    {
        $publicPath = rtrim($options['publicPath'] ?? '', '/\\');
        if (!$publicPath) {
            return null;
        }

        $fileType = $options['fileType'] ?? null;
        $deleteElementNamePrefix = $options['deleteNamePrefix'] ?? null;
        $asImage = $fileType === FileType::TYPE_IMAGE;

        $previewsBox = Html::el('div', [
            'class' => 'previews-box',
        ]);

        if (empty($value)) {
            $previewsBox->addHtml(Html::el('span', $this->translate("netteTypeful.fileUpload.no_preview")));
            return $previewsBox;
        }

        foreach ($value as $i => $fileName) {
            if ($filePreviewEl = $this->createFilePreviewEl($fileName, $publicPath, $asImage)) {
                $previewItemWrapper = Html::el('div', ['class' => 'preview-item']);
                if ($deleteElementNamePrefix) {
                    $deleteCheckbox = Html::el('input', [
                        'type' => 'checkbox',
                        'class' => 'delete-toggle',
                        'title' => $this->translate('netteTypeful.fileUpload.delete'),
                        'name' => "$deleteElementNamePrefix-$i-delete",
                    ]);

                    $previewItemWrapper->addHtml($deleteCheckbox);
                }

                $previewItemWrapper->addHtml($filePreviewEl);
                $previewsBox->addHtml($previewItemWrapper);
            }
        }

        return $previewsBox;
    }

    private function createFilePreviewEl(string $filename, string $publicPath, bool $asImage): ?Html
    {
        $class = 'file-preview';
        if ($asImage) {
            $class .= ' file-preview-image';
        }
        if (($extension = pathinfo($filename, PATHINFO_EXTENSION))) {
            $class .= ' file-preview-' . $extension;
        }

        $previewLink = Html::el("a", [
            'href' => "$publicPath/$filename",
            'class' => $class,
        ]);

        if ($asImage) {
            $img = Html::el('img', [
                'src' => "$publicPath/$filename",
            ]);
            $previewLink->addHtml($img);
        } else {
            $previewLink->setText($this->translate("netteTypeful.fileUpload.show_preview"));
        }

        return $previewLink;
    }

    public function validateValue($value, array $options = []): ?ValidationError
    {
        if (!$value) {
            return null;
        }

        if (is_string($value)) {
            /** @var FilesystemInterface $storage */
            $storage = $options['storage'];
            if (!$storage->has($value)) {
                return new ValidationError(self::FILE_NOT_FOUND);
            }

            return null;
        }

        if (!$value instanceof FileUpload) {
            return new ValidationError(ValidationError::INVALID_TYPE);
        }

        if (!$value->hasFile() && $options['nullable'] === true) {
            return null;
        }

        if (!$value->isOk()) {
            return new ValidationError(self::FILE_UPLOAD_ERROR);
        }

        return null;
    }

    public function shouldUpdate($value, $currentValue, array $typeOptions): bool {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->shouldUpdate($item, null, $typeOptions)) {
                    return true;
                }
            }

            return false;
        }

        if ($value instanceof FileDelete) {
            return true;
        }
        if ($value instanceof FileUpload) {
            return $value->hasFile();
        }

        if (!is_string($value) && !is_null($value)) {
            throw new InvalidArgumentException("Invalid type");
        }

        return false;
    }

    public function normalizePreStore($value, array $options, array $entityData = [])
    {
        /** @var FilesystemInterface $storage */
        $storage = $options['storage'];
        $getPreferredName = function() use ($options, $entityData) {
            $preferredName = $options['preferredName'] ?? null;
            if ($preferredName && $preferredName[0] === '!') {
                $field = mb_substr($preferredName, 1);
                $preferredName = $entityData[$field] ?? null;
                if (!$preferredName && $preferredName !== '0') {
                    throw new InvalidArgumentException("Preferred name with referece to !$field is not set");
                }
            }

            return $preferredName;
        };

        $result = [];
        foreach (is_array($value) ? $value : [$value] as $item) {
            if (!($item instanceof FileUpload)) {
                $result[] = $item;
                continue;
            }

            if ($item->hasFile()) {
                $result[] = $this->storeFileUpload($item, $storage, $getPreferredName());
            }
        }
        if ($options['multiple'] ?? false) {
            return $result;
        }
        if (empty($result)) {
            return null;
        }
        return reset($result);
    }

    private function storeFileUpload(FileUpload $file, FilesystemInterface $filesystem, string $preferredName = null)
    {
        $extension = pathinfo($file->getName(), PATHINFO_EXTENSION);
        if (!$preferredName) {
            do {
                $name = md5("".rand());
            } while ($filesystem->has($name));
        } else {

            $fileName = pathinfo($preferredName, PATHINFO_FILENAME);
            $name = "$fileName.$extension";
            $appendNum = 0;
            while ($filesystem->has($name)) {
                $appendNum++;
                $name = "$fileName-$appendNum.$extension";
            }
        }
        $filesystem->write($name, $file->getContents());
        return $name;
    }

    public function commitValue($value, array $typeOptions) {
        /** @var FilesystemInterface $storage */
        $storage = $typeOptions['storage'];
        foreach (is_array($value) ? $value : [$value] as $item) {
            if ($item instanceof FileDelete) {
                $storage->delete($item->getFilename());
            }
        }
    }

    public function serialize($value, array $typeOptions)
    {
        if (is_array($value)) {
            $serialized = [];
            foreach ($value as $key => $item) {
                $itemSerialized = $this->serialize($item, $typeOptions);
                if ($itemSerialized) {
                    $serialized[$key] = $itemSerialized;
                }
            }

            return implode(PATH_SEPARATOR, $serialized);
        }

        if (!$value || ($value instanceof FileDelete)) {
            return null;
        }

        return $value;
    }

    public function deserialize($serialized, array $typeOptions)
    {
        if ($typeOptions['multiple'] ?? false) {
            return explode(PATH_SEPARATOR, $serialized);
        }

        return $serialized;
    }

    private function translate(string $placeholder): string {
        if (!$this->translator) {
            return $placeholder;
        }

        return $this->translator->translate($placeholder);
    }
}
