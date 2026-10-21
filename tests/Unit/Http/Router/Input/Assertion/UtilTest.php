<?php

declare(strict_types=1);

namespace Projom\Tests\Unit\Http\Router\Input\Assertion;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use Projom\Http\Router\Input\Assertion\Util;
use Projom\Http\Router\ParameterType;

class UtilTest extends TestCase
{
	public static function verifyProvider(): array
	{
		return [
			'int' => [ParameterType::INT, '-12', true],
			'int with leading junk' => [ParameterType::INT, 'abc1', false],
			'int with trailing junk' => [ParameterType::INT, '1abc', false],
			'int with trailing newline' => [ParameterType::INT, "1\n", false],
			'numeric id' => [ParameterType::NUMERIC_ID, '16', true],
			'numeric id with leading zero' => [ParameterType::NUMERIC_ID, '016', false],
			'bool' => [ParameterType::BOOL, 'true', true],
			'bool with trailing junk' => [ParameterType::BOOL, 'trueish', false],
			'name' => [ParameterType::NAME, 'Jane Doe', true],
			'name with forbidden character' => [ParameterType::NAME, 'Jane/Doe', false],
			'string' => [ParameterType::STRING, 'any value', true],
		];
	}

	#[Test]
	#[DataProvider('verifyProvider')]
	public function verifyMatchesTheWholeValue(ParameterType $type, string $value, bool $expected): void
	{
		$definitions = ['page' => ['name' => 'page', 'type' => $type, 'required' => true]];
		$this->assertSame($expected, Util::verify(['page' => $value], $definitions));
	}
}
