<?php
namespace Tests\Unit\Http\Controllers\Api\v1;

use Gate;
use Mockery;
use App\Models\User;
use App\Models\Role;
use App\Models\Page;
use App\Models\Site;
use App\Models\Block;
use App\Models\Redirect;
use App\Models\UserSiteRole;
use App\Http\Controllers\Api\v1\PageController;
use App\Http\Transformers\Api\v1\PageTransformer;

class PageControllerTest extends ApiControllerTestCase {

	/**
	 * @test
	 * @group authentication
	 */
	public function resolve_WhenUnauthenticated_Returns401(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();
		$page->publish(new PageTransformer);

		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $page->path ]);
		$response->assertStatus(401);
	}

	/**
	 * @test
	 * @group authorization
	 */
	public function resolve_WhenRouteFoundAndUserIsAdmin_Returns200() {
		// GIVEN - we have an admin user and a page on a site
		$user = factory(User::class)->states(['admin'])->create();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();
		$this->authenticated($user);

		// WHEN we resolve the route on that page as the user
		$response = $this->action('GET', PageController::class . '@resolve', [ 'host' => $page->site->host, 'path' => $page->path ]);

		// THEN we expect a 200 response
		$response->assertStatus(200);
	}

	/**
	 * @test
	 * @group authorization
	 */
	public function copy_WhenPageIsAnAvailableLayout_CopiesPageAndReturns200() {
		// GIVEN - we have an admin user and a page on a site
		$user = factory(User::class)->states(['admin'])->create();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();
		$this->authenticated($user);

		// WHEN we resolve the route on that page as the user
		$response = $this->action('POST', PageController::class . '@copy', $page, [ 'new_title' => 'New page title', 'new_slug' => 'new-page-slug' ]);

		// THEN we expect a 200 response
		$response->assertStatus(200);

		// AND a newly created page should be returned in the response
		$json = $response->json();
		$this->assertArrayHasKey('data', $json);
		$this->assertEquals('new-page-slug', $json['data']['slug']);
		$this->assertEquals('New page title', $json['data']['title']);
	}

	/**
	 * @test
	 * @group authorization
	 */
	public function copy_WhenPageIsAnAvailableLayoutAndPageIsTheHomePage_CopiesPageAndMakesNewPageAChildOfCopiedPage() {
		// GIVEN - we have an admin user and a homepage on a site
		$user = factory(User::class)->states(['admin'])->create();
		$homepage = factory(Page::class)->states([ 'withRevision' ])->create();
		$this->authenticated($user);

		// WHEN we invoke copy on the page
		$response = $this->action('POST', PageController::class . '@copy', $homepage, [ 'new_title' => 'New page title', 'new_slug' => 'new-page-slug' ]);

		// THEN we expect a 200 response
		$response->assertStatus(200);

		// AND the newly created page should be a child of the copied page
		$json = $response->json();
		$this->assertEquals($homepage->id, $json['data']['parent_id']);
	}

	/**
	 * @test
	 * @group authorization
	 * @group wip
	 */
	public function copy_WhenPageIsAnAvailableLayoutAndPageIsNotTheHomePage_CopiesPageAndMakesNewPageASiblingOfCopiedPage() {
		// GIVEN - we have an admin user and a homepage on a site
		$user = factory(User::class)->states(['admin'])->create();
		$this->authenticated($user);
		$homepage = factory(Page::class)->states([ 'withRevision' ])->create();
		// AND we have a child page
		$response = $this->action('POST', PageController::class . '@copy', $homepage, [ 'new_title' => 'New page title', 'new_slug' => 'new-page-slug' ]);
		$json = $response->json();
		$page = Page::find($json['data']['id']); 

		// WHEN we invoke copy on the page
		$response = $this->action('POST', PageController::class . '@copy', $page, [ 'new_title' => 'Another new page title', 'new_slug' => 'another-new-page-slug' ]);

		// THEN we expect a 200 response
		$response->assertStatus(200);

		// AND the newly created page should be a sibling of the copied page
		$json = $response->json();
		$this->assertEquals($page->parent_id, $json['data']['parent_id']);
	}

	/**
	 * @test
	 * @group authorization
	 */
	public function resolve_WhenRouteFoundAndUserIsViewer_Returns200(){
		// GIVEN - we have a viewer user and a page on a site
		$user = factory(User::class)->states(['viewer'])->create();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();

		// AND the user is a member of the site
		$siteRole = new UserSiteRole([
			'user_id' => $user->id,
			'site_id' => $page->site->id,
			'role_id' => Role::first()->id,
		]);

		$user->roles()->save($siteRole);

		// WHEN we resolve the route on that page as the user
		$this->authenticated($user);
		$response = $this->action('GET', PageController::class . '@resolve', [ 'host' => $page->site->host, 'path' => $page->path ]);

		// THEN we expect a 200 response
		$response->assertStatus(200);
	}

	/**
	 * @test
	 * @group authorization
	 */
	public function resolve_WhenRouteFoundAndUserIsViewerAndIsNotMemberOfSite_Returns200(){
		// GIVEN - we have a viewer user and a page on a site
		$user = factory(User::class)->states(['viewer'])->create();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();
		$this->authenticated($user);

		// AND that user is NOT a member of the site
		$user->roles()->delete();

		// WHEN we resolve the route on that page as the user
		$response = $this->action('GET', PageController::class . '@resolve', [ 'host' => $page->site->host, 'path' => $page->path ]);

		// THEN we expect a 200 response
		$response->assertStatus(200);
	}

	/**
	 * @test
	 * @group authorization
	 */
	public function resolve_WhenRouteFoundAndUserIsMemberOfSite_Returns200(){
		// GIVEN - we have a default user and a page on a site
		$user = factory(User::class)->states(['user'])->create();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();

		// AND the user is a member of the site
		$siteRole = new UserSiteRole([
			'user_id' => $user->id,
			'site_id' => $page->site->id,
			'role_id' => Role::first()->id,
		]);

		$user->roles()->save($siteRole);

		// WHEN we resolve the route on that page as the user
		$this->authenticated($user);
		$response = $this->action('GET', PageController::class . '@resolve', [ 'host' => $page->site->host, 'path' => $page->path ]);

		// THEN we expect a 200 response
		$response->assertStatus(200);
	}

	/**
	 * @test
	 * @group authorization
	 */
	public function resolve_WhenRouteFoundAndUserIsNotMemberOfSite_Returns403(){
		// GIVEN - we have a default user and a page on a site
		$user = factory(User::class)->states(['user'])->create();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();

		// AND that user is NOT a member of the site
		$user->roles()->delete();

		// WHEN we resolve the route on that page as the user
		$this->authenticated($user);
		$response = $this->action('GET', PageController::class . '@resolve', [ 'host' => $page->site->host, 'path' => $page->path ]);

		// THEN we expect a 200 response
		$response->assertStatus(403);
	}

	/**
	 * @test
	 * @group authorization
	 *
	 * Resolves via a Route model
	 */
	public function resolve_WhenAuthenticatedAndRouteFound_ChecksAuthorization(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();
		$page->publish(new PageTransformer);

		;
		$page->publish(new PageTransformer);

		Gate::shouldReceive('allows')->with('read', Mockery::type(Page::class))->once();

		$this->authenticated();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $route->path ]);
	}

	/**
	 * @test
	 * @group authorization
	 *
	 * Resolves via a Route model
	 */
	public function resolve_WhenAuthenticatedAndRouteFoundButUnauthorized_Returns404(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();
		$page->publish(new PageTransformer);

		;
		$page->publish(new PageTransformer);

		$this->authenticatedAndUnauthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $route->path ]);
		$response->assertStatus(404);
	}

	/**
	 * @test
	 *
	 * Resolves via a Route model
	 */
	public function resolve_WhenAuthorizedAndRouteFoundButNotPublished_Returns200(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();
		$page->publish(new PageTransformer);

		;
		$page->publish(new PageTransformer);

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $route->path ]);

		$response->assertStatus(200);
	}

	/**
	 * @test
	 *
	 * Resolves via a Route model
	 */
	public function resolve_WhenAuthorizedAndRouteFoundButNotPublished_ReturnsJson(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();
		$page->publish(new PageTransformer);

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $route->path ]);

		$json = $response->json();
		$this->assertArrayHasKey('data', $json);
		$this->assertEquals($page->id, $json['data']['id']);
	}

	/**
	 * @test
	 *
	 * Resolves via a Route model
	 */
	public function resolve_WhenAuthorizedAndRouteFoundAndPublished_Returns200(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();
		$page->publish(new PageTransformer);

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $route->path ]);

		$response->assertStatus(200);
	}

	/**
	 * @test
	 *
	 * Resolves via a Route model
	 */
	public function resolve_WhenAuthorizedAndRouteFoundAndPublished_ReturnsJson(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();
		$page->publish(new PageTransformer);

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $route->path ]);

		$json = $response->json();
		$this->assertArrayHasKey('data', $json);
		$this->assertEquals($page->id, $json['data']['id']);
	}

	/**
	 * @test
	 *
	 * Resolves via a Route model
	 */
	public function resolve_WhenAuthorizedAndRouteFoundAndPublished_IncludesActiveRouteInJson(){
		return $this->markTestIncomplete();
		$active = factory(Page::class)->states([ 'withRevision' ])->create();
		$active->page->publish(new PageTransformer);

		sleep(1);

		$draft = factory(Page::class)->create(array_except(attrs_for($active), [ 'id', 'is_active' ]));

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $active->path ]);

		$json = $response->json();
		$this->assertArrayHasKey('data', $json);
		$this->assertArrayHasKey('active_route', $json['data']);
		$this->assertEquals($active->slug, $json['data']['active_route']['slug']);
	}

	/**
	 * @test
	 *
	 * Resolves via a Route model
	 */
	public function resolve_WhenAuthorizedAndRouteFoundAndPublished_IncludesPageBlocksByRegionInJson(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();
		$block = factory(Block::class)->create([ 'page_id' => $page->getKey() ]);

		$page->publish(new PageTransformer);

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $route->path ]);

		$json = $response->json();
		$this->assertArrayHasKey('data', $json);
		$this->assertArrayHasKey('blocks', $json['data']);
		$this->assertArrayHasKey('test-region', $json['data']['blocks']);
		$this->assertCount(1, $json['data']['blocks']);
	}

	/**
	 * @test
	 * @group authorization
	 *
	 * Resolves via a Redirect model
	 */
	public function resolve_WhenAuthenticatedAndRedirectFound_ChecksAuthorization(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->create();
		$page->publish(new PageTransformer);

		$redirect = new Redirect([ 'path' => '/foobar', 'page_id' => $page->getKey() ]);
		$redirect->save();

		Gate::shouldReceive('allows')->with('read', Mockery::type(Redirect::class))->once();

		$this->authenticated();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $redirect->path ]);
	}

	/**
	 * @test
	 * @group authorization
	 *
	 * Resolves via a Redirect model
	 */
	public function resolve_WhenAuthenticatedAndRedirectFoundButUnauthorized_Returns404(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->create();
		$page->publish(new PageTransformer);

		$redirect = new Redirect([ 'path' => '/foobar', 'page_id' => $page->getKey() ]);
		$redirect->save();

		$this->authenticatedAndUnauthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $redirect->path ]);
		$response->assertStatus(404);
	}

	/**
	 * @test
	 *
	 * Resolves via a Redirect model
	 */
	public function resolve_WhenAuthorizedAndRedirectFoundButNotPublished_Returns200(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->create();

		$redirect = new Redirect([ 'path' => '/foobar', 'page_id' => $page->getKey() ]);
		$redirect->save();

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $redirect->path ]);

		$response->assertStatus(200);
	}

	/**
	 * @test
	 *
	 * Resolves via a Redirect model
	 */
	public function resolve_WhenAuthorizedAndRedirectFoundButNotPublished_ReturnsJson(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->create();

		$redirect = new Redirect([ 'path' => '/foobar', 'page_id' => $page->getKey() ]);
		$redirect->save();

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $redirect->path ]);

		$json = $response->json();
		$this->assertArrayHasKey('data', $json);
		$this->assertEquals($redirect->page->id, $json['data']['id']);
	}

	/**
	 * @test
	 *
	 * Resolves via a Redirect model
	 */
	public function resolve_WhenAuthorizedAndRedirectFoundAndPublished_Returns200(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->create();
		$page->publish(new PageTransformer);

		$redirect = new Redirect([ 'path' => '/foobar', 'page_id' => $page->getKey() ]);
		$redirect->save();

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $redirect->path ]);

		$response->assertStatus(200);
	}

	/**
	 * @test
	 *
	 * Resolves via a Redirect model
	 */
	public function resolve_WhenAuthorizedAndRedirectFoundAndPublished_ReturnsJson(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->create();
		$page->publish(new PageTransformer);

		$redirect = new Redirect([ 'path' => '/foobar', 'page_id' => $page->getKey() ]);
		$redirect->save();

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $redirect->path ]);

		$json = $response->json();
		$this->assertArrayHasKey('data', $json);
		$this->assertEquals($redirect->page->id, $json['data']['id']);
	}

	/**
	 * @test
	 *
	 * Resolves via a Redirect model
	 */
	public function resolve_WhenAuthorizedAndRedirectFoundAndPublished_IncludesPageBlocksByRegionInJson(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->create();
		$block = factory(Block::class)->create([ 'page_id' => $page->getKey() ]);

		$page->publish(new PageTransformer);

		$redirect = new Redirect([ 'path' => '/foobar', 'page_id' => $page->getKey() ]);
		$redirect->save();

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $redirect->path ]);

		$json = $response->json();
		$this->assertArrayHasKey('data', $json);
		$this->assertArrayHasKey('blocks', $json['data']);
		$this->assertArrayHasKey('test-region', $json['data']['blocks']);
		$this->assertCount(1, $json['data']['blocks']);
	}

	/**
	 * @test
	 * @group integration
	 *
	 * Resolves via a Route model.
	 * This test tests behaviour applied to the Route model by the Routable trait.
	 */
	public function resolve_WhenAuthorizedAndRouteFoundAndPublishedPageIsSoftDeleted_Returns200(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->states([ 'withRevision' ])->create();
		$page->publish(new PageTransformer);

		$page->delete();

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $route->path ]);

		$response->assertStatus(200);
	}

	/**
	 * @test
	 * @group integration
	 *
	 * Resolves via a Route model.
	 * This test tests behaviour applied to the Route model by the Routable trait.
	 */
	public function resolve_WhenAuthorizedAndRouteFoundAndPublishedPageIsSoftDeleted_ReturnsJson(){
		$this->markTestIncomplete();
		$page = factory(Page::class)->states([ 'withRevision'])->create();
		$page->publish(new PageTransformer);

		$page->delete();

		$this->authenticatedAndAuthorized();
		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => $route->path ]);

		$json = $response->json();
		$this->assertArrayHasKey('data', $json);
		$this->assertEquals($page->id, $json['data']['id']);
	}


	/**
	 * @test
	 */
	public function resolve_WhenBothRouteAndRedirectAreNotFound_Returns404(){
		$this->markTestIncomplete();
		$this->authenticatedAndAuthorized();

		$response = $this->action('GET', PageController::class . '@resolve', [ 'path' => '/foobar' ]);
		$response->assertStatus(404);
	}


}
