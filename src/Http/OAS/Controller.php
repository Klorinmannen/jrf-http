<?php

declare(strict_types=1);

namespace Projom\Http\OAS;

use RuntimeException;

class Controller
{
	public static function normalize(string $operationId): array
	{
		if ($operationId === '')
			throw new RuntimeException('OAS operation is missing operationId');

		$controllerDetails = explode('@', $operationId);
		$resourceController = array_shift($controllerDetails) ?? '';
		$resourceController = str_replace('_', '\\',  ucwords($resourceController, '\\'));
		$resourceOperation = array_shift($controllerDetails) ?? '';
		return [
			'controller' => $resourceController,
			'method' =>  $resourceOperation
		];
	}
}
