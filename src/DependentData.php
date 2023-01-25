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

use Nette;
use Nette\Utils\Html;

/**
 * @property array $items
 * @property array|string|int|null $value
 * @property ?string $prompt
 *
 * @author Dusan Hudak
 * @author Ales Wita
 * @license MIT
 */
class DependentData
{
	use Nette\SmartObject;

	private array $items = [];

	private array|string|int|null $value;

	private ?string $prompt;


	public function __construct(
		array $items = [],
		string|int|null $value = null,
		?string $prompt = null
	) {
		$this->items = $items;
		$this->value = $value;
		$this->prompt = $prompt;
	}


	public function getItems(): array
	{
		return $this->items;
	}


	public function setItems(array $items): self
	{
		$this->items = $items;
		return $this;
	}


	public function getValue(): array|string|int|null
	{
		return $this->value;
	}


	public function setValue(array|string|int|null $value): self
	{
		$this->value = $value;
		return $this;
	}


	public function getPrompt(): ?string
	{
		return $this->prompt;
	}


	public function setPrompt(string $value): self
	{
		$this->prompt = $value;
		return $this;
	}


	public function getPreparedItems(/*bool|bool[]*/ bool|array $disabledItems = []): array
	{
		$items = [];
		foreach ($this->items as $key => $item) {
			if (is_array($item)) {
				$groupItems = [];
				foreach ($item as $innerKey => $innerItem) {
					$el = $this->getPreparedElement($innerKey, $innerItem, $disabledItems);
					$this->addElementToItemsList($groupItems, $el);
				}

				$items[$key] = [
					'key' => $key,
					'value' => $groupItems,
				];
			} else {
				$el = $this->getPreparedElement($key, $item, $disabledItems);
				$this->addElementToItemsList($items, $el);
			}
		}
		// make a List so the order of items is preserved when sent as JSON to client
		return array_values($items);
	}


	private function getPreparedElement(string $key, mixed $item, /*bool|bool[]*/ bool|array $disabledItems = []): Html
	{
		if (!($item instanceof Html)) {
			$el = Html::el('option')->value($key)->setText($item);
		} else {
			$el = $item;
		}

		// disable element
		if ($disabledItems === true || (is_array($disabledItems) && array_key_exists($key, $disabledItems) && $disabledItems[$key] === true)) {
			$el->disabled(true);
		}

		return $el;
	}


	private function addElementToItemsList(array &$items, Html $el): void
	{
		$items[$el->getAttribute('value')] = [
			'key' => $el->getValue(),
			'value' => $el->getText(),
		];
		end($items);
		$lKey = key($items);
		foreach ($el->attrs as $attr => $val) {
			$items[$lKey]['attributes'][$attr] = $val;
		}
	}
}
