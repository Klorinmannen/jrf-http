# JRF http module
[![PHP version support][php-version-badge]][php-version]
[![CI][ci-badge]][workflow-actions]
[![PHPUnit][phpunit-coverage-badge]][workflow-actions]

[php-version-badge]: https://img.shields.io/badge/php-%5E8.2-7A86B8
[php-version]: https://www.php.net/supported-versions.php
[ci-badge]: https://github.com/Klorinmannen/jrf-http/workflows/CI/badge.svg
[workflow-actions]: https://github.com/Klorinmannen/jrf-http/actions
[phpunit-coverage-badge]: ./phpunit-coverage-badge.svg

### Project goals
* Routing & dispatching.
* Support a selective scope of OAS 3.0.
* Lightweight, no dependencies.

### Example usage
````
use JRF\Http\Router;
use JRF\Http\Router\RouteInterface;
use JRF\Http\Router\ParameterType;

use Recipe\Controller as RecipeController;
use Recipe\Ingredient\Controller as RecipeIngredientController;

$router = new Router();

$router->addRoute('/', 
	RootController::class, 
	function (RouteInterface $route) {
		$route->get();
	}
);

$router->addRoute(
	'/recipes', 
	RecipeController::class, 
	function (RouteInterface $route) {
		
		$route->get()
			->optionalQueryParameters(['sort' => ParameterType::STR])
			->requiredQueryParameters(['page' => ParameterType::INT, 
									   'limit' => ParameterType::INT]);
		
		$route->post()->requiredPayload();
	}
);

$router->addRoute(
	'/recipes/{numeric_id:recipe_id}',
	RecipeController::class, 
	function (RouteInterface $route) {

		$route->get('getRecipe');
		$route->patch('patchRecipe')->requiredPayload();
	}
);

$router->addRoute(
	'/recipes/{numeric_id:recipe_id}/ingredients',
	RecipeIngredientController::class, 
	function (RouteInterface $route) {
		
		$route->get()
			->optionalQueryParameters(['sort' => ParameterType::STR])
			->requiredQueryParameters(['page' => ParameterType::INT, 
									   'limit' => ParameterType::INT]);
		
		$route->post()->requiredPayload();
	}
);

$router->addRoute(
	'/recipes/{numeric_id:recipe_id}/ingredients/{numeric_id:ingredient_id}',
	RecipeIngredientController::class, 
	function (RouteInterface $route) {
		$route->get('getIngredient');
		$route->patch('patchIngredient')->requiredPayload();
	}
);

$router->dispatch();
````

### OAS routes
Routes can also be loaded from an OAS 3.0 file (json, or yaml with the `yaml` extension).
````
$router = new Router();
$router->addOASRoutes(__DIR__ . '/openapi.json');
$router->dispatch();
````
* `operationId` names the controller and method: `Recipe_Controller@getRecipe` dispatches to `Recipe\Controller::getRecipe`. Without `@method`, the HTTP method is used.
* Path and query parameters of type `integer`, `string` and `boolean` are verified. Header and cookie parameters are not.
* `requestBody.required` is verified. The payload content is not.
* External `$ref`s to path items (`paths.json#/recipe`) are supported. Local `$ref`s (`#/components/...`) are not.
