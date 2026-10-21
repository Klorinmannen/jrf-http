<?php

declare(strict_types=1);

namespace Projom\Http\OAS;

use RuntimeException;

use Projom\Http\Method;
use Projom\Http\OAS\Path;
use Projom\Http\OAS\Route;
use Projom\Http\OAS\Route\InputDefinition;

class Document
{
	private const OPERATION_KEYS = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'];
	private const REF_KEY = '$ref';
	private const REF_SEPARATOR = '#/';
	private const LOCAL_REF_PREFIX = '#';

	/**
	 * Load routes from an OAS file.
	 *
	 * @return array [ oasPath => Route ]
	 */
	public static function load(string $filePath): array
	{
		$file = static::parseFile($filePath);

		$paths = $file['paths'] ?? [];
		if (!$paths)
			throw new RuntimeException("No paths found in OAS file: $filePath");

		$routes = [];
		foreach ($paths as $oasPath => $pathItem)
			$routes[$oasPath] = static::createRoute($oasPath, $pathItem, dirname($filePath));

		return $routes;
	}

	private static function createRoute(string $oasPath, array $pathItem, string $contractDirectory): Route
	{
		$pathItem = static::resolvePathItem($pathItem, $contractDirectory);
		$definitions = static::createDefinitions($pathItem);
		$pathParameterTypes = static::collectPathParameterTypes($oasPath, $definitions);

		$route = Route::create(Path::translate($oasPath, $pathParameterTypes));
		foreach ($definitions as $httpMethod => $definition)
			$route->setData(Method::from($httpMethod), $definition);

		return $route;
	}

	/**
	 * @return array [ httpMethod => InputDefinition ]
	 */
	private static function createDefinitions(array $pathItem): array
	{
		$operations = array_intersect_key($pathItem, array_flip(static::OPERATION_KEYS));
		$pathLevelParameters = $pathItem['parameters'] ?? [];

		$definitions = [];
		foreach ($operations as $httpMethod => $details) {
			$method = Method::from(strtoupper($httpMethod));
			$definitions[$method->value] = InputDefinition::createFromDetails($method, $details, $pathLevelParameters);
		}

		return $definitions;
	}

	private static function collectPathParameterTypes(string $oasPath, array $definitions): array
	{
		$pathParameterTypes = [];
		foreach ($definitions as $definition)
			$pathParameterTypes = static::mergePathParameterTypes(
				$pathParameterTypes,
				$definition->pathParameterTypes(),
				$oasPath
			);
		return $pathParameterTypes;
	}

	private static function mergePathParameterTypes(array $collected, array $types, string $oasPath): array
	{
		foreach ($types as $name => $type) {
			$collectedType = $collected[$name] ?? $type;
			if ($collectedType !== $type)
				throw new RuntimeException("Conflicting types for path parameter: $name, in OAS path: $oasPath");
			$collected[$name] = $type;
		}
		return $collected;
	}

	private static function resolvePathItem(array $pathItem, string $contractDirectory): array
	{
		$ref = $pathItem[static::REF_KEY] ?? null;
		if ($ref === null)
			return $pathItem;

		$referencedPathItem = static::loadRef($ref, $contractDirectory);
		return [...$referencedPathItem, ...$pathItem];
	}

	private static function loadRef(string $ref, string $contractDirectory): array
	{
		if (str_starts_with($ref, static::LOCAL_REF_PREFIX))
			throw new RuntimeException("Local reference not supported: $ref");
		if (!str_contains($ref, static::REF_SEPARATOR))
			throw new RuntimeException("Invalid reference: $ref");

		[$relativeFilename, $refname] = explode(static::REF_SEPARATOR, $ref, 2);
		$refFilepath = $contractDirectory . '/' . $relativeFilename;
		$fileData = static::parseFile($refFilepath);

		$pathItem = $fileData[$refname] ?? [];
		if (!$pathItem)
			throw new RuntimeException("Reference not found: $refname in $refFilepath");

		return $pathItem;
	}

	private static function parseFile(string $filePath): array
	{
		$realFilePath = realpath($filePath);
		if (!$realFilePath)
			throw new RuntimeException("File not found: $filePath");

		$file = file_get_contents($realFilePath);
		$fileData = static::decodeFile($file, $realFilePath);
		return $fileData;
	}

	private static function decodeFile(string $file, string $filePath): array
	{
		$extension = pathinfo($filePath, PATHINFO_EXTENSION);
		$fileData = match ($extension) {
			'json' => json_decode($file, associative: true) ?: [],
			'yaml', 'yml' => static::decodeYaml($file, $filePath),
			default => throw new RuntimeException("Unsupported file format: $extension for file $filePath"),
		};
		return $fileData;
	}

	private static function decodeYaml(string $file, string $filePath): array
	{
		if (!function_exists('yaml_parse'))
			throw new RuntimeException("The yaml extension is required to load: $filePath");
		return yaml_parse($file) ?: [];
	}
}
