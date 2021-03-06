<?php
namespace Tests\Unit\Models\Definitions;

use Config;
use Tests\TestCase;
use App\Models\Definitions\Block;
use App\Exceptions\DefinitionNotFoundException;

class BlockTest extends TestCase
{

	public function setUp(){
		parent::setUp();
		Config::set('app.definitions_path', base_path('tests/Support/Fixtures/definitions'));
	}

	/**
	 * @test
	 */
	public function locateDefinition_WhenDefinitionIsFound_ReturnsPath(){
		$path = Block::locateDefinition('test-block-v1');
		$this->assertEquals(base_path('tests/Support/Fixtures/definitions/blocks/test-block/v1/definition.json'), $path);
	}

	/**
	 * @test
	 */
	public function locateDefinition_WhenDefinitionIsNotFound_ReturnsNull(){
		$this->assertNull(Block::locateDefinition('foobar'));
	}

	/**
	 * @test
	 */
	public function locateDefinitionOrFail_WhenDefinitionIsFound_ReturnsPath(){
		$path = Block::locateDefinitionOrFail('test-block-v1');
		$this->assertEquals(base_path('tests/Support/Fixtures/definitions/blocks/test-block/v1/definition.json'), $path);
	}

	/**
	 * @test
	 */
	public function locateDefinitionOrFail_WhenDefinitionIsNotFound_ThrowsException(){
		$this->expectException(DefinitionNotFoundException::class);
		Block::locateDefinitionOrFail('foobar');
	}

}
