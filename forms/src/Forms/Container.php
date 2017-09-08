<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Forms;

use Nette;


/**
 * Container for form controls.
 *
 * @property   Nette\Utils\ArrayHash $values
 * @property-read \Iterator $controls
 * @property-read Form|null $form
 */
class Container extends Nette\ComponentModel\Container implements \ArrayAccess
{
	/** @var callable[]  function (Container $sender); Occurs when the form is validated */
	public $onValidate;

	/** @var ControlGroup|null */
	protected $currentGroup;

	/** @var callable[]  extension methods */
	private static $extMethods = [];

	/** @var bool */
	private $validated;


	/********************* data exchange ****************d*g**/


	/**
	 * Fill-in with default values.
	 * @return static
	 */
	public function setDefaults(iterable $values, bool $erase = false)
	{
		$form = $this->getForm(false);
		if (!$form || !$form->isAnchored() || !$form->isSubmitted()) {
			$this->setValues($values, $erase);
		}
		return $this;
	}


	/**
	 * Fill-in with values.
	 * @return static
	 * @internal
	 */
	public function setValues(iterable $values, bool $erase = false)
	{
		if ($values instanceof \Traversable) {
			$values = iterator_to_array($values);

		} elseif (!is_array($values)) {
			throw new Nette\InvalidArgumentException(sprintf('First parameter must be an array, %s given.', gettype($values)));
		}

		foreach ($this->getComponents() as $name => $control) {
			if ($control instanceof IControl) {
				if (array_key_exists($name, $values)) {
					$control->setValue($values[$name]);

				} elseif ($erase) {
					$control->setValue(null);
				}

			} elseif ($control instanceof self) {
				if (array_key_exists($name, $values)) {
					$control->setValues($values[$name], $erase);

				} elseif ($erase) {
					$control->setValues([], $erase);
				}
			}
		}
		return $this;
	}


	/**
	 * Returns the values submitted by the form.
	 * @return Nette\Utils\ArrayHash|array
	 */
	public function getValues(bool $asArray = false)
	{
		$values = $asArray ? [] : new Nette\Utils\ArrayHash;
		foreach ($this->getComponents() as $name => $control) {
			if ($control instanceof IControl && !$control->isOmitted()) {
				$values[$name] = $control->getValue();

			} elseif ($control instanceof self) {
				$values[$name] = $control->getValues($asArray);
			}
		}
		return $values;
	}


	/********************* validation ****************d*g**/


	/**
	 * Is form valid?
	 */
	public function isValid(): bool
	{
		if (!$this->validated) {
			if ($this->getErrors()) {
				return false;
			}
			$this->validate();
		}
		return !$this->getErrors();
	}


	/**
	 * Performs the server side validation.
	 * @param  IControl[]
	 */
	public function validate(array $controls = null): void
	{
		foreach ($controls === null ? $this->getComponents() : $controls as $control) {
			if ($control instanceof IControl || $control instanceof self) {
				$control->validate();
			}
		}
		if ($this->onValidate !== null) {
			if (!is_iterable($this->onValidate)) {
				throw new Nette\UnexpectedValueException('Property Form::$onValidate must be iterable, ' . gettype($this->onValidate) . ' given.');
			}
			foreach ($this->onValidate as $handler) {
				$params = Nette\Utils\Callback::toReflection($handler)->getParameters();
				$values = isset($params[1]) ? $this->getValues($params[1]->isArray()) : null;
				$handler($this, $values);
			}
		}
		$this->validated = true;
	}


	/**
	 * Returns all validation errors.
	 */
	public function getErrors(): array
	{
		$errors = [];
		foreach ($this->getControls() as $control) {
			$errors = array_merge($errors, $control->getErrors());
		}
		return array_unique($errors);
	}


	/********************* form building ****************d*g**/


	/**
	 * @return static
	 */
	public function setCurrentGroup(ControlGroup $group = null)
	{
		$this->currentGroup = $group;
		return $this;
	}


	/**
	 * Returns current group.
	 */
	public function getCurrentGroup(): ?ControlGroup
	{
		return $this->currentGroup;
	}


	/**
	 * Adds the specified component to the IContainer.
	 * @param  string|int $name
	 * @param  string|int $insertBefore
	 * @return static
	 * @throws Nette\InvalidStateException
	 */
	public function addComponent(Nette\ComponentModel\IComponent $component, $name, $insertBefore = null)
	{
		parent::addComponent($component, $name, $insertBefore);
		if ($this->currentGroup !== null) {
			$this->currentGroup->add($component);
		}
		return $this;
	}


	/**
	 * Iterates over all form controls.
	 */
	public function getControls(): \Iterator
	{
		return $this->getComponents(true, IControl::class);
	}


	/**
	 * Returns form.
	 */
	public function getForm(bool $throw = true): ?Form
	{
		return $this->lookup(Form::class, $throw);
	}


	/********************* control factories ****************d*g**/


	/**
	 * Adds single-line text input control to the form.
	 * @param  string|object $label
	 */
	public function addText(string $name, $label = null, int $cols = null, int $maxLength = null): Controls\TextInput
	{
		return $this[$name] = (new Controls\TextInput($label, $maxLength))
			->setHtmlAttribute('size', $cols);
	}


	/**
	 * Adds single-line text input control used for sensitive input such as passwords.
	 * @param  string|object $label
	 */
	public function addPassword(string $name, $label = null, int $cols = null, int $maxLength = null): Controls\TextInput
	{
		return $this[$name] = (new Controls\TextInput($label, $maxLength))
			->setHtmlAttribute('size', $cols)
			->setHtmlType('password');
	}


	/**
	 * Adds multi-line text input control to the form.
	 * @param  string|object $label
	 */
	public function addTextArea(string $name, $label = null, int $cols = null, int $rows = null): Controls\TextArea
	{
		return $this[$name] = (new Controls\TextArea($label))
			->setHtmlAttribute('cols', $cols)->setHtmlAttribute('rows', $rows);
	}


	/**
	 * Adds input for email.
	 * @param  string|object $label
	 */
	public function addEmail(string $name, $label = null): Controls\TextInput
	{
		return $this[$name] = (new Controls\TextInput($label))
			->setRequired(false)
			->addRule(Form::EMAIL);
	}


	/**
	 * Adds input for integer.
	 * @param  string|object $label
	 */
	public function addInteger(string $name, $label = null): Controls\TextInput
	{
		return $this[$name] = (new Controls\TextInput($label))
			->setNullable()
			->setRequired(false)
			->addRule(Form::INTEGER);
	}


	/**
	 * Adds control that allows the user to upload files.
	 * @param  string|object $label
	 */
	public function addUpload(string $name, $label = null, bool $multiple = false): Controls\UploadControl
	{
		return $this[$name] = new Controls\UploadControl($label, $multiple);
	}


	/**
	 * Adds control that allows the user to upload multiple files.
	 * @param  string|object $label
	 */
	public function addMultiUpload(string $name, $label = null): Controls\UploadControl
	{
		return $this[$name] = new Controls\UploadControl($label, true);
	}


	/**
	 * Adds hidden form control used to store a non-displayed value.
	 */
	public function addHidden(string $name, string $default = null): Controls\HiddenField
	{
		return $this[$name] = (new Controls\HiddenField)
			->setDefaultValue($default);
	}


	/**
	 * Adds check box control to the form.
	 * @param  string|object $caption
	 */
	public function addCheckbox(string $name, $caption = null): Controls\Checkbox
	{
		return $this[$name] = new Controls\Checkbox($caption);
	}


	/**
	 * Adds set of radio button controls to the form.
	 * @param  string|object $label
	 */
	public function addRadioList(string $name, $label = null, array $items = null): Controls\RadioList
	{
		return $this[$name] = new Controls\RadioList($label, $items);
	}


	/**
	 * Adds set of checkbox controls to the form.
	 * @param  string|object $label
	 */
	public function addCheckboxList(string $name, $label = null, array $items = null): Controls\CheckboxList
	{
		return $this[$name] = new Controls\CheckboxList($label, $items);
	}


	/**
	 * Adds select box control that allows single item selection.
	 * @param  string|object $label
	 */
	public function addSelect(string $name, $label = null, array $items = null, int $size = null): Controls\SelectBox
	{
		return $this[$name] = (new Controls\SelectBox($label, $items))
			->setHtmlAttribute('size', $size > 1 ? $size : null);
	}


	/**
	 * Adds select box control that allows multiple item selection.
	 * @param  string|object $label
	 */
	public function addMultiSelect(string $name, $label = null, array $items = null, int $size = null): Controls\MultiSelectBox
	{
		return $this[$name] = (new Controls\MultiSelectBox($label, $items))
			->setHtmlAttribute('size', $size > 1 ? $size : null);
	}


	/**
	 * Adds button used to submit form.
	 * @param  string|object $caption
	 */
	public function addSubmit(string $name, $caption = null): Controls\SubmitButton
	{
		return $this[$name] = new Controls\SubmitButton($caption);
	}


	/**
	 * Adds push buttons with no default behavior.
	 * @param  string|object $caption
	 */
	public function addButton(string $name, $caption = null): Controls\Button
	{
		return $this[$name] = new Controls\Button($caption);
	}


	/**
	 * Adds graphical button used to submit form.
	 * @param  string $src  URI of the image
	 * @param  string $alt  alternate text for the image
	 */
	public function addImage(string $name, string $src = null, string $alt = null): Controls\ImageButton
	{
		return $this[$name] = new Controls\ImageButton($src, $alt);
	}


	/**
	 * Adds naming container to the form.
	 * @param  string|int
	 */
	public function addContainer($name): self
	{
		$control = new self;
		$control->currentGroup = $this->currentGroup;
		if ($this->currentGroup !== null) {
			$this->currentGroup->add($control);
		}
		return $this[$name] = $control;
	}


	/********************* extension methods ****************d*g**/


	public function __call(string $name, array $args)
	{
		if (isset(self::$extMethods[$name])) {
			return (self::$extMethods[$name])($this, ...$args);
		} elseif ($callback = Nette\Utils\ObjectMixin::getExtensionMethod($class = __CLASS__, $name)) {
			trigger_error("Define extension method '$name' via $class::extensionMethod('$name', ...), don't use Nette\\Object or Nette\\Utils\\ObjectMixin.", E_USER_DEPRECATED);
			return $callback($this, ...$args);
		}
		return parent::__call($name, $args);
	}


	public static function extensionMethod($name, /*callable*/ $callback): void
	{
		if (strpos($name, '::') !== false) { // back compatibility
			[, $name] = explode('::', $name);
		}
		self::$extMethods[$name] = $callback;
	}


	/********************* interface \ArrayAccess ****************d*g**/


	/**
	 * Adds the component to the container.
	 * @param  string|int
	 * @param  Nette\ComponentModel\IComponent
	 */
	public function offsetSet($name, $component): void
	{
		$this->addComponent($component, $name);
	}


	/**
	 * Returns component specified by name. Throws exception if component doesn't exist.
	 * @param  string|int
	 * @throws Nette\InvalidArgumentException
	 */
	public function offsetGet($name): Nette\ComponentModel\IComponent
	{
		return $this->getComponent($name, true);
	}


	/**
	 * Does component specified by name exists?
	 * @param  string|int
	 */
	public function offsetExists($name): bool
	{
		return $this->getComponent($name, false) !== null;
	}


	/**
	 * Removes component from the container.
	 * @param  string|int
	 */
	public function offsetUnset($name): void
	{
		$component = $this->getComponent($name, false);
		if ($component !== null) {
			$this->removeComponent($component);
		}
	}


	/**
	 * Prevents cloning.
	 */
	public function __clone()
	{
		throw new Nette\NotImplementedException('Form cloning is not supported yet.');
	}
}
