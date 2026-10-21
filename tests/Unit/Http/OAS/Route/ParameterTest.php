<?php

declare(strict_types=1);

namespace Projom\Tests\Unit\Http\OAS\Route;

use RuntimeException;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use Projom\Http\OAS\Route\Parameter;
use Projom\Http\Router\ParameterType;

class ParameterTest extends TestCase
{
	public static function supportedTypeProvider(): array
	{
		return [
			['integer', ParameterType::INTEGER],
			['string', ParameterType::STRING],
			['boolean', ParameterType::BOOL],
		];
	}

	#[Test]
	#[DataProvider('supportedTypeProvider')]
	public function toParameterTypeMapsSupportedTypes(string $oasType, ParameterType $expected): void
	{
		$this->assertSame($expected, Parameter::toParameterType($oasType));
	}

	public static function unsupportedTypeProvider(): array
	{
		return [
			['number'],
			['array'],
			['object'],
			[''],
		];
	}

	#[Test]
	#[DataProvider('unsupportedTypeProvider')]
	public function toParameterTypeThrowsOnUnsupportedTypes(string $oasType): void
	{
		$this->expectException(RuntimeException::class);
		Parameter::toParameterType($oasType);
	}
}
