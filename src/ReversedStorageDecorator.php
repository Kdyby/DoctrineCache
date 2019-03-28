<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\DoctrineCache;

use Doctrine\Common\Cache\CacheProvider;
use Nette\Caching\Cache as NCache;

class ReversedStorageDecorator implements \Nette\Caching\IStorage
{

	/**
	 * @var \Doctrine\Common\Cache\CacheProvider
	 */
	private $provider;

	public function __construct(CacheProvider $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * Read from cache.
	 *
	 * @return mixed|NULL
	 */
	public function read(string $key)
	{
		$data = $this->provider->fetch($key);
		return $data === FALSE ? NULL : $data;
	}

	/**
	 * Prevents item reading and writing. Lock is released by write() or remove().
	 */
	public function lock(string $key): void
	{
		// sorry!
	}

	/**
	 * {@inheritdoc}
	 */
	public function write(string $key, $data, array $dependencies): void
	{
		$this->provider->save($key, $data);
	}

	/**
	 * Removes item from the cache.
	 */
	public function remove(string $key): void
	{
		$this->provider->delete($key);
	}

	/**
	 * Removes items from the cache by conditions.
	 *
	 * @param string[] $conditions
	 */
	public function clean(array $conditions): void
	{
		if (!isset($conditions[NCache::ALL])) {
			throw new \Kdyby\DoctrineCache\Exception\NotImplementedException();
		}

		$this->provider->deleteAll();
	}

}
