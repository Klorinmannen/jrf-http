<?php

declare(strict_types=1);

namespace Projom\Tests\Unit\Http\OAS\Route;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use Projom\Http\Method;
use Projom\Http\OAS\Route\InputDefinition;
use Projom\Http\Router\ParameterType;
use Projom\Http\Router\Route\Input\Definition;

class InputDefinitionTest extends TestCase
{
	#[Test]
	public function createFromDetailsBuildsOnCoreDefinition(): void
	{
		$definition = InputDefinition::createFromDetails(Method::GET, ['operationId' => 'Recipe_Controller@getRecipe']);
		$this->assertInstanceOf(Definition::class, $definition);
	}

	#[Test]
	public function createFromDetailsFillsQueryDefinitions(): void
	{
		$details = [
			'operationId' => 'Recipe_Controller@getRecipes',
			'parameters' => [
				['name' => 'page', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'integer']],
				['name' => 'sort', 'in' => 'query', 'schema' => ['type' => 'string']],
			]
		];

		$definition = InputDefinition::createFromDetails(Method::GET, $details);

		$this->assertSame(['page' => ParameterType::INTEGER], $definition->requiredQueryParamDefinitions);
		$this->assertSame(['sort' => ParameterType::STRING], $definition->optionalQueryParamDefinitions);
	}

	#[Test]
	public function createFromDetailsCollectsPathParameterTypesFromBothLevels(): void
	{
		$pathLevelParameters = [
			['name' => 'recipe_id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
		];

		$definition = InputDefinition::createFromDetails(
			Method::GET,
			['operationId' => 'Recipe_Controller@getRecipe'],
			$pathLevelParameters
		);

		$this->assertSame(['recipe_id' => ParameterType::INTEGER], $definition->pathParameterTypes());
	}

	#[Test]
	public function createFromDetailsSetsRequiredPayload(): void
	{
		$details = [
			'operationId' => 'Recipe_Controller@postRecipe',
			'requestBody' => ['required' => true, 'content' => ['application/json' => []]]
		];

		$definition = InputDefinition::createFromDetails(Method::POST, $details);

		$this->assertTrue($definition->requiredPayload);
	}

	#[Test]
	public function createFromDetailsResolvesControllerAndMethod(): void
	{
		$definition = InputDefinition::createFromDetails(Method::GET, ['operationId' => 'Recipe_Controller@getRecipe']);

		$this->assertSame('Recipe\Controller', $definition->controller());
		$this->assertSame('getRecipe', $definition->method());
	}

	#[Test]
	public function methodFallsBackToHttpVerbWithoutOperationMethod(): void
	{
		$definition = InputDefinition::createFromDetails(Method::PATCH, ['operationId' => 'Recipe_Controller']);

		$this->assertSame('PATCH', $definition->method());
	}
}
