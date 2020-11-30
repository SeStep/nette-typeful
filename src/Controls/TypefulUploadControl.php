<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\Controls;

use League\Flysystem\FilesystemInterface;
use Nette\Forms\Controls\UploadControl;
use Nette\Forms\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Html;
use SeStep\NetteTypeful\Types\FileDelete;
use SeStep\NetteTypeful\Types\FileType;

class TypefulUploadControl extends UploadControl
{
    /** @var FileType */
    private $type;
    /** @var array */
    private $typeOptions;
    /** @var FilesystemInterface */
    private $storage;

    private $preview = [];

    public function __construct(FileType $type, array $options, $label = null)
    {
        parent::__construct($label, $options['multiple'] ?? false);
        $this->type = $type;
        $this->typeOptions = $options;
        $this->storage = $options['storage'];
    }

    public function setValue($value)
    {
        if (!$value) {
            $preview = [];
        } elseif(is_array($value)) {
            $preview = $value;
        } else {
            $preview = [$value];
        }

        $this->preview = $preview;
    }

    public function getValue()
    {
        /** @var FileUpload|FileUpload[] $value */
        $value = parent::getValue();

        $deleteData = [];
        foreach ($this->preview as $i => $fileName) {
            $delete = $this->getHttpData(Form::DATA_TEXT, "-$i-delete") === "on";
            if ($delete) {
                $deleteData[] = new FileDelete($fileName);
            }
        }

        if ($this->typeOptions['multiple'] ?? false) {
            foreach ($deleteData as $item) {
                array_unshift($value, $item);
            }
        } else {
            if (!$value->hasFile() && !empty($deleteData)) {
                $value = reset($deleteData);
            }
        }

        return $value;
    }

    public function getControl()
    {
        $parentControl = parent::getControl();

        $elWrapper = Html::el('div', [
            'class' => 'ss-typeful-upload',
        ]);

        $elWrapper->addHtml($parentControl);

        $options = $this->typeOptions;
        $options['deleteNamePrefix'] = $this->getHtmlName();
        if ($previewElement = $this->type->renderValue($this->preview, $options)) {
            $elWrapper->addHtml($previewElement);
        }

        return $elWrapper;
    }
}
