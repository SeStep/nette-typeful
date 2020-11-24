<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\Controls;

use Nette\Forms\Controls\TextInput;

class NumberInput extends TextInput
{
    public function __construct($label = null, ?int $precision = null)
    {
        parent::__construct($label);
        $this->setHtmlType('numeric');
        $this->setOption('type', 'numeric');

        $this->setPrecision($precision);
    }

    public function setPrecision(?int $precision): self
    {
        $pattern = null;
        if (is_int($precision)) {
            if ($precision >= 0) {
                $pattern = "\d*";
                if ($pattern > 0) {
                    $pattern .= "(\.\d+)?";
                }
            } else if ($precision < 0) {
                $zeroes = -$precision;
                $pattern = "(\d+0{$zeroes})?";
            }
        }

        $this->setOption('pattern', $pattern);
        return $this;
    }

    public function getValue()
    {
        $value = parent::getValue();
        return floatval($value);
    }
}
