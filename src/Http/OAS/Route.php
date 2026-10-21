<?php

declare(strict_types=1);

namespace JRF\Http\OAS;

use JRF\Http\Method;
use JRF\Http\OAS\Route\InputDefinition;
use JRF\Http\Router\Route\Action;
use JRF\Http\Router\Route\RouteBase;

class Route extends RouteBase
{
	public function setData(Method $method, InputDefinition $definition): void
	{
		$this->inputDefinitions[$method->name] = $definition;
	}

	public function setup(): void
	{
		$controller = $this->inputDefinition->controller();
		$controllerMethod = $this->inputDefinition->method();
		$this->action = Action::create($controller, $controllerMethod);
	}
}
