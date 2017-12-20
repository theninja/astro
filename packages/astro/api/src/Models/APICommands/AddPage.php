<?php

namespace Astro\API\Models\APICommands;

use DB;
use Astro\API\Models\Page;
use Astro\API\Models\Contracts\APICommand;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Adding a page is actually adding a page at a certain route.
 * @package Astro\API\Models\APICommands
 */
class AddPage implements APICommand
{
	use AddsPagesTrait;

    /**
     * Adding a Page
     * @param $input
     * @return Page The newly added page.
     */
    public function execute($input, Authenticatable $user)
    {
        return DB::transaction(function() use($input, $user){
            $parent = Page::find($input['parent_id']);
            $page = $this->addPage($parent,
                $input['slug'],
                $input['title'],
                $user,
                $input['layout']['name'],
                $input['layout']['version']
            );
            if(!empty($input['next_id'])){
                $page->makePreviousSiblingOf(Page::find($input['next_id']));
            }
            return $page;
        });
    }

    public function messages(Collection $data, Authenticatable $user)
    {
        return [];
    }

    public function rules(Collection $data, Authenticatable $user)
    {
        $layout = $data->get('layout', []);
        $version = !empty($layout['version']) ? $layout['version'] : null;

        return [
            'parent_id' => [
                'required',
                'exists:pages,id',
				'page_is_draft:'.$data->get('parent_id')
             ],
            // if next_id exists it must have the parent_id specified for the new route / page.
            'next_id' => [
                'nullable',
                Rule::exists('pages','id')
                    ->where( function($query) use($data) { $query->where('parent_id', $data->get('parent_id'));})
            ],
            'slug' => [
                // slug is required and can only contain lowercase letters, numbers, hyphen or underscore.
                'required',
                'regex:/^[a-z0-9_-]+$/',
                // there must not be an existing draft page with the same slug under the parent page
                Rule::unique('pages', 'slug')
                   ->where('parent_id', $data->get('parent_id'))
                   ->where('version', Page::STATE_DRAFT),
            ],
            'layout.name' => [
                'string',
                'max:100',
                'required',
                'regex:/^[a-z0-9_.-]+$/i',
                'layout_exists:' . $version            ],
            'layout.version' => [
                'required',
                'integer'
            ],
            'title' => [
                'string',
                'max:150',
                'required'
            ]
        ];
    }
}