<?php

declare(strict_types=1);

namespace Projom\Tests\Unit\Http\OAS;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use Projom\Http\Method;
use Projom\Http\OAS\Route;
use Projom\Http\OAS\Route\InputDefinition;
use Projom\Http\Request;
use Projom\Http\Request\Input;

class RouteTest extends TestCase
{
	#[Test]
	public function processCreatesActionFromInputDefinition(): void
	{
		$route = Route::create('/recipes');
		$definition = InputDefinition::createFromDetails(Method::GET, ['operationId' => 'Recipe_Controller@getRecipes']);
		$route->setData(Method::GET, $definition);

		$request = Request::create(
			Input::create(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/recipes'])
		);

		$this->assertTrue($route->match($request));
		$route->process($request);

		$this->assertSame(['Recipe\Controller', 'getRecipes'], $route->action()->get());
		$this->assertSame($definition, $route->inputDefinition());
	}
}
