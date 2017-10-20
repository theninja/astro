<?php

namespace App\Models\APICommands;

use App\Exceptions\UnpublishedParentException;
use App\Models\Contracts\APICommand;
use App\Models\Page;
use DB;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Publish a page and (optionally) all its descendants.
 * @package App\Models\APICommands
 */
class PublishPage implements APICommand
{
	/**
	 * Determines if this page can be published or not. If page has a parent, then that must be published first.
	 * @param int $page_id The id of an unpublished page to test.
	 */
	public static function canBePublished($page_id)
	{
		$page = Page::draft()->find($page_id);
		if (!$page) {
			return false;
		}
		if (!$page->parent) {
			return true;
		} else {
			return $page->parent->publishedVersion();
		}
	}

	/**
	 * Carry out the command, based on the provided $input.
	 * @param array $input The input options as key=>value pairs.
	 * @return mixed
	 */
	public function execute($input, Authenticatable $user)
	{
		return DB::transaction(function () use ($input) {
			$published_page = null;
			$page = Page::find($input['id']);

			// is there already a published page at this path?
			$published_page = $page->publishedVersion();
			if ($published_page) {
				// Is it a previous version of the page we are now publishing?
				if ($published_page->revision->revision_set_id != $page->revision->revision_set_id) {
					// deleting the previously published page and all of its descendants deals with the edge case that
					// the user deleted or moved a page, then created a new page in its place which is now being published
					// without publishing the "move" or "deletion". ie. we start with a clean slate.
					$published_page->delete();
					$published_page = null;
				}
			}
			if (!$published_page) {
				// has a version of this page been published elsewhere? If so delete it...
				$published_and_moved = Page::published()
					->forSite($page->site_id)
					->whereHas('revision', function ($query) use ($page) {
						$query->where('revision_set_id', $page->revision->revision_set_id);
					})->first();
				if ($published_and_moved) {
					$published_and_moved->delete();
				}
			}

			if (!$published_page) {

				// does this page have a parent, and if so, has it been published?
				$parent = $page->parent;
				$published_parent = null;
				if ($parent) {
					$published_parent = $parent->publishedVersion();
					if (!$published_parent) {
						throw new UnpublishedParentException('Parent pages must be published first.');
					}
				}

				//
				$fields = [
					'site_id' => $page->site_id,
					'version' => Page::STATE_PUBLISHED,
					'parent_id' => $published_parent ? $published_parent->id : null,
					'slug' => $page->slug,
					'created_by' => $page->created_by,
					'updated_by' => $page->updated_by
				];
				if ($published_parent) {
					$published_page = $published_parent->children()->create($fields);
				} else {
					$published_page = Page::create($fields);
				}
			}

			$published_page->setRevision($page->revision);

			// need to check if page is in a different position relative to its siblings than before.
			// This is complicated by the fact that its siblings may have also been reordered, but the reorderings
			// not yet published...
			// Let's use the following rules:
			// 1) If the draft has a previous-sibling which has a published equivalent, position it
			//    as the next sibling of that.
			// 2) Otherwise, if it has a next-sibling which has a published equivalent, position it
			//    as the previous sibling to that.
			// 3) Otherwise, add it as the last child under its parent.
			$positioned = false;
			// (1)
			$previous = $page->siblings()->where('rgt', $page->lft - 1)->first();
			if ($previous) {
				$published_previous = Page::forSiteAndPath($page->site_id, $previous->path)->published()->first();
				if ($published_previous) {
					$published_page->makeNextSiblingOf($published_previous);
					$positioned = true;
				}
			}
			// (2)
			if (!$positioned) {
				$next = $page->siblings()->where('lft', $page->rgt + 1)->first();
				if ($next) {
					$published_next = Page::forSiteAndPath($page->site_id, $next->path)->published()->first();
					if ($published_next) {
						$published_page->makePreviousSiblingOf($published_next);
						$positioned = true;
					}
				}
			}
			// (3) is the default when adding a new page... so nothing to do here...

			// are we also publishing the descendants of this page?
			/*            if(!empty($input['tree'])){
							$pages = $this->copyPages($page->children);
							if($pages) {
								$published_page->makeTree($pages);
								// need to mark the revisions as published if they weren't already...
								$revision_ids = [];
								foreach($pages as $page){
									$revision_ids[] = $page->revision_id;
								}
								Revision::whereIn('id', $revision_ids)
										->whereNull('published_at')
										->update(['published_at' => Carbon::now()]);
							}
						}
			*/

			return $published_page;
		});
	}

	/**
	 * Create an array representing the tree of pages to add to the published page.
	 * @param $pages The pages to copy.
	 * @param string $state
	 * @return array
	 */
	public function copyPages($pages, $state = Page::STATE_PUBLISHED)
	{
		$data = [];
		foreach ($pages as $page) {
			$data[] = [
				'site_id' => $page->site_id,
				'version' => $state,
				'slug' => $page->slug,
				'created_by' => $page->created_by,
				'updated_by' => $page->updated_by,
				'revision_id' => $page->revision_id
			];
		}
		return $data;
	}

	/**
	 * Get the error messages for this command.
	 * @param Collection $data The input data for this command.
	 * @return array Custom error messages mapping field_name => message
	 */
	public function messages(Collection $data, Authenticatable $user)
	{
		return [
			'id.exists' => 'The page does not exist.',
			'id.page_is_draft' => 'You can only publish draft pages.',
			'id.parent_is_published' => 'The parents of this page must be published first.',
			'id.page_is_valid' => 'You cannot publish a page with validation errors.'
		];
	}

	/**
	 * Get the validation rules for this command.
	 * @param Collection $data The input data for this command.
	 * @return array The validation rules for this command.
	 */
	public function rules(Collection $data, Authenticatable $user)
	{
		return [
			'id' => [
				'exists:pages,id',
				'page_is_draft:' . $data->get('id'),
				'parent_is_published:' . $data->get('id'),
				'page_is_valid'
			],
		];
	}
}