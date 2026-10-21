<?php

declare(strict_types=1);

namespace Projom\Http\OAS\Route;

class Payload
{
	public static function normalize(array $expectedPayload): bool
	{
		// OAS default for required is false.
		$required = (bool) ($expectedPayload['required'] ?? false);
		return $required;
	}
}
