<?php

declare(strict_types=1);

namespace JRF\Tests\Unit\Http\OAS\Route;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use JRF\Http\OAS\Route\Payload;

class PayloadTest extends TestCase
{
	public static function requestBodyProvider(): array
	{
		return [
			'no request body' => [[], false],
			'required absent' => [['content' => ['application/json' => []]], false],
			'required false' => [['required' => false, 'content' => ['application/json' => []]], false],
			'required true' => [['required' => true, 'content' => ['application/json' => []]], true],
		];
	}

	#[Test]
	#[DataProvider('requestBodyProvider')]
	public function normalizeFollowsOasRequiredDefault(array $requestBody, bool $expected): void
	{
		$this->assertSame($expected, Payload::normalize($requestBody));
	}
}
