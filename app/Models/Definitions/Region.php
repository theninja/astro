<?php
namespace App\Models\Definitions;

use Illuminate\Support\Collection;

class Region extends BaseDefinition
{

	public static $defDir = 'regions';

	protected $casts = [
        'blocks' => 'array',
	];

	protected $blockDefinitions;

	public function __construct(){
		$this->blockDefinitions = new Collection();
	}

	/**
	 * Loads the block definitions from disk and populates $regionDefinitions.
	 *
	 * @return void
	 */
	public function loadBlockDefinitions(){
		foreach($this->blocks as $name){
			$path = Block::locateDefinition($name);

			if(!is_null($path)){
				$block = Block::fromDefinitionFile($path);
				$this->blockDefinitions->push($block);
			}
		}
	}

	/**
	 * Returns the blockDefinitions Collection, populating it from disk if necessary.
	 *
	 * @return Collection
	 */
	public function getBlockDefinitions(){
		if($this->blockDefinitions->isEmpty() && count($this->blocks)){
			$this->loadBlockDefinitions();
		}

		return $this->blockDefinitions;
	}

	/**
	 * Get the default sections and block data for this region.
	 * @return array - [ [ 'name' => 'section-name', 'blocks' =>
	 */
	public function getDefaultBlocks()
	{
		$sections = [];
		foreach($this->sections as $section_def){
			$section = [ 'name' => $section_def['name'], 'blocks' => []];
			if($section_def['defaultBlocks']){
				foreach($section_def['defaultBlocks'] as $block_id){
					$block_def = Block::fromDefinitionFile(Block::locateDefinition($block_id));
					if($block_def){
						$section['blocks'][] = $block_def->getDefaultData($this->name, $section_def['name']);
					}
				}
			}
			$sections[] = $section;
		}
		return $sections;
	}

}
