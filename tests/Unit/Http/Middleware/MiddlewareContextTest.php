<?php

declare(strict_types=1);

namespace JRF\Tests\Unit\Http\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use JRF\Http\Middleware\MiddlewareContext;

class MiddlewareContextTest extends TestCase
{
	#[Test]
	public function allCasesArePresent()
	{
		$expected = [
			MiddlewareContext::BEFORE_ROUTING,
			MiddlewareContext::BEFORE_DISPATCHING,
			MiddlewareContext::BEFORE_DISPATCHING_RESPONSE,
		];
		$this->assertSame($expected, MiddlewareContext::cases());
	}
}
