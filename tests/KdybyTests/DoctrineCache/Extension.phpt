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
	public function createContainer($function, $extension)
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array(
			'container' => array('class' => 'SystemContainer_' . md5(time()) . '_' . $function),
			'_method' => $function,
		));
		$config->addConfig(__DIR__ . '/../nette-reset.neon', !isset($config->defaultExtensions['nette']) ? 'v23' : 'v22');

		$config->onCompile[] = function ($config, Nette\DI\Compiler $compiler) use ($extension) {
			$compiler->addExtension('eval', $extension);
		};

		return $config->createContainer();
	}



	public function testFunctionality()
	{
		$container = $this->createContainer(__FUNCTION__, new EvalExtension(function (EvalExtension $extension) {
			Kdyby\DoctrineCache\DI\Helpers::processCache($extension, 'default', 'default', FALSE);
			Kdyby\DoctrineCache\DI\Helpers::processCache($extension, 'array', 'array', FALSE);
			Kdyby\DoctrineCache\DI\Helpers::processCache($extension, 'filesystem', 'filesystem', FALSE);
			Kdyby\DoctrineCache\DI\Helpers::processCache($extension, 'void', 'void', FALSE);
			Kdyby\DoctrineCache\DI\Helpers::processCache($extension, 'apc', 'apc', FALSE);
		}));

		$default = $container->getService('eval.cache.default');
		Assert::true($default instanceof Kdyby\DoctrineCache\Cache);

		$default = $container->getService('eval.cache.array');
		Assert::true($default instanceof Doctrine\Common\Cache\ArrayCache);

		$default = $container->getService('eval.cache.filesystem');
		Assert::true($default instanceof Doctrine\Common\Cache\FilesystemCache);

		$default = $container->getService('eval.cache.void');
		Assert::true($default instanceof Doctrine\Common\Cache\VoidCache);

		$default = $container->getService('eval.cache.apc');
		Assert::true($default instanceof Doctrine\Common\Cache\ApcCache);
	}



	public function testFunctionalityApcu()
	{
		if (PHP_VERSION_ID < 50500) {
			Tester\Environment::skip('ApcuCache is not supported on PHP 5.4');
		}

		$container = $this->createContainer(__FUNCTION__, new EvalExtension(function (EvalExtension $extension) {
			Kdyby\DoctrineCache\DI\Helpers::processCache($extension, 'apcu', 'apcu', FALSE);
		}));

		$default = $container->getService('eval.cache.apcu');
		Assert::true($default instanceof Doctrine\Common\Cache\ApcuCache);
	}



	public function testFunctionalityMemcache()
	{
		if (!extension_loaded('memcache')) {
			Tester\Environment::skip('The memcache extension is not loaded');
		}

		$container = $this->createContainer(__FUNCTION__, new EvalExtension(function (EvalExtension $extension) {
			$builder = $extension->getContainerBuilder();

			$builder->addDefinition($extension->prefix('memcache'))
				->setClass('Memcache');

			Kdyby\DoctrineCache\DI\Helpers::processCache($extension, (object) array('value' => 'memcache', 'attributes' => array()), 'memcache.one', FALSE);
			Kdyby\DoctrineCache\DI\Helpers::processCache($extension, new Nette\DI\Statement('memcache'), 'memcache.two', FALSE);
		}));

		$default = $container->getService('eval.cache.memcache.one');
		Assert::true($default instanceof Kdyby\DoctrineCache\MemcacheCache);

		$default = $container->getService('eval.cache.memcache.two');
		Assert::true($default instanceof Kdyby\DoctrineCache\MemcacheCache);
	}

}



class EvalExtension extends Nette\DI\CompilerExtension
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

\run(new ExtensionTest());
