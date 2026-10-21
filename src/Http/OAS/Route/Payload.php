<?php

declare(strict_types=1);

namespace Projom\Http\OAS\Route;

class Payload
{
	public static function normalize(array $expectedPayload): bool
	{
		if (!$expectedPayload)
			return false;

		$required = (bool) ($expectedPayload['required'] ?? true);

		return $required;
	}
}
