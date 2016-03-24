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
use Nette\Reflection\ClassType;
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
	 * @param $id
	 * @return bool
	 */
	protected function doFetch($id)
	{
		$cached = $this->cache->load($id);
		return $cached === NULL ? FALSE : $cached;
	}



	/**
	 * @param $id
	 * @return bool
	 */
	protected function doContains($id)
	{
		return $this->cache->load($id) !== NULL;
	}



	/**
	 * @param $id
	 * @param $data
	 * @param int $lifeTime
	 * @return bool
	 */
	protected function doSave($id, $data, $lifeTime = 0)
	{
		if ($this->debug !== TRUE) {
			return $this->doSaveDependingOnFiles($id, $data, array(), $lifeTime);
		}

		$files = array();
		if ($data instanceof Doctrine\ORM\Mapping\ClassMetadata) {
			$files[] = ClassType::from($data->name)->getFileName();
			foreach ($data->parentClasses as $class) {
				$files[] = ClassType::from($class)->getFileName();
			}
		}
		if ($data instanceof \Symfony\Component\Validator\Mapping\ClassMetadata) {
			$files[] = ClassType::from($data->name)->getFileName();
		}

		if (!empty($data)){
			if (($m = Strings::match($id, '~(?P<class>[^@$[\].]+)(?:\$(?P<prop>[^@$[\].]+))?\@\[Annot\]~i')) && class_exists($m['class'])) {
				$files[] = ClassType::from($m['class'])->getFileName();
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
		$dp = array(NCache::TAGS => array('doctrine'), NCache::FILES => $files);
		if ($lifeTime != 0) {
			$dp[NCache::EXPIRE] = time() + $lifeTime;
		}

		$this->cache->save($id, $data, $dp);

		return TRUE;
	}



	/**
	 * @param $id
	 * @return bool
	 */
	protected function doDelete($id)
	{
		$this->cache->save($id, NULL);

		return TRUE;
	}



	protected function doFlush()
	{
		$this->cache->clean(array(
			NCache::TAGS => array('doctrine')
		));
	}



	/**
	 * @return NULL
	 */
	protected function doGetStats()
	{
		return array(
			self::STATS_HITS => NULL,
			self::STATS_MISSES => NULL,
			self::STATS_UPTIME => NULL,
			self::STATS_MEMORY_USAGE => NULL,
			self::STATS_MEMORY_AVAILABLE => NULL,
		);
	}

}
