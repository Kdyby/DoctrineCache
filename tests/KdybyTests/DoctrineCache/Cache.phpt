<?php

/**
 * Test: Kdyby\Doctrine\DI\Helpers.
 *
 * @testCase Kdyby\Doctrine\CacheTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CacheTest extends Tester\TestCase
{

	public function testNonexistentKey()
	{
		$storage = new Nette\Caching\Storages\MemoryStorage();
		$cache = new Kdyby\DoctrineCache\Cache($storage, 'ns');

		Assert::false($cache->contains('nonexistent-key'));
		Assert::false($cache->fetch('nonexistent-key'));

		$cache->save('nonexistent-key', 'data');

		Assert::true($cache->contains('nonexistent-key'));
		Assert::same('data', $cache->fetch('nonexistent-key'));
	}

	public function testStoringNull()
	{
		$storage = new Nette\Caching\Storages\MemoryStorage();
		$cache = new Kdyby\DoctrineCache\Cache($storage, 'ns');

		$cache->save('nonexistent-key', NULL);

		Assert::false($cache->contains('nonexistent-key'));
		Assert::false($cache->fetch('nonexistent-key'));
	}

}

\run(new CacheTest());
