<?php

/**
 * Test: Kdyby\Doctrine\Extension.
 *
 * @testCase
 */

namespace KdybyTests\DoctrineCache;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\VoidCache;
use Kdyby\DoctrineCache\Cache as NetteCacheAdapter;
use Kdyby\DoctrineCache\DI\Helpers as DICacheHelpers;
use Kdyby\DoctrineCache\MemcacheCache;
use Memcache;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\Statement;
use Tester\Assert;
use Tester\Environment as TesterEnvironment;

require_once __DIR__ . '/../bootstrap.php';

class ExtensionTest extends \Tester\TestCase
{

	/**
	 * @return \Nette\DI\Container
	 */
	public function createContainer($function, $extension)
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters([
			'container' => ['class' => 'SystemContainer_' . md5(time()) . '_' . $function],
			'_method' => $function,
		]);
		$config->addConfig(__DIR__ . '/../nette-reset.neon');

		$config->onCompile[] = function ($config, Compiler $compiler) use ($extension) {
			$compiler->addExtension('eval', $extension);
		};

		return $config->createContainer();
	}

	public function testFunctionality()
	{
		$container = $this->createContainer(__FUNCTION__, new EvalExtension(function (EvalExtension $extension) {
			DICacheHelpers::processCache($extension, 'default', 'default', FALSE);
			DICacheHelpers::processCache($extension, 'array', 'array', FALSE);
			DICacheHelpers::processCache($extension, 'filesystem', 'filesystem', FALSE);
			DICacheHelpers::processCache($extension, 'void', 'void', FALSE);
			DICacheHelpers::processCache($extension, 'apc', 'apc', FALSE);
		}));

		$default = $container->getService('eval.cache.default');
		Assert::true($default instanceof NetteCacheAdapter);

		$default = $container->getService('eval.cache.array');
		Assert::true($default instanceof ArrayCache);

		$default = $container->getService('eval.cache.filesystem');
		Assert::true($default instanceof FilesystemCache);

		$default = $container->getService('eval.cache.void');
		Assert::true($default instanceof VoidCache);

		$default = $container->getService('eval.cache.apc');
		Assert::true($default instanceof ApcCache);
	}

	public function testFunctionalityApcu()
	{
		if (PHP_VERSION_ID < 50500) {
			TesterEnvironment::skip('ApcuCache is not supported on PHP 5.4');
		}
		if (!class_exists(ApcuCache::class)) {
			TesterEnvironment::skip('Old doctrine/cache without ApcuCache is installed');
		}

		$container = $this->createContainer(__FUNCTION__, new EvalExtension(function (EvalExtension $extension) {
			DICacheHelpers::processCache($extension, 'apcu', 'apcu', FALSE);
		}));

		$default = $container->getService('eval.cache.apcu');
		Assert::true($default instanceof ApcuCache);
	}

	public function testFunctionalityMemcache()
	{
		if (!extension_loaded('memcache')) {
			TesterEnvironment::skip('The memcache extension is not loaded');
		}

		$container = $this->createContainer(__FUNCTION__, new EvalExtension(function (EvalExtension $extension) {
			$builder = $extension->getContainerBuilder();

			$builder->addDefinition($extension->prefix('memcache'))
				->setClass(Memcache::class);

			DICacheHelpers::processCache($extension, (object) ['value' => 'memcache', 'attributes' => []], 'memcache.one', FALSE);
			DICacheHelpers::processCache($extension, new Statement('memcache'), 'memcache.two', FALSE);
		}));

		$default = $container->getService('eval.cache.memcache.one');
		Assert::true($default instanceof MemcacheCache);

		$default = $container->getService('eval.cache.memcache.two');
		Assert::true($default instanceof MemcacheCache);
	}

}

(new ExtensionTest())->run();
