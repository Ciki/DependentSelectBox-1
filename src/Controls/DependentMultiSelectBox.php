<?php

/**
 * This file is part of the NasExt extensions of Nette Framework
 *
 * Copyright (c) 2013 Dusan Hudak (http://dusan-hudak.com)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace NasExt\Forms\Controls;

use NasExt;
use Nette;

/**
 * @author Jáchym Toušek
 * @author Dusan Hudak
 * @author Ales Wita
 * @license MIT
 */
class DependentMultiSelectBox extends Nette\Forms\Controls\MultiSelectBox implements Nette\Application\UI\ISignalReceiver
{

	use NasExt\Forms\DependentTrait {
		getValue as protected traitGetValue;
	}
	/** @var string */
	const SIGNAL_NAME = DependentSelectBox::SIGNAL_NAME;


	/**
	 * @param string $label
	 * @param array<int, Nette\Forms\Control> $parents
	 */
	public function __construct(string $label, array $parents)
	{
		$this->parents = $parents;
		$this->dependentCallbackParams = $this->parents; //default
		parent::__construct($label);
	}


	/**
	 * @throws Nette\InvalidArgumentException
	 */
	public function setDisabled(/*bool|array*/$value = true): self
	{
		if (is_array($value)) {
			throw new Nette\InvalidArgumentException('NasExt\\Forms\\Controls\\DependentMultiSelectBox not supported disabled items!');
		}

		return parent::setDisabled($value);
	}


	public function getValue(): array
	{
		return $this->traitGetValue();
	}


	public function signalReceived(string $signal): void
	{
		$presenter = $this->lookup('Nette\\Application\\UI\\Presenter');

		if ($presenter->isAjax() && $signal === self::SIGNAL_NAME && !$this->isDisabled()) {
			$cbParamNames = [];
			foreach ($this->dependentCallbackParams as $param) {
				$value = $presenter->getParameter($this->getNormalizeName($param));

				if ($param instanceof Nette\Forms\Controls\MultiChoiceControl) {
					if (is_string($value)) {
						$value = explode(',', $value);
					}
					if ($value !== null) {
						$value = array_filter($value, static function ($val) {
							return !in_array($val, [null, '', []], true);
						});
					}
				}

				$param->setValue($value);

				$cbParamNames[$param->getName()] = $param->getValue();
			}

			$data = $this->getDependentData([$cbParamNames]);
			$presenter->payload->dependentselectbox = [
				'id' => $this->getHtmlId(),
				'items' => $data->getPreparedItems($this->disabled),
				'value' => $data->getValue(),
				'prompt' => false,
				'disabledWhenEmpty' => $this->disabledWhenEmpty,
			];

			$presenter->sendPayload();
		}
	}


	private function tryLoadItems(): void
	{
		if ($this->dependentCallbackParams === array_filter($this->dependentCallbackParams, function ($p) {
			return !$p->hasErrors();
		})) {
			$cbParamValues = [];
			foreach ($this->dependentCallbackParams as $param) {
				$cbParamValues[$param->getName()] = $param->getValue();
			}

			$data = $this->getDependentData([$cbParamValues]);
			$items = $data->getItems();

			if ($this->getForm()->isSubmitted()) {
				// $this->setValue($this->value);
				// try to set tempValue first to enable components like Replicator to set new value on Container
				$this->setValue($this->tempValue ?: $this->value);
			} elseif ($this->tempValue !== null) {
				$this->setValue($this->tempValue);
			} else {
				$this->setValue($data->getValue());
			}

			$this->loadHttpData();

			if ($this->isLoadedDynamically) {
				// set tempValue as the only values if valid
				if ($this->tempValue) {
					$this->setItems(array_intersect_key($items, array_combine((array) $this->tempValue, (array) $this->tempValue)));
				}
			} else {
				$this->setItems($items);
			}

			if (count($items) === 0) {
				if ($this->disabledWhenEmpty === true && !$this->isDisabled()) {
					$this->setDisabled();
				}
			}
		} else {
			// if there's an error in parent controls => set $this->value to empty array, which is valid default empty value.
			// otherwise it could/would be null which is not valid
			$this->value = [];
			// $this->setValue([]);
		}
	}
}
