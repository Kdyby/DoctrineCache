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
		return [
			[[new Nette\DI\Statement(\SplFileInfo::class)], \SplFileInfo::class],
			[[[\SplFileInfo::class]], [\SplFileInfo::class]],

			// BC
			[
				[new Nette\DI\Statement(\SplFileInfo::class)],
				(object) ['value' => \SplFileInfo::class, 'attributes' => []]
			],
			[
				[[new Nette\DI\Statement(\SplFileInfo::class)]],
				[(object)['value' => \SplFileInfo::class, 'attributes' => []]]
			],
			[
				[[new Nette\DI\Statement(\SplFileInfo::class, [__FILE__])]],
				[(object)['value' => \SplFileInfo::class, 'attributes' => [__FILE__]]]
			],
			[
				[new Nette\DI\Statement(\SplFileInfo::class, [1 => __FILE__])],
				(object) ['value' => \SplFileInfo::class, 'attributes' => ['...', __FILE__]]
			],
			[
				[new Nette\DI\Statement(\SplFileInfo::class, [1 => new Nette\DI\Statement(\SplFileInfo::class)])],
				(object) ['value' => \SplFileInfo::class, 'attributes' => [
					'...',
					(object) ['value' => \SplFileInfo::class, 'attributes' => []]
				]]
			],

			// after https://github.com/nette/di/commit/7e4236c896621e910730375094adf79eb6ef6ea4
			[
				[new Nette\DI\Statement(\SplFileInfo::class)],
				new Nette\DI\Statement(\SplFileInfo::class)
			],
			[
				[new Nette\DI\Statement(\SplFileInfo::class, [__FILE__])],
				new Nette\DI\Statement(\SplFileInfo::class, [__FILE__])
			],
			[
				[new Nette\DI\Statement(\SplFileInfo::class, [1 => __FILE__])],
				new Nette\DI\Statement(\SplFileInfo::class, ['...', __FILE__])
			],
			[
				[new Nette\DI\Statement(\SplFileInfo::class)],
				new Nette\DI\Statement(\SplFileInfo::class)
			],
			[
				[new Nette\DI\Statement(\SplFileInfo::class, [__FILE__, new Nette\DI\Statement(\SplFileInfo::class, [1 => __FILE__])])],
				new Nette\DI\Statement(\SplFileInfo::class, [__FILE__, new Nette\DI\Statement(\SplFileInfo::class, ['...', __FILE__])])
			],

			// single attribute does not have to be an array
			[
				[new Nette\DI\Statement(\SplFileInfo::class, [__FILE__])],
				(object) ['value' => \SplFileInfo::class, 'attributes' => __FILE__]
			],
		];
	}



	/**
	 * @dataProvider dataFilterArgs
	 */
	public function testFilterArgs($expected, $args)
	{
		Assert::equal($expected, Kdyby\DoctrineCache\DI\Helpers::filterArgs($args));
	}

}

(new HelpersTest())->run();
