<?php

declare(strict_types=1);

namespace JRF\Http\Router;

use JRF\Http\Request;
use JRF\Http\Router\Route\Action;

interface DispatcherInterface
{
	public function processAction(Action $action, Request $request): void;
}
