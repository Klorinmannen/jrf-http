<?php

declare(strict_types=1);

namespace JRF\Http\Router;

use Closure;

use JRF\Http\Request;
use JRF\Http\Middleware\MiddlewareContext;
use JRF\Http\Middleware\MiddlewareInterface;

class Middleware
{
	public function __construct(
		private MiddlewareInterface|Closure $middleware,
		private MiddlewareContext|null $context
	) {}

	public static function create(
		MiddlewareInterface|Closure $middleware,
		MiddlewareContext|null $context = null
	): Middleware {
		return new Middleware($middleware, $context);
	}

	public function isContext(MiddlewareContext $context): bool
	{
		return $this->context === $context;
	}

	public function process(Request $request): void
	{
		$this->middleware instanceof Closure
			? ($this->middleware)($request)
			: $this->middleware->process($request);
	}
}
