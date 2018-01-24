<?php
/**
 * Holds the setup command controller.
 * @package Miiverse
 */

namespace Miiverse\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Miiverse\DB;
use Miiverse\Net;
use Miiverse\User;

/**
 * The command that handles setting up the base data.
 * @package Miiverse
 * @author Repflez
 */
class SetupCommand extends Command
{
	/**
	 * Set up the command metadata.
	 */
	protected function configure() : void {
		$this->setName('db:setup')->setDescription('Adds data to the tables.')->setHelp('Adds the required data to the tables, only needed once after the initial migration.')
		;
	}

	/**
	 * Adds data to the database required to get everything running.
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$io = new SymfonyStyle($input, $output);
		$io->title('TestVerse Setup');

		// Check if the users table has user with id 1
		$userCheck = DB::table('users')->where('user_id', 1)->count();

		// If positive, stop
		if ($userCheck > 0) {
			$io->error("It appears that you've already done the setup already! If this isn't the case, make sure your tables are empty.");
			return 0;
		}

		// Rank data (uses column names)
		$ranks = [
			[
				'rank_hierarchy' => 1,
				'rank_name' => 'Normal user',
				'rank_multiple' => 's',
				'rank_description' => 'Regular users with regular permissions.',
				'rank_title' => 'Member',
			],
			[
				'rank_hierarchy' => 1,
				'rank_name' => 'Verified',
				'rank_multiple' => 's',
				'rank_description' => 'Users that have been verified by our staff.',
				'rank_title' => 'Verified',
			],
			[
				'rank_hierarchy' => 3,
				'rank_name' => 'Moderator',
				'rank_multiple' => 's',
				'rank_colour' => '#fa3703',
				'rank_description' => 'Users with special permissions to keep the community at peace.',
				'rank_title' => 'Moderator',
			],
			[
				'rank_hierarchy' => 4,
				'rank_name' => 'Administrator',
				'rank_multiple' => 's',
				'rank_colour' => '#824ca0',
				'rank_description' => 'Users that manage the and everything around that.',
				'rank_title' => 'Administrator',
			],
			[
				'rank_hierarchy' => 0,
				'rank_name' => 'Banned',
				'rank_colour' => '#666',
				'rank_description' => 'Banned users.',
				'rank_title' => 'Banned',
			],
		];

		// Insert all the ranks into the database
		foreach ($ranks as $rank) {
			DB::table('ranks')->insert($rank);
		}

		// Permission data
		$perms = [
			[
				'rank_id' => config('rank.regular'),
				'perm_change_profile' => true,
				'perm_change_mii' => true,
				'perm_change_bio' => true,
				'perm_posts_close' => true,
				'perm_posts_create' => true,
				'perm_posts_draw' => true,
				'perm_posts_delete' => true,
				'perm_posts_vote' => true,
			],
			[
				'rank_id' => config('rank.mod'),
				'perm_view_user_details' => true,
				'perm_is_mod' => true,
			],
			[
				'rank_id' => config('rank.admin'),
				'perm_change_username' => true,
				'perm_change_user_title' => true,
				'perm_view_user_details' => true,
				'perm_is_mod' => true,
				'perm_is_admin' => true,
				'perm_can_restrict' => true,
			],
			[
				'rank_id' => config('rank.banned'),
				'perm_change_profile' => false,
				'perm_change_mii' => false,
				'perm_change_bio' => false,
				'perm_change_username' => false,
				'perm_change_user_title' => false,
				'perm_view_user_details' => false,
				'perm_posts_close' => false,
				'perm_posts_create' => false,
				'perm_posts_draw' => false,
				'perm_posts_delete' => false,
				'perm_posts_vote' => false,
				'perm_is_mod' => false,
				'perm_is_admin' => false,
				'perm_can_restrict' => false,
			],
		];

		// Insert all the permissions into the database
		foreach ($perms as $perm) {
			DB::table('perms')->insert($perm);
		}

		// Base user
		$baseUserId = DB::table('users')->insertGetId([
			'username' => 'TestVerse',
			'username_clean' => 'testverse',
			'register_ip' => Net::pton('::1'),
			'last_ip' => Net::pton('::1'),
			'user_registered' => time(),
			'user_last_online' => 0,
			'user_country' => 'US',
			'user_activated' => true,
			'nnid' => 'Testverse',
			'nnid_clean' => 'testverse',
		]);

		// Create the actual user object
		$baseUser = User::construct($baseUserId);

		// Add ranks to the user
		$baseUser->addRanks([
			config('rank.regular'),
			config('rank.admin'),
		]);

		// Set the main rank to admin
		$baseUser->setMainRank(config('rank.admin'));

		$io->text('Success! TestVerse has been installed in this host.');
		$io->text('A dummy account has been made. Make sure to set it up to your console ID.');
		$io->text('Read the documentation for information on how to do that!');
	}
}
