<?php

declare(strict_types=1);

namespace Projom\Http\OAS;

use RuntimeException;

class Path
{
	private const TEMPLATE_PATTERN = '/\{([^{}:]+)\}/';

	/**
	 * Translate an OAS path template into the router's dynamic path syntax.
	 *
	 * * Example: Path::translate('/recipes/{recipe_id}', ['recipe_id' => ParameterType::INTEGER]) returns '/recipes/{integer:recipe_id}'
	 *
	 * @param array $pathParameterTypes [ name => ParameterType ]
	 */
	public static function translate(string $oasPath, array $pathParameterTypes): string
	{
		$path = preg_replace_callback(
			static::TEMPLATE_PATTERN,
			function ($matches) use ($oasPath, $pathParameterTypes) {

				$name = $matches[1];
				$type = $pathParameterTypes[$name] ?? null;
				if ($type === null)
					throw new RuntimeException("Path parameter: $name, is not declared in OAS path: $oasPath");

				return '{' . $type->value . ':' . $name . '}';
			},
			$oasPath
		);
		return $path;
	}
}
