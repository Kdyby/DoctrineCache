<?php

declare(strict_types = 1);

/**
 * Test: Kdyby\Doctrine\DI\Helpers.
 *
 * @testCase
 */

namespace KdybyTests\DoctrineCache;

use Kdyby\DoctrineCache\DI\Helpers as DICacheHelpers;
use Nette\DI\Definitions\Statement;
use SplFileInfo;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class HelpersTest extends \Tester\TestCase
{

	/**
	 * @return array|mixed[]
	 */
	public function dataFilterArgs(): array
	{
		return [
			[[new Statement(SplFileInfo::class)], SplFileInfo::class],
			[[[SplFileInfo::class]], [SplFileInfo::class]],

			// BC
			[
				[new Statement(SplFileInfo::class)],
				(object) ['value' => SplFileInfo::class, 'attributes' => []],
			],
			[
				[[new Statement(SplFileInfo::class)]],
				[(object) ['value' => SplFileInfo::class, 'attributes' => []]],
			],
			[
				[[new Statement(SplFileInfo::class, [__FILE__])]],
				[(object) ['value' => SplFileInfo::class, 'attributes' => [__FILE__]]],
			],
			[
				[new Statement(SplFileInfo::class, [1 => __FILE__])],
				(object) ['value' => SplFileInfo::class, 'attributes' => ['...', __FILE__]],
			],
			[
				[new Statement(SplFileInfo::class, [1 => new Statement(SplFileInfo::class)])],
				(object) [
					'value' => SplFileInfo::class,
					'attributes' => [
						'...',
						(object) ['value' => SplFileInfo::class, 'attributes' => []],
					],
				],
			],

			// after https://github.com/nette/di/commit/7e4236c896621e910730375094adf79eb6ef6ea4
			[
				[new Statement(SplFileInfo::class)],
				new Statement(SplFileInfo::class),
			],
			[
				[new Statement(SplFileInfo::class, [__FILE__])],
				new Statement(SplFileInfo::class, [__FILE__]),
			],
			[
				[new Statement(SplFileInfo::class, [1 => __FILE__])],
				new Statement(SplFileInfo::class, ['...', __FILE__]),
			],
			[
				[new Statement(SplFileInfo::class)],
				new Statement(SplFileInfo::class),
			],
			[
				[new Statement(SplFileInfo::class, [__FILE__, new Statement(SplFileInfo::class, [1 => __FILE__])])],
				new Statement(SplFileInfo::class, [__FILE__, new Statement(SplFileInfo::class, ['...', __FILE__])]),
			],

			// single attribute does not have to be an array
			[
				[new Statement(SplFileInfo::class, [__FILE__])],
				(object) ['value' => SplFileInfo::class, 'attributes' => __FILE__],
			],
		];
	}

	/**
	 * @param mixed[] $expected
	 * @param mixed $args
	 * @dataProvider dataFilterArgs
	 */
	public function testFilterArgs(array $expected, $args): void
	{
		Assert::equal($expected, DICacheHelpers::filterArgs($args));
	}

}

(new HelpersTest())->run();
