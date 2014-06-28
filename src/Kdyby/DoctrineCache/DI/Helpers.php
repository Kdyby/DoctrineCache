<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\DoctrineCache\DI;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Helpers extends Nette\Object
{

	/**
	 * @var array
	 */
	public static $cacheDriverClasses = array(
		'default' => 'Kdyby\DoctrineCache\Cache',
		'apc' => 'Doctrine\Common\Cache\ApcCache',
		'array' => 'Doctrine\Common\Cache\ArrayCache',
		'memcache' => 'Kdyby\DoctrineCache\MemcacheCache',
		'redis' => 'Doctrine\Common\Cache\RedisCache',
		'xcache' => 'Doctrine\Common\Cache\XcacheCache',
	);



	/**
	 * @param \Nette\DI\CompilerExtension $extension
	 * @param string|\stdClass $cache
	 * @param string $suffix
	 * @param bool $debug
	 * @return string
	 */
	public static function processCache(Nette\DI\CompilerExtension $extension, $cache, $suffix, $debug = NULL)
	{
		$builder = $extension->getContainerBuilder();

		$impl = $cache instanceof \stdClass ? $cache->value : ($cache instanceof Nette\DI\Statement ? $cache->entity : (string) $cache);
		list($cache) = self::filterArgs($cache);
		/** @var Nette\DI\Statement $cache */

		if (isset(self::$cacheDriverClasses[$impl])) {
			$cache->entity = self::$cacheDriverClasses[$impl];
		}

		if ($impl === 'default') {
			$cache->arguments[1] = 'Doctrine.' . ucfirst($suffix);
		}

		$def = $builder->addDefinition($serviceName = $extension->prefix('cache.' . $suffix))
			->setClass('Doctrine\Common\Cache\Cache')
			->setFactory($cache->entity, $cache->arguments)
			->setAutowired(FALSE)
			->setInject(FALSE);

		if ($impl === 'default') {
			if ($debug === NULL) {
				$debug = $builder->parameters['debugMode'];
			}

			$def->factory->arguments[2] = $debug;
		}

		return '@' . $serviceName;
	}



	/**
	 * @param string|\stdClass $statement
	 * @return Nette\DI\Statement[]
	 */
	public static function filterArgs($statement)
	{
		return self::doFilterArguments(array(is_string($statement) ? new Nette\DI\Statement($statement) : $statement));
	}



	/**
	 * Removes ... and replaces entities with Statement.
	 * @return array
	 */
	private static function doFilterArguments(array $args)
	{
		foreach ($args as $k => $v) {
			if ($v === '...') {
				unset($args[$k]);

			} elseif (is_array($v)) {
				$args[$k] = self::doFilterArguments($v);

			} elseif ($v instanceof \stdClass && isset($v->value, $v->attributes)) {
				$tmp = self::doFilterArguments(array($v->value));
				$args[$k] = new Nette\DI\Statement($tmp[0], self::doFilterArguments(is_array($v->attributes) ? $v->attributes : array($v->attributes)));
			}
		}

		return $args;
	}

}
