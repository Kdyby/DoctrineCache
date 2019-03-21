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

	public function testNonexistentKey(): void
	{
		$storage = new MemoryStorage();
		$cache = new NetteCacheAdapter($storage, 'ns');

		Assert::false($cache->contains('nonexistent-key'));
		Assert::false($cache->fetch('nonexistent-key'));

		$cache->save('nonexistent-key', 'data');

		Assert::true($cache->contains('nonexistent-key'));
		Assert::same('data', $cache->fetch('nonexistent-key'));
	}

	public function testStoringNull(): void
	{
		$storage = new MemoryStorage();
		$cache = new NetteCacheAdapter($storage, 'ns');

		$cache->save('nonexistent-key', NULL);

		Assert::false($cache->contains('nonexistent-key'));
		Assert::false($cache->fetch('nonexistent-key'));
	}

	public function testZeroAndNullLifetime(): void
	{
		$storage = new MemoryStorage();
		$cache = new NetteCacheAdapter($storage, 'ns');

		$cache->save('foo1', 'data', 0);
		$cache->save('foo2', 'data', NULL);

		Assert::true($cache->contains('foo1'));
		Assert::true($cache->contains('foo2'));
	}

}

(new CacheTest())->run();
