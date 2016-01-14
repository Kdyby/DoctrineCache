<?php

/**
 * Test: Kdyby\Doctrine\Extension.
 *
 * @testCase Kdyby\Doctrine\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine;
use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExtensionTest extends Tester\TestCase
{

	/**
	 * @return \SystemContainer|Nette\DI\Container
	 */
	public function createContainer(array $extraConfig = array())
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array(
			'container' => array('class' => 'SystemContainer_' . md5(time() . serialize($extraConfig))),
			'_config' => $extraConfig,
		));
		$config->addConfig(__DIR__ . '/../nette-reset.neon', !isset($config->defaultExtensions['nette']) ? 'v23' : 'v22');

		$config->onCompile[] = function ($config, Nette\DI\Compiler $compiler) use ($extraConfig) {
			$compiler->addConfig($extraConfig);
		};

		return $config->createContainer();
	}



	public function testFunctionality()
	{
		$container = $this->createContainer(array(
			'extensions' => array(
				'sample' => 'KdybyTests\Doctrine\SampleExtension',
			),
		));

		$default = $container->getService('sample.cache.default');
		Assert::true($default instanceof Kdyby\DoctrineCache\Cache);

		$default = $container->getService('sample.cache.array');
		Assert::true($default instanceof Doctrine\Common\Cache\ArrayCache);

		$default = $container->getService('sample.cache.filesystem');
		Assert::true($default instanceof Doctrine\Common\Cache\FilesystemCache);

		$default = $container->getService('sample.cache.void');
		Assert::true($default instanceof Doctrine\Common\Cache\VoidCache);
	}



	public function testFunctionalityMemcache()
	{
		if (!extension_loaded('memcache')) {
			Tester\Environment::skip('The memcache extension is not loaded');
		}

		$container = $this->createContainer(array(
			'extensions' => array(
				'withMemcache' => 'KdybyTests\Doctrine\WithMemcacheExtension',
			),
		));

		$default = $container->getService('withMemcache.cache.memcache.one');
		Assert::true($default instanceof Kdyby\DoctrineCache\MemcacheCache);

		$default = $container->getService('withMemcache.cache.memcache.two');
		Assert::true($default instanceof Kdyby\DoctrineCache\MemcacheCache);
	}

}


class SampleExtension extends Nette\DI\CompilerExtension
{

	public function loadConfiguration()
	{
		Kdyby\DoctrineCache\DI\Helpers::processCache($this, 'default', 'default', FALSE);
		Kdyby\DoctrineCache\DI\Helpers::processCache($this, 'array', 'array', FALSE);
		Kdyby\DoctrineCache\DI\Helpers::processCache($this, 'filesystem', 'filesystem', FALSE);
		Kdyby\DoctrineCache\DI\Helpers::processCache($this, 'void', 'void', FALSE);
	}

}


class WithMemcacheExtension extends Nette\DI\CompilerExtension
{

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('memcache'))
			->setClass('Memcache');

		Kdyby\DoctrineCache\DI\Helpers::processCache($this, (object) array('value' => 'memcache', 'attributes' => array()), 'memcache.one', FALSE);
		Kdyby\DoctrineCache\DI\Helpers::processCache($this, new Nette\DI\Statement('memcache'), 'memcache.two', FALSE);
	}

}

\run(new ExtensionTest());
