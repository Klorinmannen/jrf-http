<?php

declare(strict_types=1);

namespace JRF\Http;

use JRF\Http\Request;

abstract class Controller
{
	public function __construct(protected Request $request) {}
}
