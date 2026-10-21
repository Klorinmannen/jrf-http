<?php

declare(strict_types=1);

namespace JRF\Http\OAS\Route;

use RuntimeException;

use JRF\Http\Router\ParameterType;

class Parameter
{
	public const PATH = 'path';
	public const REQUIRED_QUERY = 'requiredQuery';
	public const OPTIONAL_QUERY = 'optionalQuery';

	private const IN_PATH = 'path';
	private const IN_QUERY = 'query';
	private const IGNORED_LOCATIONS = ['header', 'cookie'];

	private const TYPE_MAP = [
		'integer' => ParameterType::INTEGER,
		'string' => ParameterType::STRING,
		'boolean' => ParameterType::BOOL,
	];

	/**
	 * Normalize OAS parameters into path, required query and optional query definitions.
	 * Path-level parameters apply to every operation; operation-level parameters override them.
	 *
	 * @return array [ Parameter::PATH => [ name => ParameterType ], Parameter::REQUIRED_QUERY => [...], Parameter::OPTIONAL_QUERY => [...] ]
	 */
	public static function normalize(array $operationParameters, array $pathParameters = []): array
	{
		$normalized = [
			static::PATH => [],
			static::REQUIRED_QUERY => [],
			static::OPTIONAL_QUERY => [],
		];

		foreach (static::merge($operationParameters, $pathParameters) as $parameter) {

			$group = static::group($parameter);
			if ($group === null)
				continue;

			$type = static::toParameterType($parameter['schema']['type'] ?? '');
			$normalized[$group][$parameter['name']] = $type;
		}

		return $normalized;
	}

	public static function toParameterType(string $oasType): ParameterType
	{
		$type = static::TYPE_MAP[$oasType] ?? null;
		if ($type === null)
			throw new RuntimeException("Unsupported OAS parameter type: '$oasType'");
		return $type;
	}

	private static function merge(array $operationParameters, array $pathParameters): array
	{
		// Parameters are unique by name and location, later entries override earlier ones.
		$merged = [];
		foreach ([...$pathParameters, ...$operationParameters] as $parameter)
			$merged[static::uniqueKey($parameter)] = $parameter;
		return $merged;
	}

	private static function uniqueKey(array $parameter): string
	{
		$name = $parameter['name'] ?? '';
		$in = $parameter['in'] ?? '';
		if ($name === '' || $in === '')
			throw new RuntimeException('OAS parameter is missing name or in');
		return "$in:$name";
	}

	private static function group(array $parameter): null|string
	{
		$in = $parameter['in'];

		// OAS mandates path parameters to be required.
		if ($in === static::IN_PATH)
			return static::PATH;

		if (in_array($in, static::IGNORED_LOCATIONS, true))
			return null;

		if ($in !== static::IN_QUERY)
			throw new RuntimeException("Unsupported OAS parameter location: '$in'");

		// OAS default for required is false.
		$required = (bool) ($parameter['required'] ?? false);
		return $required ? static::REQUIRED_QUERY : static::OPTIONAL_QUERY;
	}
}
