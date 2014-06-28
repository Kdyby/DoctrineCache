<?php

/**
 * Test: Kdyby\Doctrine\DI\Helpers.
 *
 * @testCase Kdyby\Doctrine\HelpersTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class HelpersTest extends Tester\TestCase
{

	public function dataFilterArgs()
	{
		return array(
			array(array(new Nette\DI\Statement('SplFileInfo')), 'SplFileInfo'),
			array(array(array('SplFileInfo')), array('SplFileInfo')),

			array(
				array(new Nette\DI\Statement('SplFileInfo')),
				(object) array('value' => 'SplFileInfo', 'attributes' => array())
			),
			array(
				array(array(new Nette\DI\Statement('SplFileInfo'))),
				array((object)array('value' => 'SplFileInfo', 'attributes' => array()))
			),
			array(
				array(array(new Nette\DI\Statement('SplFileInfo', array(__FILE__)))),
				array((object)array('value' => 'SplFileInfo', 'attributes' => array(__FILE__)))
			),
			array(
				array(new Nette\DI\Statement('SplFileInfo', array(1 => __FILE__))),
				(object) array('value' => 'SplFileInfo', 'attributes' => array('...', __FILE__))
			),
			array(
				array(new Nette\DI\Statement('SplFileInfo', array(1 => new Nette\DI\Statement('SplFileInfo')))),
				(object) array('value' => 'SplFileInfo', 'attributes' => array(
					'...',
					(object) array('value' => 'SplFileInfo', 'attributes' => array())
				))
			),
			array( // single attribute does not have to be an array
				array(new Nette\DI\Statement('SplFileInfo', array(__FILE__))),
				(object) array('value' => 'SplFileInfo', 'attributes' => __FILE__)
			),
		);
	}



	/**
	 * @dataProvider dataFilterArgs
	 */
	public function testFilterArgs($expected, $args)
	{
		Assert::equal($expected, Kdyby\DoctrineCache\DI\Helpers::filterArgs($args));
	}

}

\run(new HelpersTest());
