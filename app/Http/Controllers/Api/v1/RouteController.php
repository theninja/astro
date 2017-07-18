<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\Route\ResolveRequest;
use App\Models\Traits\RouteResolver;

use App\Models\Route;
use App\Models\Redirect;
use App\Http\Transformers\Api\v1\PageTransformer;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Gate;

class RouteController extends ApiController
{
    use RouteResolver;

	/**
	 * GET /api/v1/route/resolve?path=...
	 *
	 * @param  ResolveRequest $request
	 * @return Response
	 */
	public function resolve(ResolveRequest $request){
		// Retrieve the path from the URL
		$path = $request->get('path');

		return $this->resolveRoute($path);

	}

}
