<?php

namespace KdybyTests\DoctrineCache;

class EvalExtension extends \Nette\DI\CompilerExtension
{

	/**
	 * @var callable
	 */
	public $loadConfiguration;

	public function __construct($loadConfiguration)
	{
		$this->loadConfiguration = $loadConfiguration;
	}

	public function loadConfiguration()
	{
		call_user_func($this->loadConfiguration, $this);
	}

}
