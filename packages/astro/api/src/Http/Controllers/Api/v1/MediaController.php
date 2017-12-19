<?php
namespace Astro\API\Http\Controllers\Api\v1;

use DB;
use Exception;
use Astro\API\Models\Site;
use Astro\API\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Astro\API\Http\Requests\Api\v1\Media\DeleteRequest;
use Astro\API\Http\Requests\Api\v1\Media\StoreRequest;
use Astro\API\Transformers\Api\v1\MediaTransformer;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Astro\API\Jobs\ProcessMedia;

class MediaController extends ApiController
{

	/**
	 * GET /api/v1/media
	 *
	 * Returns an index of Media objects.
	 * Supports querying with site_ids, pubishing_group_ids, types and mime_types.
	 *
	 * @param  Request $request
	 * @return Response
	 */
	public function index(Request $request){
		$query = Media::query();

		if(!$request->has('site_ids')){
			$this->authorize('index', Media::class);
		}

		if($request->has('site_ids')){
			$sites = Site::whereIn('id', $request->get('site_ids'))->get();

			foreach($sites as $site){
				$this->authorize('index', [ Media::class, $site ]);
			}

			$query->sites($sites->pluck('id')->toArray());
		}

		if($request->has('types')){
			$query->types($request->get('types'));
		}

		if($request->has('mime_types')){
			$query->mimeTypes($request->get('mime_types'));
		}

		if($request->has('order') && $request->get('order') === 'id.desc') {
			$query->orderBy('id', 'desc');
		}

		return fractal($query->get(), new MediaTransformer)->respond(200);
	}


	/**
	 * POST /api/v1/media
	 *
	 * Handles the creation of new Media objects. If the file has previously been uploaded,
	 * we associate the existing Media with the specified Site. From the users'
	 * perspective, the upload completed.
	 *
	 * @param  StoreRequest $request
	 * @param  Media $media
	 * @return Response
	 */
	public function store(StoreRequest $request){
		$file = $request->file('upload');
		$hash = Media::hash($file);

		$media = Media::findByHash($hash);
		$media = $media ?: new Media([ 'file' => $file ]);

		$this->authorizeAll($request, 'create', $media); // Ensures that the user can sync with Sites

		DB::beginTransaction();

		try {
			$media->save();

			$site_ids = array_merge($media->sites()->allRelatedIds()->toArray(), $request->get('site_ids', []));
			$media->sites()->sync($site_ids);

			DB::commit();
		} catch(Exception $e){
			DB::rollBack();
			throw $e;
		}

		// TODO: implement job for processing media based on content type
		dispatch(new ProcessMedia($media));

		return fractal($media, new MediaTransformer)->respond(201);
	}


	/**
	 * DELETE /api/v1/media/{media}
	 *
	 * Rather than deleting Media objects, this removes the specified Site associations.
	 * From a users' perspective, the Media has been removed from the system.
	 *
	 * @param  Request    $request
	 * @param  Definition $definition
	 * @return SymfonyResponse
	 */
	public function destroy(DeleteRequest $request, Media $media){
		$this->authorizeAll($request, 'delete', $media); // Ensures that the user can sync with Sites

		if($request->has('site_ids')){
			$site_ids = array_diff($media->sites()->allRelatedIds()->toArray(), $request->get('site_ids'));
			$media->sites()->sync($site_ids);
		}

		return (new SymfonyResponse())->setStatusCode(200);
	}


	/**
	 * Ensures that the user can has the required permissions for performing Media operations
	 * given a set of Site IDs.
	 *
	 * @param Request $request
	 * @param string $action
	 * @param Media $media
	 *
	 * @return void
	 */
	protected function authorizeAll(Request $request, $action, Media $media)
	{
		$acos = new Collection;

		if($request->has('site_ids')){
			$acos = $acos->merge(Site::whereIn('id', $request->get('site_ids'))->get());
		}

		foreach($acos as $model){
			$this->authorize($action, [ $media, $model ]);
		}
	}

}
