<?php

declare(strict_types=1);

namespace JRF\Http\OAS\Route;

use JRF\Http\Method;
use JRF\Http\OAS\Controller;
use JRF\Http\OAS\Route\Parameter;
use JRF\Http\OAS\Route\Payload;
use JRF\Http\Router\Route\Input\Definition;

/**
 * Route input definition built from an OAS operation.
 * Fills the core definition, so OAS routes are verified by the same input assertion as other routes.
 */
class InputDefinition extends Definition
{
	private string $controller = '';
	private array $pathParameterTypes = [];

	public static function createFromDetails(
		Method $method,
		array $details,
		array $pathLevelParameters = []
	): InputDefinition {

		$controllerDetails = Controller::normalize($details['operationId'] ?? '');
		$parameters = Parameter::normalize($details['parameters'] ?? [], $pathLevelParameters);

		$definition = new InputDefinition($method, $controllerDetails['method']);
		$definition->controller = $controllerDetails['controller'];
		$definition->pathParameterTypes = $parameters[Parameter::PATH];
		$definition->requiredQueryParamDefinitions = $parameters[Parameter::REQUIRED_QUERY];
		$definition->optionalQueryParamDefinitions = $parameters[Parameter::OPTIONAL_QUERY];
		$definition->requiredPayload = Payload::normalize($details['requestBody'] ?? []);

		return $definition;
	}

	public function controller(): string
	{
		return $this->controller;
	}

	/**
	 * @return array [ name => ParameterType ]
	 */
	public function pathParameterTypes(): array
	{
		return $this->pathParameterTypes;
	}
}
