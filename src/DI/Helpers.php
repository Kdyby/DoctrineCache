<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\DoctrineCache\DI;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\VoidCache;
use Doctrine\Common\Cache\XcacheCache;
use Kdyby\DoctrineCache\Cache as NetteCacheAdapter;
use Kdyby\DoctrineCache\MemcacheCache;
use Kdyby\DoctrineCache\MemcachedCache;
use Kdyby\DoctrineCache\RedisCache;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use stdClass;

class Helpers
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var string[]
	 */
	public static $cacheDriverClasses = [
		'default' => NetteCacheAdapter::class,
		'apc' => ApcCache::class,
		'apcu' => ApcuCache::class,
		'array' => ArrayCache::class,
		'filesystem' => FilesystemCache::class,
		'memcache' => MemcacheCache::class,
		'memcached' => MemcachedCache::class,
		'redis' => RedisCache::class,
		'void' => VoidCache::class,
		'xcache' => XcacheCache::class,
	];

	/**
	 * @param \Nette\DI\CompilerExtension $extension
	 * @param string|\stdClass|\Nette\DI\Statement $cache
	 * @param string $suffix
	 * @param bool $debug
	 * @return string
	 */
	public static function processCache(CompilerExtension $extension, $cache, $suffix, $debug = NULL)
	{
		$builder = $extension->getContainerBuilder();

		$impl = ($cache instanceof stdClass) ? $cache->value : (($cache instanceof Statement) ? $cache->getEntity() : $cache);
		if (!is_string($impl)) {
			throw new \InvalidArgumentException('Cache implementation cannot be resolved. Pass preferably string or Nette\DI\Statement as $cache argument.');
		}

		/** @var \Nette\DI\Statement $cache */
		list($cache) = self::filterArgs($cache);
		if (isset(self::$cacheDriverClasses[$impl])) {
			$cache = new Statement(self::$cacheDriverClasses[$impl], $cache->arguments);
		}

		if ($impl === 'default') {
			$cache->arguments[1] = 'Doctrine.' . ucfirst($suffix);
			$cache->arguments[2] = $debug !== NULL ? $debug : $builder->parameters['debugMode'];
		}

		if ($impl === 'filesystem') {
			$cache->arguments[] = $builder->parameters['tempDir'] . '/cache/Doctrine.' . ucfirst($suffix);
		}

		$def = $builder->addDefinition($serviceName = $extension->prefix('cache.' . $suffix))
			->setClass(Cache::class)
			->setFactory($cache->getEntity(), $cache->arguments)
			->setAutowired(FALSE);

		if (class_exists($cache->getEntity()) && is_subclass_of($cache->getEntity(), CacheProvider::class)) {
			$ns = 'Kdyby_' . $serviceName;

			if (preg_match('~^(?P<projectRoot>.+)(?:\\\\|\\/)vendor(?:\\\\|\\/)kdyby(?:\\\\|\\/)doctrine-cache(?:\\\\|\\/).+\\z~i', __DIR__, $m)) {
				$ns .= '_' . substr(md5($m['projectRoot']), 0, 8);
			}

			$def->addSetup('setNamespace', [$ns]);
		}

		return '@' . $serviceName;
	}

	/**
	 * @param string|\stdClass|\Nette\DI\Statement $statement
	 * @return \Nette\DI\Statement[]
	 */
	public static function filterArgs($statement)
	{
		return self::doFilterArguments([is_string($statement) ? new Statement($statement) : $statement]);
	}

	/**
	 * Removes ... recursively.
	 *
	 * @return array
	 */
	private static function doFilterArguments(array $args)
	{
		foreach ($args as $k => $v) {
			if ($v === '...') {
				unset($args[$k]);

			} elseif (is_array($v)) {
				$args[$k] = self::doFilterArguments($v);

			} elseif ($v instanceof Statement) {
				$tmp = self::doFilterArguments([$v->getEntity()]);
				$args[$k] = new Statement($tmp[0], self::doFilterArguments($v->arguments));

			} elseif ($v instanceof stdClass && isset($v->value, $v->attributes)) {
				$tmp = self::doFilterArguments([$v->value]);
				$args[$k] = new Statement($tmp[0], self::doFilterArguments(is_array($v->attributes) ? $v->attributes : [$v->attributes]));
			}
		}

		return $args;
	}

}
