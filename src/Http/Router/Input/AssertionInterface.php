<?php

declare(strict_types=1);

namespace JRF\Http\Router\Input;

use JRF\Http\Request;
use JRF\Http\Router\Route\Input\Definition;

interface AssertionInterface
{
	/**
	 * Verify the request against the route's requirements.
	 */
	public function verify(Request $request, Definition $inputDefinition): void;
}
