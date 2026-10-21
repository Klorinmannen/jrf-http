<?php

declare(strict_types=1);

namespace Projom\Tests\Unit\Http\OAS;

use RuntimeException;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use Projom\Http\OAS\Document;
use Projom\Http\OAS\Route;

class DocumentTest extends TestCase
{
	private string $jsonFile;
	private string $yamlFile;
	private string $refJsonFile;

	protected function setUp(): void
	{
		$this->jsonFile = tempnam(sys_get_temp_dir(), 'oas') . '.json';
		$this->yamlFile = tempnam(sys_get_temp_dir(), 'oas') . '.yaml';
		$this->refJsonFile = tempnam(sys_get_temp_dir(), 'oasref') . '.json';
	}

	protected function tearDown(): void
	{
		@unlink($this->jsonFile);
		@unlink($this->yamlFile);
		@unlink($this->refJsonFile);
	}

	#[Test]
	public function loadThrowsIfNoPaths()
	{
		file_put_contents($this->jsonFile, json_encode(['info' => 'no paths']));
		$this->expectException(RuntimeException::class);
		Document::load($this->jsonFile);
	}

	#[Test]
	public function loadParsesJsonFile()
	{
		$data = [
			'paths' => [
				'/foo' => [
					'get' => ['summary' => 'Get foo']
				]
			]
		];
		file_put_contents($this->jsonFile, json_encode($data));
		$routes = Document::load($this->jsonFile);
		$this->assertArrayHasKey('/foo', $routes);
		$this->assertInstanceOf(Route::class, $routes['/foo']);
	}

	#[Test]
	#[RequiresPhpExtension('yaml')]
	public function loadParsesYamlFile()
	{
		$data = [
			'paths' => [
				'/bar' => [
					'post' => ['summary' => 'Post bar']
				]
			]
		];
		file_put_contents($this->yamlFile, yaml_emit($data));
		$routes = Document::load($this->yamlFile);
		$this->assertArrayHasKey('/bar', $routes);
		$this->assertInstanceOf(Route::class, $routes['/bar']);
	}

	#[Test]
	public function loadWithRefResolvesReference()
	{
		$refData = [
			'fooRef' => [
				'put' => ['summary' => 'Put ref']
			]
		];
		file_put_contents($this->refJsonFile, json_encode($refData));
		$data = [
			'paths' => [
				'/ref' => [
					'$ref' => basename($this->refJsonFile) . '#/fooRef'
				]
			]
		];
		file_put_contents($this->jsonFile, json_encode($data));
		$routes = Document::load($this->jsonFile);
		$this->assertArrayHasKey('/ref', $routes);
		$this->assertInstanceOf(Route::class, $routes['/ref']);
	}

	#[Test]
	public function loadThrowsIfFileNotFound()
	{
		$this->expectException(RuntimeException::class);
		Document::load('/not/a/real/file.json');
	}

	#[Test]
	public function loadThrowsIfUnsupportedExtension()
	{
		$txtFile = tempnam(sys_get_temp_dir(), 'oas') . '.txt';
		file_put_contents($txtFile, 'foo');
		$this->expectException(RuntimeException::class);
		try {
			Document::load($txtFile);
		} finally {
			@unlink($txtFile);
		}
	}
}
