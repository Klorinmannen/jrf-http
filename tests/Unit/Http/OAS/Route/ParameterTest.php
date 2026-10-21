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

	private static function oasParameter(string $name, string $in, string $type, null|bool $required = null): array
	{
		$parameter = ['name' => $name, 'in' => $in, 'schema' => ['type' => $type]];
		if ($required !== null)
			$parameter['required'] = $required;
		return $parameter;
	}

	#[Test]
	public function normalizeSplitsQueryParametersOnRequired(): void
	{
		$operationLevel = [
			static::oasParameter('page', 'query', 'integer', required: true),
			static::oasParameter('sort', 'query', 'string', required: false),
			static::oasParameter('lang', 'query', 'string'),
		];

		$normalized = Parameter::normalize($operationLevel);

		$this->assertSame(['page' => ParameterType::INTEGER], $normalized[Parameter::REQUIRED_QUERY]);
		$this->assertSame(
			['sort' => ParameterType::STRING, 'lang' => ParameterType::STRING],
			$normalized[Parameter::OPTIONAL_QUERY]
		);
		$this->assertSame([], $normalized[Parameter::PATH]);
	}

	#[Test]
	public function normalizeCollectsPathParameters(): void
	{
		$operationLevel = [static::oasParameter('recipe_id', 'path', 'integer', required: true)];

		$normalized = Parameter::normalize($operationLevel);

		$this->assertSame(['recipe_id' => ParameterType::INTEGER], $normalized[Parameter::PATH]);
	}

	#[Test]
	public function normalizeIgnoresHeaderAndCookieParameters(): void
	{
		$operationLevel = [
			static::oasParameter('X-Trace', 'header', 'string'),
			static::oasParameter('session', 'cookie', 'string'),
		];

		$normalized = Parameter::normalize($operationLevel);

		$this->assertSame([], $normalized[Parameter::PATH]);
		$this->assertSame([], $normalized[Parameter::REQUIRED_QUERY]);
		$this->assertSame([], $normalized[Parameter::OPTIONAL_QUERY]);
	}

	#[Test]
	public function normalizeMergesPathLevelParametersWithOperationLevelWinning(): void
	{
		$pathLevel = [
			static::oasParameter('recipe_id', 'path', 'string', required: true),
			static::oasParameter('lang', 'query', 'string'),
		];
		$operationLevel = [static::oasParameter('recipe_id', 'path', 'integer', required: true)];

		$normalized = Parameter::normalize($operationLevel, $pathLevel);

		$this->assertSame(['recipe_id' => ParameterType::INTEGER], $normalized[Parameter::PATH]);
		$this->assertSame(['lang' => ParameterType::STRING], $normalized[Parameter::OPTIONAL_QUERY]);
	}

	#[Test]
	public function normalizeThrowsOnUnsupportedType(): void
	{
		$this->expectException(RuntimeException::class);
		Parameter::normalize([static::oasParameter('price', 'query', 'number')]);
	}

	#[Test]
	public function normalizeThrowsWhenNameOrLocationIsMissing(): void
	{
		$this->expectException(RuntimeException::class);
		Parameter::normalize([['schema' => ['type' => 'string']]]);
	}

	#[Test]
	public function normalizeThrowsOnUnknownLocation(): void
	{
		$this->expectException(RuntimeException::class);
		Parameter::normalize([static::oasParameter('page', 'body', 'integer')]);
	}
}
