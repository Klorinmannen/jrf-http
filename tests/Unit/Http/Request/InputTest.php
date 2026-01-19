<?php

declare(strict_types=1);

namespace JRF\Tests\Unit\Http\Request;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use JRF\Http\Request\Input;

class InputTest extends TestCase
{
	#[Test]
	public function create(): void
	{
		$input = Input::create();

		$this->assertIsArray($input->request);
		$this->assertIsArray($input->server);
		$this->assertIsArray($input->files);
		$this->assertIsArray($input->cookies);
		$this->assertIsString($input->payload);
	}
}
