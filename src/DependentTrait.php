<?php

/**
 * This file is part of the NasExt extensions of Nette Framework
 *
 * Copyright (c) 2013 Dusan Hudak (http://dusan-hudak.com)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace NasExt\Forms;

use NasExt;
use Nette;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;

/**
 * @author Ales Wita
 * @license MIT
 */
trait DependentTrait
{
	private array $parents;

	/**
	 * Defaults to $this->parents
	 * Useful when selectbox should receive more params that those it's being changed by
	 */
	private array $dependentCallbackParams;

	/** @var callable */
	private $dependentCallback;

	private bool $disabledWhenEmpty = false;

	private bool $isLoadedDynamically = false;

	private string|int|float|array|null $tempValue = null;


	public function getControl(): Html
	{
		$this->tryLoadItems();

		$attrs = [];
		$control = parent::getControl();
		$form = $this->getForm();

		$parents = [];
		foreach ($this->parents as $parent) {
			$parents[$this->getNormalizeName($parent)] = $parent->getHtmlId();
		}

		$params = [];
		foreach ($this->dependentCallbackParams as $param) {
			$params[$this->getNormalizeName($param)] = $param->getHtmlId();
		}

		$attrs['data-dependentselectbox-parents'] = Nette\Utils\Json::encode($parents);
		$attrs['data-dependentselectbox-params'] = Nette\Utils\Json::encode($params);
		$attrs['data-dependentselectbox-load-dynamically'] = Nette\Utils\Json::encode(true);
		$attrs['data-dependentselectbox'] = $this->isLoadedDynamically ? 'null' : $form->getPresenter()->link($this->lookupPath('Nette\\Application\\UI\\Presenter') . Nette\ComponentModel\IComponent::NAME_SEPARATOR . self::SIGNAL_NAME . '!');

		$control->addAttributes($attrs);
		return $control;
	}


	public function getValue(): string|int|float|array|null
	{
		$this->tryLoadItems();

		if (!in_array($this->tempValue, [null, '', []], true)) {
			return $this->tempValue;
		}

		return parent::getValue();
	}


	public function setValue(/*string|int|float|array|null*/ $value): self
	{
		$this->tempValue = $value;
		return $this;
	}


	public function setItems(array $items, bool $useKeys = true): self
	{
		parent::setItems($items, $useKeys);

		if (!in_array($this->tempValue, [null, '', []], true)) {
			parent::setValue($this->tempValue);
		}

		return $this;
	}


	/**
	 * @throws DependentCallbackException
	 */
	private function getDependentData(array $args = []): DependentData
	{
		if ($this->dependentCallback === null) {
			throw new DependentCallbackException('Dependent callback for "' . $this->getHtmlId() . '" must be set!');
		}

		$dependentData = call_user_func_array($this->dependentCallback, $args);

		if (!($dependentData instanceof DependentData) && !($dependentData instanceof NasExt\Forms\Controls\DependentSelectBoxData)) {
			throw new DependentCallbackException('Callback for "' . $this->getHtmlId() . '" must return NasExt\\Forms\\DependentData instance!');
		}

		return $dependentData;
	}


	public function setDependentCallback(callable $callback): self
	{
		$this->dependentCallback = $callback;
		return $this;
	}


	public function setDependentCallbackParams(array $params): self
	{
		$this->dependentCallbackParams = $params;
		return $this;
	}


	public function setDisabledWhenEmpty(bool $value = true): self
	{
		$this->disabledWhenEmpty = $value;
		return $this;
	}


	public function setIsLoadedDynamically(bool $isLoadedDynamically = true): self
	{
		$this->isLoadedDynamically = $isLoadedDynamically;
		return $this;
	}


	private function getNormalizeName(BaseControl $parent): string
	{
		return str_replace('-', '_', $parent->getHtmlId());
	}
}
