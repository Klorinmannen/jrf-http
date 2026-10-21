<?php

declare(strict_types=1);

namespace JRF\Tests\Unit\Http\OAS;

use RuntimeException;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use JRF\Http\OAS\Controller;

class ControllerTest extends TestCase
{
	public static function operationIdProvider(): array
	{
		return [
			['Recipe_Controller@getRecipe', 'Recipe\Controller', 'getRecipe'],
			['Recipe_Controller', 'Recipe\Controller', ''],
			['recipe_controller@getRecipe', 'Recipe\Controller', 'getRecipe'],
			['api_v2_recipeList@get', 'Api\V2\RecipeList', 'get'],
			['API_Recipe@get', 'API\Recipe', 'get'],
		];
	}

	#[Test]
	#[DataProvider('operationIdProvider')]
	public function normalizeSplitsControllerAndMethod(
		string $operationId,
		string $expectedController,
		string $expectedMethod
	): void {
		$expected = ['controller' => $expectedController, 'method' => $expectedMethod];
		$this->assertSame($expected, Controller::normalize($operationId));
	}

	#[Test]
	public function normalizeThrowsOnEmptyOperationId(): void
	{
		$this->expectException(RuntimeException::class);
		Controller::normalize('');
	}
}
