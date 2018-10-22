<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Psy\Command\Command;

class Kernel extends ConsoleKernel
{
	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		Commands\CheckDefns::class,
        Commands\AddSite::class,
        Commands\AddUser::class,
        Commands\SetupPermissions::class,
		Commands\ManageAdmins::class,
		Commands\ClearCache::class,
		Commands\MigratePages::class,
		Commands\RenewUserAPITokens::class,
		Commands\UpdateSiteURL::class,
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		$schedule->command('astro:renewapitokens')->dailyAt('05:00');
	}

	/**
	 * Register the Closure based commands for the application.
	 *
	 * @return void
	 */
	protected function commands()
	{
		require base_path('routes/console.php');
	}
}
