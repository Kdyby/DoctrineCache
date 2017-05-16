<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\DoctrineCache;

use Doctrine;
use Kdyby;
use Nette;
use Nette\Caching\Cache AS NCache;
use Nette\Utils\Strings;



/**
 * Nette cache driver for doctrine
 *
 * @author Patrik Votoček (http://patrik.votocek.cz)
 * @author Filip Procházka <filip@prochazka.su>
 */
class Cache extends Doctrine\Common\Cache\CacheProvider
{

	const CACHE_NS = 'Doctrine';

	/**
	 * @var NCache
	 */
	private $cache;

	/**
	 * @var bool
	 */
	private $debug = FALSE;



	/**
	 * @param \Nette\Caching\IStorage $storage
	 * @param string $namespace
	 * @param bool $debugMode
	 */
	public function __construct(Nette\Caching\IStorage $storage, $namespace = self::CACHE_NS, $debugMode = FALSE)
	{
		$this->cache = new NCache($storage, $namespace);
		$this->debug = $debugMode;
	}



	/**
	 * {@inheritdoc}
	 */
	protected function doFetch($id)
	{
		$cached = $this->cache->load($id);
		return $cached === NULL ? FALSE : $cached;
	}



	/**
	 * {@inheritdoc}
	 */
	protected function doContains($id)
	{
		return $this->cache->load($id) !== NULL;
	}



	/**
	 * {@inheritdoc}
	 */
	protected function doSave($id, $data, $lifeTime = 0)
	{
		if ($this->debug !== TRUE) {
			return $this->doSaveDependingOnFiles($id, $data, [], $lifeTime);
		}

		$files = [];
		if ($data instanceof Doctrine\ORM\Mapping\ClassMetadata) {
			$files[] = self::getClassFilename($data->name);
			foreach ($data->parentClasses as $class) {
				$files[] = self::getClassFilename($class);
			}
		}
		if ($data instanceof \Symfony\Component\Validator\Mapping\ClassMetadata) {
			$files[] = self::getClassFilename($data->name);
		}

		if (!empty($data)){
			if (($m = Strings::match($id, '~(?P<class>[^@$[\].]+)(?:\$(?P<prop>[^@$[\].]+))?\@\[Annot\]~i')) && class_exists($m['class'])) {
				$files[] = self::getClassFilename($m['class']);
			}
		}

		return $this->doSaveDependingOnFiles($id, $data, $files, $lifeTime);
	}



	/**
	 * @param string $id
	 * @param mixed $data
	 * @param array $files
	 * @param integer $lifeTime
	 * @return boolean
	 */
	protected function doSaveDependingOnFiles($id, $data, array $files, $lifeTime = 0)
	{
		$dp = [NCache::TAGS => ['doctrine'], NCache::FILES => $files];
		if ($lifeTime != 0) {
			$dp[NCache::EXPIRE] = time() + $lifeTime;
		}

		$this->cache->save($id, $data, $dp);

		return TRUE;
	}



	/**
	 * {@inheritdoc}
	 */
	protected function doDelete($id)
	{
		$this->cache->save($id, NULL);

		return TRUE;
	}



	/**
	 * {@inheritdoc}
	 */
	protected function doFlush()
	{
		$this->cache->clean([
			NCache::TAGS => ['doctrine']
		]);

		return true;
	}



	/**
	 * {@inheritdoc}
	 */
	protected function doGetStats()
	{
		return [
			self::STATS_HITS => NULL,
			self::STATS_MISSES => NULL,
			self::STATS_UPTIME => NULL,
			self::STATS_MEMORY_USAGE => NULL,
			self::STATS_MEMORY_AVAILABLE => NULL,
		];
	}



	/**
	 * @param string $className
	 * @return string
	 */
	private static function getClassFilename($className)
	{
		$reflection = new \ReflectionClass($className);
		return $reflection->getFileName();
	}

}
