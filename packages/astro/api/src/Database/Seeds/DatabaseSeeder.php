<?php

namespace Astro\API\Database\Seeds;

use Astro\API\Models\LocalAPIClient;
use App\Models\User;
use Astro\API\Models\Role;
use Astro\API\Models\Page;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
	/**
	 * @var array
	 */
	private $tables = [
	    'revisions',
        'redirects',
	    'deleted_pages',
        'revision_sets',
	    'pages',
        'sites',
		'users',
		'blocks'
	];


	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		if(\App::environment() === 'production')
		{
			exit('The seeder should only be run in dev mode.');
		}

		$this->cleanDatabase();

		$user = factory(User::class)->create([
			'username' => 'admin',
			'name'=> 'Admin',
			'password'=> \Hash::make('admin'),
			'role' => 'admin',
            'api_token' => 'test'
		]);

		// create some users to test with...
		$editor = factory(User::class)->create([
			'username' => 'editor',
			'name' => 'Editor',
			'password' => \Hash::make('editor'),
			'role' => 'user',
			'api_token' => 'editor-test'
		]);

		$owner = factory(User::class)->create([
			'username' => 'owner',
			'name' => 'Owner',
			'password' => \Hash::make('owner'),
			'role' => 'user',
			'api_token' => 'owner-test'
		]);

		$contributor = factory(User::class)->create([
			'username' => 'contributor',
			'name' => 'Contributor',
			'password' => \Hash::make('contributor'),
			'role' => 'user',
			'api_token' => 'contributor-test'
		]);



		$client = new LocalAPIClient($user);
        $site = $client->createSite(
            'Test Site', 'example.com', '', ['name'=>'school-site','version'=>1]
        );
        $client->publishPage(Page::forSiteAndPath($site->id, '/')->first()->id);

		$client->updateSiteUserRole($site->id,'editor', Role::EDITOR);
		$client->updateSiteUserRole($site->id,'owner', Role::OWNER);
		$client->updateSiteUserRole($site->id,'contributor', Role::CONTRIBUTOR);
    }

	/**
	 * Empty the database
	 *
	 * @return void
	 */
	private function cleanDatabase()
	{
		// allow mass assignment
		\Eloquent::unguard();

		\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

		foreach($this->tables as $table)
		{
			\DB::table($table)->truncate();
		}

		\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
	}

}