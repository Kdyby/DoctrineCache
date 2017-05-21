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
use Doctrine\ORM\Mapping\ClassMetadata as DoctrineClassMetadata;
use Kdyby;
use Nette;
use Nette\Caching\Cache as NCache;
use Nette\Caching\IStorage;
use Nette\Utils\Strings;
use ReflectionClass;
use Symfony\Component\Validator\Mapping\ClassMetadata as SymfonyClassMetadata;

/**
 * Nette cache driver for doctrine
 */
class Cache extends \Doctrine\Common\Cache\CacheProvider
{

	use \Kdyby\StrictObjects\Scream;

	const CACHE_NS = 'Doctrine';

	/**
	 * @var \Nette\Caching\Cache
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
	public function __construct(IStorage $storage, $namespace = self::CACHE_NS, $debugMode = FALSE)
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
		if ($data instanceof DoctrineClassMetadata) {
			$files[] = self::getClassFilename($data->name);
			foreach ($data->parentClasses as $class) {
				$files[] = self::getClassFilename($class);
			}
		}
		if ($data instanceof SymfonyClassMetadata) {
			$files[] = self::getClassFilename($data->name);
		}

		if (!empty($data)) {
			$m = Strings::match($id, '~(?P<class>[^@$[\].]+)(?:\$(?P<prop>[^@$[\].]+))?\@\[Annot\]~i');
			if ($m !== NULL && class_exists($m['class'])) {
				$files[] = self::getClassFilename($m['class']);
			}
		}

		return $this->doSaveDependingOnFiles($id, $data, $files, $lifeTime);
	}

	/**
	 * @param string $id
	 * @param mixed $data
	 * @param array $files
	 * @param int $lifeTime
	 * @return bool
	 */
	protected function doSaveDependingOnFiles($id, $data, array $files, $lifeTime = 0)
	{
		$dp = [NCache::TAGS => ['doctrine'], NCache::FILES => $files];
		if ($lifeTime !== 0) {
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
			NCache::TAGS => ['doctrine'],
		]);

		return TRUE;
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
		$reflection = new ReflectionClass($className);
		return $reflection->getFileName();
	}

}
