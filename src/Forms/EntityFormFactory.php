<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\Forms;

use Nette\Forms\Form;
use Nette\InvalidArgumentException;

final class EntityFormFactory
{
    /** @var EntityFormPopulator */
    private $populator;

    /** @var string */
    private $defaultFormClass;
    /** @var \Closure */
    private $createCallback;

    public function __construct(EntityFormPopulator $populator, callable $createCallback = null, $defaultFormClass = Form::class)
    {
        $this->populator = $populator;
        $this->defaultFormClass = $defaultFormClass;
        $this->createCallback = $createCallback ?: function($formClass) {
            return new $formClass();
        };
    }

    public function create(string $entityName, $createMode = true, array $properties = [], $formClass = null): Form
    {
        $form = $this->createForm($formClass ?: $this->defaultFormClass);
        $this->populator->fillFromReflection($form, $entityName, $properties);
        if ($createMode) {
            $form->addSubmit('submit', 'messages.create');
        } else {
            $form->addSubmit('submit', 'messages.saveChanges');
        }

        return $form;
    }

    private function createForm(string $formClass): Form
    {
        if (!is_a($formClass, Form::class, true)) {
            throw new InvalidArgumentException("'$formClass' is not an instance of " . Form::class);
        }

        return call_user_func($this->createCallback, $formClass);
    }
}
