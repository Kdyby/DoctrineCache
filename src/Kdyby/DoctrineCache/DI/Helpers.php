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
use Nette\DI\Statement;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Helpers extends Nette\Object
{

	/**
	 * @var array
	 */
	public static $cacheDriverClasses = [
		'default' => 'Kdyby\DoctrineCache\Cache',
		'apc' => 'Doctrine\Common\Cache\ApcCache',
		'apcu' => 'Doctrine\Common\Cache\ApcuCache',
		'array' => 'Doctrine\Common\Cache\ArrayCache',
		'filesystem' => 'Doctrine\Common\Cache\FilesystemCache',
		'memcache' => 'Kdyby\DoctrineCache\MemcacheCache',
		'redis' => 'Kdyby\DoctrineCache\RedisCache',
		'void' => 'Doctrine\Common\Cache\VoidCache',
		'xcache' => 'Doctrine\Common\Cache\XcacheCache',
	];



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

		$impl = $cache instanceof \stdClass ? $cache->value : ($cache instanceof Statement ? $cache->getEntity() : (string) $cache);
		list($cache) = self::filterArgs($cache);
		/** @var Statement $cache */

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
			->setClass('Doctrine\Common\Cache\Cache')
			->setFactory($cache->getEntity(), $cache->arguments)
			->setAutowired(FALSE);

		if (method_exists($def, 'setInject')) {
			@$def->setInject(FALSE); // wow, such deprecated, many BC!
		}

		if (class_exists($cache->getEntity()) && is_subclass_of($cache->getEntity(), 'Doctrine\Common\Cache\CacheProvider')) {
			$ns = 'Kdyby_' . $serviceName;

			if (preg_match('~^(?P<projectRoot>.+)(?:\\\\|\\/)vendor(?:\\\\|\\/)kdyby(?:\\\\|\\/)doctrine-cache(?:\\\\|\\/).+\\z~i', __DIR__, $m)) {
				$ns .= '_' . substr(md5($m['projectRoot']), 0, 8);
			}

			$def->addSetup('setNamespace', [$ns]);
		}

		return '@' . $serviceName;
	}



	/**
	 * @param string|\stdClass $statement
	 * @return Statement[]
	 */
	public static function filterArgs($statement)
	{
		return self::doFilterArguments([is_string($statement) ? new Statement($statement) : $statement]);
	}



	/**
	 * Removes ... recursively.
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

			} elseif ($v instanceof \stdClass && isset($v->value, $v->attributes)) {
				$tmp = self::doFilterArguments([$v->value]);
				$args[$k] = new Statement($tmp[0], self::doFilterArguments(is_array($v->attributes) ? $v->attributes : [$v->attributes]));
			}
		}

		return $args;
	}

}
