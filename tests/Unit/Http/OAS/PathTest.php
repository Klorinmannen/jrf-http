<?php

declare(strict_types=1);

namespace JRF\Tests\Unit\Http\OAS;

use RuntimeException;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use JRF\Http\OAS\Path;
use JRF\Http\Router\ParameterType;
use JRF\Http\Router\Route\Path as RouterPath;

class PathTest extends TestCase
{
	public static function translateProvider(): array
	{
		return [
			'static path' => [
				'/recipes',
				[],
				'/recipes'
			],
			'one parameter' => [
				'/recipes/{recipe_id}',
				['recipe_id' => ParameterType::INTEGER],
				'/recipes/{integer:recipe_id}'
			],
			'two parameters' => [
				'/recipes/{recipe_id}/ingredients/{ingredient_name}',
				['recipe_id' => ParameterType::INTEGER, 'ingredient_name' => ParameterType::STRING],
				'/recipes/{integer:recipe_id}/ingredients/{string:ingredient_name}'
			],
		];
	}

	#[Test]
	#[DataProvider('translateProvider')]
	public function translateRewritesTemplatesToRouterSyntax(string $oasPath, array $types, string $expected): void
	{
		$this->assertSame($expected, Path::translate($oasPath, $types));
	}

	#[Test]
	public function translatedPathMatchesRequestPath(): void
	{
		$routerPath = RouterPath::create(Path::translate('/recipes/{recipe_id}', ['recipe_id' => ParameterType::INTEGER]));

		$this->assertSame([true, ['recipe_id' => '5']], $routerPath->test('/recipes/5'));
		$this->assertSame([false, []], $routerPath->test('/recipes/five'));
	}

	#[Test]
	public function translateThrowsOnUndeclaredParameter(): void
	{
		$this->expectException(RuntimeException::class);
		Path::translate('/recipes/{recipe_id}', []);
	}
}
