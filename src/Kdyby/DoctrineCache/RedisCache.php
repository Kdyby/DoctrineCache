<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\DoctrineCache;

use Kdyby;
use Nette;
use Redis;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class RedisCache extends \Doctrine\Common\Cache\RedisCache
{

	public function __construct(Redis $redis = NULL)
	{
		if ($redis !== NULL) {
			$this->setRedis($redis);
		}
	}

}
