<?php

declare(strict_types = 1);

namespace KdybyTests\DoctrineCache\Data;

class EvalExtension extends \Nette\DI\CompilerExtension
{

	/**
	 * @var callable
	 */
	public $loadConfiguration;

	public function __construct(callable $loadConfiguration)
	{
		$this->loadConfiguration = $loadConfiguration;
	}

	public function loadConfiguration(): void
	{
		call_user_func($this->loadConfiguration, $this);
	}

}
