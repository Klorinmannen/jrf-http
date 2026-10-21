<?php

declare(strict_types=1);

namespace Projom\Http\OAS;

use RuntimeException;

class Controller
{
	private const METHOD_SEPARATOR = '@';
	private const OAS_NAMESPACE_SEPARATOR = '_';
	private const NAMESPACE_SEPARATOR = '\\';

	public static function normalize(string $operationId): array
	{
		if ($operationId === '')
			throw new RuntimeException('OAS operation is missing operationId');

		$controllerDetails = explode(static::METHOD_SEPARATOR, $operationId);
		$resourceController = static::toClassName(array_shift($controllerDetails) ?? '');
		$resourceOperation = array_shift($controllerDetails) ?? '';
		return [
			'controller' => $resourceController,
			'method' => $resourceOperation
		];
	}

	/**
	 * Converts 'recipe_controller' to 'Recipe\Controller', capitalizing each namespace segment.
	 */
	private static function toClassName(string $resourceController): string
	{
		$className = str_replace(static::OAS_NAMESPACE_SEPARATOR, static::NAMESPACE_SEPARATOR, $resourceController);
		return ucwords($className, static::NAMESPACE_SEPARATOR);
	}
}
