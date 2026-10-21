<?php

declare(strict_types=1);

namespace JRF\Tests\Unit\Http\OAS;

use RuntimeException;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use JRF\Http\OAS\Document;
use JRF\Http\OAS\Route;
use JRF\Http\Request;
use JRF\Http\Request\Input;

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
					'get' => ['summary' => 'Get foo', 'operationId' => 'Foo_Controller@getFoo']
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
					'post' => ['summary' => 'Post bar', 'operationId' => 'Bar_Controller@postBar']
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
				'put' => ['summary' => 'Put ref', 'operationId' => 'Ref_Controller@putRef']
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

	#[Test]
	public function loadBuildsRoutesThatMatchRequestPaths()
	{
		$data = [
			'paths' => [
				'/recipes/{recipe_id}' => [
					'summary' => 'A recipe',
					'parameters' => [
						['name' => 'recipe_id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
					],
					'get' => ['operationId' => 'Recipe_Controller@getRecipe']
				]
			]
		];
		file_put_contents($this->jsonFile, json_encode($data));
		$routes = Document::load($this->jsonFile);

		$request = Request::create(
			Input::create(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/recipes/5'])
		);
		$this->assertTrue($routes['/recipes/{recipe_id}']->match($request));
		$this->assertSame(['recipe_id' => '5'], $request->pathParameters());
	}

	#[Test]
	public function loadThrowsOnConflictingPathParameterTypes()
	{
		$data = [
			'paths' => [
				'/recipes/{recipe_id}' => [
					'get' => [
						'operationId' => 'Recipe_Controller@getRecipe',
						'parameters' => [['name' => 'recipe_id', 'in' => 'path', 'schema' => ['type' => 'integer']]]
					],
					'delete' => [
						'operationId' => 'Recipe_Controller@deleteRecipe',
						'parameters' => [['name' => 'recipe_id', 'in' => 'path', 'schema' => ['type' => 'string']]]
					]
				]
			]
		];
		file_put_contents($this->jsonFile, json_encode($data));
		$this->expectException(RuntimeException::class);
		Document::load($this->jsonFile);
	}

	#[Test]
	public function loadThrowsOnInvalidJson()
	{
		file_put_contents($this->jsonFile, '{"paths": ');
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Invalid JSON');
		Document::load($this->jsonFile);
	}

	#[Test]
	#[RequiresPhpExtension('yaml')]
	public function loadThrowsOnInvalidYaml()
	{
		file_put_contents($this->yamlFile, 'paths: {a: [');
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Invalid YAML');
		Document::load($this->yamlFile);
	}

	#[Test]
	public function loadThrowsOnScalarJson()
	{
		file_put_contents($this->jsonFile, '"foo"');
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('must contain an object');
		Document::load($this->jsonFile);
	}

	#[Test]
	#[RequiresPhpExtension('yaml')]
	public function loadThrowsOnScalarYaml()
	{
		file_put_contents($this->yamlFile, 'foo');
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('must contain an object');
		Document::load($this->yamlFile);
	}

	#[Test]
	public function loadThrowsOnLocalReference()
	{
		$data = ['paths' => ['/ref' => ['$ref' => '#/components/pathItems/ref']]];
		file_put_contents($this->jsonFile, json_encode($data));
		$this->expectException(RuntimeException::class);
		Document::load($this->jsonFile);
	}

	#[Test]
	public function loadThrowsOnYamlWithoutExtension()
	{
		if (extension_loaded('yaml'))
			$this->markTestSkipped('Only applies when the yaml extension is missing.');

		file_put_contents($this->yamlFile, "paths: {}\n");
		$this->expectException(RuntimeException::class);
		Document::load($this->yamlFile);
	}
}
