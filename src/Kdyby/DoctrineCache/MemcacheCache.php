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
use Memcache;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class MemcacheCache extends Doctrine\Common\Cache\MemcacheCache
{

	public function __construct(Memcache $memcache = NULL)
	{
		$this->setMemcache($memcache);
	}

}
