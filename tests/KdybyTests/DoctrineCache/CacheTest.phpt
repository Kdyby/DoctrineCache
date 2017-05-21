<?php

/**
 * Test: Kdyby\Doctrine\DI\Helpers.
 *
 * @testCase
 */

namespace KdybyTests\DoctrineCache;

use Kdyby\DoctrineCache\Cache as NetteCacheAdapter;
use Nette\Caching\Storages\MemoryStorage;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class CacheTest extends \Tester\TestCase
{

	public function testNonexistentKey()
	{
		$storage = new MemoryStorage();
		$cache = new NetteCacheAdapter($storage, 'ns');

		Assert::false($cache->contains('nonexistent-key'));
		Assert::false($cache->fetch('nonexistent-key'));

		$cache->save('nonexistent-key', 'data');

		Assert::true($cache->contains('nonexistent-key'));
		Assert::same('data', $cache->fetch('nonexistent-key'));
	}

	public function testStoringNull()
	{
		$storage = new MemoryStorage();
		$cache = new NetteCacheAdapter($storage, 'ns');

		$cache->save('nonexistent-key', NULL);

		Assert::false($cache->contains('nonexistent-key'));
		Assert::false($cache->fetch('nonexistent-key'));
	}

}

(new CacheTest())->run();
