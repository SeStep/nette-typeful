<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\Forms;

use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\TextArea;
use Nette\Forms\Controls\TextInput;
use Nette\InvalidArgumentException;
use SeStep\NetteTypeful\Controls\NumberInput;
use SeStep\NetteTypeful\Controls\TypefulUploadControl;
use SeStep\NetteTypeful\Types\FileType;
use SeStep\Typeful\Types as TypefulTypes;

class StandardControlsFactory
{
    public static function createText(string $label, TypefulTypes\TextType $type, array $options)
    {
        if (isset($options['richText']) && $options['richText']) {
            $richText = $options['richText'];
            $control = new TextArea($label);
            $control->getControlPrototype()->class[] = $richText === true ? 'richtext' : $richText;
        } else {
            $control = new TextInput($label);
        }

        if (isset($options['maxLength'])) {
            $control->setMaxLength($options['maxLength']);
        }

        return $control;
    }

    public static function createInt(string $label, TypefulTypes\IntType $intType, array $options)
    {
        return self::createNumber($label, $intType, $options);
    }

    public static function createNumber(string $label, TypefulTypes\NumberType $type, array $options)
    {
        $control = new NumberInput($label);
        self::assignAttributes($control, $options, ['min', 'max', 'step']);

        return $control;
    }

    public static function createSelection(string $label, TypefulTypes\SelectionType $type, array $options)
    {
        $multiple = $options['multiple'] ?? false;
        $control = $multiple ? new MultiSelectBox($label) : new SelectBox($label);
        $control->setItems($type->getItems($options));

        return $control;
    }

    public static function createFile(string $label, FileType $fileType, array $options)
    {
        $uploadControl = new TypefulUploadControl($fileType, $options, $label);
        if (isset($options['fileType'])) {
            if ($options['fileType'] === 'image') {
                $uploadControl->addRule(Form::IMAGE);
            } else {
                throw new InvalidArgumentException("fileType option '$options[fileType]' invalid ");
            }
        }

        return $uploadControl;
    }

    private static function assignAttributes(BaseControl $control, array $options, $attributes)
    {
        foreach ($attributes as $target => $attribute) {
            if (is_numeric($target)) {
                $target = $attribute;
            }
            if (!isset($options[$attribute])) {
                continue;
            }

            $control->setHtmlAttribute($target, $options[$attribute]);
        }
    }
}
