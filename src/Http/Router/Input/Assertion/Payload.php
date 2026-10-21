<?php

declare(strict_types=1);

namespace JRF\Http\Router\Input\Assertion;

class Payload
{
	public static function verify(string $payload, bool $isRequired): bool
	{
		if ($isRequired)
			if (empty($payload))
				return false;
		return true;
	}
}
