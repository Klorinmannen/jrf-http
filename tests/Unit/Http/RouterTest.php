<?php

declare(strict_types=1);

namespace JRF\Tests\Unit;

use Closure;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use JRF\Http\Controller;
use JRF\Http\Request;
use JRF\Http\Request\Input;
use JRF\Http\Router;
use JRF\Http\Router\DispatcherInterface;
use JRF\Http\Router\ParameterType;
use JRF\Http\Router\RouteInterface;
use JRF\Http\Router\Route\Action;

class InvoiceController extends Controller
{
	public function get(): void
	{
		// Simulate processing of get action - do nothing.

	}

	public function post(): void
	{
		// Simulate processing of post action - do nothing.
	}
}

class RouterTest extends TestCase
{
	public static function routeDataProvider(): array
	{
		return [
			[
				'/invoices',
				Input::create(
					server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/invoices?page=1'],
				),
				function (RouteInterface $route) {
					$route->get()
						->requiredQueryParameters(['page' => ParameterType::INT])
						->optionalQueryParameters(['limit' => ParameterType::INT]);
				},
				'GET'
			],
			[
				'/invoices/{numeric_id:invoice_id}/lines',
				Input::create(
					server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/invoices/16/lines?page=1&limit=10&sort=asc'],
				),
				function (RouteInterface $route) {
					$route->get()
						->requiredQueryParameters(['page,limit' => ParameterType::INT])
						->optionalQueryParameters(['sort,lang' => ParameterType::STR]);
				},
				'GET'
			],
			[
				'/invoices/{numeric_id:invoice_id}/lines',
				Input::create(
					server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/invoices/16/lines?page=1&limit=10'],
				),
				function (RouteInterface $route) {
					$route->get()
						->requiredQueryParameters(['sort,lang' => ParameterType::STR]) // This will be overridden by the mandatoryQueryParameters call.
						->mandatoryQueryParameters(['page,limit' => ParameterType::INT]);
				},
				'GET'
			],
			[
				'/invoices/{numeric_id:invoice_id}/lines/{numeric_id:invoice_line_id}',
				Input::create(
					server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/invoices/16/lines/446?lang=en'],
				),
				function (RouteInterface $route) {
					$route->get()->requiredQueryParameters(['lang' => ParameterType::STR]);
				},
				'GET'
			]
		];
	}

	#[Test]
	#[DataProvider('routeDataProvider')]
	public function route(string $requestPath, Input $input, Closure $closure, string $expectedMethod): void
	{
		$router = new Router();
		$router->addRoute($requestPath, InvoiceController::class, $closure);
		[$controller, $controllerMethod] = $router->route(Request::create($input));

		$this->assertEquals(InvoiceController::class, $controller);
		$this->assertEquals($expectedMethod, $controllerMethod);
	}

	#[Test]
	public function routeMatchesOASRoutesNextToDefinedRoutes(): void
	{
		$oasFile = tempnam(sys_get_temp_dir(), 'oas') . '.json';
		$oas = [
			'paths' => [
				'/recipes/{recipe_id}' => [
					'parameters' => [
						['name' => 'recipe_id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
					],
					'get' => [
						'operationId' => 'Recipe_Controller@getRecipe',
						'parameters' => [
							['name' => 'page', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'integer']]
						]
					]
				]
			]
		];
		file_put_contents($oasFile, json_encode($oas));

		$router = new Router();
		$router->addRoute('/invoices', InvoiceController::class, function (RouteInterface $route) {
			$route->get();
		});
		try {
			$router->addOASRoutes($oasFile);
		} finally {
			@unlink($oasFile);
		}

		$oasAction = $router->route(Request::create(
			Input::create(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/recipes/5?page=1'])
		));
		$definedAction = $router->route(Request::create(
			Input::create(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/invoices'])
		));

		$this->assertSame(['Recipe\Controller', 'getRecipe'], $oasAction);
		$this->assertSame([InvoiceController::class, 'GET'], $definedAction);
	}

	#[Test]
	public function dispatch(): void
	{
		$this->expectNotToPerformAssertions();

		$dispatcher = new class implements DispatcherInterface {
			public function processAction(Action $action, Request $request): void
			{
				// Simulate processing of the action and request - do nothing.
			}
		};
		$router = new Router($dispatcher);

		$router->addRoute('/users', InvoiceController::class, function (RouteInterface $route) {
			$route->get()->optionalQueryParameters(['page' => 'integer', 'limit' => 'integer']);
		});

		$request = Request::create(
			Input::create(
				server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/users?page=1&limit=10'],
			)
		);
		$router->dispatch($request);
	}
}
