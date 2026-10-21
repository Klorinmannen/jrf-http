<?php

declare(strict_types=1);

namespace Projom\Http\OAS\Route;

use RuntimeException;

use Projom\Http\Router\ParameterType;

class Parameter
{
	private const TYPE_MAP = [
		'integer' => ParameterType::INTEGER,
		'string' => ParameterType::STRING,
		'boolean' => ParameterType::BOOL,
	];

	public static function normalize(array $parameterDefinitions): array
	{
		$normalized = [];
		foreach ($parameterDefinitions as $parameterData) {

			$in = $parameterData['in']; // 'path' or 'query'.
			$name = $parameterData['name'] ?? '';
			$type = $parameterData['schema']['type'] ?? '';

			// Default to required: true for stricter safety precaution.
			$required = (bool) ($parameterData['required'] ?? true);

			$normalized[$in][] = [
				'name' => $name,
				'type' => static::toParameterType($type),
				'required' => $required
			];
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
}
