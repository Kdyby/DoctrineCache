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
	public function createContainer()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('container' => array('class' => 'SystemContainer_' . md5(time()))));
		$config->addConfig(__DIR__ . '/../nette-reset.neon');

		$config->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('sample', new SampleExtension());
		};

		return $config->createContainer();
	}



	public function testFunctionality()
	{
		$container = $this->createContainer();

		$default = $container->getService('sample.cache.default');
		Assert::true($default instanceof Kdyby\DoctrineCache\Cache);

		$default = $container->getService('sample.cache.array');
		Assert::true($default instanceof Doctrine\Common\Cache\ArrayCache);

		$default = $container->getService('sample.cache.memcache.one');
		Assert::true($default instanceof Kdyby\DoctrineCache\MemcacheCache);

		$default = $container->getService('sample.cache.memcache.two');
		Assert::true($default instanceof Kdyby\DoctrineCache\MemcacheCache);
	}

}


class SampleExtension extends Nette\DI\CompilerExtension
{

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('memcache'))
			->setClass('Memcache');

		Kdyby\DoctrineCache\DI\Helpers::processCache($this, 'default', 'default', FALSE);
		Kdyby\DoctrineCache\DI\Helpers::processCache($this, 'array', 'array', FALSE);
		Kdyby\DoctrineCache\DI\Helpers::processCache($this, (object) array('value' => 'memcache', 'attributes' => array()), 'memcache.one', FALSE);
		Kdyby\DoctrineCache\DI\Helpers::processCache($this, new Nette\DI\Statement('memcache'), 'memcache.two', FALSE);
	}

}

\run(new ExtensionTest());
