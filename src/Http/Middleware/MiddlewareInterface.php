<?php

declare(strict_types=1);

namespace JRF\Http\Middleware;

use JRF\Http\Request;

interface MiddlewareInterface
{
	public function process(Request $request): void;
}
