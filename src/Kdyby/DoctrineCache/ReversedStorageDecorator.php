<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\DoctrineCache;

use Doctrine\Common\Cache\CacheProvider;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ReversedStorageDecorator extends Nette\Object implements Nette\Caching\IStorage
{

	/**
	 * @var CacheProvider
	 */
	private $provider;



	public function __construct(CacheProvider $provider)
	{
		$this->provider = $provider;
	}



	/**
	 * Read from cache.
	 *
	 * @param string $key
	 * @return mixed|NULL
	 */
	public function read($key)
	{
		$data = $this->provider->fetch($key);
		return $data === FALSE ? NULL : $data;
	}



	/**
	 * Prevents item reading and writing. Lock is released by write() or remove().
	 *
	 * @param string $key
	 * @return void
	 */
	public function lock($key)
	{
		// sorry!
	}



	/**
	 * Writes item into the cache.
	 *
	 * @param string $key
	 * @param mixed $data
	 * @param array $dependencies
	 * @return void
	 */
	public function write($key, $data, array $dependencies)
	{
		$this->provider->save($key, $data);
	}



	/**
	 * Removes item from the cache.
	 *
	 * @param string $key
	 * @return void
	 */
	public function remove($key)
	{
		$this->provider->delete($key);
	}



	/**
	 * Removes items from the cache by conditions.
	 *
	 * @param array $conditions
	 * @return void
	 */
	public function clean(array $conditions)
	{
		if (!isset($conditions[Nette\Caching\Cache::ALL])) {
			throw new NotImplementedException;
		}

		$this->provider->deleteAll();
	}

}
