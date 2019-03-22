<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\DoctrineCache\Exception;

class NotImplementedException extends \LogicException implements \Kdyby\DoctrineCache\Exception
{

}

class_alias(\Kdyby\DoctrineCache\Exception\NotImplementedException::class, '\Kdyby\DoctrineCache\NotImplementedException');
