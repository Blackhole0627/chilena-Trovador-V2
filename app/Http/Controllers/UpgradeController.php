<?php

namespace App\Http\Controllers;

use App\Helper;
use App\Models\User;
use App\Models\Media;
use App\Models\Plans;
use App\Models\Sticker;
use App\Models\Updates;
use App\Models\Messages;
use App\Models\Languages;
use App\Models\Referrals;
use App\Models\AdminSettings;
use App\Models\MediaMessages;
use App\Models\Notifications;
use App\Models\Subscriptions;
use App\Models\PaymentGateways;
use Illuminate\Support\Facades\DB;
use App\Models\ReferralTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Storage;

class UpgradeController extends Controller
{

	public function __construct(AdminSettings $settings, Updates $updates, User $user)
	{
		$this->settings  = $settings::first();
		$this->user      = $user::first();
		$this->updates   = $updates::first();
	}

	/**
	 * Move a file
	 *
	 */
	private static function moveFile($file, $newFile, $copy)
	{
		if (File::exists($file) && $copy == false) {
			File::delete($newFile);
			File::move($file, $newFile);
		} else if (File::exists($newFile) && isset($copy)) {
			File::copy($newFile, $file);
		}
	}

	/**
	 * Copy a directory
	 *
	 */
	private static function moveDirectory($directory, $destination, $copy)
	{
		if (File::isDirectory($directory) && $copy == false) {
			File::moveDirectory($directory, $destination);
		} else if (File::isDirectory($destination) && isset($copy)) {
			File::copyDirectory($destination, $directory);
		}
	}

	public function update($version)
	{
		$DS = DIRECTORY_SEPARATOR;

		$ROOT = base_path() . $DS;
		$APP = app_path() . $DS;
		$BOOTSTRAP_CACHE = base_path('bootstrap' . $DS . 'cache') . $DS;
		$ACTIONS = app_path('Actions') . $DS;
		$ENUMS = app_path('Enums') . $DS;
		$LIBRARY = app_path('Library') . $DS;
		$MODELS = app_path('Models') . $DS;
		$NOTIFICATIONS = app_path('Notifications') . $DS;
		$CONTROLLERS = app_path('Http' . $DS . 'Controllers') . $DS;
		$CONTROLLERS_AUTH = app_path('Http' . $DS . 'Controllers' . $DS . 'Auth') . $DS;
		$MIDDLEWARE = app_path('Http' . $DS . 'Middleware') . $DS;
		$JOBS = app_path('Jobs') . $DS;
		$TRAITS = app_path('Http' . $DS . 'Controllers' . $DS . 'Traits') . $DS;
		$PROVIDERS = app_path('Providers') . $DS;
		$SERVICES = app_path('Services') . $DS;
		$EVENTS = app_path('Events') . $DS;
		$EXCEPTIONS = app_path('Exceptions') . $DS;
		$LISTENERS = app_path('Listeners') . $DS;
		$RULES = app_path('Rules') . $DS;

		$CONFIG = config_path() . $DS;
		$ROUTES = base_path('routes') . $DS;

		$PUBLIC_ADMIN = public_path('admin') . $DS;
		$PUBLIC_JS_ADMIN = public_path('admin' . $DS . 'js') . $DS;
		$PUBLIC_CSS_ADMIN = public_path('admin' . $DS . 'css') . $DS;
		$PUBLIC_BOOTSTRAP_CSS = public_path('bootstrap' . $DS . 'css') . $DS;
		$PUBLIC_BOOTSTRAP_JS = public_path('bootstrap' . $DS . 'js') . $DS;
		$PUBLIC_JS = public_path('js') . $DS;
		$PUBLIC_JS_REELS = public_path('js' . $DS . 'reels') . $DS;
		$PUBLIC_CSS = public_path('css') . $DS;
		$PUBLIC_IMG = public_path('img') . $DS;
		$PUBLIC_IMG_ICONS = public_path('img' . $DS . 'icons') . $DS;
		$PUBLIC_FONTS = public_path('webfonts') . $DS;

		$VIEWS = resource_path('views') . $DS;
		$VIEWS_ADMIN = resource_path('views' . $DS . 'admin') . $DS;
		$VIEWS_AJAX = resource_path('views' . $DS . 'ajax') . $DS;
		$VIEWS_AUTH = resource_path('views' . $DS . 'auth') . $DS;
		$VIEWS_AUTH_PASS = resource_path('views' . $DS . 'auth' . $DS . 'passwords') . $DS;
		$VIEWS_EMAILS = resource_path('views' . $DS . 'emails') . $DS;
		$VIEWS_ERRORS = resource_path('views' . $DS . 'errors') . $DS;
		$VIEWS_INCLUDES = resource_path('views' . $DS . 'includes') . $DS;
		$VIEWS_REELS = resource_path('views' . $DS . 'reels') . $DS;
		$VIEWS_INSTALL = resource_path('views' . $DS . 'installer') . $DS;
		$VIEWS_INDEX = resource_path('views' . $DS . 'index') . $DS;
		$VIEWS_LAYOUTS = resource_path('views' . $DS . 'layouts') . $DS;
		$VIEWS_PAGES = resource_path('views' . $DS . 'pages') . $DS;
		$VIEWS_SHOP = resource_path('views' . $DS . 'shop') . $DS;
		$VIEWS_USERS = resource_path('views' . $DS . 'users') . $DS;

		$upgradeDone = '<h2 style="text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #4BBA0B;">' . trans('admin.upgrade_done') . ' <a style="text-decoration: none; color: #F50;" href="' . url('/') . '">' . trans('error.go_home') . '</a> ' . trans('general.or') . ' <a style="text-decoration: none; color: #F50;" href="' . url('panel/admin') . '">' . trans('admin.admin') . '</a></h2>';

		if ($version == '1.1') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$file1 = 'Helper.php';
			$file2 = 'UserController.php';
			$file3 = 'StripeWebHookController.php';

			$file4 = 'Messages.php';
			$file5 = 'Comments.php';
			$file6 = 'Notifications.php';

			$file7 = 'edit_my_page.blade.php';
			$file8 = 'blog.blade.php';
			$file9 = 'posts.blade.php';
			$file10 = 'updates.blade.php';

			$file11 = 'app-functions.js';


			//============== Moving Files ================//
			$this->moveFile($path . $file1, $APP . $file1, $copy);
			$this->moveFile($path . $file2, $CONTROLLERS . $file2, $copy);
			$this->moveFile($path . $file3, $CONTROLLERS . $file3, $copy);

			$this->moveFile($path . $file4, $MODELS . $file4, $copy);
			$this->moveFile($path . $file5, $MODELS . $file5, $copy);
			$this->moveFile($path . $file6, $MODELS . $file6, $copy);

			$this->moveFile($path . $file7, $VIEWS_USERS . $file7, $copy);
			$this->moveFile($path . $file8, $VIEWS_INDEX . $file8, $copy);
			$this->moveFile($path . $file9, $VIEWS_ADMIN . $file9, $copy);
			$this->moveFile($path . $file10, $VIEWS_INCLUDES . $file10, $copy);

			$this->moveFile($path . $file11, $PUBLIC_JS . $file11, $copy);


			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 1.1 ----->>

		if ($version == '1.2') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			if (!Schema::hasColumn('admin_settings', 'widget_creators_featured', 'home_style')) {
				Schema::table('admin_settings', function ($table) {
					$table->enum('widget_creators_featured', ['on', 'off'])->default('on');
					$table->unsignedInteger('home_style');
				});
			}

			if (!Schema::hasColumn('updates', 'fixed_post')) {
				Schema::table('updates', function ($table) {
					$table->enum('fixed_post', ['0', '1'])->default('0');
				});
			}

			if (!Schema::hasColumn('users', 'dark_mode')) {
				Schema::table('users', function ($table) {
					$table->enum('dark_mode', ['on', 'off'])->default('off');
				});
			}

			// Create Table Bookmarks
			if (!Schema::hasTable('bookmarks')) {
				Schema::create('bookmarks', function ($table) {
					$table->increments('id');
					$table->unsignedInteger('user_id')->index();
					$table->unsignedInteger('updates_id')->index();
					$table->timestamps();
				});
			} // <<--- End Create Table Bookmarks

			//============== Files Affected ================//
			$file1 = 'UpdatesController.php';
			$file2 = 'UserController.php';
			$file3 = 'AdminController.php';
			$file4 = 'HomeController.php';
			$file5 = 'MessagesController.php';
			$file6 = 'SocialAccountService.php';
			$file7 = 'PayPalController.php';

			$file8 = 'UserDelete.php'; // Traits
			$file9 = 'User.php';
			$file10 = 'Bookmarks.php';
			$file11 = 'Updates.php';

			$file12 = 'web.php';

			$file14 = 'bookmarks.blade.php';
			$file15 = 'home-session.blade.php';
			$file16 = 'css_general.blade.php';
			$file17 = 'javascript_general.blade.php';
			$file18 = 'limits.blade.php';
			$file19 = 'navbar.blade.php';
			$file20 = 'footer.blade.php';

			$file21 = 'settings.blade.php';
			$file22 = 'layout.blade.php';
			$file23 = 'updates.blade.php';

			$file24 = 'home.blade.php';
			$file25 = 'profile.blade.php';

			$file26 = 'withdrawals.blade.php';
			$file27 = 'withdrawals.blade.php';
			$file28 = 'social-login.blade.php';
			$file29 = 'app.blade.php';

			$file30 = 'app-functions.js';
			$file31 = 'bootstrap-dark.min.css';

			$file32 = 'bell-light.svg';
			$file33 = 'compass-light.svg';
			$file34 = 'home-light.svg';
			$file35 = 'paper-light.svg';


			//============== Moving Files ================//
			$this->moveFile($path . $file1, $CONTROLLERS . $file1, $copy);
			$this->moveFile($path . $file2, $CONTROLLERS . $file2, $copy);
			$this->moveFile($path . $file3, $CONTROLLERS . $file3, $copy);
			$this->moveFile($path . $file4, $CONTROLLERS . $file4, $copy);
			$this->moveFile($path . $file5, $CONTROLLERS . $file5, $copy);
			$this->moveFile($path . $file6, $APP . $file6, $copy);
			$this->moveFile($path . $file7, $CONTROLLERS . $file7, $copy);

			$this->moveFile($path . $file8, $TRAITS . $file8, $copy);
			$this->moveFile($path . $file9, $MODELS . $file9, $copy);
			$this->moveFile($path . $file10, $MODELS . $file10, $copy);
			$this->moveFile($path . $file11, $MODELS . $file11, $copy);

			$this->moveFile($path . $file12, $ROUTES . $file12, $copy);

			$this->moveFile($path . $file14, $VIEWS_USERS . $file14, $copy);
			$this->moveFile($path . $file15, $VIEWS_INDEX . $file15, $copy);
			$this->moveFile($path . $file16, $VIEWS_INCLUDES . $file16, $copy);
			$this->moveFile($path . $file17, $VIEWS_INCLUDES . $file17, $copy);
			$this->moveFile($path . $file18, $VIEWS_ADMIN . $file18, $copy);
			$this->moveFile($path . $file19, $VIEWS_INCLUDES . $file19, $copy);
			$this->moveFile($path . $file20, $VIEWS_INCLUDES . $file20, $copy);
			$this->moveFile($path . $file21, $VIEWS_ADMIN . $file21, $copy);
			$this->moveFile($path . $file22, $VIEWS_ADMIN . $file22, $copy);
			$this->moveFile($path . $file23, $VIEWS_INCLUDES . $file23, $copy);
			$this->moveFile($path . $file24, $VIEWS_INDEX . $file24, $copy);
			$this->moveFile($path . $file25, $VIEWS_USERS . $file25, $copy);
			$this->moveFile($path . $file26, $VIEWS_USERS . $file26, $copy);
			$this->moveFile($pathAdmin . $file27, $VIEWS_ADMIN . $file27, $copy);
			$this->moveFile($path . $file28, $VIEWS_ADMIN . $file28, $copy);
			$this->moveFile($path . $file29, $VIEWS_LAYOUTS . $file29, $copy);

			$this->moveFile($path . $file30, $PUBLIC_JS . $file30, $copy);
			$this->moveFile($path . $file31, $PUBLIC_CSS . $file31, $copy);

			$this->moveFile($path . $file32, $PUBLIC_IMG_ICONS . $file32, $copy);
			$this->moveFile($path . $file33, $PUBLIC_IMG_ICONS . $file33, $copy);
			$this->moveFile($path . $file34, $PUBLIC_IMG_ICONS . $file34, $copy);
			$this->moveFile($path . $file35, $PUBLIC_IMG_ICONS . $file35, $copy);


			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 1.2 ----->>

		if ($version == '1.3') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			if (!Schema::hasColumn('admin_settings', 'file_size_allowed_verify_account')) {
				Schema::table('admin_settings', function ($table) {
					$table->unsignedInteger('file_size_allowed_verify_account');
				});

				if (Schema::hasColumn('admin_settings', 'file_size_allowed_verify_account')) {
					AdminSettings::whereId(1)->update([
						'file_size_allowed_verify_account' => 1024
					]);
				}
			}

			//============== Files Affected ================//
			$file3 = 'AdminController.php';
			$file5 = 'MessagesController.php';

			$file8 = 'UserDelete.php'; // Traits

			$file14 = 'verify_account.blade.php';
			$file16 = 'css_general.blade.php';
			$file18 = 'limits.blade.php';

			$file22 = 'dashboard.blade.php';

			$file29 = 'app.blade.php';

			$file30 = 'app-functions.js';
			$file31 = 'messages.js';

			//============== Moving Files ================//
			$this->moveFile($path . $file3, $CONTROLLERS . $file3, $copy);
			$this->moveFile($path . $file5, $CONTROLLERS . $file5, $copy);

			$this->moveFile($path . $file8, $TRAITS . $file8, $copy);

			$this->moveFile($path . $file14, $VIEWS_USERS . $file14, $copy);
			$this->moveFile($path . $file16, $VIEWS_INCLUDES . $file16, $copy);
			$this->moveFile($path . $file18, $VIEWS_ADMIN . $file18, $copy);

			$this->moveFile($path . $file22, $VIEWS_ADMIN . $file22, $copy);

			$this->moveFile($path . $file29, $VIEWS_LAYOUTS . $file29, $copy);

			$this->moveFile($path . $file30, $PUBLIC_JS . $file30, $copy);
			$this->moveFile($path . $file31, $PUBLIC_JS . $file31, $copy);


			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 1.3 ----->>

		if ($version == '1.4') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			PaymentGateways::whereId(1)->update([
				'recurrent' => 'no',
				'logo' => 'paypal.png',
			]);

			PaymentGateways::whereId(2)->update([
				'logo' => 'stripe.png',
			]);

			//============== Files Affected ================//
			$file3 = 'AdminController.php';
			$file5 = 'UserController.php';
			$file18 = 'storage.blade.php';
			$file29 = 'app.blade.php';


			//============== Moving Files ================//
			$this->moveFile($path . $file3, $CONTROLLERS . $file3, $copy);
			$this->moveFile($path . $file5, $CONTROLLERS . $file5, $copy);
			$this->moveFile($path . $file18, $VIEWS_ADMIN . $file18, $copy);
			$this->moveFile($path . $file29, $VIEWS_LAYOUTS . $file29, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 1.4 ----->>

		if ($version == '1.5') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$file5 = 'UserController.php';
			$file6 = 'SocialAccountService.php';
			$file18 = 'updates.blade.php';
			$file29 = 'app.blade.php';
			$file30 = 'profile.blade.php';
			$file31 = 'edit_my_page.blade.php';


			//============== Moving Files ================//
			$this->moveFile($path . $file5, $CONTROLLERS . $file5, $copy);
			$this->moveFile($path . $file6, $APP . $file6, $copy);
			$this->moveFile($path . $file18, $VIEWS_INCLUDES . $file18, $copy);
			$this->moveFile($path . $file29, $VIEWS_LAYOUTS . $file29, $copy);
			$this->moveFile($path . $file30, $VIEWS_USERS . $file30, $copy);
			$this->moveFile($path . $file31, $VIEWS_USERS . $file31, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 1.5 ----->>

		if ($version == '1.6') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			if (!Schema::hasColumn(
				'users',
				'gender',
				'birthdate',
				'allow_download_files',
				'language'
			)) {
				Schema::table('users', function ($table) {
					$table->string('gender', 50);
					$table->string('birthdate', 30);
					$table->enum('allow_download_files', ['no', 'yes'])->default('no');
					$table->string('language', 10);
				});
			}

			if (!Schema::hasColumn('transactions', 'type')) {
				Schema::table('transactions', function ($table) {
					$table->enum('type', ['subscription', 'tip', 'ppv'])->default('subscription');
				});
			}

			if (!Schema::hasColumn(
				'admin_settings',
				'payout_method_paypal',
				'payout_method_bank',
				'min_tip_amount',
				'max_tip_amount',
				'min_ppv_amount',
				'max_ppv_amount',
				'min_deposits_amount',
				'max_deposits_amount',
				'button_style',
				'twitter_login',
				'hide_admin_profile',
				'requests_verify_account',
				'navbar_background_color',
				'navbar_text_color',
				'footer_background_color',
				'footer_text_color'

			)) {
				Schema::table('admin_settings', function ($table) {
					$table->enum('payout_method_paypal', ['on', 'off'])->default('on');
					$table->enum('payout_method_bank', ['on', 'off'])->default('on');
					$table->unsignedInteger('min_tip_amount');
					$table->unsignedInteger('max_tip_amount');
					$table->unsignedInteger('min_ppv_amount');
					$table->unsignedInteger('max_ppv_amount');
					$table->unsignedInteger('min_deposits_amount');
					$table->unsignedInteger('max_deposits_amount');
					$table->enum('button_style', ['rounded', 'normal'])->default('rounded');
					$table->enum('twitter_login', ['on', 'off'])->default('off');
					$table->enum('hide_admin_profile', ['on', 'off'])->default('off');
					$table->enum('requests_verify_account', ['on', 'off'])->default('on');
					$table->string('navbar_background_color', 30);
					$table->string('navbar_text_color', 30);
					$table->string('footer_background_color', 30);
					$table->string('footer_text_color', 30);
				});
			}

			file_put_contents(
				'.env',
				"\nTWITTER_CLIENT_ID=\nTWITTER_CLIENT_SECRET=\n",
				FILE_APPEND
			);

			$sql = new Languages();
			$sql->name = 'Español';
			$sql->abbreviation = 'es';
			$sql->save();

			AdminSettings::whereId(1)->update([
				'navbar_background_color' => '#ffffff',
				'navbar_text_color' => '#3a3a3a',
				'footer_background_color' => '#ffffff',
				'footer_text_color' => '#5f5f5f',
				'min_tip_amount' => 5,
				'max_tip_amount' => 99
			]);

			DB::statement("ALTER TABLE reports MODIFY reason ENUM('copyright', 'privacy_issue', 'violent_sexual', 'spoofing', 'spam', 'fraud', 'under_age') NOT NULL");

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 1.6 ----->>

		if ($version == '1.7') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$file5 = 'UserController.php';
			$file6 = 'RegisterController.php';
			$file18 = 'home-login.blade.php';
			$file29 = 'app.blade.php';
			$file30 = 'password.blade.php';
			$file31 = 'edit_my_page.blade.php';
			$file32 = 'invoice.blade.php';


			//============== Moving Files ================//
			$this->moveFile($path . $file5, $CONTROLLERS . $file5, $copy);
			$this->moveFile($path . $file6, $CONTROLLERS_AUTH . $file6, $copy);
			$this->moveFile($path . $file18, $VIEWS_INDEX . $file18, $copy);
			$this->moveFile($path . $file29, $VIEWS_LAYOUTS . $file29, $copy);
			$this->moveFile($path . $file30, $VIEWS_USERS . $file30, $copy);
			$this->moveFile($path . $file31, $VIEWS_USERS . $file31, $copy);
			$this->moveFile($path . $file32, $VIEWS_USERS . $file32, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 1.7 ----->>

		if ($version == '1.8') {

			//============ Starting moving files...
			$oldVersion = '1.6';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = false;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion && $this->settings->version != '1.7' || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			if (!Schema::hasColumn('payment_gateways', 'subscription')) {
				Schema::table('payment_gateways', function ($table) {
					$table->enum('subscription', ['yes', 'no'])->default('yes');
				});
			}

			DB::table('payment_gateways')->insert([
				[
					'name' => 'Bank Transfer',
					'type' => 'bank',
					'enabled' => '0',
					'fee' => 0.0,
					'fee_cents' => 0.00,
					'email' => '',
					'key' => '',
					'key_secret' => '',
					'bank_info' => '',
					'recurrent' => 'no',
					'logo' => '',
					'webhook_secret' => '',
					'subscription' => 'no',
					'token' => str_random(150),
				]
			]);

			if (!Schema::hasColumn('admin_settings', 'announcements', 'preloading', 'preloading_image', 'watermark')) {
				Schema::table('admin_settings', function ($table) {
					$table->text('announcements');
					$table->enum('preloading', ['on', 'off'])->default('off');
					$table->string('preloading_image', 100);
					$table->enum('watermark', ['on', 'off'])->default('on');
					$table->enum('earnings_simulator', ['on', 'off'])->default('on');
				});
			}

			if (!Schema::hasColumn('users', 'free_subscription', 'wallet')) {
				Schema::table('users', function ($table) {
					$table->enum('free_subscription', ['yes', 'no'])->default('no');
					$table->decimal('wallet', 10, 2);
					$table->string('tiktok', 200);
					$table->string('snapchat', 200);
				});
			}

			if (!Schema::hasColumn('updates', 'price', 'youtube', 'vimeo', 'file_name', 'file_size')) {
				Schema::table('updates', function ($table) {
					$table->decimal('price', 10, 2);
					$table->string('video_embed', 200);
					$table->string('file_name', 255);
					$table->string('file_size', 50);
				});
			}

			if (!Schema::hasColumn('subscriptions', 'free')) {
				Schema::table('subscriptions', function ($table) {
					$table->enum('free', ['yes', 'no'])->default('no');
				});
			}

			if (!Schema::hasColumn('messages', 'price', 'tip', 'tip_amount')) {
				Schema::table('messages', function ($table) {
					$table->decimal('price', 10, 2);
					$table->enum('tip', ['yes', 'no'])->default('no');
					$table->unsignedInteger('tip_amount');
				});
			}

			// Create table Deposits
			if (!Schema::hasTable('deposits')) {

				Schema::create('deposits', function ($table) {

					$table->engine = 'InnoDB';
					$table->increments('id');
					$table->unsignedInteger('user_id');
					$table->string('txn_id', 200);
					$table->unsignedInteger('amount');
					$table->string('payment_gateway', 100);
					$table->timestamp('date');
					$table->enum('status', ['active', 'pending'])->default('active');
					$table->string('screenshot_transfer', 100);
				});
			} // <<< --- Create table Deposits

			//============== Files Affected ================//
			$files = [
				'UpdatesController.php' => $CONTROLLERS,
				'PayPalController.php' => $CONTROLLERS,
				'AdminController.php' => $CONTROLLERS,
				'HomeController.php' => $CONTROLLERS,
				'MessagesController.php' => $CONTROLLERS,
				'SubscriptionsController.php' => $CONTROLLERS,
				'StripeController.php' => $CONTROLLERS,
				'AddFundsController.php' => $CONTROLLERS,
				'UserController.php' => $CONTROLLERS,
				'InstallScriptController.php' => $CONTROLLERS,
				'Helper.php' => $APP,
				'Subscriptions.php' => $MODELS,
				'Deposits.php' => $MODELS,
				'app.blade.php' => $VIEWS_LAYOUTS,
				'javascript_general.blade.php' => $VIEWS_INCLUDES,
				'home-login.blade.php' => $VIEWS_INDEX,
				'register.blade.php' => $VIEWS_AUTH,
				'notifications.blade.php' => $VIEWS_USERS,
				'my_payments.blade.php' => $VIEWS_USERS,
				'navbar.blade.php' => $VIEWS_INCLUDES,
				'edit-update.blade.php' => $VIEWS_USERS,
				'listing-creators.blade.php' => $VIEWS_INCLUDES,
				'explore_creators.blade.php' => $VIEWS_INCLUDES,
				'listing-explore-creators.blade.php' => $VIEWS_INCLUDES,
				'updates.blade.php' => $VIEWS_INCLUDES,
				'footer-tiny.blade.php' => $VIEWS_INCLUDES,
				'messages-chat.blade.php' => $VIEWS_INCLUDES,
				'footer.blade.php' => $VIEWS_INCLUDES,
				'profile.blade.php' => $VIEWS_USERS,
				'cards-settings.blade.php' => $VIEWS_INCLUDES,
				'subscription.blade.php' => $VIEWS_USERS,
				'messages-inbox.blade.php' => $VIEWS_INCLUDES,
				'css_general.blade.php' => $VIEWS_INCLUDES,
				'invoice.blade.php' => $VIEWS_USERS,
				'my_subscriptions.blade.php' => $VIEWS_USERS,
				'my_subscribers.blade.php' => $VIEWS_USERS,
				'dashboard.blade.php' => $VIEWS_USERS,
				'listing-categories.blade.php' => $VIEWS_INCLUDES,
				'email.blade.php' => $VIEWS_AUTH_PASS,
				'payout_method.blade.php' => $VIEWS_USERS,
				'sitemaps.blade.php' => $VIEWS_INDEX,
				'home-session.blade.php' => $VIEWS_INDEX,
				'form-post.blade.php' => $VIEWS_INCLUDES,
				'edit_my_page.blade.php' => $VIEWS_USERS,
				'home.blade.php' => $VIEWS_INDEX,
				'wallet.blade.php' => $VIEWS_USERS,
				'withdrawals.blade.php' => $VIEWS_USERS,
				'messages-show.blade.php' => $VIEWS_USERS,
				'requirements.blade.php' => $VIEWS_INSTALL,
				'transfer_verification.blade.php' => $VIEWS_EMAILS,
				'verify_account' => $VIEWS_USERS,
				'web.php' => $ROUTES,
				'arial.TTF' => $PUBLIC_FONTS,
				'add-funds.js' => $PUBLIC_JS,
				'app-functions.js' => $PUBLIC_JS,
				'messages.js' => $PUBLIC_JS,
				'payment.js' => $PUBLIC_JS
			];

			$filesAdmin = [
				'verification.blade.php' => $VIEWS_ADMIN,
				'transactions.blade.php' => $VIEWS_ADMIN,
				'posts.blade.php' => $VIEWS_ADMIN,
				'deposits-view.blade.php' => $VIEWS_ADMIN,
				'dashboard.blade.php' => $VIEWS_ADMIN,
				'charts.blade.php' => $VIEWS_ADMIN,
				'deposits.blade.php' => $VIEWS_ADMIN,
				'members.blade.php' => $VIEWS_ADMIN,
				'bank-transfer-settings.blade.php' => $VIEWS_ADMIN,
				'layout.blade.php' => $VIEWS_ADMIN,
				'settings.blade.php' => $VIEWS_ADMIN,
				'payments-settings.blade.php' => $VIEWS_ADMIN
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 1.8 ----->>

		if ($version == '1.9') {

			//============ Starting moving files...
			$oldVersion = '1.8';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			// Replace String
			$findStringLang = ');';

			// Ennglish
			$replaceLangEN    = "
	// Version 1.9
	'login_as_user' => 'Login as user',
	'login_as_user_warning' => 'This action will close your current session',
	'become_creator' => 'Become a creator',
);";
			$fileLangEN = 'resources/lang/en/general.php';
			@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

			// Español
			$replaceLangES    = "
	//----- Version 1.9
	'login_as_user' => 'Iniciar sesión como usuario',
	'login_as_user_warning' => 'Esta acción cerrará su sesión actual',
	'become_creator' => 'Conviértete en un creador',
);";
			$fileLangES = 'resources/lang/es/general.php';
			@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

			//============== Files Affected ================//
			$files = [
				'TipController.php' => $CONTROLLERS,
				'UpdatesController.php' => $CONTROLLERS,
				'AdminController.php' => $CONTROLLERS,
				'HomeController.php' => $CONTROLLERS,
				'MessagesController.php' => $CONTROLLERS,
				'UserController.php' => $CONTROLLERS,
				'app.blade.php' => $VIEWS_LAYOUTS,
				'javascript_general.blade.php' => $VIEWS_INCLUDES,
				'navbar.blade.php' => $VIEWS_INCLUDES,
				'listing-creators.blade.php' => $VIEWS_INCLUDES,
				'listing-explore-creators.blade.php' => $VIEWS_INCLUDES,
				'updates.blade.php' => $VIEWS_INCLUDES,
				'profile.blade.php' => $VIEWS_USERS,
				'cards-settings.blade.php' => $VIEWS_INCLUDES,
				'css_general.blade.php' => $VIEWS_INCLUDES,
				'edit_my_page.blade.php' => $VIEWS_USERS,
				'home.blade.php' => $VIEWS_INDEX,
				'messages-show.blade.php' => $VIEWS_USERS,
				'web.php' => $ROUTES,
				'app-functions.js' => $PUBLIC_JS,
				'messages.js' => $PUBLIC_JS,
				'UserDelete.php' => $TRAITS,
				'functions.js' => $PUBLIC_JS_ADMIN
			];

			$filesAdmin = [
				'charts.blade.php' => $VIEWS_ADMIN,
				'deposits.blade.php' => $VIEWS_ADMIN,
				'edit-member.blade.php' => $VIEWS_ADMIN,
				'layout.blade.php' => $VIEWS_ADMIN,
				'reports.blade.php' => $VIEWS_ADMIN
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 1.9 ----->>

		if ($version == '2.0') {

			//============ Starting moving files...
			$oldVersion = '1.9';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			file_put_contents(
				'.env',
				"\nBACKBLAZE_ACCOUNT_ID=\nBACKBLAZE_APP_KEY=\nBACKBLAZE_BUCKET=\nBACKBLAZE_BUCKET_ID=\n\nVULTR_ACCESS_KEY=\nVULTR_SECRET_KEY=\nVULTR_REGION=\nVULTR_BUCKET=\nVULTR_ENDPOINT=https://ewr1.vultrobjects.com\n\nPWA_SHORT_NAME=\"Sponzy\"\nPWA_ICON_72=public/images/icons/icon-72x72.png\nPWA_ICON_96=public/images/icons/icon-96x96.png\nPWA_ICON_128=public/images/icons/icon-128x128.png\nPWA_ICON_144=public/images/icons/icon-144x144.png\nPWA_ICON_152=public/images/icons/icon-152x152.png\nPWA_ICON_384=public/images/icons/icon-384x384.png\nPWA_ICON_512=public/images/icons/icon-512x512.png\n\nPWA_SPLASH_640=public/images/icons/splash-640x1136.png\nPWA_SPLASH_750=public/images/icons/splash-750x1334.png\nPWA_SPLASH_1125=public/images/icons/splash-1125x2436.png\nPWA_SPLASH_1242=public/images/icons/splash-1242x2208.png\nPWA_SPLASH_1536=public/images/icons/splash-1536x2048.png\nPWA_SPLASH_1668=public/images/icons/splash-1668x2224.png\nPWA_SPLASH_2048=public/images/icons/splash-2048x2732.png\n",
				FILE_APPEND
			);

			if (!Schema::hasColumn('verification_requests', 'form_w9')) {
				Schema::table('verification_requests', function ($table) {
					$table->string('form_w9', 100);
				});
			}

			if (!Schema::hasColumn('reserved', 'offline')) {
				\DB::table('reserved')->insert(
					['name' => 'offline']
				);
			}

			if (!Schema::hasColumn('admin_settings', 'custom_css', 'custom_js', 'alert_adult')) {
				Schema::table('admin_settings', function ($table) {
					$table->text('custom_css');
					$table->text('custom_js');
					$table->enum('alert_adult', ['on', 'off'])->default('off');
				});
			}

			if (Schema::hasTable('payment_gateways')) {
				\DB::table('payment_gateways')->insert(
					[
						[
							'name' => 'CCBill',
							'type' => 'card',
							'enabled' => '0',
							'fee' => 0.0,
							'fee_cents' => 0.00,
							'email' => '',
							'key' => '',
							'key_secret' => '',
							'logo' => '',
							'bank_info' => '',
							'token' => str_random(150),
						],
						[
							'name' => 'Paystack',
							'type' => 'card',
							'enabled' => '0',
							'fee' => 0.0,
							'fee_cents' => 0.00,
							'email' => '',
							'key' => '',
							'key_secret' => '',
							'logo' => '',
							'bank_info' => '',
							'token' => str_random(150),
						]
					]
				);
			}

			if (!Schema::hasColumn('payment_gateways', 'ccbill_accnum', 'ccbill_subacc', 'ccbill_flexid', 'ccbill_salt')) {
				Schema::table('payment_gateways', function ($table) {
					$table->string('ccbill_accnum', 200);
					$table->string('ccbill_subacc', 200);
					$table->string('ccbill_flexid', 200);
					$table->string('ccbill_salt', 200);
				});
			}

			PaymentGateways::whereId(1)->update([
				'recurrent' => 'yes'
			]);

			if (!Schema::hasColumn(
				'users',
				'paystack_plan',
				'paystack_authorization_code',
				'paystack_last4',
				'paystack_exp',
				'paystack_card_brand'
			)) {
				Schema::table('users', function ($table) {
					$table->string('paystack_plan', 100);
					$table->string('paystack_authorization_code', 100);
					$table->unsignedInteger('paystack_last4');
					$table->string('paystack_exp', 50);
					$table->string('paystack_card_brand', 25);
				});
			}

			if (!Schema::hasColumn('subscriptions', 'subscription_id', 'cancelled')) {
				Schema::table('subscriptions', function ($table) {
					$table->string('subscription_id', 50);
					$table->enum('cancelled', ['yes', 'no'])->default('no');
				});
			}


			// Replace String
			$findStringLang = ');';

			// Ennglish
			$replaceLangEN    = "
		//----- Version 2.0
		'show_errors' => 'Show Errors',
		'info_show_errors' => 'Recommended only in local or test mode',
		'alert_not_subscription' => 'You must set a price or enable Free Subscription to activate your subscription',
		'activate' => 'Activate',
		'my_cards' => 'My cards',
		'info_my_cards' => 'Cards available in your account',
		'add' => 'Add',
		'expiry' => 'Expiry',
		'powered_by' => 'Powered by',
		'notice_charge_to_card' => 'We will make a one-time charge of :amount when adding your payment card', // Not remove :amount
		'redirected_to_paypal_website' => 'You will be redirected to the PayPal website',
		'subscription_expire' => 'Your subscription will be active until',
		'subscribed_until' => 'Subscribed until',
		'cancel_subscription_paypal' => 'Cancel your subscription from your PayPal account, it will be active until',
		'confirm_cancel_payment' => 'Are you sure you want to cancel this transaction?',
		'test_smtp' => 'If you are using SMTP, do a test on the following link to verify that your data is correct.',
		'alert_paypal_delay' => '(Important: PayPal may have a delay, reload the page or wait a minute, otherwise, contact us)',
		'error_currency' => 'Currency not supported (Only NGN, USD, ZAR or GHS allowed)',
		'custom_css_js' => 'Custom CSS/JS',
		'custom_css' => 'Custom CSS (without <style> tags)',
		'custom_js' => 'Custom JavaScript (without <script> tags)',
		'show_alert_adult' => 'Show alert that the site has adult content',
		'alert_content_adult' => 'Attention! This site contains adult content, by accessing you acknowledge that you are 18 years of age.',
		'i_am_age' => 'I am of age',
		'leave' => 'Leave',
		'pwa_short_name' => 'App short name (Ex: Sponzy)',
		'alert_pwa_https' => 'You must use HTTPS (SSL) for PWA to work.',
		'error_internet_disconnected_pwa' => 'You are currently not connected to any networks.',
		'error_internet_disconnected_pwa_2' => 'Check your connection and try again',
		'complete_profile_alert' => 'To submit a verification request you must complete your profile.',
		'set_avatar' => 'Upload a profile picture',
		'set_cover' => 'Upload a cover image',
		'set_country' => 'Select your country of origin',
		'set_birthdate' => 'Set your date of birth',
		'form_w9' => 'Form W-9',
		'not_applicable' => 'Not applicable',
		'form_w9_required' => 'As a US citizen, you must submit the Form W-9',
		'upload_form_w9' => 'Upload Form W-9',
		'formats_available_verification_form_w9' => 'Invalid format, only :formats are allowed.', // Not remove/edit :formats
);";
			$fileLangEN = 'resources/lang/en/general.php';
			@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

			// Español
			$replaceLangES    = "
	//----- Version 2.0
	'show_errors' => 'Mostrar Errores',
	'info_show_errors' => 'Se recomienda solo en modo local o prueba',
	'alert_not_subscription' => 'Debe establecer un precio o habilitar la Suscripción Gratuita para activar su suscripción',
	'activate' => 'Activar',
	'my_cards' => 'Mis tarjetas',
	'info_my_cards' => 'Tarjetas disponibles en tu cuenta',
	'add' => 'Agregar',
	'expiry' => 'Vencimiento',
	'powered_by' => 'Desarrollado por',
	'notice_charge_to_card' => 'Haremos un cargo único de :amount al agregar su tarjeta de pago', // Not remove :amount
	'redirected_to_paypal_website' => 'Serás redirigido al sitio web de PayPal',
	'subscription_expire' => 'Su suscripción estará activa hasta',
	'subscribed_until' => 'Suscrito hasta',
	'cancel_subscription_paypal' => 'Cancela tu suscripción desde tu cuenta PayPal, estará activa hasta',
	'confirm_cancel_payment' => '¿Estás seguro de que desea cancelar esta transacción?',
	'test_smtp' => 'Si está usando SMTP, haz una prueba en el siguiente enlace para verificar que tus datos sean correctos.',
	'alert_paypal_delay' => '(Importante: PayPal puede tener un retraso, recargue la página o espere un minuto, de lo contrario, contáctenos)',
	'error_currency' => 'Moneda no soportada (Solo se permite NGN, USD, ZAR o GHS)',
	'custom_css_js' => 'CSS/JS Personalizado',
	'custom_css' => 'CSS Personalizado (sin la etiqueta <style>)',
	'custom_js' => 'JavaScript Personalizado (sin la etiqueta <script>)',
	'show_alert_adult' => 'Mostrar alerta que el sitio tiene contenido para adultos',
	'alert_content_adult' => '¡Atención! este sitio contiene contenido para adultos, al acceder usted admite tener 18 años de edad.',
	'i_am_age' => 'Soy mayor de edad',
	'leave' => 'Salir',
	'pwa_short_name' => 'Nombre corto de App (Ej: Sponzy)',
	'alert_pwa_https' => 'Debes usar HTTPS (SSL) para que PWA funcione.',
	'error_internet_disconnected_pwa' => 'Actualmente no estás conectado a ninguna red.',
	'error_internet_disconnected_pwa_2' => 'Verifica tu conexión e intente de nuevo',
	'complete_profile_alert' => 'Para enviar una solicitud de verificación, debe completar su perfil.',
	'set_avatar' => 'Sube una imagen de perfil',
	'set_cover' => 'Sube una imagen de portada',
	'set_country' => 'Selecciona tu país de origen',
	'set_birthdate' => 'Establece tu fecha de nacimiento',
	'form_w9' => 'Formulario W-9',
	'not_applicable' => 'No aplica',
	'form_w9_required' => 'Como ciudadano estadounidense, debe enviar el Formulario W-9',
	'upload_form_w9' => 'Subir Formulario W-9',
	'formats_available_verification_form_w9' => 'Formato no válido, solo se permiten :formats', // Not remove/edit :formats
);";
			$fileLangES = 'resources/lang/es/general.php';
			@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

			//============== Files Affected ================//
			$files = [
				'UpdatesController.php' => $CONTROLLERS,
				'PayPalController.php' => $CONTROLLERS,
				'AdminController.php' => $CONTROLLERS,
				'HomeController.php' => $CONTROLLERS,
				'MessagesController.php' => $CONTROLLERS,
				'PaystackController.php' => $CONTROLLERS,
				'SubscriptionsController.php' => $CONTROLLERS,
				'StripeController.php' => $CONTROLLERS,
				'CommentsController.php' => $CONTROLLERS,
				'LoginController.php' => $CONTROLLERS_AUTH,
				'RegisterController.php' => $CONTROLLERS_AUTH,
				'BlogController.php' => $CONTROLLERS,
				'AddFundsController.php' => $CONTROLLERS,
				'CCBillController.php' => $CONTROLLERS,
				'UserController.php' => $CONTROLLERS,
				'TipController.php' => $CONTROLLERS,
				'Helper.php' => $APP,
				'Subscriptions.php' => $MODELS,
				'User.php' => $MODELS,
				'app.blade.php' => $VIEWS_LAYOUTS,
				'javascript_general.blade.php' => $VIEWS_INCLUDES,
				'home-login.blade.php' => $VIEWS_INDEX,
				'register.blade.php' => $VIEWS_AUTH,
				'login.blade.php' => $VIEWS_AUTH,
				'notifications.blade.php' => $VIEWS_USERS,
				'my_payments.blade.php' => $VIEWS_USERS,
				'navbar.blade.php' => $VIEWS_INCLUDES,
				'listing-creators.blade.php' => $VIEWS_INCLUDES,
				'explore_creators.blade.php' => $VIEWS_INCLUDES,
				'listing-explore-creators.blade.php' => $VIEWS_INCLUDES,
				'updates.blade.php' => $VIEWS_INCLUDES,
				'comments.blade.php' => $VIEWS_INCLUDES,
				'footer-tiny.blade.php' => $VIEWS_INCLUDES,
				'messages-chat.blade.php' => $VIEWS_INCLUDES,
				'footer.blade.php' => $VIEWS_INCLUDES,
				'profile.blade.php' => $VIEWS_USERS,
				'cards-settings.blade.php' => $VIEWS_INCLUDES,
				'subscription.blade.php' => $VIEWS_USERS,
				'messages-inbox.blade.php' => $VIEWS_INCLUDES,
				'css_general.blade.php' => $VIEWS_INCLUDES,
				'my_subscriptions.blade.php' => $VIEWS_USERS,
				'my_cards.blade.php' => $VIEWS_USERS,
				'my_subscribers.blade.php' => $VIEWS_USERS,
				'dashboard.blade.php' => $VIEWS_USERS,
				'listing-categories.blade.php' => $VIEWS_INCLUDES,
				'payout_method.blade.php' => $VIEWS_USERS,
				'home-session.blade.php' => $VIEWS_INDEX,
				'edit_my_page.blade.php' => $VIEWS_USERS,
				'home.blade.php' => $VIEWS_INDEX,
				'wallet.blade.php' => $VIEWS_USERS,
				'withdrawals.blade.php' => $VIEWS_USERS,
				'messages-show.blade.php' => $VIEWS_USERS,
				'verify_account.blade.php' => $VIEWS_USERS,
				'menu-mobile.blade.php' => $VIEWS_INCLUDES,
				'password.blade.php' => $VIEWS_USERS,
				'web.php' => $ROUTES,
				'add-funds.js' => $PUBLIC_JS,
				'serviceworker.js' => $ROOT,
				'app-functions.js' => $PUBLIC_JS,
				'messages.js' => $PUBLIC_JS,
				'core.min.js' => $PUBLIC_JS,
				'payment.js' => $PUBLIC_JS,
				'UserDelete.php' => $TRAITS,
				'Functions.php' => $TRAITS,
				'laravelpwa.php' => $CONFIG,
				'filesystems.php' => $CONFIG,
				'packages.php' => $BOOTSTRAP_CACHE,
				'verify.blade.php' => $VIEWS_EMAILS,
				'VerifyCsrfToken.php' => $MIDDLEWARE,
				'jquery.tagsinput.min.css' => public_path('plugins' . $DS . 'tagsinput') . $DS
			];

			$filesAdmin = [
				'verification.blade.php' => $VIEWS_ADMIN,
				'css-js.blade.php' => $VIEWS_ADMIN,
				'email-settings.blade.php' => $VIEWS_ADMIN,
				'limits.blade.php' => $VIEWS_ADMIN,
				'transactions.blade.php' => $VIEWS_ADMIN,
				'storage.blade.php' => $VIEWS_ADMIN,
				'deposits-view.blade.php' => $VIEWS_ADMIN,
				'dashboard.blade.php' => $VIEWS_ADMIN,
				'pwa.blade.php' => $VIEWS_ADMIN,
				'deposits.blade.php' => $VIEWS_ADMIN,
				'edit-member.blade.php' => $VIEWS_ADMIN,
				'members.blade.php' => $VIEWS_ADMIN,
				'bank-transfer-settings.blade.php' => $VIEWS_ADMIN,
				'paystack-settings.blade.php' => $VIEWS_ADMIN,
				'ccbill-settings.blade.php' => $VIEWS_ADMIN,
				'layout.blade.php' => $VIEWS_ADMIN,
				'settings.blade.php' => $VIEWS_ADMIN,
				'subscriptions.blade.php' => $VIEWS_ADMIN,
				'payments-settings.blade.php' => $VIEWS_ADMIN,
				'reports.blade.php' => $VIEWS_ADMIN
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy Folders
			$filePathPublic1 = $path . 'images';
			$pathPublic1 = public_path('images');

			$this->moveDirectory($filePathPublic1, $pathPublic1, $copy);

			// Copy Folders
			$filePathPublic2 = $path . 'laravelpwa';
			$pathPublic2 = resource_path('views' . $DS . 'vendor' . $DS . 'laravelpwa');

			$this->moveDirectory($filePathPublic2, $pathPublic2, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 2.0 ----->>

		if ($version == '2.1') {

			//============ Starting moving files...
			$oldVersion = '2.0';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'UpdatesController.php' => $CONTROLLERS,
				'AdminController.php' => $CONTROLLERS,
				'HomeController.php' => $CONTROLLERS,
				'MessagesController.php' => $CONTROLLERS,
				'PaystackController.php' => $CONTROLLERS,
				'SubscriptionsController.php' => $CONTROLLERS,
				'StripeController.php' => $CONTROLLERS,
				'CommentsController.php' => $CONTROLLERS,
				'StripeWebHookController.php' => $CONTROLLERS,
				'AddFundsController.php' => $CONTROLLERS,
				'CCBillController.php' => $CONTROLLERS,
				'UserController.php' => $CONTROLLERS,
				'TipController.php' => $CONTROLLERS,
				'Helper.php' => $APP,
				'app.blade.php' => $VIEWS_LAYOUTS,
				'notifications.blade.php' => $VIEWS_USERS,
				'navbar.blade.php' => $VIEWS_INCLUDES,
				'listing-creators.blade.php' => $VIEWS_INCLUDES,
				'listing-explore-creators.blade.php' => $VIEWS_INCLUDES,
				'updates.blade.php' => $VIEWS_INCLUDES,
				'comments.blade.php' => $VIEWS_INCLUDES,
				'messages-chat.blade.php' => $VIEWS_INCLUDES,
				'footer.blade.php' => $VIEWS_INCLUDES,
				'profile.blade.php' => $VIEWS_USERS,
				'post-detail.blade.php' => $VIEWS_USERS,
				'edit-update.blade.php' =>  $VIEWS_USERS,
				'messages-inbox.blade.php' => $VIEWS_INCLUDES,
				'my_subscriptions.blade.php' => $VIEWS_USERS,
				'my_subscribers.blade.php' => $VIEWS_USERS,
				'wallet.blade.php' => $VIEWS_USERS,
				'messages-show.blade.php' => $VIEWS_USERS,
				'add-funds.js' => $PUBLIC_JS,
				'payment.js' => $PUBLIC_JS,

			];

			$filesAdmin = [
				'verification.blade.php' => $VIEWS_ADMIN,
				'dashboard.blade.php' => $VIEWS_ADMIN,
				'edit-member.blade.php' => $VIEWS_ADMIN,
				'members.blade.php' => $VIEWS_ADMIN,
				'layout.blade.php' => $VIEWS_ADMIN,
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return redirect('panel/admin')
				->withSuccessUpdate(trans('admin.upgrade_done'));
		}
		//<<---- End Version 2.1 ----->>

		if ($version == '2.2') {

			//============ Starting moving files...
			$oldVersion = '2.1';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			if (!Schema::hasTable('sessions')) {
				Schema::create('sessions', function ($table) {
					$table->string('id', 191)->unique();
					$table->foreignId('user_id')->nullable();
					$table->string('ip_address', 45)->nullable();
					$table->text('user_agent')->nullable();
					$table->text('payload');
					$table->integer('last_activity');
				});
			}

			Helper::envUpdate('SESSION_DRIVER', 'database');

			if (!Schema::hasColumn('users', 'notify_new_tip', 'hide_profile', 'hide_last_seen', 'last_login')) {
				Schema::table('users', function ($table) {
					$table->enum('notify_new_tip', ['yes', 'no'])->default('yes');
					$table->enum('hide_profile', ['yes', 'no'])->default('no');
					$table->enum('hide_last_seen', ['yes', 'no'])->default('no');
					$table->string('last_login', 250);
				});
			}

			if (!Schema::hasColumn('admin_settings', 'genders')) {
				Schema::table('admin_settings', function ($table) {
					$table->string('genders', 250);
				});
			}

			$this->settings->whereId(1)->update([
				'genders' => 'male,female'
			]);

			file_put_contents(
				'.env',
				"\nBACKBLAZE_BUCKET_REGION=\n",
				FILE_APPEND
			);

			// Replace String
			$findStringLang = ');';

			// Ennglish
			$replaceLangEN    = "
			// Version 2.2
			'subscribers' => 'Subscriber|Subscribers',
			'cancel_subscription_ccbill' => 'Cancel your subscription from :ccbill, it will be active until', // Not remove/edit :ccbill
			'genders' => 'Genders',
			'genders_required' => 'The genders field is required.',
			'gay' => 'Gay',
			'lesbian' => 'Lesbian',
			'bisexual' => 'Bisexual',
			'transgender' => 'Transgender',
			'metrosexual' => 'Metrosexual',
			'someone_sent_tip' => 'Someone sent me a tip',
			'privacy_security' => 'Privacy and Security',
			'desc_privacy' => 'Set your privacy',
			'hide_profile' => 'Hide profile',
			'hide_last_seen' => 'Hide last seen',
			'login_sessions' => 'Login sessions',
			'last_login_record' => 'Last login record was from',
			'this_device' => 'This device',
			'last_activity' => 'Last activity',
);";
			$fileLangEN = 'resources/lang/en/general.php';
			@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

			// Español
			$replaceLangES    = "
		// Version 2.2
		'subscribers' => 'Suscriptor|Suscriptores',
		'cancel_subscription_ccbill' => 'Cancele su suscripción desde :ccbill, estará activa hasta', // Not remove/edit :ccbill
		'genders' => 'Géneros',
		'genders_required' => 'Géneros es obligatorio',
		'gay' => 'Gay',
		'lesbian' => 'Lesbiana',
		'bisexual' => 'Bisexual',
		'transgender' => 'Transgénero',
		'metrosexual' => 'Metrosexual',
		'someone_sent_tip' => 'Alguien me ha enviado una propina',
		'privacy_security' => 'Privacidad y seguridad',
		'desc_privacy' => 'Configura tu privacidad',
		'hide_profile' => 'Ocultar perfil',
		'hide_last_seen' => 'Ocultar visto por última vez',
		'login_sessions' => 'Sesiones de inicio de sesión',
		'last_login_record' => 'Último registro de inicio de sesión fue desde',
		'this_device' => 'Este dispositivo',
		'last_activity' => 'Última actividad',
);";
			$fileLangES = 'resources/lang/es/general.php';
			@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));


			//============== Files Affected ================//
			$files = [
				'InstallScriptController.php' => $CONTROLLERS,
				'AdminController.php' => $CONTROLLERS,
				'HomeController.php' => $CONTROLLERS,
				'MessagesController.php' => $CONTROLLERS,
				'PaystackController.php' => $CONTROLLERS,
				'SubscriptionsController.php' => $CONTROLLERS,
				'StripeController.php' => $CONTROLLERS,
				'CommentsController.php' => $CONTROLLERS,
				'StripeWebHookController.php' => $CONTROLLERS,
				'AddFundsController.php' => $CONTROLLERS,
				'CCBillController.php' => $CONTROLLERS,
				'UserController.php' => $CONTROLLERS,
				'TipController.php' => $CONTROLLERS,
				'Helper.php' => $APP,
				'app.blade.php' => $VIEWS_LAYOUTS,
				'notifications.blade.php' => $VIEWS_USERS,
				'navbar.blade.php' => $VIEWS_INCLUDES,
				'profile.blade.php' => $VIEWS_USERS,
				'post-detail.blade.php' => $VIEWS_USERS,
				'bookmarks.blade.php' => $VIEWS_USERS,
				'form-post.blade.php' => $VIEWS_INCLUDES,
				'my_subscriptions.blade.php' => $VIEWS_USERS,
				'my_subscribers.blade.php' => $VIEWS_USERS,
				'wallet.blade.php' => $VIEWS_USERS,
				'messages-show.blade.php' => $VIEWS_USERS,
				'payment.js' => $PUBLIC_JS,
				'laravelpwa.php' => $CONFIG,
				'Functions.php' => $TRAITS,
				'serviceworker.js' => $ROOT,
				'home-session.blade.php' => $VIEWS_INDEX,
				'paypal-white.png' => public_path('img' . $DS . 'payments') . $DS,
				'meta.blade.php' => resource_path('views' . $DS . 'vendor' . $DS . 'laravelpwa'),
				'web.php' => $ROUTES,
				'bootstrap-icons.css' => $PUBLIC_CSS,
				'bootstrap-icons.woff' => $PUBLIC_FONTS,
				'bootstrap-icons.woff2' => $PUBLIC_FONTS,
				'css_general.blade.php' => $VIEWS_INCLUDES,
				'cards-settings.blade.php' => $VIEWS_INCLUDES,
				'plyr.min.js' => public_path('js' . $DS . 'plyr') . $DS,
				'plyr.css' => public_path('js' . $DS . 'plyr') . $DS,
				'plyr.polyfilled.min.js' => public_path('js' . $DS . 'plyr') . $DS,
				'verify_account.blade.php' => $VIEWS_USERS,
				'select2.min.css' => public_path('plugins' . $DS . 'select2') . $DS,
				'functions.js' => public_path('admin' . $DS . 'js') . $DS,
				'edit_my_page.blade.php' => $VIEWS_USERS,
				'Notifications.php' => $MODELS,
				'app-functions.js' => $PUBLIC_JS,
				'dashboard.blade.php' => $VIEWS_USERS,
				'subscription.blade.php' => $VIEWS_USERS,
				'my_cards.blade.php' => $VIEWS_USERS,
				'password.blade.php' => $VIEWS_USERS,
				'my_payments.blade.php' => $VIEWS_USERS,
				'payout_method.blade.php' => $VIEWS_USERS,
				'withdrawals.blade.php' => $VIEWS_USERS,
				'privacy_security.blade.php' => $VIEWS_USERS,
				'javascript_general.blade.php' => $VIEWS_INCLUDES,
				'add_payment_card.blade.php' => $VIEWS_USERS,

			];

			$filesAdmin = [
				'verification.blade.php' => $VIEWS_ADMIN,
				'theme.blade.php' => $VIEWS_ADMIN,
				'edit-member.blade.php' => $VIEWS_ADMIN,
				'storage.blade.php' => $VIEWS_ADMIN,
				'settings.blade.php' => $VIEWS_ADMIN,
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		} //<<---- End Version 2.2 ----->>

		if ($version == '2.3') {

			//============ Starting moving files...
			$oldVersion = '2.2';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			// Create Table PayPerViews
			if (!Schema::hasTable('pay_per_views')) {
				Schema::create('pay_per_views', function ($table) {
					$table->increments('id');
					$table->unsignedInteger('user_id')->index();
					$table->unsignedInteger('updates_id')->index();
					$table->unsignedInteger('messages_id')->index();
					$table->timestamps();
				});
			} // <<--- End Create Table PayPerViews

			Schema::table('users', function ($table) {
				$table->decimal('price', 10, 2)->change();
			});

			if (!Schema::hasColumn('transactions', 'percentage_applied')) {
				Schema::table('transactions', function ($table) {
					$table->string('percentage_applied', 50);
				});
			}

			if (!Schema::hasColumn('admin_settings', 'cover_default', 'who_can_see_content', 'users_can_edit_post', 'disable_wallet')) {
				Schema::table('admin_settings', function ($table) {
					$table->string('cover_default', 100);
					$table->enum('who_can_see_content', ['all', 'users'])->default('all');
					$table->enum('users_can_edit_post', ['on', 'off'])->default('on');
					$table->enum('disable_wallet', ['on', 'off'])->default('off');
				});
			}

			if (!Schema::hasColumn(
				'users',
				'hide_count_subscribers',
				'hide_my_country',
				'show_my_birthdate',
				'notify_new_post',
				'notify_email_new_post',
				'custom_fee',
				'hide_name'
			)) {
				Schema::table('users', function ($table) {
					$table->enum('hide_count_subscribers', ['yes', 'no'])->default('no');
					$table->enum('hide_my_country', ['yes', 'no'])->default('no');
					$table->enum('show_my_birthdate', ['yes', 'no'])->default('no');
					$table->enum('notify_new_post', ['yes', 'no'])->default('yes');
					$table->enum('notify_email_new_post', ['yes', 'no'])->default('no');
					$table->unsignedInteger('custom_fee');
					$table->enum('hide_name', ['yes', 'no'])->default('no');
				});
			}

			// Replace String
			$findStringLang = ');';

			// Ennglish
			$replaceLangEN    = "
			// Version 2.3
			'complete_form_W9_here' => 'Complete IRS W-9 Form here',
			'info_hide_profile' => '(Search, page explore, explore creators)',
			'hide_count_subscribers' => 'Hide number of subscribers',
			'hide_my_country' => 'Hide my country',
			'show_my_birthdate' => 'Show my birthdate',
			'creators_with_free_subscription' => 'Creators with free subscription',
			'cover_default' => 'Cover default',
			'percentage_applied' => 'Percentage applied:',
			'platform' => 'Platform',
			'custom_fee' => 'Custom fee',
			'who_can_see_content' => 'Who can see content?',
			'users_can_edit_post' => 'Users can edit/delete post?',
			'disable_wallet' => 'Disable wallet',
			'error_delete_post' => 'By policies of our platform, you can not delete this post, if you have active subscribers.',
			'set_price_for_post' => 'Set a price for this post, your non-subscribers or free subscribers will have to pay to view it.',
			'set_price_for_msg' => 'Set a price for this message.',
			'hide_name' => 'Show username instead of your Full name',
			'min_ppv_amount' => 'Minimum Pay Per View (Post/Message Locked)',
			'max_ppv_amount' => 'Maximum Pay Per View (Post/Message Locked)',
			'unlock_post_for' => 'Unlock post for',
			'unlock_for' => 'Unlock for',
			'unlock_content' => 'Unlock content',
			'has_bought_your_content' => 'has bought your post',
			'has_bought_your_message' => 'has bought your message',
			'already_purchased_content' => 'You have already purchased this content',
			'purchased' => 'Purchased',
			'not_purchased_any_content' => 'You have not purchased any content',
);";
			$fileLangEN = 'resources/lang/en/general.php';
			@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

			// Español
			$replaceLangES    = "
		// Version 2.3
		'complete_form_W9_here' => 'Complete el formulario W-9 IRS aquí',
		'info_hide_profile' => '(Búsqueda, pagina explorar, explorar creadores)',
		'hide_count_subscribers' => 'Ocultar número de suscriptores',
		'hide_my_country' => 'Ocultar mi país',
		'show_my_birthdate' => 'Mostrar mi fecha de cumpleaños',
		'creators_with_free_subscription' => 'Creadores con suscripciones gratuita',
		'cover_default' => 'Portada predeterminada',
		'percentage_applied' => 'Porcentaje aplicado:',
		'platform' => 'Plataforma',
		'custom_fee' => 'Tarifa personalizada',
		'who_can_see_content' => '¿Quién puede ver el contenido?',
		'users_can_edit_post' => '¿Los usuarios pueden editar/eliminar la publicación?',
		'disable_wallet' => 'Desactivar billetera',
		'error_delete_post' => 'Por políticas de nuestra plataforma, no puede eliminar esta publicación, si tiene suscriptores activos.',
		'set_price_for_post' => 'Establezca un precio para esta publicación, sus no suscriptores o suscriptores gratuitos deberán pagar para verla.',
		'set_price_for_msg' => 'Establezca un precio para este mensaje.',
		'hide_name' => 'Mostrar nombre de usuario en lugar de tu Nombre completo',
		'min_ppv_amount' => 'Pago mínimo por ver (Publicación/Mensaje bloqueado)',
		'max_ppv_amount' => 'Pago máximo por ver (Publicación/Mensaje bloqueado)',
		'unlock_post_for' => 'Desbloquear publicación por',
		'unlock_for' => 'Desbloquear por',
		'unlock_content' => 'Desbloquear contenido',
		'has_bought_your_content' => 'ha comprado tu publicación',
		'has_bought_your_message' => 'ha comprado tu mensaje',
		'already_purchased_content' => 'Ya has comprado este contenido',
		'purchased' => 'Comprado',
		'not_purchased_any_content' => 'No has comprado ningún contenido',
);";
			$fileLangES = 'resources/lang/es/general.php';
			@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));


			//============== Files Affected ================//
			$files = [
				'InstallScriptController.php' => $CONTROLLERS,
				'AdminController.php' => $CONTROLLERS,
				'HomeController.php' => $CONTROLLERS,
				'MessagesController.php' => $CONTROLLERS,
				'PaystackController.php' => $CONTROLLERS,
				'PayPalController.php' => $CONTROLLERS,
				'SubscriptionsController.php' => $CONTROLLERS,
				'StripeController.php' => $CONTROLLERS,
				'CommentsController.php' => $CONTROLLERS,
				'StripeWebHookController.php' => $CONTROLLERS,
				'AddFundsController.php' => $CONTROLLERS,
				'CCBillController.php' => $CONTROLLERS,
				'UserController.php' => $CONTROLLERS,
				'TipController.php' => $CONTROLLERS,
				'PayPerViewController.php' => $CONTROLLERS,
				'RegisterController.php' => $CONTROLLERS_AUTH,
				'UpdatesController.php' => $CONTROLLERS,
				'Authenticate.php' => $MIDDLEWARE,
				'PrivateContent.php' => $MIDDLEWARE,
				'Functions.php' => $TRAITS,
				'UserDelete.php' => $TRAITS,
				'PayPerViews.php' => $MODELS,
				'Messages.php' => $MODELS,
				'User.php' => $MODELS,
				'Helper.php' => $APP,
				'SocialAccountService.php' => $APP,
				'app.blade.php' => $VIEWS_LAYOUTS,
				'notifications.blade.php' => $VIEWS_USERS,
				'navbar.blade.php' => $VIEWS_INCLUDES,
				'profile.blade.php' => $VIEWS_USERS,
				'post-detail.blade.php' => $VIEWS_USERS,
				'form-post.blade.php' => $VIEWS_INCLUDES,
				'updates.blade.php' => $VIEWS_INCLUDES,
				'my_subscriptions.blade.php' => $VIEWS_USERS,
				'my_subscribers.blade.php' => $VIEWS_USERS,
				'wallet.blade.php' => $VIEWS_USERS,
				'messages-show.blade.php' => $VIEWS_USERS,
				'messages-inbox.blade.php' => $VIEWS_INCLUDES,
				'messages-chat.blade.php' => $VIEWS_INCLUDES,
				'my-purchases.blade.php' => $VIEWS_USERS,
				'add-funds.js' => $PUBLIC_JS,
				'payment.js' => $PUBLIC_JS,
				'messages.js' => $PUBLIC_JS,
				'payments-ppv.js' => $PUBLIC_JS,
				'plyr.min.js' => public_path('js' . $DS . 'plyr') . $DS,
				'plyr.polyfilled.min.js' => public_path('js' . $DS . 'plyr') . $DS,
				'home-session.blade.php' => $VIEWS_INDEX,
				'home-login.blade.php' => $VIEWS_INDEX,
				'creators.blade.php' => $VIEWS_INDEX,
				'categories.blade.php' => $VIEWS_INDEX,
				'post.blade.php' => $VIEWS_INDEX,
				'listing-categories.blade.php' => $VIEWS_INCLUDES,
				'comments.blade.php' => $VIEWS_INCLUDES,
				'web.php' => $ROUTES,
				'css_general.blade.php' => $VIEWS_INCLUDES,
				'cards-settings.blade.php' => $VIEWS_INCLUDES,
				'listing-explore-creators.blade.php' => $VIEWS_INCLUDES,
				'listing-creators.blade.php' => $VIEWS_INCLUDES,
				'verify_account.blade.php' => $VIEWS_USERS,
				'edit_my_page.blade.php' => $VIEWS_USERS,
				'edit-update.blade.php' => $VIEWS_USERS,
				'Notifications.php' => $MODELS,
				'app-functions.js' => $PUBLIC_JS,
				'dashboard.blade.php' => $VIEWS_USERS,
				'subscription.blade.php' => $VIEWS_USERS,
				'my_cards.blade.php' => $VIEWS_USERS,
				'password.blade.php' => $VIEWS_USERS,
				'my_payments.blade.php' => $VIEWS_USERS,
				'payout_method.blade.php' => $VIEWS_USERS,
				'invoice-deposits.blade.php' => $VIEWS_USERS,
				'invoice.blade.php' => $VIEWS_USERS,
				'privacy_security.blade.php' => $VIEWS_USERS,
				'javascript_general.blade.php' => $VIEWS_INCLUDES,
				'Kernel.php' => app_path('Http') . $DS,

			];

			$filesAdmin = [
				'verification.blade.php' => $VIEWS_ADMIN,
				'dashboard.blade.php' => $VIEWS_ADMIN,
				'theme.blade.php' => $VIEWS_ADMIN,
				'edit-member.blade.php' => $VIEWS_ADMIN,
				'languages.blade.php' => $VIEWS_ADMIN,
				'settings.blade.php' => $VIEWS_ADMIN,
				'charts.blade.php' => $VIEWS_ADMIN,
				'payments-settings.blade.php' => $VIEWS_ADMIN,
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		} //<<---- End Version 2.3 ----->>

		if ($version == '2.4') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = false;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			// Replace String
			$findStringLang = ');';

			// Ennglish
			$replaceLangEN    = "
			// Version 2.4
		'creator' => 'Creator',
		'birthdate_changed_info' => 'Can be edited only once',
		'disable_banner_cookies' => 'Disable cookie policy banner',
		'has_created_new_post' => 'has created a new post',
		'new_post_creators_subscribed' => 'New post of the creators I\'ve subscribed',
		'more_active' => 'More active',
		'more_active_creators' => 'Most active creators',
		'someone_bought_my_content' => 'Someone has bought my content (Post, Message)',
		'sent_you_a_tip_for' => 'sent you a tip for',
		'go_payments_received' => 'Go to payments received',
		'deposit_pending' => 'Deposit pending',
		'view_details_panel_admin' => 'View details in Panel Admin',
		'verification_pending' => 'Verification pending',
		'withdrawal_request' => 'Withdrawal request',
		'note_disable_subs_payment' => 'Note: if you disable, this payment gateway will not be available for subscriptions, tips or Pay Per View, only to recharge the wallet.',
		'active_status_online' => 'Active Status (Online)',
		'wallet_format' => 'Wallet format',
		'credits' => 'Credits',
		'points' => 'Points',
		'tokens' => 'Tokens',
		'real_money' => 'Real money',
		'equivalent_money_format' => '1 credit, point or token equals',
		'credit_equivalent_money' => '1 credit equals',
		'point_equivalent_money' => '1 point equals',
		'token_equivalent_money' => '1 token equals',
		'media_type_upload' => 'Photo, Video or Audio MP3',
		'years' => 'years',
		'price_per_month' => ':price/mo', // Not replace :price
		'maximum_files_post' => 'Maximum files in a Post',
		'maximum_files_msg' => 'Maximum files in a Message',
		'no_binary' => 'Non-binary',
		'ffmpeg_path' => 'FFMPEG path',
		'to_all_my_subscribers' => 'All my subscribers',
		'new_message_all_subscribers' => 'New message all subscribers',
		'great' => 'Great!',
		'msg_success_sent_all_subscribers' => 'The message was successfully sent to all your subscribers',
		'automatically_renewed_wallet' => '* It will be automatically renewed from your wallet balance.',
		'payment_process_wallet' => 'Your payment has been received, if you cannot see it in your account it is being processed.',
		'maximum_selected_categories' => 'You can only select :limit categories', // No remove :limit
		'searching' => 'Searching...',
		'limit_categories' => 'Limit of categories that the user can select',
		'announcements' => 'Announcements',
		'announcement_content' => 'Announcements Content',
		'announcement_info' => 'Accept text, html or javascript. Leave it blank to disable it. (Important: Make sure to close the HTML tags correctly)',
		'show_announcement_to' => 'Show announcement to',
		'all_users' => 'All users',
		'only_creators' => 'Only Creators',
		'no_free_posts' => 'No free posts yet',
		'minimum_photo_width' => 'Minimum photo width',
);";
			$fileLangEN = 'resources/lang/en/general.php';
			@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

			// Español
			$replaceLangES    = "
		// Version 2.4
	'creator' => 'Creador',
	'birthdate_changed_info' => 'Se puede editar sólo una vez',
	'disable_banner_cookies' => 'Desactivar banner de la política de cookies',
	'has_created_new_post' => 'ha creado un nuevo post',
	'new_post_creators_subscribed' => 'Nueva publicación de los creadores que me he suscrito',
	'more_active' => 'Más activo',
	'more_active_creators' => 'Creadores más activo',
	'someone_bought_my_content' => 'Alguien ha comprado mi contenido (Post, Mensaje)',
	'sent_you_a_tip_for' => 'te envió una propina por',
	'go_payments_received' => 'Ir a pagos recibidos',
	'deposit_pending' => 'Depósito pendiente',
	'view_details_panel_admin' => 'Ver detalles en Panel Admin',
	'verification_pending' => 'Verificación pendiente',
	'withdrawal_request' => 'Solicitud de retiro',
	'note_disable_subs_payment' => 'Nota: si desactiva, esta pasarela de pago no estará disponible para suscripciones, propinas o Pago Por Ver, solo para recargar la billetera.',
	'active_status_online' => 'Estado activo (En linea)',
	'wallet_format' => 'Formato de billetera',
	'credits' => 'Créditos',
	'points' => 'Puntos',
	'tokens' => 'Tokens',
	'real_money' => 'Dinero real',
	'equivalent_money' => 'equivale a',
	'credit_equivalent_money' => '1 crédito equivale a',
	'point_equivalent_money' => '1 punto equivale a',
	'token_equivalent_money' => '1 token equivale a',
	'media_type_upload' => 'Foto, Vídeo o Audio MP3',
	'years' => 'años',
	'price_per_month' => ':price/mes', // Not replace :price
	'maximum_files_post' => 'Archivos máximos en un Post',
	'maximum_files_msg' => 'Archivos máximos en un Mensaje',
	'no_binary' => 'No binario',
	'ffmpeg_path' => 'Ruta FFMPEG',
	'to_all_my_subscribers' => 'A todos mis suscriptores',
	'new_message_all_subscribers' => 'Nuevo mensaje a todos los suscriptores',
	'great' => '¡Excelente!',
	'msg_success_sent_all_subscribers' => 'El mensaje fue enviado con éxito a todos tus suscriptores',
	'automatically_renewed_wallet' => '* Será renovada automaticamente del saldo de su billtera.',
	'payment_process_wallet' => 'Su pago ha sido recibido, si no logra verlo en su cuenta está siendo procesado.',
	'maximum_selected_categories' => 'Sólo puedes seleccionar :limit categorías', // No remover :limit
	'searching' => 'Buscando...',
	'limit_categories' => 'Límite de categorias que el usuario puede seleccionar',
	'announcements' => 'Anuncios',
	'announcement_content' => 'Contenido del Anuncio',
	'announcement_info' => 'Acepta texto, html o javascript. Déjelo en blanco para deshabilitarlo. (Importante: Asegurate de cerrar las etiquetas HTML correctamente)',
	'show_announcement_to' => 'Mostrar anuncio a',
	'all_users' => 'Todos los usuarios',
	'only_creators' => 'Solo creadores',
	'no_free_posts' => 'Aún no hay posts gratuitos',
	'minimum_photo_width' => 'Ancho mínimo de la foto',

);";
			$fileLangES = 'resources/lang/es/general.php';
			@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));


			//============== Files Affected ================//
			$files = [
				'AddFundsController.php' => $CONTROLLERS, // v2.4
				'AdminController.php' => $CONTROLLERS, // v2.4
				'HomeController.php' => $CONTROLLERS, // v2.4
				'MessagesController.php' => $CONTROLLERS, // v2.4
				'PayPalController.php' => $CONTROLLERS, // v2.4
				'SubscriptionsController.php' => $CONTROLLERS, // v2.4
				'CommentsController.php' => $CONTROLLERS, // v2.4
				'StripeWebHookController.php' => $CONTROLLERS,
				'CCBillController.php' => $CONTROLLERS, // v2.4
				'TipController.php' => $CONTROLLERS, // v2.4
				'PayPerViewController.php' => $CONTROLLERS, // v2.4
				'RegisterController.php' => $CONTROLLERS_AUTH,
				'UpdatesController.php' => $CONTROLLERS, // v2.4
				'UploadMediaController.php' => $CONTROLLERS, // v2.4
				'UploadMediaMessageController.php' => $CONTROLLERS, // v2.4
				'UserController.php' => $CONTROLLERS, // v2.4

				'VerifyCsrfToken.php' => $MIDDLEWARE, // v2.4

				'Messages.php' => $MODELS, // v2.4
				'Media.php' => $MODELS, // v2.4
				'MediaMessages.php' => $MODELS, // v2.4
				'User.php' => $MODELS, // v2.4
				'Transactions.php' => $MODELS, // v2.4
				'Deposits.php' => $MODELS, // v2.4
				'Blogs.php' => $MODELS, // v2.4
				'VerificationRequests.php' => $MODELS, // v2.4
				'Withdrawals.php' => $MODELS, // v2.4
				'Subscriptions.php' => $MODELS, // v2.4
				'Reports.php' => $MODELS, // v2.4
				'Notifications.php' => $MODELS, // v2.4
				'Updates.php' => $MODELS, // v2.4

				'AdminDepositPending.php' => $NOTIFICATIONS, // v2.4
				'AdminVerificationPending.php' => $NOTIFICATIONS, // v2.4
				'AdminWithdrawalPending.php' => $NOTIFICATIONS, // v2.4
				'NewPost.php' => $NOTIFICATIONS, // v2.4
				'PayPerViewReceived.php' => $NOTIFICATIONS, // v2.4
				'TipReceived.php' => $NOTIFICATIONS, // v2.4

				'Functions.php' => $TRAITS, // v2.4
				'UserDelete.php' => $TRAITS, // V2.4

				'Helper.php' => $APP, // v2.4

				'EventServiceProvider.php' => $PROVIDERS, // v2.4

				'app.php' => $CONFIG, // v2.4
				'laravel-ffmpeg.php' => $CONFIG, // v2.4

				'web.php' => $ROUTES, // v2.4

				'app.blade.php' => $VIEWS_LAYOUTS, // v2.4

				'register.blade.php' => $VIEWS_AUTH, // v2.4
				'login.blade.php' => $VIEWS_AUTH, // v2.4
				'email.blade.php' => $VIEWS_AUTH_PASS, // v2.4
				'reset.blade.php' => $VIEWS_AUTH_PASS, // v2.4

				'verify.blade.php' => $VIEWS_EMAILS, // v2.4

				'home-session.blade.php' => $VIEWS_INDEX, // v2.4
				'home-login.blade.php' => $VIEWS_INDEX, // v2.4
				'home.blade.php' => $VIEWS_INDEX, // v2.4
				'creators.blade.php' => $VIEWS_INDEX, // v2.4
				'categories.blade.php' => $VIEWS_INDEX, // v2.4
				'contact.blade.php' => $VIEWS_INDEX, // v2.4
				'explore.blade.php' => $VIEWS_INDEX, // v2.4

				'navbar.blade.php' => $VIEWS_INCLUDES, // v2.4
				'form-post.blade.php' => $VIEWS_INCLUDES, // v2.4
				'footer.blade.php' => $VIEWS_INCLUDES, // v2.4
				'footer-tiny.blade.php' => $VIEWS_INCLUDES, // v2.4
				'updates.blade.php' => $VIEWS_INCLUDES, // v2.4
				'messages-inbox.blade.php' => $VIEWS_INCLUDES, // v2.4
				'messages-chat.blade.php' => $VIEWS_INCLUDES, // v2.4
				'listing-categories.blade.php' => $VIEWS_INCLUDES, // v2.4
				'comments.blade.php' => $VIEWS_INCLUDES, // v2.4
				'css_general.blade.php' => $VIEWS_INCLUDES, // v2.4
				'cards-settings.blade.php' => $VIEWS_INCLUDES, // v2.4
				'listing-explore-creators.blade.php' => $VIEWS_INCLUDES, // v2.4
				'listing-creators.blade.php' => $VIEWS_INCLUDES, // v2.4
				'javascript_general.blade.php' => $VIEWS_INCLUDES, // v2.4
				'media-post.blade.php' => $VIEWS_INCLUDES, // v2.4
				'media-messages.blade.php' => $VIEWS_INCLUDES, // v2.4
				'modal-new-message.blade.php' => $VIEWS_INCLUDES, // v2.4
				'sidebar-messages-inbox.blade.php' => $VIEWS_INCLUDES, // v2.4
				'menu-sidebar-home.blade.php' => $VIEWS_INCLUDES, // v2.4

				'bookmarks.blade.php' =>  $VIEWS_USERS, // v2.4
				'profile.blade.php' => $VIEWS_USERS, // v2.4
				'notifications.blade.php' => $VIEWS_USERS, // v2.4
				'my_subscriptions.blade.php' => $VIEWS_USERS, // v2.4
				'my_subscribers.blade.php' => $VIEWS_USERS, // v2.4
				'wallet.blade.php' => $VIEWS_USERS, // v2.4
				'messages-show.blade.php' => $VIEWS_USERS, // v2.4
				'messages.blade.php' => $VIEWS_USERS, // v2.4
				'my-purchases.blade.php' => $VIEWS_USERS, // V2.4
				'edit_my_page.blade.php' => $VIEWS_USERS, // v2.4
				'edit-update.blade.php' => $VIEWS_USERS, // v2.4
				'dashboard.blade.php' => $VIEWS_USERS, // v2.4
				'subscription.blade.php' => $VIEWS_USERS, // v2.4
				'my-purchases.blade.php' => $VIEWS_USERS, // v2.4
				'password.blade.php' => $VIEWS_USERS, // v2.4
				'my_payments.blade.php' => $VIEWS_USERS, // v2.4
				'privacy_security.blade.php' => $VIEWS_USERS, // v2.4
				'delete_account.blade.php' => $VIEWS_USERS, // v2.4
				'header.blade.php' => resource_path('views' . $DS . 'vendor' . $DS . 'mail' . $DS . 'html') . $DS, // v2.4
				'message.blade.php' => resource_path('views' . $DS . 'vendor' . $DS . 'mail' . $DS . 'html') . $DS, // v2.4
				'button.blade.php' => resource_path('views' . $DS . 'vendor' . $DS . 'mail' . $DS . 'html') . $DS, // v2.4
				'email.blade.php' => resource_path('views' . $DS . 'vendor' . $DS . 'notifications') . $DS, // v2.4
				'loadmore.blade.php' => resource_path('views' . $DS . 'vendor' . $DS . 'pagination') . $DS, // v2.4

				'add-funds.js' => $PUBLIC_JS, // v2.4
				'payment.js' => $PUBLIC_JS, // v2.4
				'messages.js' => $PUBLIC_JS, // v2.4
				'payments-ppv.js' => $PUBLIC_JS, // v2.4
				'core.min.js' => $PUBLIC_JS, // v2.4
				'paginator-messages.js' => $PUBLIC_JS, // v2.4
				'core.min.css' => $PUBLIC_CSS, // v2.4
				'app-functions.js' => $PUBLIC_JS, // v2.4
				'functions.js' => $PUBLIC_JS_ADMIN, // v2.4
				'swiper-bundle.min.js.map' => $PUBLIC_JS, // v2.4
				'bootstrap-icons.css' => $PUBLIC_CSS, // v2.4
				'bootstrap-icons.woff' => $PUBLIC_FONTS, // v2.4
				'bootstrap-icons.woff2' => $PUBLIC_FONTS, // v2.4
				'plyr.css' => public_path('js' . $DS . 'plyr') . $DS, // v2.4

				'popular.png' => $PUBLIC_IMG, // v2.4
				'featured.png' => $PUBLIC_IMG, // v2.4
				'more-active.png' => $PUBLIC_IMG, // v2.4
				'creators.png' => $PUBLIC_IMG, // v2.4
				'unlock.png' => $PUBLIC_IMG, // v2.4
				'coinpayments.png' => public_path('img' . $DS . 'payments') . $DS, // v2.4
				'coinpayments-white.png' => public_path('img' . $DS . 'payments') . $DS, // v2.4

				'Kernel.php' => app_path('Console') . $DS, // v2.4

			];

			$filesAdmin = [
				'edit-blog.blade.php' => $VIEWS_ADMIN, // v2.4
				'blog.blade.php' => $VIEWS_ADMIN, // v2.4
				'create-blog.blade.php' => $VIEWS_ADMIN, // v2.4
				'verification.blade.php' => $VIEWS_ADMIN, // v2.4
				'dashboard.blade.php' => $VIEWS_ADMIN, // v2.4
				'edit-member.blade.php' => $VIEWS_ADMIN, // v2.4
				'settings.blade.php' => $VIEWS_ADMIN, // v2.4
				'charts.blade.php' => $VIEWS_ADMIN, // v2.4
				'posts.blade.php' => $VIEWS_ADMIN, // v2.4
				'email-settings.blade.php' => $VIEWS_ADMIN, // v2.4
				'payments-settings.blade.php' => $VIEWS_ADMIN,
				'subscriptions.blade.php' => $VIEWS_ADMIN, // v2.4
				'transactions.blade.php' => $VIEWS_ADMIN, // v2.4
				'paypal-settings.blade.php' => $VIEWS_ADMIN, // v2.4
				'coinpayments-settings.blade.php' => $VIEWS_ADMIN, // v2.4
				'paystack-settings.blade.php' => $VIEWS_ADMIN, // v2.4
				'stripe-settings.blade.php' => $VIEWS_ADMIN, // v2.4
				'ccbill-settings.blade.php' => $VIEWS_ADMIN, // v2.4
				'limits.blade.php' => $VIEWS_ADMIN, // v2.4
				'deposits-view.blade.php' => $VIEWS_ADMIN, // v2.4
				'members.blade.php' => $VIEWS_ADMIN, // v2.4
				'layout.blade.php' => $VIEWS_ADMIN, // v2.4
				'announcements.blade.php' => $VIEWS_ADMIN, // v2.4
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy Folders

			// Events
			$filePathFolderEvents = $path . 'Events';
			$pathFolderEvents = app_path('Events') . $DS;

			$this->moveDirectory($filePathFolderEvents, $pathFolderEvents, $copy);

			// Listeners
			$filePathFolderListeners = $path . 'Listeners';
			$pathFolderListeners = app_path('Listeners') . $DS;

			$this->moveDirectory($filePathFolderListeners, $pathFolderListeners, $copy);

			// Jobs
			$filePathFolderJobs = $path . 'Jobs';
			$pathFolderJobs = app_path('Jobs') . $DS;

			$this->moveDirectory($filePathFolderJobs, $pathFolderJobs, $copy);

			// Fileuploader
			$filePathFolderFileuploader = $path . 'fileuploader';
			$pathFolderFileuploader = public_path('js' . $DS . 'fileuploader') . $DS;

			$this->moveDirectory($filePathFolderFileuploader, $pathFolderFileuploader, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Start v2.4 ====================================

			$this->settings->update([
				'min_width_height_image' => '400'
			]);

			// Create Table Media
			if (!Schema::hasTable('media')) {
				Schema::create('media', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('updates_id')->index();
					$table->unsignedInteger('user_id')->index();
					$table->string('type', 100)->index();
					$table->string('image');
					$table->string('width', 5)->nullable();
					$table->string('height', 5)->nullable();
					$table->string('img_type');
					$table->string('video');
					$table->string('video_poster')->nullable();
					$table->string('video_embed', 200);
					$table->string('music');
					$table->string('file');
					$table->string('file_name');
					$table->string('file_size');
					$table->string('token')->index();
					$table->enum('status', ['active', 'pending'])->default('active');
					$table->timestamps();
				});
			} // <<--- End Create Table Media

			// Move all media to new table
			if (Schema::hasTable('media')) {

				$allUpdates = Updates::where('image', '<>', '')
					->orWhere('video', '<>', '')
					->orWhere('music', '<>', '')
					->orWhere('file', '<>', '')
					->orWhere('video_embed', '<>', '')
					->get();

				if ($allUpdates) {
					foreach ($allUpdates as $key) {

						if ($key->image) {
							$type = 'image';
						}

						if ($key->video) {
							$type = 'video';
						}

						if ($key->music) {
							$type = 'music';
						}

						if ($key->file) {
							$type = 'file';
						}

						if ($key->video_embed) {
							$type = 'video';
						}

						$data[] = [
							'updates_id' => $key->id,
							'user_id' => $key->user_id,
							'type' => $type,
							'image' => $key->image,
							'width' => null,
							'height' => null,
							'video' => $key->video,
							'video_poster' => null,
							'video_embed' => $key->video_embed,
							'music' => $key->music,
							'file' => $key->file,
							'file_name' => $key->file_name,
							'file_size' => $key->file_size,
							'img_type' => $key->img_type,
							'token' => $key->token_id,
							'created_at' => now()
						];
					}

					if (isset($data)) {

						foreach (array_chunk($data, 500) as $key => $smlArray) {
							foreach ($smlArray as $index => $value) {
								$tmp[$index] = $value;
							}
							Media::insert($tmp);
						}
					}
				} // allUpdates

			} // <<--- Move all media to new table

			// Create Table Media Messages
			if (!Schema::hasTable('media_messages')) {
				Schema::create('media_messages', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('messages_id')->index();
					$table->string('type', 100)->index();
					$table->string('file');
					$table->string('width', 5)->nullable();
					$table->string('height', 5)->nullable();
					$table->string('video_poster')->nullable();
					$table->string('file_name');
					$table->string('file_size');
					$table->string('token')->index();
					$table->enum('status', ['active', 'pending'])->default('active');
					$table->timestamps();
				});
			} // <<--- End Create Table Media Messages

			// Move all media messages to new table
			if (Schema::hasTable('media_messages')) {
				$allMessages = Messages::where('file', '<>', '')->get();

				if ($allMessages) {
					foreach ($allMessages as $key) {

						$dataMessages[] = [
							'messages_id' => $key->id,
							'type' => $key->format,
							'file' => $key->file,
							'width' => null,
							'height' => null,
							'video_poster' => null,
							'file_name' => $key->original_name,
							'file_size' => $key->size,
							'token' => str_random(150) . uniqid() . now()->timestamp,
							'created_at' => now()
						];
					}

					if (isset($dataMessages)) {
						foreach (array_chunk($dataMessages, 500) as $key => $smlArray) {
							foreach ($smlArray as $index => $value) {
								$tmp[$index] = $value;
							}
							MediaMessages::insert($tmp);
						}
					}
				} // allMessages

			} // <<--- Move all media messages to new table

			if (!Schema::hasColumn('users', 'birthdate_changed', 'email_new_tip', 'email_new_ppv')) {
				Schema::table('users', function ($table) {
					$table->enum('birthdate_changed', ['yes', 'no'])->default('no');
					$table->enum('email_new_tip', ['yes', 'no'])->default('yes');
					$table->enum('email_new_ppv', ['yes', 'no'])->default('yes');
					$table->enum('notify_new_ppv', ['yes', 'no'])->default('yes');
					$table->enum('active_status_online', ['yes', 'no'])->default('yes');
				});
			}

			Schema::table('users', function ($table) {
				$table->dropColumn('created_at');
				$table->dropColumn('updated_at');
			});

			Schema::table('users', function ($table) {
				$table->string('name', 150)->change();
			});

			Schema::table('admin_settings', function ($table) {
				$table->dropColumn('announcements');
			});

			if (!Schema::hasColumn(
				'admin_settings',
				'disable_banner_cookies',
				'wallet_format',
				'maximum_files_post',
				'maximum_files_msg',
				'announcement',
				'announcement_show',
				'announcement_cookie',
				'limit_categories',
				'ffmpeg_path'
			)) {
				Schema::table('admin_settings', function ($table) {
					$table->enum('disable_banner_cookies', ['on', 'off'])->default('off');
					$table->enum('wallet_format', ['real_money', 'credits', 'points', 'tokens'])->default('real_money');
					$table->unsignedInteger('maximum_files_post')->default(5);
					$table->unsignedInteger('maximum_files_msg')->default(5);
					$table->longText('announcement')->collation('utf8mb4_unicode_ci');
					$table->string('announcement_show', 100);
					$table->string('announcement_cookie', 20);
					$table->unsignedInteger('limit_categories')->default(3);
					$table->string('ffmpeg_path');
				});
			}

			if (!Schema::hasColumn('subscriptions', 'rebill_wallet')) {
				Schema::table('subscriptions', function ($table) {
					$table->enum('rebill_wallet', ['on', 'off'])->default('off');
				});
			}

			Schema::table('users', function ($table) {
				$table->string('categories_id')->change();
			});

			DB::statement('ALTER TABLE pages MODIFY COLUMN content MEDIUMTEXT');
			DB::statement('ALTER TABLE users CHANGE featured_date featured_date TIMESTAMP NULL DEFAULT NULL');
			DB::statement("ALTER TABLE users CHANGE notify_email_new_post notify_email_new_post ENUM('yes','no') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'yes'");

			@file_put_contents(
				'.env',
				"\nQUEUE_CONNECTION=database\n\nFFMPEG_PATH=\"\"",
				FILE_APPEND
			);

			// Create Table Jobs
			if (!Schema::hasTable('jobs')) {
				Schema::create('jobs', function ($table) {
					$table->bigIncrements('id');
					$table->string('queue')->index();
					$table->longText('payload');
					$table->unsignedTinyInteger('attempts');
					$table->unsignedInteger('reserved_at')->nullable();
					$table->unsignedInteger('available_at');
					$table->unsignedInteger('created_at');
				});
			} // <<--- End Create Table Jobs

			// Create Table Failed Jobs
			if (!Schema::hasTable('failed_jobs')) {
				Schema::create('failed_jobs', function ($table) {
					$table->id();
					$table->text('connection');
					$table->text('queue');
					$table->longText('payload');
					$table->longText('exception');
					$table->timestamp('failed_at')->useCurrent();
				});
			} // <<--- End Create Table Failed Jobs

			// Add Artisan and Explore as a reserved name
			if (!Schema::hasColumn('reserved', 'artisan', 'explore')) {
				\DB::table('reserved')->insert([
					['name' => 'artisan'],
					['name' => 'explore']
				]);
			} // <<--- End

			if (Schema::hasTable('payment_gateways')) {
				\DB::table('payment_gateways')->insert(
					[
						[
							'name' => 'Coinpayments',
							'type' => 'normal',
							'enabled' => '0',
							'fee' => 0.0,
							'fee_cents' => 0.00,
							'email' => '',
							'key' => '',
							'key_secret' => '',
							'recurrent' => 'no',
							'logo' => 'coinpayments.png',
							'subscription' => 'no',
							'bank_info' => '',
							'token' => str_random(150),
						]
					]
				);
			} // End add Coinpayments

			// End Query v2.4 ====================================

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		} //<<---- End Version 2.4 ----->>

		if ($version == '2.5') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'AddFundsController.php' => $CONTROLLERS, // v2.5
				'AdminController.php' => $CONTROLLERS, // v2.5
				'HomeController.php' => $CONTROLLERS, // v2.5
				'PayPalController.php' => $CONTROLLERS, // v2.5
				'CommentsController.php' => $CONTROLLERS, // v2.5
				'InstallScriptController.php' => $CONTROLLERS, // v2.5
				'UpdatesController.php' => $CONTROLLERS, // v2.5
				'UserController.php' => $CONTROLLERS, // v2.5

				'Comments.php' => $MODELS, // v2.5
				'CommentsLikes.php' => $MODELS, // v2.5

				'UserDelete.php' => $TRAITS, // v2.5

				'app.blade.php' => $VIEWS_LAYOUTS, // v2.5

				'contact.blade.php' => $VIEWS_INDEX, // v2.5
				'explore.blade.php' => $VIEWS_INDEX, // v2.5

				'navbar.blade.php' => $VIEWS_INCLUDES, // v2.5
				'form-post.blade.php' => $VIEWS_INCLUDES, // v2.5
				'comments.blade.php' => $VIEWS_INCLUDES, // v2.5
				'css_general.blade.php' => $VIEWS_INCLUDES, // v2.5
				'javascript_general.blade.php' => $VIEWS_INCLUDES, // v2.5
				'media-post.blade.php' => $VIEWS_INCLUDES, // v2.5
				'media-messages.blade.php' => $VIEWS_INCLUDES, // v2.5
				'modal-new-message.blade.php' => $VIEWS_INCLUDES, // v2.5
				'sidebar-messages-inbox.blade.php' => $VIEWS_INCLUDES,
				'menu-sidebar-home.blade.php' => $VIEWS_INCLUDES, // v2.5
				'updates.blade.php' => $VIEWS_INCLUDES, // v2.5

				'requirements.blade.php' => $VIEWS_INSTALL, // v2.5

				'profile.blade.php' => $VIEWS_USERS, // v2.5
				'post-detail.blade.php' => $VIEWS_USERS, // v2.5
				'notifications.blade.php' => $VIEWS_USERS, // v2.5
				'messages-show.blade.php' => $VIEWS_USERS, // v2.5
				'payout_method.blade.php' => $VIEWS_USERS, // v2.5

				'app-functions.js' => $PUBLIC_JS, // v2.5
				'payoneer.png' => public_path('img' . $DS . 'payments') . $DS, // v2.5
				'payoneer-white.png' => public_path('img' . $DS . 'payments') . $DS, // v2.5
				'zelle.png' => public_path('img' . $DS . 'payments') . $DS, // v2.5
				'zelle-white.png' => public_path('img' . $DS . 'payments') . $DS, // v2.5

			];

			$filesAdmin = [
				'edit-member.blade.php' => $VIEWS_ADMIN, // v2.5
				'settings.blade.php' => $VIEWS_ADMIN, // v2.5
				'payments-settings.blade.php' => $VIEWS_ADMIN, // v2.5
				'coinpayments-settings.blade.php' => $VIEWS_ADMIN, // v2.5
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			//============== Start Query v2.5 ====================================

			// Replace String
			$findStringLang = ');';

			// Ennglish
			$replaceLangEN    = "
		 // Version 2.5
 'price_post_ppv' => 'Set a price for this post',
 'captcha_contact' => 'Captcha on Page Contact us',
 'disable_tips' => 'Disable tips',
 'payout_method_info' => 'Select the payment method you want to receive your earnings.',
 'processor_fees_may_apply' => 'Some processor fees may apply',
 'email_payoneer' => 'Email Payoneer',
 'confirm_email_payoneer' => 'Confirm Email Payoneer',
 'email_zelle' => 'Email Zelle',
 'confirm_email_zelle' => 'Confirm Email Zelle',
 'liked_your_comment' => 'liked your comment in',
 'someone_liked_comment' => 'Someone liked your comment',
);";
			$fileLangEN = 'resources/lang/en/general.php';
			@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

			// Español
			$replaceLangES    = "
	 // Version 2.5
 'price_post_ppv' => 'Establezca un precio para esta publicación',
 'captcha_contact' => 'Captcha en Página Contáctenos',
 'disable_tips' => 'Desactivar propinas',
 'payout_method_info' => 'Selecciona el método de pago que deseas recibir tus ganancias.',
 'processor_fees_may_apply' => 'Es posible que se apliquen algunas tarifas del procesador.',
 'email_payoneer' => 'Email de Payoneer',
 'confirm_email_payoneer' => 'Confirmar correo Payoneer',
 'email_zelle' => 'Email de Zelle',
 'confirm_email_zelle' => 'Confirmar correo Zelle',
 'liked_your_comment' => 'le gustó tu comentario en',
 'someone_liked_comment' => 'A alguien le gustó tu comentario',
);";
			$fileLangES = 'resources/lang/es/general.php';
			@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

			@file_put_contents(
				'routes/web.php',
				"
Route::post('comment/like','CommentsController@like')->middleware('auth');",
				FILE_APPEND
			);

			if (!Schema::hasColumn(
				'users',
				'payoneer_account',
				'zelle_account'
			)) {
				Schema::table('users', function ($table) {
					$table->string('payoneer_account', 200);
					$table->string('zelle_account', 200);
					$table->enum('notify_liked_comment', ['yes', 'no'])->default('yes');
				});
			}

			if (!Schema::hasColumn(
				'admin_settings',
				'captcha_contact',
				'disable_tips'
			)) {
				Schema::table('admin_settings', function ($table) {
					$table->enum('captcha_contact', ['on', 'off'])->default('on');
					$table->enum('disable_tips', ['on', 'off'])->default('off');
					$table->enum('payout_method_payoneer', ['on', 'off'])->default('off');
					$table->enum('payout_method_zelle', ['on', 'off'])->default('off');
				});
			}

			Schema::table('admin_settings', function ($table) {
				$table->dropColumn('ffmpeg_path');
			});

			// Create Table Comments Likes
			if (!Schema::hasTable('comments_likes')) {
				Schema::create('comments_likes', function ($table) {
					$table->increments('id');
					$table->unsignedInteger('user_id')->index();
					$table->unsignedInteger('comments_id')->index();
					$table->timestamps();
				});
			} // <<--- End Create Table Bookmarks

			//=============== End Query v2.5 ====================================

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		} //<<---- End Version 2.5 ----->>

		if ($version == '2.6') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'serviceworker.js' => $ROOT, // v2.6
				'Helper.php' => $APP, // v2.6
				'PostRejected.php' => $NOTIFICATIONS, // v2.6
				'queue.php' => $CONFIG, // v2.6
				'web.php' => $ROUTES, // v2.6
				'Kernel.php' => app_path('Http') . $DS, // v2.6

				//============ CONTROLLERS =================//
				'AddFundsController.php' => $CONTROLLERS, // v2.6
				'AdminController.php' => $CONTROLLERS, // v2.6
				'CCBillController.php' => $CONTROLLERS, //v2.6
				'HomeController.php' => $CONTROLLERS, // v2.6
				'InstallScriptController.php' => $CONTROLLERS, // v2.6
				'PayPalController.php' => $CONTROLLERS, // v2.6
				'TipController.php' => $CONTROLLERS, // v2.6
				'CommentsController.php' => $CONTROLLERS,
				'SubscriptionsController.php' => $CONTROLLERS, // v2.6
				'UploadMediaController.php' => $CONTROLLERS, // v2.6
				'UploadMediaMessageController.php' => $CONTROLLERS, // v2.6
				'UpdatesController.php' => $CONTROLLERS, // v2.6
				'UserController.php' => $CONTROLLERS, // v2.6
				'MessagesController.php' => $CONTROLLERS, // v2.6
				'RegisterController.php' => $CONTROLLERS, // v2.6

				'Role.php' => $MIDDLEWARE, // v2.6
				'UserCountry.php' => $MIDDLEWARE, // v2.6

				'DeleteMedia.php' => $JOBS, // v2.6
				'EncodeVideo.php' => $JOBS, // v2.6
				'EncodeVideoMessages.php' => $JOBS, // v2.6

				'Comments.php' => $MODELS,
				'User.php' => $MODELS, // v2.6
				'Conversations.php' => $MODELS, // v2.6

				'Functions.php' => $TRAITS, // v2.6
				'UserDelete.php' => $TRAITS, // v2.6

				//============ PUBLIC =================//

				'app-functions.js' => $PUBLIC_JS, // v2.6
				'messages.js' => $PUBLIC_JS, // v2.6
				'core.min.js' => $PUBLIC_JS, // v2.6

				'functions.js' => $PUBLIC_JS_ADMIN, // v2.6
				'AdminLTE.min.css' => $PUBLIC_CSS_ADMIN, // v2.6
				'app.css' => $PUBLIC_CSS_ADMIN, // v2.6
				'fileuploader-msg.js' => public_path('js' . $DS . 'fileuploader') . $DS, // v2.6
				'fileuploader-post.js' => public_path('js' . $DS . 'fileuploader') . $DS, // v2.6

				//=========== VIEWS ===================//

				'register.blade.php' => $VIEWS_AUTH, // v2.6

				'app.blade.php' => $VIEWS_LAYOUTS, // v2.6

				'blog.blade.php' => $VIEWS_INDEX, // v2.6
				'explore.blade.php' => $VIEWS_INDEX,
				'home-session.blade.php' => $VIEWS_INDEX, // v2.6
				'home.blade.php' => $VIEWS_INDEX, // v2.6
				'post.blade.php' => $VIEWS_INDEX, // v2.6

				'navbar.blade.php' => $VIEWS_INCLUDES, // v2.6
				'messages-chat.blade.php' => $VIEWS_INCLUDES, // v2.6
				'comments.blade.php' => $VIEWS_INCLUDES,
				'css_general.blade.php' => $VIEWS_INCLUDES, // v2.6
				'javascript_general.blade.php' => $VIEWS_INCLUDES,
				'media-post.blade.php' => $VIEWS_INCLUDES,
				'media-messages.blade.php' => $VIEWS_INCLUDES, // v2.6
				'sidebar-messages-inbox.blade.php' => $VIEWS_INCLUDES,
				'form-post.blade.php' => $VIEWS_INCLUDES, // v2.6
				'updates.blade.php' => $VIEWS_INCLUDES, // v2.6
				'css_admin.blade.php' => $VIEWS_INCLUDES, // v2.6
				'cards-settings.blade.php' => $VIEWS_INCLUDES, // v2.6
				'modal-new-message.blade.php' => $VIEWS_INCLUDES, // v2.6

				'requirements.blade.php' => $VIEWS_INSTALL,

				'dashboard.blade.php' => $VIEWS_USERS, // v2.6
				'profile.blade.php' => $VIEWS_USERS, // v2.6
				'invoice-deposits.blade.php' => $VIEWS_USERS, // v2.6
				'invoice.blade.php' => $VIEWS_USERS, // v2.6
				'notifications.blade.php' => $VIEWS_USERS, // v2.6
				'messages-show.blade.php' => $VIEWS_USERS, // v2.6
				'my-purchases.blade.php' => $VIEWS_USERS, // v2.6
				'wallet.blade.php' => $VIEWS_USERS, // v2.6
				'my_posts.blade.php' => $VIEWS_USERS, // v2.6
				'edit_my_page.blade.php' => $VIEWS_USERS, // v2.6
				'block_countries.blade.php' => $VIEWS_USERS, // v2.6

				'meta.blade.php' => resource_path('views' . $DS . 'vendor' . $DS . 'laravelpwa'), // v2.6

			];

			$filesAdmin = [
				'charts.blade.php' => $VIEWS_ADMIN, // v2.6
				'dashboard.blade.php' => $VIEWS_ADMIN, // v2.6
				'announcements.blade.php' => $VIEWS_ADMIN, // v2.6
				'limits.blade.php' => $VIEWS_ADMIN, // v2.6
				'posts.blade.php' => $VIEWS_ADMIN, // v2.6
				'edit-member.blade.php' => $VIEWS_ADMIN, // v2.6
				'members.blade.php' => $VIEWS_ADMIN, // v2.6
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, // v2.6
				'layout.blade.php' => $VIEWS_ADMIN, // v2.6
				'blog.blade.php' => $VIEWS_ADMIN, // v2.6
				'languages.blade.php' => $VIEWS_ADMIN, // v2.6
				'edit-languages.blade.php' => $VIEWS_ADMIN, // v2.6
				'pages.blade.php' => $VIEWS_ADMIN, // v2.6
				'edit-pages.blade.php' => $VIEWS_ADMIN, // v2.6
				'add-page.blade.php' => $VIEWS_ADMIN, // v2.6
				'categories.blade.php' => $VIEWS_ADMIN, // v2.6
				'unauthorized.blade.php' => $VIEWS_ADMIN, // v2.6
				'settings.blade.php' => $VIEWS_ADMIN, // v2.6
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Folder Console
			$filePathFolderConsole = $path . 'Console';
			$pathFolderConsole = app_path('Console') . $DS;

			File::deleteDirectory($pathFolderConsole);

			$this->moveDirectory($filePathFolderConsole, $pathFolderConsole, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			//============== Start Query v2.6 ====================================

			// Replace String
			$findStringLang = ');';

			// Ennglish
			$replaceLangEN    = "
		 // Version 2.6
	 'explore_posts' => 'Explore Posts',
	 'transaction_fee_info' => '* Transaction fee is not included in the amount, only on invoice.',
	 'type_announcement' => 'Type announcement',
	 'informative' => 'Informative',
	 'important' => 'Important',
	 'compared_yesterday' => 'Compared to yesterday',
	 'compared_last_week' => 'Compared to last week',
	 'compared_last_month' => 'Compared to last month',
	 'auto_approve_post' => 'Auto approve Post',
	 'post_pending_review' => 'Post pending review',
	 'alert_post_pending_review' => 'Your publication will be available after it is reviewed, you can see in',
	 'my_posts' => 'My Posts',
	 'yes_confirm_reject_post' => 'Yes, reject post!',
	 'yes_confirm_approve_post' => 'Yes, approve post!',
	 'delete_confirm_post' => 'An email will be sent to the user notifying that their post was rejected.',
	 'approve_confirm_post' => 'An notification will be sent to the user notifying that their post was approved.',
	 'rejected_post' => 'Post Rejected',
	 'approve_post_success' => 'Post has been approved successfully!',
	 'line_rejected_post' => 'Your post \":title\" was rejected because it does not meet our terms and conditions.', // Do not remove :title
	 'has_approved_your_post' => 'Your post has been approved',
	 'all_post_created' => 'All the posts you have created',
	 'interactions' => 'Interactions',
	 'not_post_created' => 'You have not created any post so far',
	 'role_and_permissions' => 'Role and permissions',
	 'can_see' => 'Can see (Read only)',
	 'can_crud' => 'Can Create, Read, Update, Approve, Delete, etc.',
	 'can_see_post_blocked' => 'See blocked posts or premium (PPV)',
	 'info_can_see_post_blocked' => 'If you give access to manage posts you must select \"Yes\"',
	 'limited_access' => 'Limited Access',
	 'info_limited_access' => 'The user will be able to access all the sections of the Panel Admin, but will not be able to add, edit or delete anything.',
	 'give_access_error' => 'To give access to a section you must uncheck the Limited Access option',
	 'select_all' => 'Select all',
	 'unauthorized_action' => 'You are not authorized to perform this action',
	 'unauthorized_section' => 'You do not have permission to view this section, go to the available sections found in the left menu.',
	 'block_countries' => 'Block Countries',
	 'block_countries_info' => 'Select the countries in which you do not want your profile to be displayed, they will not be able to see your profile in any section of the site.',
	 'super_admin' => 'Super Admin',
	 'couple' => 'Couple',
	 'video_on_way' => 'Video on the way...',
	 'video_processed_info' => 'Your video is being processed, you will receive a notification when it is ready.',
	 'video_processed_successfully_post' => 'Your video has been processed successfully (Post)',
	 'video_processed_successfully_message' => 'Your video has been processed successfully (Message)',
);";
			$fileLangEN = 'resources/lang/en/general.php';
			@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

			// Español
			$replaceLangES    = "
	 // Version 2.6
 'explore_posts' => 'Explorar Posts',
 'transaction_fee_info' => '* La tarifa de transacción no está incluida en el monto, solo en factura.',
 'type_announcement' => 'Tipo de anuncio',
 'informative' => 'Informativo',
 'important' => 'Importante',
 'compared_yesterday' => 'Comparado con ayer',
 'compared_last_week' => 'Comparado con la semana pasada',
 'compared_last_month' => 'Comparado con el mes pasado',
 'auto_approve_post' => 'Auto aprobar Publicación',
 'post_pending_review' => 'Publicación pendiente de revision',
 'alert_post_pending_review' => 'Tu publicación estará disponible despues que sea revisada, puedes ver en',
 'my_posts' => 'Mis Posts',
 'yes_confirm_reject_post' => 'Sí, ¡rechazar post!',
 'yes_confirm_approve_post' => 'Sí, ¡aprobar post!',
 'delete_confirm_post' => 'Se enviará un correo electrónico al usuario notificando que su publicación fue rechazada.',
 'approve_confirm_post' => 'Se enviará una notificación al usuario notificando que su publicación fue aprobada.',
 'rejected_post' => 'Post Rechazado',
 'approve_post_success' => '¡Post ha sido aprobado con éxito!',
 'line_rejected_post' => 'Su post \":title\" fue rechazado porque no cumple con nuestros términos y condiciones.', // Do not remove :title
 'has_approved_your_post' => 'Tu post ha sido aprobado',
 'all_post_created' => 'Todos los posts que has creado',
 'interactions' => 'Interacciones',
 'not_post_created' => 'No has creado ningún post hasta el momento',
 'role_and_permissions' => 'Rol y permisos',
 'can_see' => 'Puede ver (Solo lectura)',
 'can_crud' => 'Puede Crear, Leer, Actualizar Aprobar, Borrar, etc.',
 'can_see_post_blocked' => 'Ver posts bloqueados o premium (PPV)',
 'info_can_see_post_blocked' => 'Si das acceso a manejar posts debes seleccionar \"Sí\"',
 'limited_access' => 'Acceso Limitado',
 'info_limited_access' => 'El usuario podrá acceder a todas las secciones del Panel Admin, pero no podrá agregar, editar o eliminar nada.',
 'give_access_error' => 'Para dar acceso a una sección debes desmarcar la opción Acceso Limitado',
 'select_all' => 'Seleccionar todo',
 'unauthorized_action' => 'No estas autorizado para realizar está acción',
 'unauthorized_section' => 'No tienes permiso para ver esta sección, ingresa a las secciones disponibles que se encuentrán en el menú izquierdo.',
 'block_countries' => 'Bloquear Países',
 'block_countries_info' => 'Selecciona los países en los que no desea que se muestre su perfil, no podrán ver su perfil en ninguna sección del sitio.',
 'super_admin' => 'Super Admin',
 'couple' => 'Pareja',
 'video_on_way' => 'Vídeo en camino...',
 'video_processed_info' => 'Tu video está siendo procesado, recibirá una notificación cuando esté listo.',
 'video_processed_successfully_post' => 'Tu video ha sido procesado con éxito (Post)',
 'video_processed_successfully_message' => 'Tu video ha sido procesado con éxito (Mensaje)',
);";
			$fileLangES = 'resources/lang/es/general.php';
			@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

			//============ Start v2.6
			if (!Schema::hasColumn(
				'admin_settings',
				'type_announcement',
				'referral_system',
				'auto_approve_post'
			)) {
				Schema::table('admin_settings', function ($table) {
					$table->char('type_announcement', 10)->default('primary');
					$table->enum('referral_system', ['on', 'off'])->default('off');
					$table->enum('auto_approve_post', ['on', 'off'])->default('on');
				});
			}

			if (!Schema::hasColumn('updates', 'status')) {
				Schema::table('updates', function ($table) {
					$table->char('status', 20)->default('active')->index();
				});
			}

			Schema::table('notifications', function ($table) {
				$table->unsignedInteger('type')->change();
			});

			if (!Schema::hasColumn(
				'users',
				'permissions',
				'blocked_countries'
			)) {
				Schema::table('users', function ($table) {
					$table->text('permissions');
					$table->text('blocked_countries');
				});
			}

			// Update permissions to Admin
			if (Schema::hasColumn('users', 'permissions')) {
				User::whereId(1)->update([
					'permissions' => 'full_access'
				]);
			}

			// Add Percentage to table Deposits
			if (!Schema::hasColumn('deposits', 'percentage_applied', 'transaction_fee')) {
				Schema::table('deposits', function ($table) {
					$table->string('percentage_applied', 50);
					$table->float('transaction_fee', 10, 2);
				});
			}

			if (!Schema::hasColumn('media', 'encoded')) {
				Schema::table('media', function ($table) {
					$table->enum('encoded', ['yes', 'no'])->default('no')->after('video')->index();
				});
			}

			if (!Schema::hasColumn('messages', 'mode')) {
				Schema::table('messages', function ($table) {
					$table->enum('mode', ['active', 'pending'])->default('active')->index();
				});
			}

			if (!Schema::hasColumn('media_messages', 'encoded')) {
				Schema::table('media_messages', function ($table) {
					$table->enum('encoded', ['yes', 'no'])->default('no')->index();
				});
			}


			//=============== End Query v2.6 ====================================

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 2.6 ----->>

		if ($version == '2.7') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'SendTwoFactorCode.php' => $NOTIFICATIONS, // v2.7
				'Kernel.php' => app_path('Http') . $DS, // v2.7
				'Helper.php' => $APP, // v2.7
				'web.php' => $ROUTES, // v2.7

				//============ CONTROLLERS =================//
				'AddFundsController.php' => $CONTROLLERS, // v2.7
				'AdminController.php' => $CONTROLLERS, // v2.7
				'HomeController.php' => $CONTROLLERS, // v2.7
				'TwoFactorAuthController.php' => $CONTROLLERS, // v2.7
				'UpdatesController.php' => $CONTROLLERS, // v2.7
				'UserController.php' => $CONTROLLERS, // v2.7
				'MessagesController.php' => $CONTROLLERS, // v2.7
				'RegisterController.php' => $CONTROLLERS_AUTH, // v2.7
				'LoginController.php' => $CONTROLLERS_AUTH, // v2.7

				'UserCountry.php' => $MIDDLEWARE, // v2.7
				'Referred.php' => $MIDDLEWARE, // v2.7

				'EncodeVideo.php' => $JOBS, // v2.7
				'EncodeVideoMessages.php' => $JOBS, // v2.7

				'User.php' => $MODELS, // v2.7
				'Referrals.php' => $MODELS, // v2.7
				'TwoFactorCodes.php' => $MODELS, // v2.7

				'Functions.php' => $TRAITS, // v2.7
				'UserDelete.php' => $TRAITS, // v2.7

				//============ PUBLIC =================//
				'app-functions.js' => $PUBLIC_JS, // v2.7

				//=========== VIEWS ===================//
				'app.blade.php' => $VIEWS_LAYOUTS, // v2.7

				'navbar.blade.php' => $VIEWS_INCLUDES, // v2.7
				'messages-chat.blade.php' => $VIEWS_INCLUDES, // v2.7
				'css_general.blade.php' => $VIEWS_INCLUDES, // v2.7
				'javascript_general.blade.php' => $VIEWS_INCLUDES, // v2.7
				'media-post.blade.php' => $VIEWS_INCLUDES, // v2.7
				'media-messages.blade.php' => $VIEWS_INCLUDES, // v2.7
				'cards-settings.blade.php' => $VIEWS_INCLUDES, // v2.7
				'modal-2fa.blade.php' => $VIEWS_INCLUDES, // v2.7
				'modal-new-message.blade.php' => $VIEWS_INCLUDES, // v2.7
				'messages-inbox.blade.php' => $VIEWS_INCLUDES, // v2.7
				'updates.blade.php' => $VIEWS_INCLUDES, // v2.7

				'profile.blade.php' => $VIEWS_USERS, // v2.7
				'notifications.blade.php' => $VIEWS_USERS, // v2.7
				'wallet.blade.php' => $VIEWS_USERS, // v2.7
				'my_posts.blade.php' => $VIEWS_USERS, // v2.7
				'edit_my_page.blade.php' => $VIEWS_USERS, // v2.7
				'subscription.blade.php' => $VIEWS_USERS, // v2.7
				'referrals.blade.php' => $VIEWS_USERS, // v2.7
				'payout_method.blade.php' => $VIEWS_USERS, // v2.7
				'privacy_security.blade.php' => $VIEWS_USERS, // v2.7
				'delete_account.blade.php' => $VIEWS_USERS, // v2.7

			];

			$filesAdmin = [
				'posts.blade.php' => $VIEWS_ADMIN, // v2.7
				'payments-settings.blade.php' => $VIEWS_ADMIN, // v.2.7
				'transactions.blade.php' => $VIEWS_ADMIN, // v.2.7
				'edit-page.blade.php' => $VIEWS_ADMIN, // v.2.7
				'email-settings.blade.php' => $VIEWS_ADMIN, // v2.7
				'settings.blade.php' => $VIEWS_ADMIN, // v2.7
				'pwa.blade.php' => $VIEWS_ADMIN, // v2.7
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Folder Console
			$filePathFolderConsole = $path . 'Console';
			$pathFolderConsole = app_path('Console') . $DS;

			if ($copy == false) {
				File::deleteDirectory($pathFolderConsole);
			}

			$this->moveDirectory($filePathFolderConsole, $pathFolderConsole, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			//============== Start Query v2.6 ====================================

			// Replace String
			$findStringLang = ');';

			// Ennglish
			$replaceLangEN    = "
		 // Version 2.7
		 'watermark_on_videos' => 'Watermark on videos',
		 'subscription_price' => 'Subscription price',
		 'referrals' => 'Referrals',
		 'referrals_desc' => 'Welcome to your referral panel. Share your link and earn :percentage% of your referrals first transaction, be it a subscription, send a tip or a PPV!',// Not remove :percentage
		 'referral_system' => 'Referral system',
		 'percentage_referred' => 'Percentage of profit for each referral',
		 'total_registered_users' => 'Total registered users',
		 'total_transactions' => 'Total transactions',
		 'earnings_total' => 'Total Earnings',
		 'no_transactions_yet' => 'No transactions yet',
		 'your_referral_link' => 'Your referral link is:',
		 'referral_system_disabled' => 'The Referral System is currently disabled',
		 'referrals_made' => 'One of your referrals has made a',
		 'transaction' => 'transaction',
		 'referral_commission_applied' => 'Referral commission was applied',
		 'security' => 'Security',
		 'two_step_auth' => 'Two-Step Authentication',
		 'two_step_auth_info' => 'A code will be sent to your email every time you log in',
		 'two_step_authentication_code' => 'Two-Step Authentication Code',
		 'your_code_is' => 'Your code is: :code', // Not remove :code
		 'enter_code' => 'Enter the code',
		 '2fa_title_modal' => 'We have sent you a code to your email',
		 'code_2fa_invalid' => 'The code you entered is invalid',
		 'resend_code' => 'Resend code?',
		 'resend_code_success' => 'We have sent you a new code to your email',
		 'please_enter_code' => 'Please enter the code',
		 'delete_account_alert' => 'Watch out! This will permanently delete your account, and all your files, subscriptions, etc, and you will not be able to enter the site again.',
		 'chats' => 'Chats',
		 'no_chats' => 'You don\'t have any chat',
		 'error_active_system_referrals' => 'You cannot activate the Referral System if your commission fee is equal to 0',
);";
			$fileLangEN = 'resources/lang/en/general.php';
			@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

			// Español
			$replaceLangES    = "
	 // Version 2.6
	 'watermark_on_videos' => 'Marca de agua en vídeos',
	 'subscription_price' => 'Precio de suscripción',
	 'referrals' => 'Referidos',
	 'referrals_desc' => 'Bienvenido a su panel de referencia. ¡Comparta su enlace y gane un :percentage% de la primera transacción de su referido, ya sea una suscripción, enviar una propina o un PPV!',  // Not remove :percentage
	 'referral_system' => 'Sistema de referidos',
	 'percentage_referred' => 'Porcentaje de ganancia por cada referido',
	 'total_registered_users' => 'Total de usuarios registrados',
	 'total_transactions' => 'Total de transacciones',
	 'earnings_total' => 'Ganacias totales',
	 'no_transactions_yet' => 'Aún no hay transacciones',
	 'your_referral_link' => 'Tu enlace de referencia es:',
	 'referral_system_disabled' => 'El Sistema de Referidos actualmente está deshabilitado',
	 'referrals_made' => 'Uno de tus referidos ha realizado una',
	 'transaction' => 'transacción',
	 'referral_commission_applied' => 'Se aplicó la comisión de referidos',
	 'security' => 'Seguridad',
	 'two_step_auth' => 'Autenticación de dos pasos',
	 'two_step_auth_info' => 'Se le enviará un código a su correo electrónico cada vez que inicie sesión',
	 'two_step_authentication_code' => 'Código de Autenticación de dos pasos',
	 'your_code_is' => 'Tu código es: :code', // Not remove :code
	 'enter_code' => 'Ingrese el código',
	 '2fa_title_modal' => 'Te hemos enviado un código a tu correo electrónico',
	 'code_2fa_invalid' => 'El código que has ingresado no es válido',
	 'resend_code' => '¿Reenviar código?',
	 'resend_code_success' => 'Le hemos enviado un nuevo código a su correo electrónico',
	 'please_enter_code' => 'Por favor ingresa el código',
	 'delete_account_alert' => '¡Cuidado! Esto eliminará permanentemente su cuenta., y todos sus archivos, suscripciones, etc, y no podrá ingresar de nuevo al sitio.',
	 'chats' => 'Conversaciones',
	 'no_chats' => 'No tienes ninguna conversación',
	 'error_active_system_referrals' => 'No puede activar el Sistema de Referidos si tu cuota de comisión es igual a 0',
);";
			$fileLangES = 'resources/lang/es/general.php';
			@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

			//============ Start Query SQL ====================================
			if (!Schema::hasColumn(
				'admin_settings',
				'watermark_on_videos',
				'percentage_referred'
			)) {
				Schema::table('admin_settings', function ($table) {
					$table->enum('watermark_on_videos', ['on', 'off'])->default('on');
					$table->unsignedInteger('percentage_referred')->default(5);
				});
			}

			if (!Schema::hasTable('referrals')) {
				Schema::create('referrals', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('user_id')->index();
					$table->unsignedInteger('referred_by')->index();
					$table->float('earnings', 10, 2);
					$table->char('type', 25);
					$table->timestamps();
				});
			}

			if (!Schema::hasColumn('transactions', 'referred_commission')) {
				Schema::table('transactions', function ($table) {
					$table->unsignedInteger('referred_commission');
				});
			}

			if (!Schema::hasTable('two_factor_codes')) {
				Schema::create('two_factor_codes', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('user_id');
					$table->string('code', 25);
					$table->timestamps();
				});
			}

			if (!Schema::hasColumn('users', 'two_factor_auth')) {
				Schema::table('users', function ($table) {
					$table->enum('two_factor_auth', ['yes', 'no'])->default('no');
				});
			}

			//=============== End Query SQL ====================================

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 2.7 ----->>

		if ($version == '2.8') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'Helper.php' => $APP, // v2.8
				'SocialAccountService.php' => $APP, // v2.8

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, // v2.8
				'HomeController.php' => $CONTROLLERS, // v2.8
				'TwoFactorAuthController.php' => $CONTROLLERS, // v2.8
				'UpdatesController.php' => $CONTROLLERS, // v2.8
				'UserController.php' => $CONTROLLERS, // v2.8
				'MessagesController.php' => $CONTROLLERS, // v2.8
				'StripeController.php' => $CONTROLLERS, // v2.8
				'StripeWebHookController.php' => $CONTROLLERS, // v2.8
				'SubscriptionsController.php' => $CONTROLLERS, // v2.8
				'PaystackController.php' => $CONTROLLERS, // v2.8
				'CCBillController.php' => $CONTROLLERS, // v2.8
				'PayPalController.php' => $CONTROLLERS, // v2.8
				'PagesController.php' => $CONTROLLERS, // v2.8
				'UploadMediaController.php' => $CONTROLLERS, // v2.8
				'UploadMediaMessageController.php' => $CONTROLLERS, // v2.8

				'MassMessagesListener.php' => $LISTENERS, // v2.8
				'NewPostListener.php' => $LISTENERS, // v2.8
				'SubscriptionDisabledListener.php' => $LISTENERS, // v2.8

				'SubscriptionDisabledEvent.php' => $EVENTS, // v2.8

				'User.php' => $MODELS, // v2.8
				'ReferralTransactions.php' => $MODELS, // v2.8
				'Subscriptions.php' => $MODELS, // v2.8

				'SubscriptionDisabled.php' => $NOTIFICATIONS, // v2.8

				'EventServiceProvider.php' => $PROVIDERS, // v2.8

				'RebillWallet.php' => $JOBS, // v2.8

				'Functions.php' => $TRAITS, // v2.8
				'UserDelete.php' => $TRAITS, // v2.8

				//============ PUBLIC =================//
				'app-functions.js' => $PUBLIC_JS, // v2.8
				'core.min.js' => $PUBLIC_JS, // v2.8

				//=========== VIEWS ===================//
				'app.blade.php' => $VIEWS_LAYOUTS, // v2.8

				'categories.blade.php' => $VIEWS_INDEX, // v2.8
				'creators.blade.php' => $VIEWS_INDEX, // v2.8

				'css_general.blade.php' => $VIEWS_INCLUDES, // v2.8
				'modal-2fa.blade.php' => $VIEWS_INCLUDES, // v2.8
				'messages-inbox.blade.php' => $VIEWS_INCLUDES, // v2.8
				'updates.blade.php' => $VIEWS_INCLUDES, // v2.8
				'footer-tiny.blade.php' => $VIEWS_INCLUDES, // v2.8
				'footer.blade.php' => $VIEWS_INCLUDES, // v2.8
				'media-messages.blade.php' => $VIEWS_INCLUDES, // v2.8
				'media-post.blade.php' => $VIEWS_INCLUDES, // v2.8

				'profile.blade.php' => $VIEWS_USERS, // v2.8
				'notifications.blade.php' => $VIEWS_USERS, // v2.8
				'add_payment_card.blade.php' => $VIEWS_USERS, // v2.8
				'my_cards.blade.php' => $VIEWS_USERS, // v2.8
				'referrals.blade.php' => $VIEWS_USERS, // v2.8
				'my_subscribers.blade.php' => $VIEWS_USERS, // v2.8
				'my_subscriptions.blade.php' => $VIEWS_USERS, // v2.8
				'subscription.blade.php' => $VIEWS_USERS, // v2.8

				'show.blade.php' => $VIEWS_PAGES, // v2.8

				'payment.blade.php' => resource_path('views' . $DS . 'vendor' . $DS . 'cashier') . $DS, // v2.8
				'receipt.blade.php' => resource_path('views' . $DS . 'vendor' . $DS . 'cashier') . $DS, // v2.8
			];

			$filesAdmin = [
				'posts.blade.php' => $VIEWS_ADMIN, // v2.8
				'members.blade.php' => $VIEWS_ADMIN, // v2.8
				'transactions.blade.php' => $VIEWS_ADMIN,
				'edit-page.blade.php' => $VIEWS_ADMIN, // v2.8
				'add-page.blade.php' => $VIEWS_ADMIN, // v2.8
				'pages.blade.php' => $VIEWS_ADMIN, // v2.8
				'payments-settings.blade.php' => $VIEWS_ADMIN, // v2.8
				'settings.blade.php' => $VIEWS_ADMIN, // v2.8
				'reports.blade.php' => $VIEWS_ADMIN, // v2.8
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			//============== Start Query ====================================

			// Replace String
			$findStringLang = ');';

			// Ennglish
			$replaceLangEN    = "
		 // Version 2.8
		 'encode' => 'Encode',
		 'your_amount_payment' => 'Your :amount payment', // Not remove :amount
		 'payment_processing' => 'Payment Processing',
		 'payment_processing_info' => 'This payment is currently processing. Refresh this page from time to time to see its status.',
		 'stripe_payment_info' => 'A valid payment method is needed to process your payment. Please confirm your payment by filling out your payment details below.',
		 'payment_method' => 'Payment Method',
		 'payment_method_info' => 'Please select the payment method which you\'d like to use.',
		 'processing' => 'Processing...',
		 'stripe_text_info_1' => 'Your payment will be processed by',
		 'stripe_text_info_2' => 'Payment details',
		 'stripe_text_info_3' => 'Remember payment method for future usage',
		 'stripe_text_info_4' => 'Confirm your :amount payment with', // Not remove :amount
		 'stripe_text_info_5' => 'Please provide your name and e-mail address.',
		 'reject' => 'Reject',
		 'resending_code' => 'Resending code...',
		 'referral_transaction_limit' => 'Limit of transactions by referrals',
 		'referrals_welcome_desc' => 'Welcome to your referral panel. Share your link and earn :percentage% of your referrals, be it a Subscription, Tip or a PPV!',// Not remove :percentage
 		'total_transactions_per_referral' => 'You will earn :percentage% for the first transaction of your referral|You will earn :percentage% for the first :total transactions of your referral', // Not remove :percentage and :total
 		'total_transactions_referral_unlimited' => 'You will earn :percentage% for each transaction of your referral',
 		'error_fee_commission_zero' => 'Your fee commission cannot be 0% if the Referral System is enabled',
		'payment_received_subscription_renewal' => 'payment received for subscription renewal',
		'page_lang' => 'Select the language that you will write this page',
		'default_language' => 'Default language',
		'default_language_info' => 'This language will be taken by default when the user language does not exist.',
		'slug_lang_info' => 'If this page is a translation of an existing page, put the Slug/Url of that page.',
		'video_encoding' => 'Video encoding',
		'video_encoding_alert' => 'You must have FFMPEG installed',
		'alert_disable_free_subscriptions' => 'If you have free subscribers, the subscriptions will be canceled.',
		'alert_disable_paid_subscriptions' => 'If you have paid subscribers, they will be notified that you have switched to free subscription. They will be able to cancel their subscription.',
		'has_changed_subscription_free_subject' => 'has changed their subscription to free.',
		'has_changed_subscription_free' => 'has changed their subscription to free, to cancel your current subscription click on the following button.',
		'has_changed_subscription_paid' => 'has changed your subscription to paid',
		'subscribe_now' => 'Subscribe now!',

);";
			$fileLangEN = 'resources/lang/en/general.php';
			@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

			// Español
			$replaceLangES    = "
	 // Version 2.8
	 'encode' => 'Codificar',
	 'your_amount_payment' => 'Su pago de :amount', // Not remove :amount
	 'payment_processing' => 'Procesando pago',
	 'payment_processing_info' => 'Este pago se está procesando actualmente. Actualice esta página de vez en cuando para ver su estado.',
	 'stripe_payment_info' => 'Se necesita un método de pago válido para procesar su pago. Confirme su pago completando los detalles de pago a continuación.',
	 'payment_method' => 'Método de pago',
	 'payment_method_info' => 'Seleccione el método de pago que le gustaría utilizar.',
	 'processing' => 'Procesando...',
	 'stripe_text_info_1' => 'Su pago será procesado por',
	 'stripe_text_info_2' => 'Detalles del pago',
	 'stripe_text_info_3' => 'Recuerde el método de pago para uso futuro',
	 'stripe_text_info_4' => 'Confirme su pago de :amount con', // Not remove :amount
	 'stripe_text_info_5' => 'Proporcione su nombre y dirección de correo electrónico.',
	 'reject' => 'Rechazar',
	 'resending_code' => 'Reenviando código...',
	 'referral_transaction_limit' => 'Límite de transacciones por referidos',
 	'referrals_welcome_desc' => 'Bienvenido a su panel de referencia. ¡Comparta su enlace y gane un :percentage% de su referido, ya sea una Suscripción, Propina o un PPV!', // Not remove :percentage
 	'total_transactions_per_referral' => 'Ganará el :percentage% por las primer transacción de su referido|Ganará el :percentage% por las primeras :total transacciones de su referido', // Not remove :percentage and :total
 	'total_transactions_referral_unlimited' => 'Ganará el :percentage% por cada transacción de su referido',
 	'error_fee_commission_zero' => 'La cuota de comisión no puede ser del 0% si el Sistema de Referencia está habilitado',
	'payment_received_subscription_renewal' => 'pago recibido por renovación de suscripción',
	'page_lang' => 'Seleccione el idioma en el que escribirá esta página',
	'default_language' => 'Lenguaje por defecto',
	'default_language_info' => 'Este lenguaje será tomado por defecto cuando el lenguaje del usuario no exista.',
	'slug_lang_info' => 'Si esta página es una traducción de una página existente, coloque el Slug/Url de esa página.',
	'encode_videos' => 'Codificación de videos',
	'video_encoding_alert' => 'Debe tener FFMPEG instalado',
	'alert_disable_free_subscriptions' => 'Si tienes suscriptores gratuitos, las suscripciones serán canceladas.',
	'alert_disable_paid_subscriptions' => 'Si tienes suscriptores de pago, se le notificará que has cambiado a suscripción gratuita. Podrán cancelar su suscripción.',
	'has_changed_subscription_free_subject' => 'ha cambiado su suscripción a gratuita.',
	'has_changed_subscription_free' => 'ha cambiado su suscripción a gratuita, para cancelar tu actual suscripción haz clic en el siguiente botón.',
	'has_changed_subscription_paid' => 'ha cambiado su suscripción a paga',
	'subscribe_now' => '¡Suscríbase ahora!',
);";
			$fileLangES = 'resources/lang/es/general.php';
			@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

			//============ Start Query SQL ====================================
			Schema::table('subscriptions', function ($table) {
				$table->renameColumn('stripe_plan', 'stripe_price');
			});

			Schema::table('subscription_items', function ($table) {
				$table->renameColumn('stripe_plan', 'stripe_price');
			});

			Schema::table('users', function ($table) {
				$table->renameColumn('card_brand', 'pm_type');
				$table->renameColumn('card_last_four', 'pm_last_four');
			});

			Schema::table('subscription_items', function ($table) {
				$table->string('stripe_product')->nullable()->after('stripe_id');
			});

			Schema::table('subscription_items', function ($table) {
				$table->integer('quantity')->nullable()->change();
			});

			if (!Schema::hasColumn('admin_settings', 'referral_transaction_limit', 'conversion_ffmpeg')) {
				Schema::table('admin_settings', function ($table) {
					$table->char('referral_transaction_limit', 10)->default('1');
					$table->enum('video_encoding', ['on', 'off'])->default('off');
				});
			}


			if (!Schema::hasTable('referral_transactions')) {
				Schema::create('referral_transactions', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('referrals_id')->index();
					$table->unsignedInteger('user_id')->index();
					$table->unsignedInteger('referred_by')->index();
					$table->float('earnings', 10, 2);
					$table->char('type', 25);
					$table->timestamps();
				});
			}

			if (Schema::hasTable('referral_transactions')) {

				$referrals = Referrals::where('type', '<>', '')->get();

				foreach ($referrals as $ref) {
					$data[] = [
						'referrals_id' => $ref->id,
						'user_id' => $ref->user_id,
						'referred_by' => $ref->referred_by,
						'earnings' => $ref->earnings,
						'type' => $ref->type,
						'created_at' =>  $ref->updated_at
					];
				}

				if (isset($data)) {
					ReferralTransactions::insert($data);
				}

				Schema::table('referrals', function ($table) {
					$table->dropColumn('earnings');
					$table->dropColumn('type');
				});
			}

			if (!Schema::hasColumn('pages', 'lang')) {
				Schema::table('pages', function ($table) {
					$table->char('lang', 10)->default(session('locale'));
				});
			}

			if (!Schema::hasColumn('failed_jobs', 'uuid')) {
				Schema::table('failed_jobs', function ($table) {
					$table->string('uuid')->after('id')->nullable()->unique();
				});
			}

			file_put_contents(
				'.env',
				"\nDEFAULT_LOCALE=" . session('locale') . "\n",
				FILE_APPEND
			);

			//=============== End Query SQL ====================================

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 2.8 ----->>

		if ($version == '2.9') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'Helper.php' => $APP, // v2.9
				'web.php' => $ROUTES, // v2.9

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, // v2.9
				'HomeController.php' => $CONTROLLERS, // v2.9
				'LiveStreamingsController.php' => $CONTROLLERS, // v2.9
				'MessagesController.php' => $CONTROLLERS, // v2.9
				'TipController.php' => $CONTROLLERS, // v2.9
				'PagesController.php' => $CONTROLLERS, // v2.9
				'UpdatesController.php' => $CONTROLLERS, // v2.9
				'UserController.php' => $CONTROLLERS, // v2.9

				'LiveBroadcastingListener.php' => $LISTENERS, // v2.9

				'LiveBroadcasting.php' => $EVENTS, // v2.9

				'EventServiceProvider.php' => $PROVIDERS, // v2.9

				'User.php' => $MODELS, // v2.9
				'LiveComments.php' => $MODELS, // v2.9
				'LiveStreamings.php' => $MODELS, // v2.9
				'LiveOnlineUsers.php' => $MODELS, // v2.9
				'LiveLikes.php' => $MODELS, // v2.9

				'PreventRequestsDuringMaintenance.php' => $MIDDLEWARE, // v2.9
				'UserOnline.php' => $MIDDLEWARE, // v2.9
				'UserCountry.php' => $MIDDLEWARE, // v2.9
				'OnlineUsersLive.php' => $MIDDLEWARE, // v2.9

				//============ PUBLIC =================//
				'bootstrap-icons.css' => $PUBLIC_CSS, // v2.9
				'bootstrap-icons.woff' => $PUBLIC_FONTS, // v2.9
				'bootstrap-icons.woff2' => $PUBLIC_FONTS, // v2.9

				'app-functions.js' => $PUBLIC_JS, // v2.9
				'live.js' => $PUBLIC_JS, // v2.9
				'messages.js' => $PUBLIC_JS, // v2.9
				'payment.js' => $PUBLIC_JS, // v2.9

				'live.png' => $PUBLIC_IMG, // v2.9

				//=========== VIEWS ===================//
				'app.blade.php' => $VIEWS_LAYOUTS, // v2.9

				'categories.blade.php' => $VIEWS_INDEX, // v2.9
				'creators.blade.php' => $VIEWS_INDEX, // v2.9
				'creators-live.blade.php' => $VIEWS_INDEX, // v2.9

				'cards-settings.blade.php' => $VIEWS_INCLUDES, // v2.9
				'css_general.blade.php' => $VIEWS_INCLUDES, // v2.9
				'comments-live.blade.php' => $VIEWS_INCLUDES, // v2.9
				'form-post.blade.php' => $VIEWS_INCLUDES, // v2.9
				'updates.blade.php' => $VIEWS_INCLUDES, // v2.9
				'footer.blade.php' => $VIEWS_INCLUDES, // v2.9
				'navbar.blade.php' => $VIEWS_INCLUDES, // v2.9
				'modal-login.blade.php' => $VIEWS_INCLUDES, // v2.9
				'modal-payperview.blade.php' => $VIEWS_INCLUDES, // v2.9
				'modal-pay-live.blade.php' => $VIEWS_INCLUDES, // v2.9
				'modal-live-stream.blade.php' => $VIEWS_INCLUDES, // v2.9
				'modal-tip.blade.php' => $VIEWS_INCLUDES, // v2.9
				'listing-creators-live.blade.php' => $VIEWS_INCLUDES, // v2.9

				'profile.blade.php' => $VIEWS_USERS, // v2.9
				'notifications.blade.php' => $VIEWS_USERS, // v2.9
				'privacy_security.blade.php' => $VIEWS_USERS, // v2.9
				'my_posts.blade.php' => $VIEWS_USERS, // v2.9
				'withdrawals.blade.php' => $VIEWS_USERS, // v2.9
				'edit_my_page.blade.php' => $VIEWS_USERS, // v2.9
				'live.blade.php' => $VIEWS_USERS, // v2.9

				'Kernel.php' => app_path('Http') . $DS, // v2.9
			];

			$filesAdmin = [
				'edit-member.blade.php' => $VIEWS_ADMIN, // v2.9
				'live_streaming.blade.php' => $VIEWS_ADMIN, // v2.9
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, // v2.9
				'layout.blade.php' => $VIEWS_ADMIN, // v2.9
				'profiles-social.blade.php' => $VIEWS_ADMIN, // v2.9
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Agora Folder
			$filePathFolderAgora = $path . 'agora';
			$pathFolderAgora = public_path('js' . $DS . 'agora') . $DS;

			$this->moveDirectory($filePathFolderAgora, $pathFolderAgora, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			//============== Start Query ====================================

			// Replace String
			$findStringLang = ');';

			// Ennglish
			$replaceLangEN    = "
		 // Version 2.9
		'live' => 'Live',
 		'live_streaming' => 'Live Streaming',
 		'live_streaming_min_price' => 'Minimum price Live Streaming',
 		'live_streaming_max_price' => 'Maximum price Live Streaming',
 		'stream_live' => 'Stream Live',
 		'create_live_stream' => 'Create Live Stream',
 		'create_live_stream_subtitle' => 'Start a live stream and interact with your subscribers.',
 		'info_price_live' => 'Price to be paid by free subscribers or non-subscribers.',
 		'chat' => 'Chat',
 		'welcome_live_room' => 'Welcome to my Live room!',
 		'info_offline_live' => 'I am currently not online, when I am online you will receive a notification.',
 		'info_offline_live_non_subscribe' => 'I am not currently online, but feel free to subscribe to receive notifications about my upcoming live streams.',
 		'end_live' => 'End Live Stream',
 		'has_joined' => 'has joined',
 		'you_have_joined' => 'you have joined',
 		'Join_live_stream' => 'Join Live Stream',
 		'already_payment_live_access' => 'You have already paid to access this live',
 		'confirm_end_live' => 'Are you sure you want to end the Live Stream?',
 		'yes_confirm_end_live' => 'Yes, finalize!',
 		'is_streaming_live' => 'is streaming live',
 		'go_live_stream' => 'Go to the live stream',
 		'tipped' => 'tipped',
 		'creators_live' => 'Creators Broadcasting live',
 		'join' => 'Join',
 		'no_live_streams' => 'There are no live streams at this time',
 		'exit_live_stream' => 'Exit Live Stream',
 		'withdrawal_pending' => 'You have a pending payment request.',
);";
			$fileLangEN = 'resources/lang/en/general.php';
			@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

			// Español
			$replaceLangES    = "
	 // Version 2.9
	'live' => 'Vivo',
 	'live_streaming' => 'Transmisión en vivo',
 	'live_streaming_min_price' => 'Precio mínimo Transmisión en vivo',
 	'live_streaming_max_price' => 'Precio máximo Transmisión en vivo',
 	'stream_live' => 'Transmitir en vivo',
 	'create_live_stream' => 'Crear transmisión en vivo',
 	'create_live_stream_subtitle' => 'Inicie una transmisión en vivo e interactúe con sus suscriptores.',
 	'info_price_live' => 'Precio que deben pagar suscriptores gratuitos o no suscriptores.',
 	'chat' => 'Chat',
 	'welcome_live_room' => '¡Bienvenido a mi sala en vivo!',
 	'info_offline_live' => 'Actualmente no estoy en línea, cuando esté en línea recibirás una notificación.',
 	'info_offline_live_non_subscribe' => 'Actualmente no estoy en línea, pero siéntete libre de suscribirte para recibir notificaciones sobre mis próximas transmisiones en vivo.',
 	'end_live' => 'Finalizar transmisión en vivo',
 	'has_joined' => 'se ha unido',
 	'you_have_joined' => 'te has unido',
 	'Join_live_stream' => 'Únete a la transmisión en vivo de',
 	'already_payment_live_access' => 'Ya pagó para acceder a este en vivo',
 	'confirm_end_live' => '¿Está seguro de que desea finalizar la transmisión en vivo?',
 	'yes_confirm_end_live' => '¡Sí, finalizar!',
 	'is_streaming_live' => 'está transmitiendo en vivo',
 	'go_live_stream' => 'Ir a la transmisión en vivo',
 	'tipped' => 'envío una propina de',
 	'creators_live' => 'Creadores Transmitiendo en vivo',
 	'join' => 'Únete',
 	'no_live_streams' => 'No hay transmisiones en vivo en este momento',
 	'exit_live_stream' => 'Salir de la transmisión en vivo',
 	'withdrawal_pending' => 'Tienes una solicitud de pago pendiente.',
);";
			$fileLangES = 'resources/lang/es/general.php';
			@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

			//============ Start Query SQL ====================================
			if (!Schema::hasColumn(
				'admin_settings',
				'live_streaming_status',
				'live_streaming_minimum_price',
				'live_streaming_max_price',
				'agora_app_id',
				'tiktok',
				'snapchat',
			)) {
				Schema::table('admin_settings', function ($table) {
					$table->enum('live_streaming_status', ['on', 'off'])->default('off');
					$table->unsignedInteger('live_streaming_minimum_price')->default(5);
					$table->unsignedInteger('live_streaming_max_price')->default(100);
					$table->string('agora_app_id', 200);
					$table->string('tiktok', 200);
					$table->string('snapchat', 200);
				});
			}

			if (!Schema::hasTable('live_streamings')) {
				Schema::create('live_streamings', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('user_id')->index();
					$table->string('name', 255);
					$table->text('channel');
					$table->unsignedInteger('price');
					$table->enum('status', ['0', '1'])->default(0);
					$table->timestamps();
				});
			}

			if (!Schema::hasTable('live_likes')) {
				Schema::create('live_likes', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('user_id')->index();
					$table->unsignedInteger('live_streamings_id')->index();
					$table->timestamps();
				});
			}

			if (!Schema::hasTable('live_online_users')) {
				Schema::create('live_online_users', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('user_id')->index();
					$table->unsignedInteger('live_streamings_id')->index();
					$table->timestamps();
				});
			}

			if (!Schema::hasTable('live_comments')) {
				Schema::create('live_comments', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('user_id')->index();
					$table->unsignedInteger('live_streamings_id')->index();
					$table->text('comment')->collation('utf8mb4_unicode_ci');
					$table->unsignedInteger('joined')->default(1);
					$table->enum('tip', ['0', '1'])->default(0);
					$table->unsignedInteger('tip_amount');
					$table->timestamps();
				});
			}

			Schema::table('transactions', function ($table) {
				$table->string('type', 100)->change();
			});

			//=============== End Query SQL ====================================

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 2.9 ----->>

		if ($version == '3.0') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'Helper.php' => $APP, // v3.0
				'web.php' => $ROUTES, //v3.0
				'filesystems.php' => $CONFIG, //v3.0

				'Functions.php' => $TRAITS, // v3.0

				//============ CONTROLLERS =================//
				'AddFundsController.php' => $CONTROLLERS, // v3.0
				'AdminController.php' => $CONTROLLERS, // v3.0
				'HomeController.php' => $CONTROLLERS, // v3.0
				'CCBillController.php' => $CONTROLLERS, // v3.0
				'LiveStreamingsController.php' => $CONTROLLERS, // v3.0
				'MessagesController.php' => $CONTROLLERS, // v3.0
				'TipController.php' => $CONTROLLERS, // v3.0
				'PagesController.php' => $CONTROLLERS, // v3.0
				'UpdatesController.php' => $CONTROLLERS, // v3.0
				'PaystackController.php' => $CONTROLLERS, // v3.0
				'PayPalController.php' => $CONTROLLERS, // v3.0
				'PayPerViewController.php' => $CONTROLLERS, // v3.0
				'UserController.php' => $CONTROLLERS, // v3.0
				'CountriesStatesController.php' => $CONTROLLERS, // v3.0
				'SubscriptionsController.php' => $CONTROLLERS, // v3.0
				'TaxRatesController.php' => $CONTROLLERS, // v3.0
				'UploadMediaController.php' => $CONTROLLERS, // v3.0
				'UploadMediaMessageController.php' => $CONTROLLERS, // v3.0
				'StripeWebHookController.php' => $CONTROLLERS, // v3.0
				'StripeController.php' => $CONTROLLERS, // v3.0

				'LiveBroadcastingListener.php' => $LISTENERS, // v3.0
				'NewPostListener.php' => $LISTENERS, // v3.0
				'SubscriptionDisabledListener.php' => $LISTENERS, // v3.0
				'MassMessagesListener.php' => $LISTENERS, // v3.0

				'LiveBroadcasting.php' => $EVENTS, // v3.0

				'DeleteMedia.php' => $JOBS, // v3.0
				'EncodeVideo.php' => $JOBS, // v3.0
				'EncodeVideoMessages.php' => $JOBS, // v3.0
				'RebillWallet.php' => $JOBS, // v3.0

				'User.php' => $MODELS, // v3.0
				'LiveComments.php' => $MODELS, // v3.0
				'LiveStreamings.php' => $MODELS, // v3.0
				'LiveOnlineUsers.php' => $MODELS, // v3.0
				'LiveLikes.php' => $MODELS, // v3.0
				'Notifications.php' => $MODELS, // v3.0
				'TaxRates.php' => $MODELS, // v3.0
				'Plans.php' => $MODELS, // v3.0
				'Subscriptions.php' => $MODELS, // v3.0
				'Countries.php' => $MODELS, // v3.0
				'States.php' => $MODELS, // v3.0

				'AdminDepositPending.php' => $NOTIFICATIONS, // v3.0
				'AdminVerificationPending.php' => $NOTIFICATIONS, // v3.0
				'AdminWithdrawalPending.php' => $NOTIFICATIONS, // v3.0
				'PayPerViewReceived.php' => $NOTIFICATIONS, // v3.0
				'TipReceived.php' => $NOTIFICATIONS, // v3.0

				'Authenticate.php' => $MIDDLEWARE, // v3.0
				'UserCountry.php' => $MIDDLEWARE, // v3.0

				//============ PUBLIC =================//
				'core.min.css' => $PUBLIC_CSS, // v3.0

				'fileuploader-msg.js' => public_path('js' . $DS . 'fileuploader') . $DS, // v3.0
				'fileuploader-post.js' => public_path('js' . $DS . 'fileuploader') . $DS, // v3.0

				'app-functions.js' => $PUBLIC_JS, // v3.0
				'payment.js' => $PUBLIC_JS, // v3.0
				'payments-ppv.js' => $PUBLIC_JS, // v3.0
				'live.js' => $PUBLIC_JS, // v3.0
				'messages.js' => $PUBLIC_JS, // v3.0
				'core.min.js' => $PUBLIC_JS, // v3.0
				'upload-avatar-cover.js' => $PUBLIC_JS, // v3.0

				'agora-broadcast-client-v4.js' => public_path('js' . $DS . 'agora') . $DS, // v3.0
				'AgoraRTCSDK-v4.js' => public_path('js' . $DS . 'agora') . $DS, // v3.0

				'ckeditor-init.js' => $PUBLIC_JS_ADMIN, // v3.0

				//=========== VIEWS ===================//
				'app.blade.php' => $VIEWS_LAYOUTS, // v3.0

				'home-session.blade.php' => $VIEWS_INDEX, // v3.0

				'css_general.blade.php' => $VIEWS_INCLUDES, // v3.0
				'comments-live.blade.php' => $VIEWS_INCLUDES, // v3.0
				'form-post.blade.php' => $VIEWS_INCLUDES, // v3.0
				'updates.blade.php' => $VIEWS_INCLUDES, // v3.0
				'footer-tiny.blade.php' => $VIEWS_INCLUDES, // v3.0
				'footer.blade.php' => $VIEWS_INCLUDES, // v3.0
				'navbar.blade.php' => $VIEWS_INCLUDES, // v3.0
				'modal-tip.blade.php' => $VIEWS_INCLUDES, // v3.0
				'modal-payperview.blade.php' => $VIEWS_INCLUDES, // v3.0
				'modal-pay-live.blade.php' => $VIEWS_INCLUDES, // v3.0
				'modal-live-stream.blade.php' => $VIEWS_INCLUDES, // v3.0
				'modal-taxes.blade.php' => $VIEWS_INCLUDES, // v3.0
				'modal-new-message.blade.php' => $VIEWS_INCLUDES, // v3.0
				'listing-creators-live.blade.php' => $VIEWS_INCLUDES, // v3.0
				'listing-creators.blade.php' => $VIEWS_INCLUDES, // v3.0
				'messages-chat.blade.php' => $VIEWS_INCLUDES, // v3.0

				'profile.blade.php' => $VIEWS_USERS, // v3.0
				'notifications.blade.php' => $VIEWS_USERS, // v3.0
				'privacy_security.blade.php' => $VIEWS_USERS,
				'edit_my_page.blade.php' => $VIEWS_USERS, // v3.0
				'live.blade.php' => $VIEWS_USERS, // v3.0
				'wallet.blade.php' => $VIEWS_USERS, // v3.0
				'withdrawals.blade.php' => $VIEWS_USERS, // v3.0
				'subscription.blade.php' => $VIEWS_USERS, // v3.0
				'my_subscribers.blade.php' => $VIEWS_USERS, // v3.0
				'my_subscriptions.blade.php' => $VIEWS_USERS, // v3.0
				'invoice-deposits.blade.php' => $VIEWS_USERS, // v3.0
				'invoice.blade.php' => $VIEWS_USERS, // v3.0
				'messages-show.blade.php' => $VIEWS_USERS, // v3.0

				'email.blade.php' => resource_path('views' . $DS . 'vendor' . $DS . 'notifications') . $DS, // v3.0

				'Kernel.php' => app_path('Console') . $DS, // v3.0
			];

			$filesAdmin = [
				'add-page.blade.php' => $VIEWS_ADMIN, // v3.0
				'add-country.blade.php' => $VIEWS_ADMIN, // v3.0
				'add-tax.blade.php' => $VIEWS_ADMIN, // v3.0
				'add-state.blade.php' => $VIEWS_ADMIN, // v3.0
				'tax-rates.blade.php' => $VIEWS_ADMIN, // v3.0
				'edit-tax.blade.php' => $VIEWS_ADMIN, // v3.0
				'edit-state.blade.php' => $VIEWS_ADMIN, // v3.0
				'edit-country.blade.php' => $VIEWS_ADMIN, // v3.0
				'create-blog.blade.php' => $VIEWS_ADMIN, // v3.0
				'countries.blade.php' => $VIEWS_ADMIN, // v3.0
				'edit-blog.blade.php' => $VIEWS_ADMIN, // v3.0
				'edit-page.blade.php' => $VIEWS_ADMIN, // v3.0
				'payments-settings.blade.php' => $VIEWS_ADMIN, // v3.0
				'live_streaming.blade.php' => $VIEWS_ADMIN, // v3.0
				'storage.blade.php' => $VIEWS_ADMIN, // v3.0
				'layout.blade.php' => $VIEWS_ADMIN, // v3.0
				'states.blade.php' => $VIEWS_ADMIN, // v3.0
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, // v3.0
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Folder Library
			$filePathFolderLibrary = $path . 'Library';
			$pathFolderLibrary = app_path('Library') . $DS;

			$this->moveDirectory($filePathFolderLibrary, $pathFolderLibrary, $copy);

			//============== Start Query ====================================

			// Replace String
			$findStringLang = ');';

			// Ennglish
			$replaceLangEN    = "
		 // Version 3.0
		 'streamed_live' => 'streamed live',
 		'type_withdrawals' => 'Type of withdrawals',
 		'custom_amount' => 'Custom amount',
 		'total_balance' => 'Total balance',
 		'subscription_price_weekly' => 'Subscription Price (Weekly)',
 		'subscription_price_quarterly' => 'Subscription Price (3 months)',
 		'subscription_price_biannually' => 'Subscription Price (6 months)',
 		'subscription_price_yearly' => 'Subscription Price (12 months)',
 		'subscription_bundles' => 'Subscription Bundles',
 		'interval' => 'Interval',
 		'subscribe_month' => 'Subscribe :price per month', // Not replace :price
 		'subscribe_weekly' => 'Subscribe :price per week', // Not replace :price
 		'subscribe_quarterly' => 'Subscribe :price for 3 months', // Not replace :price
 		'subscribe_biannually' => 'Subscribe :price for 6 months', // Not replace :price
 		'subscribe_yearly' => 'Subscribe :price for 12 months', // Not replace :price
 		'monthly' => 'Monthly',
 		'weekly' => 'Weekly',
 		'quarterly' => '3 months',
 		'biannually' => '6 months',
 		'yearly' => 'Yearly',
 		'discount' => 'off',
 		'available_everyone_paid' => 'Available to everyone (Paid)',
 		'desc_available_everyone_paid' => 'All users must pay to access',
 		'available_free_paid_subscribers' => 'Free for paying subscribers',
 		'available_everyone_free' => 'Free for everyone',
 		'desc_everyone_free' => 'All users can access for free',
 		'limit_live_streaming_paid' => 'Limit of paid live streaming',
 		'live_streaming_free' => 'Live streaming free',
 		'limit_live_streaming_free' => 'Limit live streaming free',
 		'limit__minutes_per_transmission_paid' => 'Limit of :min minutes per Paid Streaming',// Not replace :min
 		'limit__minutes_per_transmission_free' => 'Limit of :min minutes per Free Streaming',// Not replace :min
 		'minutes' => 'min',
 		'tax_rates' => 'Tax Rates',
 		'percentage' => 'Percentage',
 		'countries_states' => 'Countries / States',
 		'countries' => 'Countries',
 		'state' => 'State',
 		'states' => 'States',
 		'all_states' => 'All states',
 		'create_state' => 'Create a state',
 		'success_add_tax' => 'Tax successfully added!',
 		'iso_code' => 'ISO Code',
 		'iso_code_country' => 'Two-letter country code',
 		'iso_code_states' => 'Without country prefix. For example, \"NY\"',
 		'alert_store_state' => 'States will only be used for the Tax Rates section.',
 		'applied_price' => 'applied to the price',
 		'alert_store_tax' => 'Important: If you will use Stripe you must enable and add the keys, so that the taxes are created in Stripe',
 		'confirm_exit_live' => 'Are you sure you want to exit the Live Stream?',
 		'yes_confirm_exit_live' => 'Yes, get out!',
);";
			$fileLangEN = 'resources/lang/en/general.php';
			@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

			// Español
			$replaceLangES    = "
	 // Version 3.0
	 'streamed_live' => 'transmitió en vivo',
 	'type_withdrawals' => 'Tipo de retiros',
 	'custom_amount' => 'Cantidad personalizada',
 	'total_balance' => 'Balance total',
 	'subscription_price_weekly' => 'Precio de suscripción (Semanal)',
 	'subscription_price_quarterly' => 'Subscription Price (3 meses)',
 	'subscription_price_biannually' => 'Subscription Price (6 meses)',
 	'subscription_price_yearly' => 'Subscription Price (12 Meses)',
 	'subscription_bundles' => 'Paquetes de Suscripción',
 	'interval' => 'Intervalo',
 	'subscribe_month' => 'Suscríbete :price por mes', // Not replace :price
 	'subscribe_weekly' => 'Suscríbete :price por semana', // No reemplazar :price
 	'subscribe_quarterly' => 'Suscríbete :price por 3 meses', // No reemplazar :price
 	'subscribe_biannually' => 'Suscríbete :price por 6 meses', // No reemplazar :price
 	'subscribe_yearly' => 'Suscríbete :price por 12 meses', // No reemplazar :price
 	'monthly' => 'Mensual',
 	'weekly' => 'Semanal',
 	'quarterly' => '3 meses',
 	'biannually' => '6 meses',
 	'yearly' => 'Anual',
 	'discount' => 'descuento',
 	'available_everyone_paid' => 'Disponible para todos (Pago)',
 	'desc_available_everyone_paid' => 'Todos los usuarios deben pagar para acceder',
 	'available_free_paid_subscribers' => 'Gratis para suscriptores de pago',
 	'available_everyone_free' => 'Gratis para todos',
 	'desc_everyone_free' => 'Todos los usuarios pueden acceder de forma gratuita',
 	'limit_live_streaming_paid' => 'Límite de transmisión en vivo paga',
 	'live_streaming_free' => 'Transmisión en vivo gratis',
 	'limit_live_streaming_free' => 'Límite de transmisión en vivo gratis',
 	'limit__minutes_per_transmission_paid' => 'Límite de :min minutos por Transmisión paga',// Not replace :min
 	'limit__minutes_per_transmission_free' => 'Límite de :min minutos por Transmisión gratuita',// Not replace :min
 	'minutes' => 'min',
 	'tax_rates' => 'Tasas de impuestos',
 	'percentage' => 'Porcentaje',
 	'countries_states' => 'Países / Estados',
 	'countries' => 'Países',
 	'state' => 'Estado',
 	'states' => 'Estados',
 	'all_states' => 'Todos los estados',
 	'create_state' => 'Crear un estado',
 	'success_add_tax' => '¡Impuesto agregado con éxito!',
 	'iso_code' => 'Código ISO',
 	'iso_code_country' => 'Código de país de dos letras',
 	'iso_code_states' => 'Sin prefijo de país. Por ejemplo, \"NY\"',
 	'alert_store_state' => 'Los estados solo se utilizarán para la sección Tasas de impuestos.',
 	'applied_price' => 'aplicado al precio',
 	'alert_store_tax' => 'Importante: Si vas a usar Stripe debes habilitar y agregar las claves, para que los impuestos se creen en Stripe.',
 	'confirm_exit_live' => '¿Estás seguro de que quieres salir de la transmisión en vivo?',
 	'yes_confirm_exit_live' => '¡Sí, salir!',
);";
			$fileLangES = 'resources/lang/es/general.php';
			@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

			//============ Start Query SQL ====================================
			if (!Schema::hasColumn(
				'admin_settings',
				'type_withdrawals',
				'limit_live_streaming_paid',
				'live_streaming_free',
				'limit_live_streaming_free',
			)) {
				Schema::table('admin_settings', function ($table) {
					$table->unsignedInteger('limit_live_streaming_paid');
					$table->unsignedInteger('limit_live_streaming_free');
					$table->enum('live_streaming_free', ['0', '1'])->default(0);
					$table->char('type_withdrawals', 50)->default('custom');
				});
			}

			Notifications::whereType(14)->delete();

			if (!Schema::hasTable('plans')) {
				Schema::create('plans', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('user_id')->index();
					$table->string('name', 100)->index();
					$table->decimal('price', 10, 2);
					$table->string('interval', 100);
					$table->string('paystack', 150)->index();
					$table->enum('status', ['0', '1'])->default(1);
					$table->timestamps();
				});
			}

			// Insert User Plans
			if (Schema::hasTable('plans')) {
				$userPlans = User::whereVerifiedId('yes')
					->where('plan', '<>', '')
					->get();

				if ($userPlans) {
					foreach ($userPlans as $key) {

						$data[] = [
							'user_id' => $key->id,
							'name' => $key->plan,
							'price' => $key->price,
							'interval' => 'monthly',
							'paystack' => $key->paystack_plan,
							'status' => $key->price == 0.00 ? '0' : '1',
							'created_at' => now()
						];
					}

					if (isset($data)) {

						foreach (array_chunk($data, 500) as $key => $smlArray) {
							foreach ($smlArray as $index => $value) {
								$tmp[$index] = $value;
							}
							Plans::insert($tmp);
						}
					}
				} // all users
			} // Has Table Plans

			if (!Schema::hasColumn('subscriptions', 'interval', 'taxes')) {
				Schema::table('subscriptions', function ($table) {
					$table->string('interval', 100)->default('monthly');
					$table->text('taxes');
				});
			}

			if (!Schema::hasColumn('live_streamings', 'availability')) {
				Schema::table('live_streamings', function ($table) {
					$table->char('availability', 50)->default('all_pay');
				});
			}

			if (!Schema::hasTable('tax_rates')) {
				Schema::create('tax_rates', function ($table) {
					$table->increments('id');
					$table->string('name', 250)->index('name');
					$table->boolean('type')->index('type')->default(1);
					$table->decimal('percentage', 5, 2);
					$table->string('country', 100)->nullable();
					$table->string('state', 100)->nullable();
					$table->char('iso_state', 10)->nullable();
					$table->string('stripe_id', 100)->nullable();
					$table->enum('status', ['0', '1'])->default(1);
					$table->timestamps();
				});
			}

			if (!Schema::hasTable('states')) {
				Schema::create('states', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('countries_id')->index();
					$table->string('name', 250)->index('name');
					$table->char('code', 10)->index('code');
					$table->timestamps();
				});
			}

			if (!Schema::hasColumn('deposits', 'taxes')) {
				Schema::table('deposits', function ($table) {
					$table->text('taxes');
				});
			}

			if (!Schema::hasColumn('transactions', 'taxes')) {
				Schema::table('transactions', function ($table) {
					$table->text('taxes');
				});
			}

			//=============== End Query SQL ====================================

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 3.0 ----->>

		if ($version == '3.1') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [

				//============ CONTROLLERS =================//
				'StripeWebHookController.php' => $CONTROLLERS, // v3.0
				'StripeController.php' => $CONTROLLERS, // v3.0

				'User.php' => $MODELS, // v3.1
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}


			//============ Start Query SQL ====================================
			Schema::table('deposits', function ($table) {
				$table->text('taxes')->nullable()->change();
			});

			Schema::table('subscriptions', function ($table) {
				$table->text('taxes')->nullable()->change();
			});

			Schema::table('transactions', function ($table) {
				$table->text('taxes')->nullable()->change();
			});

			//=============== End Query SQL ====================================

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 3.1 ----->>

		if ($version == '3.2') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				//============ CONTROLLERS =================//
				'HomeController.php' => $CONTROLLERS, //Affected
				'MessagesController.php' => $CONTROLLERS, //Affected
				'SubscriptionsController.php' => $CONTROLLERS, //Affected
				'UserController.php' => $CONTROLLERS, //Affected

				'User.php' => $MODELS, //Affected

				'updates.blade.php' => $VIEWS_INCLUDES, //Affected

				'profile.blade.php' => $VIEWS_USERS, //Affected

			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {


				//============ Start Query SQL ====================================
				Plans::whereName('')->delete();

				Subscriptions::whereStripePrice('')->delete();

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} // end $copy == false


			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 3.2 ----->>

		if ($version == '3.3') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //v3.3
				'PayPalController.php' => $CONTROLLERS, //v3.3
				'StripeController.php' => $CONTROLLERS, //v3.3
				'SubscriptionsController.php' => $CONTROLLERS, //v3.3
				'PaystackController.php' => $CONTROLLERS, //v3.3

				'payment.js' => $PUBLIC_JS, //v3.3
				'payments-ppv.js' => $PUBLIC_JS, //v3.3

				'messages-chat.blade.php' => $VIEWS_INCLUDES, //v3.3

				'wallet.blade.php' => $VIEWS_USERS, //v3.3
			];

			$filesAdmin = [
				'storage.blade.php' => $VIEWS_ADMIN, //v3.3
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Query SQL ====================================
				Helper::envUpdate('DOS_CDN', null);


				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 3.3 ----->>

		if ($version == '3.4') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'Helper.php' => $APP, // v3.4
				'app.php' => $CONFIG, //v3.4
				'filesystems.php' => $CONFIG, //v3.4
				'flutterwave.php' => $CONFIG, //v3.4
				'path.php' => $CONFIG, //v3.4
				'web.php' => $ROUTES, //v3.4

				//============ CONTROLLERS =================//
				'AddFundsController.php' => $CONTROLLERS, // v3.4
				'AdminController.php' => $CONTROLLERS, //v3.4
				'PayPalController.php' => $CONTROLLERS, //v3.4
				'PaystackController.php' => $CONTROLLERS, // v3.4
				'SubscriptionsController.php' => $CONTROLLERS, //v3.4
				'StripeWebHookController.php' => $CONTROLLERS, // v3.4
				'CCBillController.php' => $CONTROLLERS, // v3.4
				'PayPerViewController.php' => $CONTROLLERS, // v3.4
				'PagesController.php' => $CONTROLLERS, // v3.4
				'UserController.php' => $CONTROLLERS, // v3.4
				'UpdatesController.php' => $CONTROLLERS, // v3.4
				'ProductsController.php' => $CONTROLLERS, // v3.4
				'UploadMediaFileShopController.php' => $CONTROLLERS, // v3.4
				'UploadMediaPreviewShopController.php' => $CONTROLLERS, // v3.4

				'Functions.php' => $TRAITS, //v3.4
				'UserDelete.php' => $TRAITS, //v3.4

				'EncodeVideo.php' => $JOBS, // v3.4
				'EncodeVideoMessages.php' => $JOBS, // v3.4

				'Referrals.php' => $MODELS, // v3.4
				'ReferralTransactions.php' => $MODELS, // v3.4
				'Purchases.php' => $MODELS, // v3.4
				'Products.php' => $MODELS, // v3.4
				'MediaProducts.php' => $MODELS, // v3.4
				'User.php' => $MODELS, // v3.4

				'mercadopago.png' => public_path('img' . $DS . 'payments') . $DS, // v2.4
				'flutterwave.png' => public_path('img' . $DS . 'payments') . $DS, // v2.4
				'mercadopago-white.png' => public_path('img' . $DS . 'payments') . $DS, // v2.4
				'flutterwave-white.png' => public_path('img' . $DS . 'payments') . $DS, // v2.4

				'add-funds.js' => $PUBLIC_JS, //v3.4
				'app-functions.js' => $PUBLIC_JS, // v3.4
				'live.js' => $PUBLIC_JS, // v3.4
				'agora-broadcast-client-v4.js' => public_path('js' . $DS . 'agora') . $DS, // v3.4
				'shop.js' => $PUBLIC_JS, // v3.4
				'fileuploader-shop-file.js' => public_path('js' . $DS . 'fileuploader') . $DS, // v3.4
				'fileuploader-shop-preview.js' => public_path('js' . $DS . 'fileuploader') . $DS, // v3.4

				'app.blade.php' => $VIEWS_LAYOUTS, //v3.4

				'explore.blade.php' => $VIEWS_INDEX, // v3.4

				'cards-settings.blade.php' => $VIEWS_INCLUDES, //v3.4
				'css_general.blade.php' => $VIEWS_INCLUDES, //v3.4
				'footer.blade.php' => $VIEWS_INCLUDES, //v3.4
				'footer-tiny.blade.php' => $VIEWS_INCLUDES, //v3.4
				'navbar.blade.php' => $VIEWS_INCLUDES, //v3.4
				'form-post.blade.php' => $VIEWS_INCLUDES, //v3.4
				'menu-mobile.blade.php' => $VIEWS_INCLUDES, //v3.4
				'listing-creators.blade.php' => $VIEWS_INCLUDES, //v3.4
				'listing-explore-creators.blade.php' => $VIEWS_INCLUDES, //v3.4
				'updates.blade.php' => $VIEWS_INCLUDES, //v3.4
				'modal-custom-content.blade.php' => $VIEWS_INCLUDES, //v3.4

				'edit_my_page.blade.php' => $VIEWS_USERS, //v3.4
				'live.blade.php' => $VIEWS_USERS, // v3.4
				'my_payments.blade.php' => $VIEWS_USERS, // v3.4
				'my_subscriptions.blade.php' => $VIEWS_USERS, // v3.4
				'my_products.blade.php' => $VIEWS_USERS, // v3.4
				'my-sales.blade.php' => $VIEWS_USERS, // v3.4
				'my_subscribers.blade.php' => $VIEWS_USERS, // v3.4
				'invoice.blade.php' => $VIEWS_USERS, // v3.4
				'invoice-deposits.blade.php' => $VIEWS_USERS, // v3.4
				'purchased_items.blade.php' => $VIEWS_USERS, // v3.4
				'notifications.blade.php' => $VIEWS_USERS, // v3.4
				'profile.blade.php' => $VIEWS_USERS, // v3.4
				'wallet.blade.php' => $VIEWS_USERS, // v3.4
			];

			$filesAdmin = [
				'bank-settings.blade.php' => $VIEWS_ADMIN, // v3.4
				'storage.blade.php' => $VIEWS_ADMIN, // v3.4
				'pages.blade.php' => $VIEWS_ADMIN, // v3.4
				'add-page.blade.php' => $VIEWS_ADMIN, // v3.4
				'edit-page.blade.php' => $VIEWS_ADMIN, // v3.4
				'referrals.blade.php' => $VIEWS_ADMIN, // v3.4
				'layout.blade.php' => $VIEWS_ADMIN, // v3.4
				'shop.blade.php' => $VIEWS_ADMIN, // v3.4
				'products.blade.php' => $VIEWS_ADMIN, // v3.4
				'flutterwave-settings.blade.php' => $VIEWS_ADMIN, // v3.4
				'mercadopago-settings.blade.php' => $VIEWS_ADMIN, // v3.4
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, // v3.4
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy Folder
			$folderShop = $path . 'shop';
			$pathFolderShop = $VIEWS_SHOP;

			$this->moveDirectory($folderShop, $pathFolderShop, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 3.4
		'access' => 'Access',
		'who_can_access_this_page' => 'Who can access this page?',
		'referred_by' => 'Referred by',
		'shop' => 'Shop',
		'explore_products_creators' => 'Explore products from our creators',
		'type_sale' => 'Type of sale',
		'digital_products' => 'Digital products',
		'custom_content' => 'Custom content',
		'minimum_price_of_sale' => 'Minimum price of sale',
		'maximum_price_of_sale' => 'Maximum price of sale',
		'add_product' => 'Add product',
		'add_custom_content' => 'Add custom content',
		'image_preview_required' => 'Image preview required',
		'file_required' => 'File required',
		'buy_now' => 'Buy Now',
		'purchase' => 'Purchase',
		'purchases' => 'Purchases',
		'purchase_item' => 'Purchase of item',
		'file_to_downloaded' => '* File to be downloaded',
		'download' => 'Download',
		'already_purchased_item' => 'You have already purchased this item',
		'has_bought_your_item' => 'has bought your item',
		'purchased_items' => 'Purchased Items',
		'sales' => 'Sales',
		'sales_your_products' => 'Sales of your products',
		'item' => 'Item',
		'purchased_items_subtitle' => 'Items you have bought in the store',
		'products' => 'Products',
		'other_items_of' => 'Other Items of',
		'oldest' => 'Oldest',
		'lowest_price' => 'Lowest price',
		'highest_price' => 'Highest price',
		'error_type_sale' => 'Please select a type of sale',
		'choose_type_sale' => 'Choose the type of sale',
		'digital_products_desc' => 'Downloadable products, image packages, videos, etc.',
		'custom_content_desc' => 'Custom videos, video calls, greeting messages, etc.',
		'delivery_time' => 'Delivery time',
		'delivery_status' => 'Delivery status',
		'delivered' => 'Delivered',
		'description_custom_content' => 'Details for custom content: Name, for whom, the occasion, details of the occasion.',
		'details_custom_content' => 'Details for custom content',
		'purchase_processed_shortly' => 'Your purchase will be processed shortly, you will be notified by email.',
		'digital_download' => 'Digital Download',
		'custom_content' => 'Custom content',
		'see_details' => 'See details',
		'mark_as_delivered' => 'Mark as delivered',
		'buyer' => 'Buyer',
		'all_products_published' => 'All the products you have published',
		'sell_custom_content' => 'Sell custom content',
);";
				$fileLangEN = 'resources/lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 3.4
	'access' => 'Acceso',
	'who_can_access_this_page' => '¿Quién puede acceder a esta página?',
	'referred_by' => 'Referido por',
	'shop' => 'Tienda',
	'explore_products_creators' => 'Explora productos de nuestros creadores',
	'type_sale' => 'Tipo de venta',
	'digital_products' => 'Productos digitales',
	'minimum_price_of_sale' => 'Precio minímo de venta',
	'maximum_price_of_sale' => 'Precio máximo de venta',
	'add_product' => 'Agregar producto',
	'add_new_product_to_shop' => 'Agrega un nuevo producto a la tienda',
	'add_custom_content' => 'Agrega contenido personalizado',
	'image_preview_required' => 'Se requiere una vista previa de la imagen',
	'file_required' => 'Archivo requerido',
	'buy_now' => 'Comprar ahora',
	'purchase' => 'Compra',
	'purchases' => 'Compras',
	'purchase_item' => 'Compra de item',
	'file_to_downloaded' => '* Archivo a descargar',
	'download' => 'Descargar',
	'already_purchased_item' => 'Ya has comprado este item',
	'has_bought_your_item' => 'ha comprado tu item',
	'purchased_items' => 'Items comprados',
	'sales' => 'Ventas',
	'sales_your_products' => 'Ventas de tus productos',
	'item' => 'Item',
	'purchased_items_subtitle' => 'Items que has comprado en la tienda',
	'products' => 'Productos',
	'other_items_of' => 'Otros artículos de',
	'oldest' => 'Más antiguo',
	'lowest_price' => 'Precio más bajo',
	'highest_price' => 'Precio más alto',
	'error_type_sale' => 'Seleccione un tipo de venta',
	'choose_type_sale' => 'Elige el tipo de venta',
	'digital_products_desc' => 'Productos descargables, paquetes de imágenes, videos, etc.',
	'custom_content_desc' => 'Videos personalizados, videollamadas, mensajes de saludo, etc.',
	'delivery_time' => 'Hora de entrega',
	'delivery_status' => 'Estado de entrega',
	'delivered' => 'Entregado',
	'description_custom_content' => 'Detalles del contenido personalizado: Nombre, para quién, la ocasión, detalles de la ocasión.',
	'details_custom_content' => 'Detalles del contenido personalizado',
	'purchase_processed_shortly' => 'Su compra se procesará en breve, se le notificará por correo electrónico',
	'digital_download' => 'Descarga digital',
	'custom_content' => 'Contenido personalizado',
	'see_details' => 'Ver detalles',
	'mark_as_delivered' => 'Marcar como entregado',
	'buyer' => 'Comprador',
	'all_products_published' => 'Todos los productos que has publicado',
	'sell_custom_content' => 'Vender contenido personalizado',
);";
				$fileLangES = 'resources/lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn(
					'admin_settings',
					'shop',
					'min_price_product',
					'max_price_product',
					'digital_product_sale',
					'custom_content'
				)) {
					Schema::table('admin_settings', function ($table) {
						$table->boolean('shop')->default(false);
						$table->unsignedInteger('min_price_product')->default(5);
						$table->unsignedInteger('max_price_product')->default(100);
						$table->boolean('digital_product_sale')->default(false);
						$table->boolean('custom_content')->default(false);
					});
				}

				if (!Schema::hasColumn('pages', 'access')) {
					Schema::table('pages', function ($table) {
						$table->string('access', 50)->default('all');
					});
				}

				if (!Schema::hasTable('products')) {
					Schema::create('products', function ($table) {
						$table->bigIncrements('id');
						$table->unsignedInteger('user_id')->index();
						$table->string('name', 255);
						$table->char('type', 20)->default('digital');
						$table->decimal('price', 10, 2);
						$table->unsignedInteger('delivery_time');
						$table->text('tags');
						$table->text('description');
						$table->string('file', 255);
						$table->string('mime', 50)->nullable();
						$table->string('extension', 50)->nullable();
						$table->string('size', 50)->nullable();
						$table->enum('status', ['0', '1'])->default(1);
						$table->timestamps();
					});
				}

				if (!Schema::hasTable('media_products')) {
					Schema::create('media_products', function ($table) {
						$table->bigIncrements('id');
						$table->unsignedInteger('products_id')->index();
						$table->string('name', 255);
						$table->timestamps();
					});
				}

				if (!Schema::hasTable('purchases')) {
					Schema::create('purchases', function ($table) {
						$table->bigIncrements('id');
						$table->unsignedInteger('transactions_id')->index();
						$table->unsignedInteger('user_id')->index();
						$table->unsignedInteger('products_id')->index();
						$table->string('delivery_status', 50)->default('delivered');
						$table->longText('description_custom_content')->nullable();
						$table->timestamps();
					});
				}

				if (!Schema::hasColumn('reserved', 'shop')) {
					\DB::table('reserved')->insert(
						['name' => 'shop']
					);
				}

				if (Schema::hasTable('payment_gateways')) {
					\DB::table('payment_gateways')->insert(
						[
							[
								'name' => 'Mercadopago',
								'type' => 'normal',
								'enabled' => '0',
								'fee' => 0.0,
								'fee_cents' => 0.00,
								'email' => '',
								'key' => '',
								'key_secret' => '',
								'recurrent' => 'no',
								'logo' => 'mercadopago.png',
								'subscription' => 'no',
								'bank_info' => '',
								'token' => str_random(150),
							]
						]
					);
				} // End add Mercadopago

				PaymentGateways::whereName('Bank Transfer')->update([
					'name' => 'Bank'
				]);

				file_put_contents(
					'.env',
					"\nFLW_PUBLIC_KEY=\nFLW_SECRET_KEY=\n",
					FILE_APPEND
				);

				if (Schema::hasTable('payment_gateways')) {
					\DB::table('payment_gateways')->insert(
						[
							[
								'name' => 'Flutterwave',
								'type' => 'normal',
								'enabled' => '0',
								'fee' => 0.0,
								'fee_cents' => 0.00,
								'email' => '',
								'key' => '',
								'key_secret' => '',
								'recurrent' => 'no',
								'logo' => 'flutterwave.png',
								'subscription' => 'no',
								'bank_info' => '',
								'token' => str_random(150),
							]
						]
					);
				} // End add Flutterwave

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 3.4 ----->>

		if ($version == '3.5') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES, //v3.5

				'Helper.php' => $APP, //v3.5

				//============ CONTROLLERS =================//
				'AddFundsController.php' => $CONTROLLERS, //v3.5
				'AdminController.php' => $CONTROLLERS, //v3.5
				'ProductsController.php' => $CONTROLLERS, //v3.5
				'UpdatesController.php' => $CONTROLLERS, //v3.5

				'add-funds.js' => $PUBLIC_JS, //v3.5

				'explore.blade.php' => $VIEWS_INDEX, // v3.5

				'css_general.blade.php' => $VIEWS_INCLUDES, //v3.5
				'navbar.blade.php' => $VIEWS_INCLUDES, //v3.5
				'menu-mobile.blade.php' => $VIEWS_INCLUDES, //v3.5

				'add-custom-content.blade.php' => $VIEWS_SHOP, //v3.5
				'show.blade.php' => $VIEWS_SHOP, //v3.5

				'wallet.blade.php' => $VIEWS_USERS, //v3.5

			];

			$filesAdmin = [
				'products.blade.php' => $VIEWS_ADMIN, //v3.5
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 3.5
		'error_length_tags' => 'Tags must have at least 2 characters',
);";
				$fileLangEN = 'resources/lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 3.5
	'error_length_tags' => 'Las etiquetas deben tener al menos 2 caracteres',
);";
				$fileLangES = 'resources/lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

				//============ Start Query SQL ====================================

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 3.5 ----->>

		if ($version == '3.6') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES, //v3.6

				'Functions.php' => $TRAITS, //v3.6

				//============ CONTROLLERS =================//
				'AddFundsController.php' => $CONTROLLERS, //v3.6
				'AdminController.php' => $CONTROLLERS, //v3.6
				'UploadMediaFileShopController.php' => $CONTROLLERS, //v3.6

				'mollie.png' => public_path('img' . $DS . 'payments') . $DS, //v3.6
				'razorpay.png' => public_path('img' . $DS . 'payments') . $DS, //v3.6
				'mollie-white.png' => public_path('img' . $DS . 'payments') . $DS, //v3.6
				'razorpay-white.png' => public_path('img' . $DS . 'payments') . $DS, //v3.6

				'fileuploader-shop-file.js' => public_path('js' . $DS . 'fileuploader') . $DS, //v3.6

				'cards-settings.blade.php' => $VIEWS_INCLUDES, //v3.6
				'css_general.blade.php' => $VIEWS_INCLUDES, //v3.6
				'navbar.blade.php' => $VIEWS_INCLUDES, //v3.6
				'menu-mobile.blade.php' => $VIEWS_INCLUDES, //v3.6
				'footer.blade.php' => $VIEWS_INCLUDES, //v3.6

				'products.blade.php' => $VIEWS_SHOP, //v3.6
				'add-item.blade.php' => $VIEWS_SHOP, //v3.6
				'modal-add-item.blade.php' => $VIEWS_SHOP, //v3.6

				'wallet.blade.php' => $VIEWS_USERS, //v3.6
				'profile.blade.php' => $VIEWS_USERS, //v3.6

			];

			$filesAdmin = [
				'mercadopago-settings.blade.php' => $VIEWS_ADMIN, // v3.6
				'mollie-settings.blade.php' => $VIEWS_ADMIN, // v3.6
				'razorpay-settings.blade.php' => $VIEWS_ADMIN, // v3.6
				'payments-settings.blade.php' => $VIEWS_ADMIN, // v3.6
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 3.6
		'account' => 'Account',
		'apply_taxes_wallet' => 'Apply taxes in Wallet',
);";
				$fileLangEN = 'resources/lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 3.6
	'account' => 'Cuenta',
	'apply_taxes_wallet' => 'Aplicar impuestos en Billetera',
);";
				$fileLangES = 'resources/lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn('admin_settings', 'tax_on_wallet')) {
					Schema::table('admin_settings', function ($table) {
						$table->boolean('tax_on_wallet')->default(true);
					});
				}

				if (Schema::hasTable('payment_gateways')) {
					\DB::table('payment_gateways')->insert(
						[
							[
								'name' => 'Mollie',
								'type' => 'normal',
								'enabled' => '0',
								'fee' => 0.0,
								'fee_cents' => 0.00,
								'email' => '',
								'key' => '',
								'key_secret' => '',
								'recurrent' => 'no',
								'logo' => 'mollie.png',
								'subscription' => 'no',
								'bank_info' => '',
								'token' => str_random(150),
							]
						]
					);
				} // End add Mollie

				if (Schema::hasTable('payment_gateways')) {
					\DB::table('payment_gateways')->insert(
						[
							[
								'name' => 'Razorpay',
								'type' => 'normal',
								'enabled' => '0',
								'fee' => 0.0,
								'fee_cents' => 0.00,
								'email' => '',
								'key' => '',
								'key_secret' => '',
								'recurrent' => 'no',
								'logo' => 'razorpay.png',
								'subscription' => 'no',
								'bank_info' => '',
								'token' => str_random(150),
							]
						]
					);
				} // End add Razorpay

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 3.6 ----->>

		if ($version == '3.7') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'Helper.php' => $APP, //v3.7

				'web.php' => $ROUTES, //v3.7

				'laravelpwa.php' => $CONFIG, //v3.7

				'Functions.php' => $TRAITS, //v3.7

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //v3.7
				'ProductsController.php' => $CONTROLLERS, //v3.7
				'SubscriptionsController.php' => $CONTROLLERS, //v3.7
				'StripeConnectController.php' => $CONTROLLERS, //v3.7
				'HomeController.php' => $CONTROLLERS, //v3.7
				'CommentsController.php' => $CONTROLLERS, //v3.7
				'UpdatesController.php' => $CONTROLLERS, //v3.7
				'UserController.php' => $CONTROLLERS, //v3.7

				'LiveBroadcastingListener.php' => $LISTENERS, //v3.7

				'ReferralTransactions.php' => $MODELS, //v3.7
				'User.php' => $MODELS, //v3.7

				'functions.js' => $PUBLIC_JS_ADMIN, //v3.7

				'app-functions.js' => $PUBLIC_JS, //v3.7
				'install-app.js' => $PUBLIC_JS, //v3.7
				'live.js' => $PUBLIC_JS, //v3.7

				'cards-settings.blade.php' => $VIEWS_INCLUDES, //v3.7
				'css_general.blade.php' => $VIEWS_INCLUDES, //v3.7
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //v3.7
				'navbar.blade.php' => $VIEWS_INCLUDES, //v3.7
				'menu-mobile.blade.php' => $VIEWS_INCLUDES, //v3.7
				'footer.blade.php' => $VIEWS_INCLUDES, //v3.7
				'footer-tiny.blade.php' => $VIEWS_INCLUDES, //v3.7
				'modal-custom-content.blade.php' => $VIEWS_INCLUDES, //v3.7
				'modal-login.blade.php' => $VIEWS_INCLUDES, //v3.7
				'updates.blade.php' => $VIEWS_INCLUDES, //v3.7
				'form-post.blade.php' => $VIEWS_INCLUDES, //v3.7
				'emojis.blade.php' => $VIEWS_INCLUDES, //v3.7

				'home.blade.php' => $VIEWS_INDEX, //v3.7
				'explore.blade.php' => $VIEWS_INDEX, //v3.7

				'add-custom-content.blade.php' => $VIEWS_SHOP, //v3.7
				'modal-edit.blade.php' => $VIEWS_SHOP, //v3.7
				'modal-buy.blade.php' => $VIEWS_SHOP, //v3.7

				'edit_my_page.blade.php' => $VIEWS_USERS, //v3.7
				'wallet.blade.php' => $VIEWS_USERS, //v3.7
				'profile.blade.php' => $VIEWS_USERS, //v3.7
				'my-sales.blade.php' => $VIEWS_USERS, //v3.7
				'notifications.blade.php' => $VIEWS_USERS, //v3.7
				'my_payments.blade.php' => $VIEWS_USERS, //v3.7
				'my_products.blade.php' => $VIEWS_USERS, //v3.7
				'payout_method.blade.php' => $VIEWS_USERS, //v3.7
				'messages-show.blade.php' => $VIEWS_USERS, //v3.7

				'meta.blade.php' => resource_path('views' . $DS . 'vendor' . $DS . 'laravelpwa') . $DS, //3.7

			];

			$filesAdmin = [
				'layout.blade.php' => $VIEWS_ADMIN, //v3.7
				'deposits.blade.php' => $VIEWS_ADMIN, //v3.7
				'sales.blade.php' => $VIEWS_ADMIN, //v3.7
				'payments-settings.blade.php' => $VIEWS_ADMIN, //v3.7
				'pwa.blade.php' => $VIEWS_ADMIN, //v3.7
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 3.7
		'unlockable' => 'Unlockable',
		'has_mentioned_you' => 'has mentioned you in',
		'someone_live_streaming' => 'Live streams from creators I\'ve subscribed to',
		'someone_mentioned_me' => 'Someone mentioned me',
		'separate_with_comma' => 'separate with comma',
		'alert_buy_custom_content' => 'Verify the data entered because you will not be able to change it later.',
		'confirm_reject_order' => 'By rejecting the money will be refunded.',
		'reject_order' => 'Reject order',
		'action_cannot_reversed' => 'This action cannot be reversed',
		'refund' => 'Refund',
		'refund_success' => 'Refund made successfully!',
		'connect_stripe_account' => 'Connect Stripe Account',
		'view_stripe_account' => 'View Stripe account',
		'connected' => 'Connected',
		'not_connected' => 'Not connected',
		'stripe_connect_desc' => 'You will receive payments made with Stripe directly into your account.',
		'stripe_connect_setup_success' => 'Stripe Connect account set up successfully!',
		'stripe_connect_countries' => 'Stripe Connect (Countries)',
		'direct_payment' => 'Direct payment',
		'info_stripe_connect_countries' => 'Countries which you want to send direct payments to your members, configure in',
		'install_web_app' => 'Install Web App',
);";
				$fileLangEN = 'resources/lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 3.7
	'unlockable' => 'Desbloqueable',
	'has_mentioned_you' => 'te ha mencionado en',
	'someone_live_streaming' => 'Transmisiones en vivo de los creadores que me he suscrito',
	'someone_mentioned_me' => 'Alguien me ha mencionado',
	'separate_with_comma' => 'separar con coma',
	'alert_buy_custom_content' => 'Verifique los datos ingresados porque no podrá cambiarlos más tarde.',
	'confirm_reject_order' => 'Al rechazar será reembolsado el dinero.',
	'reject_order' => 'Rechazar orden',
	'action_cannot_reversed' => 'Esta acción no se puede revertir',
	'refund' => 'Reembolso',
	'refund_success' => '¡Reembolso realizado con éxito!',
	'connect_stripe_account' => 'Cuenta Stripe Connect',
	'view_stripe_account' => 'Ver cuenta Stripe',
	'connected' => 'Conectado',
	'not_connected' => 'No conectado',
	'stripe_connect_desc' => 'Recibirá los pagos realizados con Stripe directamente en su cuenta.',
	'stripe_connect_setup_success' => '¡La cuenta de Stripe Connect se configuró correctamente!',
	'stripe_connect_countries' => 'Stripe Connect (Países)',
	'direct_payment' => 'Pago directo',
	'info_stripe_connect_countries' => 'Países al que desea enviar pagos directos a sus miembros, configurar en',
	'install_web_app' => 'Instalar App Web',
);";
				$fileLangES = 'resources/lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn('admin_settings', 'stripe_connect', 'stripe_connect_countries')) {
					Schema::table('admin_settings', function ($table) {
						$table->unsignedTinyInteger('stripe_connect')->default(0);
						$table->longText('stripe_connect_countries');
					});
				}

				if (!Schema::hasColumn(
					'users',
					'notify_live_streaming',
					'notify_mentions',
					'stripe_connect_id',
					'completed_stripe_onboarding',
					'device_token',
					'telegram',
					'vk',
					'twitch',
					'discord'
				)) {
					Schema::table('users', function ($table) {
						$table->enum('notify_live_streaming', ['yes', 'no'])->default('yes');
						$table->enum('notify_mentions', ['yes', 'no'])->default('yes');
						$table->string('stripe_connect_id')->nullable();
						$table->boolean('completed_stripe_onboarding')->default(false);
						$table->string('device_token', 255)->nullable();
						$table->string('telegram', 200);
						$table->string('vk', 200);
						$table->string('twitch', 200);
						$table->string('discord', 200);
					});
				}

				if (!Schema::hasColumn('referral_transactions', 'transactions_id')) {
					Schema::table('referral_transactions', function ($table) {
						$table->unsignedInteger('transactions_id')->after('id')->index()->nullable();
					});
				}

				if (!Schema::hasColumn('transactions', 'direct_payment')) {
					Schema::table('transactions', function ($table) {
						$table->boolean('direct_payment')->default(false);
					});
				}

				if (!Schema::hasTable('stripe_state_tokens')) {
					Schema::create('stripe_state_tokens', function ($table) {
						$table->id();
						$table->foreignId('user_id');
						$table->string('token')->nullable();
						$table->timestamps();
					});
				}

				file_put_contents(
					'.env',
					"\nPWA_SPLASH_828=public/images/icons/splash-828x1792.png\nPWA_SPLASH_1242_2=public/images/icons/splash-1242x2688.png",
					FILE_APPEND
				);
				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 3.7 ----->>

		if ($version == '3.8') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'Helper.php' => $APP, //v3.8

				'Functions.php' => $TRAITS, //v3.8

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //v3.8

				'ckeditor-init.js' => $PUBLIC_JS_ADMIN, //v3.8

				'css_general.blade.php' => $VIEWS_INCLUDES, //v3.8
				'form-post.blade.php' => $VIEWS_INCLUDES, //v3.8

				'add-product.blade.php' => $VIEWS_SHOP, //v3.8

				'my_payments.blade.php' => $VIEWS_USERS, //v3.8
				'messages-show.blade.php' => $VIEWS_USERS, //v3.8
			];

			$filesAdmin = [];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 3.8 ----->>

		if ($version == '3.9') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES, //v3.9

				'Helper.php' => $APP, //v3.9

				'Restrictions.php' => $MODELS, //v3.9
				'User.php' => $MODELS, //v3.9

				'NewSale.php' => $NOTIFICATIONS, //v3.9

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //v3.9
				'ProductsController.php' => $CONTROLLERS, //v3.9
				'UpdatesController.php' => $CONTROLLERS, //v3.9
				'UserController.php' => $CONTROLLERS, //v3.9

				'core.min.css' => $PUBLIC_CSS, //v3.9

				'app-functions.js' => $PUBLIC_JS, //v3.9

				'functions.js' => $PUBLIC_JS_ADMIN, //v3.9

				'css_general.blade.php' => $VIEWS_INCLUDES, //v3.9
				'cards-settings.blade.php' => $VIEWS_INCLUDES, //v3.9
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //v3.9
				'updates.blade.php' => $VIEWS_INCLUDES, //v3.9

				'edit_my_page.blade.php' => $VIEWS_USERS, //v3.9
				'profile.blade.php' => $VIEWS_USERS, //v3.9
				'notifications.blade.php' => $VIEWS_USERS, //v3.9
				'live.blade.php' => $VIEWS_USERS, //v3.9
				'restricted_users.blade.php' => $VIEWS_USERS, //v3.9
				'messages-show.blade.php' => $VIEWS_USERS, //v3.9

				'agora-broadcast-client-v4.js' => public_path('js' . $DS . 'agora') . $DS, //v3.9
			];

			$filesAdmin = [
				'layout.blade.php' => $VIEWS_ADMIN, //v3.9
				'theme.blade.php' => $VIEWS_ADMIN, //v3.9
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 3.9
		'my_sales' => 'My sales',
		'mute_audio' => 'Mute audio',
		'unmute_audio' => 'Unmute audio',
		'mute_video' => 'Mute video',
		'unmute_video' => 'Unmute video',
		'results' => 'Results:',
		'restrict' => 'Restrict',
		'restricted_users' => 'Restricted users',
		'confirm_restrict' => 'Restricting this user will prevent them from sending you private messages or commenting on your posts, but they will still be able to view your profile.',
		'remove_restriction' => 'Remove restriction',
		'info_restricted_users' => 'Users you have restricted',
);";
				$fileLangEN = 'resources/lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 3.9
	'my_sales' => 'Mis ventas',
	'mute_audio' => 'Silenciar el audio',
	'unmute_audio' => 'Activar audio',
	'mute_video' => 'Silenciar vídeo',
	'unmute_video' => 'Activar vídeo',
	'results' => 'Resultados:',
	'restrict' => 'Restringir',
	'restricted_users' => 'Usuarios restringidos',
	'confirm_restrict' => 'Restringir a este usuario evitará que te envíe mensajes privados o comente tus publicaciones, pero aún podrá ver tu perfil.',
	'remove_restriction' => 'Quitar restricción',
	'info_restricted_users' => 'Usuarios que ha restringido',
);";
				$fileLangES = 'resources/lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

				//============ Start Query SQL ====================================
				if (!Schema::hasTable('restrictions')) {
					Schema::create('restrictions', function ($table) {
						$table->id();
						$table->unsignedInteger('user_id')->index();
						$table->unsignedInteger('user_restricted')->index();
						$table->timestamps();
					});
				}
				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 3.9 ----->>

		if ($version == '4.0') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES, //v4.0

				'Helper.php' => $APP, //v4.0
				'SocialAccountService.php' => $APP, //v4.0

				'Reports.php' => $MODELS, //v4.0
				'Referrals.php' => $MODELS, //v4.0
				'Products.php' => $MODELS, //v4.0
				'Notifications.php' => $MODELS, //v4.0
				'ShopCategories.php' => $MODELS, //v4.0
				'User.php' => $MODELS, //v4.0

				'Functions.php' => $TRAITS, //v4.0

				'app.php' => $CONFIG, //v4.0
				'paypal.php' => $CONFIG, //v4.0

				//============ CONTROLLERS =================//
				'AddFundsController.php' => $CONTROLLERS, //v4.0
				'AdminController.php' => $CONTROLLERS, //v4.0
				'LoginController.php' => $CONTROLLERS_AUTH, //v4.0
				'RegisterController.php' => $CONTROLLERS_AUTH, //v4.0
				'ProductsController.php' => $CONTROLLERS, //v4.0
				'HomeController.php' => $CONTROLLERS, //v4.0
				'PayPalController.php' => $CONTROLLERS, //v4.0
				'PayPerViewController.php' => $CONTROLLERS, //v4.0
				'TipController.php' => $CONTROLLERS, //v4.0
				'TaxRatesController.php' => $CONTROLLERS, //v4.0
				'UpdatesController.php' => $CONTROLLERS, //v4.0
				'UserController.php' => $CONTROLLERS, //v4.0

				'EncodeVideo.php' => $JOBS, //v4.0
				'EncodeVideoMessages.php' => $JOBS, //v4.0

				'admin-functions.js' => $PUBLIC_ADMIN, //v4.0
				'admin-styles.css' => $PUBLIC_ADMIN, //v4.0
				'bootstrap.bundle.min.js.map' => $PUBLIC_ADMIN, //v4.0
				'bootstrap.min.css' => $PUBLIC_ADMIN, //v4.0
				'bootstrap.min.css.map' => $PUBLIC_ADMIN, //v4.0
				'bootstrap.min.js' => $PUBLIC_ADMIN, //v4.0

				'core.min.css' => $PUBLIC_CSS, //v4.0
				'bootstrap-icons.css' => $PUBLIC_CSS, //v4.0

				'genders.png' => $PUBLIC_IMG, //v4.0

				'bootstrap-icons.woff' => $PUBLIC_FONTS, //v4.0,
				'bootstrap-icons.woff2' => $PUBLIC_FONTS, //v4.0

				'core.min.js' => $PUBLIC_JS, //v4.0
				'app-functions.js' => $PUBLIC_JS, //v4.0

				'app.blade.php' => $VIEWS_LAYOUTS, //v4.0

				'verify.blade.php' => $VIEWS_EMAILS, //v4.0

				'login.blade.php' => $VIEWS_AUTH, //v4.0
				'register.blade.php' => $VIEWS_AUTH, //v4.0

				'categories.blade.php' => $VIEWS_INDEX, //v4.0
				'creators.blade.php' => $VIEWS_INDEX, //v4.0
				'creators-live.blade.php' => $VIEWS_INDEX, //v4.0
				'home-login.blade.php' => $VIEWS_INDEX, //v4.0

				'errors-forms.blade.php' => $VIEWS_ERRORS, //v4.0

				'css_admin.blade.php' => $VIEWS_INCLUDES, //v4.0
				'css_general.blade.php' => $VIEWS_INCLUDES, //v4.0
				'navbar.blade.php' => $VIEWS_INCLUDES, //v4.0
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //v4.0
				'footer.blade.php' => $VIEWS_INCLUDES, //v4.0
				'footer-tiny.blade.php' => $VIEWS_INCLUDES, //v4.0
				'form-post.blade.php' => $VIEWS_INCLUDES, //v4.0
				'modal-pay-live.blade.php' => $VIEWS_INCLUDES, //v4.0
				'modal-login.blade.php' => $VIEWS_INCLUDES, //v4.0
				'modal-custom-content.blade.php' => $VIEWS_INCLUDES, //v4.0
				'modal-taxes.blade.php' => $VIEWS_INCLUDES, //v4.0
				'menu-filters-creators.blade.php' => $VIEWS_INCLUDES, //v4.0
				'messages-chat.blade.php' => $VIEWS_INCLUDES, //v4.0
				'updates.blade.php' => $VIEWS_INCLUDES, //v4.0

				'add-physical-product.blade.php' => $VIEWS_SHOP, //v4.0
				'add-custom-content.blade.php' => $VIEWS_SHOP, //v4.0
				'products.blade.php' => $VIEWS_SHOP, //v4.0
				'add-product.blade.php' => $VIEWS_SHOP, //v4.0
				'modal-add-item.blade.php' => $VIEWS_SHOP, //v4.0
				'modal-buy.blade.php' => $VIEWS_SHOP, //v4.0
				'show.blade.php' => $VIEWS_SHOP, //v4.0
				'modal-edit.blade.php' => $VIEWS_SHOP, //v4.0
				'listing-products.blade.php' => $VIEWS_SHOP, //v4.0

				'dashboard.blade.php' => $VIEWS_USERS, //v4.0
				'edit_my_page.blade.php' => $VIEWS_USERS, //v4.0
				'profile.blade.php' => $VIEWS_USERS, //v4.0
				'messages-show.blade.php' => $VIEWS_USERS, //v4.0
				'my_subscriptions.blade.php' => $VIEWS_USERS, //v4.0
				'my_subscribers.blade.php' => $VIEWS_USERS, //v4.0
				'my_products.blade.php' => $VIEWS_USERS, //v4.0
				'my-sales.blade.php' => $VIEWS_USERS, //v4.0
				'invoice.blade.php' => $VIEWS_USERS, //v4.0
				'invoice-deposits.blade.php' => $VIEWS_USERS, //v4.0
				'purchased_items.blade.php' => $VIEWS_USERS, //v4.0
				'payout_method.blade.php' => $VIEWS_USERS, //v4.0
				'likes.blade.php' => $VIEWS_USERS, //v4.0
				'wallet.blade.php' => $VIEWS_USERS, //v4.0
				'notifications.blade.php' => $VIEWS_USERS, //v4.0
				'withdrawals.blade.php' => $VIEWS_USERS, //v4.0
				'verify_account.blade.php' => $VIEWS_USERS, //v4.0

				'AgoraRTCSDK-v4.js' => public_path('js' . $DS . 'agora') . $DS, //v4.0

				'ckeditor-init.js' => public_path('js' . $DS . 'ckeditor') . $DS, //v4.0

				'layout.blade.php' => resource_path('views' . $DS . 'vendor' . $DS . 'mail' . $DS . 'html') . $DS, //v4.0
			];

			$filesAdmin = [
				'add-categories.blade.php' => $VIEWS_ADMIN, //v4.0
				'add-country.blade.php' => $VIEWS_ADMIN, //v4.0
				'add-languages.blade.php' => $VIEWS_ADMIN, //v4.0
				'add-page.blade.php' => $VIEWS_ADMIN, //v4.0
				'add-state.blade.php' => $VIEWS_ADMIN, //v4.0
				'add-tax.blade.php' => $VIEWS_ADMIN, //v4.0
				'announcements.blade.php' => $VIEWS_ADMIN, //v4.0
				'billing.blade.php' => $VIEWS_ADMIN, //v4.0
				'blog.blade.php' => $VIEWS_ADMIN, //v4.0
				'categories.blade.php' => $VIEWS_ADMIN, //v4.0
				'ccbill-settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'coinpayments-settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'countries.blade.php' => $VIEWS_ADMIN, //v4.0
				'create-blog.blade.php' => $VIEWS_ADMIN, //v4.0
				'css-js.blade.php' => $VIEWS_ADMIN, //v4.0
				'deposits-view.blade.php' => $VIEWS_ADMIN, //v4.0
				'deposits.blade.php' => $VIEWS_ADMIN, //v4.0
				'edit-blog.blade.php' => $VIEWS_ADMIN, //v4.0
				'edit-categories.blade.php' => $VIEWS_ADMIN, //v4.0
				'edit-country.blade.php' => $VIEWS_ADMIN, //v4.0
				'edit-languages.blade.php' => $VIEWS_ADMIN, //v4.0
				'edit-member.blade.php' => $VIEWS_ADMIN, //v4.0
				'edit-page.blade.php' => $VIEWS_ADMIN, //v4.0
				'edit-state.blade.php' => $VIEWS_ADMIN, //v4.0
				'edit-tax.blade.php' => $VIEWS_ADMIN, //v4.0
				'flutterwave-settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'google.blade.php' => $VIEWS_ADMIN, //v4.0
				'instamojo-settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'languages.blade.php' => $VIEWS_ADMIN, //v4.0
				'live_streaming.blade.php' => $VIEWS_ADMIN, //v4.0
				'maintenance_mode.blade.php' => $VIEWS_ADMIN, //v4.0
				'members.blade.php' => $VIEWS_ADMIN, //v4.0
				'mercadopago-settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'mollie-settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'pages.blade.php' => $VIEWS_ADMIN, //v4.0
				'paystack-settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'posts.blade.php' => $VIEWS_ADMIN, //v4.0
				'profiles-social.blade.php' => $VIEWS_ADMIN, //v4.0
				'pwa.blade.php' => $VIEWS_ADMIN, //v4.0
				'razorpay-settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'referrals.blade.php' => $VIEWS_ADMIN, //v4.0
				'social-login.blade.php' => $VIEWS_ADMIN, //v4.0
				'states.blade.php' => $VIEWS_ADMIN, //v4.0
				'storage.blade.php' => $VIEWS_ADMIN, //v4.0
				'stripe-settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'subscriptions.blade.php' => $VIEWS_ADMIN, //v4.0
				'tax-rates.blade.php' => $VIEWS_ADMIN, //v4.0
				'theme.blade.php' => $VIEWS_ADMIN, //v4.0
				'timezone.blade.php' => $VIEWS_ADMIN, //v4.0
				'transactions.blade.php' => $VIEWS_ADMIN, //v4.0
				'unauthorized.blade.php' => $VIEWS_ADMIN, //v4.0
				'verification.blade.php' => $VIEWS_ADMIN, //v4.0
				'withdrawal-view.blade.php' => $VIEWS_ADMIN, //v4.0
				'withdrawals.blade.php' => $VIEWS_ADMIN, //v4.0
				'dashboard.blade.php' => $VIEWS_ADMIN, //v4.0
				'charts.blade.php' => $VIEWS_ADMIN, //v4.0
				'add-shop-categories.blade.php' => $VIEWS_ADMIN, //v4.0
				'edit-shop-categories.blade.php' => $VIEWS_ADMIN, //v4.0
				'shop-categories.blade.php' => $VIEWS_ADMIN, //v4.0
				'shop.blade.php' => $VIEWS_ADMIN, //v4.0
				'reports.blade.php' => $VIEWS_ADMIN, //v4.0
				'layout.blade.php' => $VIEWS_ADMIN, //v4.0
				'limits.blade.php' => $VIEWS_ADMIN, //v4.0
				'payments-settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, //v4.0
				'settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'bank-settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'products.blade.php' => $VIEWS_ADMIN, //v4.0
				'sales.blade.php' => $VIEWS_ADMIN, //v4.0
				'paypal-settings.blade.php' => $VIEWS_ADMIN, //v4.0
				'email-settings.blade.php' => $VIEWS_ADMIN, //v4.0
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Jquery UI Folder
			$filePathFolderJquery = $pathPublic . 'jquery-ui';
			$pathFolderJquery = public_path('js' . $DS . 'jquery-ui') . $DS;
			$this->moveDirectory($filePathFolderJquery, $pathFolderJquery, $copy);

			// Select2 Folder
			$filePathFolderSelect2 = $pathPublic . 'select2';
			$pathFolderSelect2 = public_path('js' . $DS . 'select2') . $DS;
			$this->moveDirectory($filePathFolderSelect2, $pathFolderSelect2, $copy);

			// jvectormap Folder public
			$filePathFolderAdminJs = $pathPublic . 'jvectormap';
			$pathFolderAdminJs = public_path('admin') . $DS;
			$this->moveDirectory($filePathFolderAdminJs, $pathFolderAdminJs, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 4.0
		'physical_products' => 'Physical products',
		'add_physical_product' => 'Add physical product',
		'physical_products_desc' => 'T-shirts, mugs, shoes, video games, etc.',
		'country_free_shipping' => 'Country Free Shipping',
		'shipping_fee' => 'Shipping Fee',
		'shipping' => 'Shipping',
		'quantity' => 'Quantity available',
		'box_contents' => 'Box contents',
		'error_price_shipping_fee' => 'Shipping costs cannot be equal to or greater than the price of the item',
		'sold_out' => 'Sold out',
		'out_stock' => 'This item is out of stock',
		'free_shipping' => 'Free shipping',
		'phone' => 'Phone',
		'shipping_information' => 'Shipping information',
		'shop_categories' => 'Shop Categories',
		'all_categories' => 'All Categories',
		'disable_registration_login_email' => 'Disable Registration/Login via Email',
		'report_item' => 'Report item',
		'item_not_received' => 'Item not received',
		'no_likes' => 'No likes yet',
		'qr_code' => 'QR code',
		'left_with_space' => 'Left with space',
		'right_with_space' => 'Right with space',
		'disable_contact' => 'Disable section Contact us',
		'flat_rate' => 'Flat rate',
		'tips' => 'Tips',
		'mentions' => 'Mentions',
		'specific_day_payment_withdrawals' => 'Specific day payment (withdrawals)',
		'day_of_each_month' => 'Day :day of each month',// Not remove :day
		'disable_new_post_email_notification' => 'Disable new post email notification',
		'disable_creators_search' => 'Disable creators search',
		'title_post_block' => 'Set a title if the post is text only and will not be public',
		'title_post_info' => 'Maximum :numbers characters, it will be ignored if the post contains multimedia files, or is public.',// Not remove :numbers
		'in_process' => 'In process...',
		'gender_age' => 'Gender and Age',
		'filter_by_gender_age' => 'Filter by gender and age',
		'all_genders' => 'All genders',
		'min_age' => 'Min age',
		'max_age' => 'Max age',
		'info_verification_user_reverse_id' => 'Upload a photo of the back of your identity document.',
		'info_verification_user_selfie' => 'Upload a photo holding your identity document and a sign that says: \":sitename\", your username and today\'s date.',// Not remove :sitename
		'verification_image_id' => 'Photo of your identity document',
		'verification_image_reverse_id' => 'Photo of the back of your identity document',
		'verification_image_selfie' => 'Photo holding your identity document',
		'testing_smtp' => 'Take an SMTP test',
		'subscriptions_the_month' => 'Subscriptions of the month',
		'browse_creators_by_gender_age' => 'Browse creators by gender and age',
		'allow_qr_code_generate' => 'Allow QR code generate',
		'auto_follow_admin' => 'Auto-follow Admin (Only if the profile is free)',
);";
				$fileLangEN = 'resources/lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 4.0
	'physical_products' => 'Productos físicos',
	'add_physical_product' => 'Agregar producto físico',
	'physical_products_desc' => 'Camisetas, tazas, zapatos, videojuegos, etc.',
	'country_free_shipping' => 'País envío gratis',
	'shipping_fee' => 'Gastos de envío',
	'shipping' => 'Envío',
	'quantity' => 'Cantidad disponible',
	'box_contents' => 'Contenido de la caja',
	'error_price_shipping_fee' => 'Los gastos de envío no pueden ser igual o mayor al precio del artículo',
	'sold_out' => 'Agotado',
	'out_stock' => 'Este artículo está agotado',
	'free_shipping' => 'Envío gratis',
	'phone' => 'Teléfono',
	'shipping_information' => 'Datos de envío',
	'shop_categories' => 'Categorías tienda',
	'all_categories' => 'Todas las Categorías',
	'disable_registration_login_email' => 'Desactivar Registro/Inicio de sesión por correo electrónico',
	'report_item' => 'Report artículo',
	'item_not_received' => 'Artículo no recibido',
	'no_likes' => 'Aún no hay me gustas',
	'qr_code' => 'Código QR',
	'left_with_space' => 'Izquierda con espacio',
	'right_with_space' => 'Derecha con espacio',
	'disable_contact' => 'Desactivar sección Contáctenos',
	'flat_rate' => 'Tarifa plana',
	'tips' => 'Propinas',
	'mentions' => 'Menciones',
	'specific_day_payment_withdrawals' => 'Pago día específico (retiros)',
	'day_of_each_month' => 'Día :day de cada mes',// Not remove :day
	'disable_new_post_email_notification' => 'Desactivar notificación por correo nuevo post',
	'disable_creators_search' => 'Desactivar búsqueda de creadores',
	'title_post_block' => 'Establezca un título si la publicación es solo de texto y no será pública',
	'title_post_info' => 'Máximo :numbers caracteres, se ignorará si la publicación contiene archivos multimedia, o es pública.',// Not remove :numbers
	'in_process' => 'En proceso...',
	'gender_age' => 'Género y Edad',
	'filter_by_gender_age' => 'Filtrar por género y edad',
	'all_genders' => 'Todos los géneros',
	'min_age' => 'Min edad',
	'max_age' => 'Max edad',
	'info_verification_user_reverse_id' => 'Sube una foto del reverso de tu documento de identidad.',
	'info_verification_user_selfie' => 'Sube una foto sosteniendo tu documento de identidad y un cartel que diga: \":sitename\", tu nombre de usuario y la fecha del día.',// Not remove :sitename
	'verification_image_id' => 'Foto de tu documento de identidad',
	'verification_image_reverse_id' => 'Foto del reverso de tu documento de identidad',
	'verification_image_selfie' => 'Foto sosteniendo tu documento de identidad',
	'testing_smtp' => 'Haz una prueba de SMTP',
	'subscriptions_the_month' => 'Suscripciones del mes',
	'browse_creators_by_gender_age' => 'Buscar creadores por género y edad',
	'allow_qr_code_generate' => 'Permitir generar código QR',
	'auto_follow_admin' => 'Auto-seguir Admin (Solo si es gratis el perfil)',
);";
				$fileLangES = 'resources/lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn(
					'admin_settings',
					'physical_products',
					'disable_login_register_email',
					'disable_contact',
					'specific_day_payment_withdrawals',
					'disable_new_post_notification',
					'disable_search_creators',
					'search_creators_genders',
					'generate_qr_code',
					'autofollow_admin'
				)) {
					Schema::table('admin_settings', function ($table) {
						$table->boolean('physical_products')->default(false);
						$table->boolean('disable_login_register_email')->default(false);
						$table->boolean('disable_contact')->default(false);
						$table->unsignedInteger('specific_day_payment_withdrawals');
						$table->boolean('disable_new_post_notification')->default(false);
						$table->boolean('disable_search_creators')->default(false);
						$table->boolean('search_creators_genders')->default(false);
						$table->boolean('generate_qr_code')->default(false);
						$table->boolean('autofollow_admin')->default(false);
					});
				} // End Schema

				if (!Schema::hasColumn('products', 'country_free_shipping', 'shipping_fee', 'quantity', 'box_contents')) {
					Schema::table('products', function ($table) {
						$table->char('country_free_shipping', 20)->after('delivery_time');
						$table->decimal('shipping_fee', 10, 2);
						$table->unsignedInteger('quantity');
						$table->string('box_contents', 200);
						$table->string('category', 20)->nullable();
					});
				} // End Schema

				if (!Schema::hasColumn('purchases', 'address', 'city', 'zip', 'phone')) {
					Schema::table('purchases', function ($table) {
						$table->string('address', 200)->nullable();
						$table->string('city', 150)->nullable();
						$table->string('zip', 50)->nullable();
						$table->char('phone', 20)->nullable();
					});
				} // End Schema

				if (!Schema::hasTable('shop_categories')) {
					Schema::create('shop_categories', function ($table) {
						$table->id();
						$table->string('name', 255);
						$table->string('slug', 200)->index();
					});
				} // End Schema

				Schema::table('reports', function ($table) {
					$table->string('type', 100)->change();
					$table->string('reason', 100)->change();
				}); // End Schema

				Schema::table('payment_gateways', function ($table) {
					$table->decimal('fee_cents', 6, 2)->change();
				}); // End Schema

				Schema::table('admin_settings', function ($table) {
					$table->string('currency_position', 100)->change();
				}); // End Schema

				if (!Schema::hasColumn('users', 'reddit', 'spotify')) {
					Schema::table('users', function ($table) {
						$table->string('reddit', 200);
						$table->string('spotify', 200);
					});
				} // End Schema

				if (!Schema::hasColumn('updates', 'title')) {
					Schema::table('updates', function ($table) {
						$table->string('title', 150)->collation('utf8mb4_unicode_ci')->after('id')->nullable();
					});
				} // End Schema

				if (!Schema::hasColumn('withdrawals', 'estimated_payment')) {
					Schema::table('withdrawals', function ($table) {
						$table->timestamp('estimated_payment')->after('account')->nullable();
					});
				} // End Schema

				if (Schema::hasColumn(
					'updates',
					'image',
					'video',
					'music',
					'file',
					'img_type',
					'video_embed',
					'file_name',
					'file_size'
				)) {
					Schema::table('updates', function ($table) {
						$table->dropColumn([
							'image',
							'video',
							'music',
							'file',
							'img_type',
							'video_embed',
							'file_name',
							'file_size'
						]);
					});
				} // End Schema

				if (!Schema::hasColumn('media', 'duration_video', 'quality_video')) {
					Schema::table('media', function ($table) {
						$table->string('duration_video', 50)->after('video_poster')->nullable();
						$table->string('quality_video', 20)->after('duration_video')->nullable();
					});
				} // End Schema

				if (!Schema::hasColumn('verification_requests', 'image_reverse', 'image_selfie')) {
					Schema::table('verification_requests', function ($table) {
						$table->string('image_reverse', 100)->after('image')->nullable();
						$table->string('image_selfie', 100)->after('image_reverse')->nullable();
					});
				} // End Schema

				if (!Schema::hasColumn('media_messages', 'duration_video', 'quality_video')) {
					Schema::table('media_messages', function ($table) {
						$table->string('duration_video', 50)->after('video_poster')->nullable();
						$table->string('quality_video', 20)->after('duration_video')->nullable();
					});
				} // End Schema

				file_put_contents(
					'.env',
					"\nPAYPAL_MODE=sandbox\nPAYPAL_SANDBOX_CLIENT_ID=\nPAYPAL_SANDBOX_CLIENT_SECRET=\nPAYPAL_LIVE_CLIENT_ID=\nPAYPAL_LIVE_CLIENT_SECRET=\nPAYPAL_WEBHOOK_ID=\n",
					FILE_APPEND
				);

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.0 ----->>

		if ($version == '4.1') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //v4.1

				'web.php' => $ROUTES, //v4.1

				'Functions.php' => $TRAITS, //v4.1

				'app.php' => $CONFIG, //v4.1

				//============ CONTROLLERS =================//
				'AddFundsController.php' => $CONTROLLERS, //v4.1
				'AdminController.php' => $CONTROLLERS, //v4.1
				'ProductsController.php' => $CONTROLLERS, //v4.1
				'PayPalController.php' => $CONTROLLERS, //v4.1
				'PayPerViewController.php' => $CONTROLLERS, //v4.1
				'HomeController.php' => $CONTROLLERS, //v4.1
				'TipController.php' => $CONTROLLERS, //v4.1

				'sitemaps.blade.php' => $VIEWS_INDEX, //v4.1

				'css_general.blade.php' => $VIEWS_INCLUDES, //v4.1
				'footer.blade.php' => $VIEWS_INCLUDES, //v4.1
				'form-post.blade.php' => $VIEWS_INCLUDES, //v4.1
				'modal-new-message.blade.php' => $VIEWS_INCLUDES, //v4.1
				'menu-filters-creators.blade.php' => $VIEWS_INCLUDES, //v4.1
				'updates.blade.php' => $VIEWS_INCLUDES, //v4.1

				'messages-show.blade.php' => $VIEWS_USERS, //v4.1
				'wallet.blade.php' => $VIEWS_USERS, //v4.1

			];

			$filesAdmin = [
				'live_streaming.blade.php' => $VIEWS_ADMIN, //v4.1
				'posts.blade.php' => $VIEWS_ADMIN, //v4.1
				'profiles-social.blade.php' => $VIEWS_ADMIN, //v4.1
				'theme.blade.php' => $VIEWS_ADMIN, //v4.1
				'layout.blade.php' => $VIEWS_ADMIN, //v4.1
				'limits.blade.php' => $VIEWS_ADMIN, //v4.1
				'settings.blade.php' => $VIEWS_ADMIN, //v4.1
				'bank-settings.blade.php' => $VIEWS_ADMIN, //v4.1
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 4.1
		'allow_zip_files' => 'Allow upload zip files (Post and Messages)',
);";
				$fileLangEN = 'resources/lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 4.1
	'allow_zip_files' => 'Permitir subir archivos zip (Post y Mensajes)',
);";
				$fileLangES = 'resources/lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn(
					'admin_settings',
					'allow_zip_files',
					'reddit',
					'telegram',
					'linkedin'
				)) {
					Schema::table('admin_settings', function ($table) {
						$table->boolean('allow_zip_files')->default(true);
						$table->string('reddit', 200);
						$table->string('telegram', 200);
						$table->string('linkedin', 200);
					});
				} // End Schema

				Schema::table('deposits', function ($table) {
					$table->string('percentage_applied', 50)->nullable()->change();
				}); // End Schema

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.1 ----->>

		if ($version == '4.2') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //v4.2
				'composer.lock' => $ROOT, //v4.2

				'Helper.php' => $APP, //v4.2
				'SocialAccountService.php' => $APP, //v4.2

				'Kernel.php' => app_path('Http') . $DS,

				'api.php' => $ROUTES, //v4.2
				'web.php' => $ROUTES, //v4.2

				'Functions.php' => $TRAITS, //v4.2
				'UserDelete.php' => $TRAITS, //v4.2
				'PushNotificationTrait.php' => $TRAITS, //v4.2

				'EncodeVideo.php' => $JOBS, //v4.2
				'EncodeVideoMessages.php' => $JOBS, //v4.2
				'EncodeVideoStory.php' => $JOBS, //v4.2
				'DeleteMedia.php' => $JOBS, //v4.2

				'Comments.php' => $MODELS, //v4.2
				'Replies.php' => $MODELS, //v4.2
				'Subscriptions.php' => $MODELS, //v4.2
				'Notifications.php' => $MODELS, //v4.2
				'UserDevices.php' => $MODELS, //v4.2
				'LoginSessions.php' => $MODELS, //v4.2
				'Updates.php' => $MODELS, //v4.2
				'VideoViews.php' => $MODELS, //v4.2
				'Stories.php' => $MODELS, //v4.2
				'MediaStories.php' => $MODELS, //v4.2
				'StoryBackgrounds.php' => $MODELS, //v4.2
				'StoryViews.php' => $MODELS, //v4.2
				'User.php' => $MODELS, //v4.2

				'app.php' => $CONFIG, //v4.2
				'path.php' => $CONFIG, //v4.2
				'image.php' => $CONFIG, //v4.2
				'laravelpwa.php' => $CONFIG, //v4.2

				'admin-styles.css' => $PUBLIC_ADMIN, //v4.2

				'core.min.css' => $PUBLIC_CSS, //v4.2
				'styles.css' => $PUBLIC_CSS, //v4.2

				'app-functions.js' => $PUBLIC_JS, //v4.2
				'core.min.js' => $PUBLIC_JS, //v4.2
				'create-story.js' => $PUBLIC_JS, //v4.2
				'OneSignalSDKWorker.js' => $PUBLIC_JS, //v4.2
				'fileuploader-story-file.js' => public_path('js' . $DS . 'fileuploader') . $DS, //v4.2
				'jquery.fileuploader-theme-dragdrop.css' => public_path('js' . $DS . 'fileuploader') . $DS, //v4.2
				'install-app.js' => $PUBLIC_JS, //v4.2
				'plyr.min.js' => public_path('js' . $DS . 'plyr') . $DS, //v4.2
				'plyr.css' => public_path('js' . $DS . 'plyr') . $DS, //v4.2
				'plyr.polyfilled.min.js' => public_path('js' . $DS . 'plyr') . $DS, //v4.2

				'admin-functions.js' => $PUBLIC_ADMIN, //v4.2

				//============ CONTROLLERS =================//
				'RegisterController.php' => $CONTROLLERS_AUTH, //v4.2
				'LoginController.php' => $CONTROLLERS_AUTH, //v4.2

				'AddFundsController.php' => $CONTROLLERS, //v4.2
				'AdminController.php' => $CONTROLLERS, //v4.2
				'PushNotificationsController.php' => $CONTROLLERS, //v4.2
				'PayPalController.php' => $CONTROLLERS, //v4.2
				'TwoFactorAuthController.php' => $CONTROLLERS, //v4.2
				'HomeController.php' => $CONTROLLERS, //v4.2
				'InstallScriptController.php' => $CONTROLLERS, //v4.2
				'RepliesController.php' => $CONTROLLERS, //v4.2
				'CommentsController.php' => $CONTROLLERS, //v4.2
				'UpdatesController.php' => $CONTROLLERS, //v4.2
				'UploadMediaMessageController.php' => $CONTROLLERS, //v4.2
				'UploadMediaController.php' => $CONTROLLERS, //v4.2
				'UploadMediaStoryController.php' => $CONTROLLERS, //v4.2
				'StoriesController.php' => $CONTROLLERS, //v4.2
				'UserController.php' => $CONTROLLERS, //v4.2

				'app.blade.php' => $VIEWS_LAYOUTS, //v4.2

				'blog.blade.php' => $VIEWS_INDEX, //v4.2
				'creators.blade.php' => $VIEWS_INDEX, //v4.2
				'home-session.blade.php' => $VIEWS_INDEX, //v4.2
				'explore.blade.php' => $VIEWS_INDEX, //v4.2

				'explore_creators.blade.php' => $VIEWS_INCLUDES, //v4.2
				'css_general.blade.php' => $VIEWS_INCLUDES, //v4.2
				'cards-settings.blade.php' => $VIEWS_INCLUDES, //v4.2
				'footer.blade.php' => $VIEWS_INCLUDES,
				'form-post.blade.php' => $VIEWS_INCLUDES, //v4.2
				'modal-new-message.blade.php' => $VIEWS_INCLUDES, //v4.2
				'menu-filters-creators.blade.php' => $VIEWS_INCLUDES, //v4.2
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //v4.2
				'messages-chat.blade.php' => $VIEWS_INCLUDES, //v4.2
				'replies.blade.php' => $VIEWS_INCLUDES, //v4.2
				'comments.blade.php' => $VIEWS_INCLUDES, //v4.2
				'updates.blade.php' => $VIEWS_INCLUDES, //v4.2
				'modal-edit-comment.blade.php' => $VIEWS_INCLUDES, //v4.2
				'listing-categories.blade.php' => $VIEWS_INCLUDES, //v4.2
				'alert-payment-disabled.blade.php' => $VIEWS_INCLUDES, //v4.2
				'modal-logout-devices.blade.php' => $VIEWS_INCLUDES, //v4.2
				'modal-add-story.blade.php' => $VIEWS_INCLUDES, //v4.2
				'modal-story-views.blade.php' => $VIEWS_INCLUDES, //v4.2

				'requirements.blade.php' => $VIEWS_INSTALL, //v4.2

				'dashboard.blade.php' => $VIEWS_USERS, //v4.2
				'edit_my_page.blade.php' => $VIEWS_USERS, //v4.2
				'create-story.blade.php' => $VIEWS_USERS, //v4.2
				'create-story-text.blade.php' => $VIEWS_USERS, //v4.2
				'bookmarks.blade.php' => $VIEWS_USERS, //v4.2
				'messages-show.blade.php' => $VIEWS_USERS, //v4.2
				'live.blade.php' => $VIEWS_USERS, //v4.2
				'verify_account.blade.php' => $VIEWS_USERS, //v4.2
				'privacy_security.blade.php' => $VIEWS_USERS, //v4.2
				'my_payments.blade.php' => $VIEWS_USERS, //v4.2
				'invoice.blade.php' => $VIEWS_USERS, //v4.2
				'invoice-deposits.blade.php' => $VIEWS_USERS, //v4.2
				'my_payments.blade.php' => $VIEWS_USERS, //v4.2
				'bookmarks.blade.php' => $VIEWS_USERS, //v4.2
				'my-purchases.blade.php' => $VIEWS_USERS, //v4.2
				'likes.blade.php' => $VIEWS_USERS, //v4.2
				'notifications.blade.php' => $VIEWS_USERS, //v4.2
				'withdrawals.blade.php' => $VIEWS_USERS, //v4.2
				'my_posts.blade.php' => $VIEWS_USERS, //v4.2
				'my-stories.blade.php' => $VIEWS_USERS, //v4.2
				'profile.blade.php' => $VIEWS_USERS, //v4.2


			];

			$filesAdmin = [
				'dashboard.blade.php' => $VIEWS_ADMIN, //v4.2
				'edit-member.blade.php' => $VIEWS_ADMIN, //v4.2
				'pwa.blade.php' => $VIEWS_ADMIN, //v4.2
				'members.blade.php' => $VIEWS_ADMIN, //v4.2
				'deposits.blade.php' => $VIEWS_ADMIN, //v4.2
				'theme.blade.php' => $VIEWS_ADMIN, //v4.2
				'layout.blade.php' => $VIEWS_ADMIN, //v4.2
				'email-settings.blade.php' => $VIEWS_ADMIN, //v4.2
				'settings.blade.php' => $VIEWS_ADMIN, //v4.2
				'withdrawals.blade.php' => $VIEWS_ADMIN, //v4.2
				'withdrawal-view.blade.php' => $VIEWS_ADMIN, //v4.2
				'verification.blade.php' => $VIEWS_ADMIN, //v4.2
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, //v4.2
				'push_notifications.blade.php' => $VIEWS_ADMIN, //v4.2
				'transactions.blade.php' => $VIEWS_ADMIN, //v4.2
				'payments-settings.blade.php' => $VIEWS_ADMIN, //v4.2
				'charts.blade.php' => $VIEWS_ADMIN, //v4.2
				'maintenance_mode.blade.php' => $VIEWS_ADMIN, //v4.2
				'stories-settings.blade.php' =>  $VIEWS_ADMIN, //v4.2
				'stories-posts.blade.php' =>  $VIEWS_ADMIN, //v4.2
				'stories-backgrounds.blade.php' =>  $VIEWS_ADMIN, //v4.2
				'storage.blade.php' =>  $VIEWS_ADMIN, //v4.2
				'social-login.blade.php' =>  $VIEWS_ADMIN, //v4.2
				'verification.blade.php' =>  $VIEWS_ADMIN, //v4.2
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Move folder Stories Backgrounds
			$filePathFolderStoriesBg = $path . 'stories-bg';
			$pathFolderStoriesBg = public_path('img' . $DS . 'stories-bg') . $DS;
			$this->moveDirectory($filePathFolderStoriesBg, $pathFolderStoriesBg, $copy);

			// Move folder Stories
			$filePathFolderStories = $path . 'stories';
			$pathFolderStories = public_path('uploads' . $DS . 'stories') . $DS;
			$this->moveDirectory($filePathFolderStories, $pathFolderStories, $copy);

			// Move folder Story
			$filePathFolderStory = $path . 'story';
			$pathFolderStory = public_path('js' . $DS . 'story') . $DS;
			$this->moveDirectory($filePathFolderStory, $pathFolderStory, $copy);

			if ($copy == false) {
				Helper::envUpdate('SESSION_DRIVER', 'file');

				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 4.2
		'push_notification_title' => 'Activate Push Notifications to be aware of :app interactions.',// Not remove :app
		'maybe_later' => 'Maybe Later',
		'push_notifications' => 'Push Notifications',
		'notifications_activated_successfully' => 'Notifications activated successfully!',
		'has_mentioned_you_post' => 'he mentioned you in a post',
		'referrals_made_transaction' => 'One of your referrals has made a transaction',
		'has_sent_you_tip' => 'has sent you a tip',
		'like_your_comment' => 'liked your comment',
		'login_session_alert' => 'If you don\'t recognize this session record, change your password immediately.',
		'zip_verification_creator' => 'Request ZIP code to verify creator account',
		'push_notification_warning' => 'Push Notifications will also be affected.',
		'reply' => 'Reply',
		'view_replies' => 'View replies',
		'replying_to' => 'Replying to',
		'payoneer_account' => 'Payoneer Account',
		'zelle_account' => 'Zelle Account',
		'this_month' => 'This month',
		'last_month' => 'Last month',
		'this_year' =>  'This year',
		'amount_max_withdrawal' =>  'Maximum withdrawal amount',
		'info_max_withdrawal' =>  'Leave at zero to set no limits',
		'edit_comment' =>  'Edit comment',
		'like_likes' =>  ':total like|:total likes',// Not remove :total
		'comment_comments' =>  ':total comment|:total comments',// Not remove :total
		'view_views' =>  ':total view|:total views',// Not remove :total
		'clear_cache' => 'Clear cache',
		'successfully_cleaned' => 'Successfully cleaned!',
		'earnings_net_subscriptions' => 'Net earnings from subscriptions',
		'earnings_net_tips' => 'Net earnings from tips',
		'earnings_net_ppv' => 'Net earnings from Pay Per View',
		'show_only_free' => 'Show only free',
		'show_all' => 'Show all',
		'payment_method_configured_disabled' => 'The :payment you have configured was disabled, please select another.',// Not remove :payment
		'posts_privacy' => 'Unregistered people can see my posts',
		'alert_posts_privacy' => 'Only registered people can see posts from :user',// Not remove :user
		'close_all_sessions' => 'Close all sessions',
		'active_now' => 'Active now',
		'stories' => 'Stories',
		'backgrounds' => 'Background images',
		'settings' => 'Settings',
		'story_image' => 'Story with Image/Video',
		'story_text' => 'Story with text',
		'success_removed' => 'Successfully removed',
		'add_story'=> 'Add story',
		'choose_type_story' => 'Choose the type of story',
		'add_story_image_subtitle' => 'Create an story with video or photo',
		'add_story_text_subtitle' => 'Create an story with only text',
		'select_media_story' => 'Please select an image or video',
		'story_max_videos_length' => 'Max videos length',
		'seconds' => 'seconds',
		'error_story_max_videos_length' => 'Videos can not longer than :length seconds.',// Not remove :length
		'story_on_way' => 'Story on way...',
		'story_successfully_posted' => 'Your story is now available!',
		'see_story' => 'See story',
		'my_stories' => 'My stories',
		'my_stories_subtitle' => 'All the stories you\'ve created',
		'start_typing' => 'Start typing',
		'text' => 'Text',
		'touch_unmute' => 'Touch to unmute',
		'ago' => 'ago',
		'hour' => 'hour',
		'hours' => 'hours',
		'minute' => 'minute',
		'minutes' => 'minutes',
		'fromnow' => 'from now',
		'seconds' => 'seconds',
		'yesterday' => 'yesterday',
		'people_seen_story' => 'People who have seen the story',
		'no_one_seen_story_yet' => 'No one has seen your story yet',
);";
				$fileLangEN = 'resources/lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 4.2
	'push_notification_title' => 'Active las Notificaciones Push para estar al tanto de las interacciones de :app', // Not remove :app
	'maybe_later' => 'Quizas mas tarde',
	'push_notifications' => 'Notificaciones Push',
	'notifications_activated_successfully' => '¡Notificaciones activadas con éxito!',
	'has_mentioned_you_post' => 'te ha mencionado en un post',
	'referrals_made_transaction' => 'Uno de tus referidos ha realizado una transacción',
	'has_sent_you_tip' => 'te ha enviado una propina',
	'like_your_comment' => 'le gustó tu comentario',
	'login_session_alert' => 'Si no reconoce este registro de sesión, cambie su contraseña inmediatamente.',
	'zip_verification_creator' => 'Solicitar Código Postal para verificar la cuenta del creador',
	'push_notification_warning' => 'Las Notificaciones Push tambien se verán afectadas.',
	'reply' => 'Responder',
	'view_replies' => 'Ver respuestas',
	'replying_to' => 'Respondiendo a',
	'payoneer_account' => 'Cuenta Payoneer',
	'zelle_account' => 'Cuenta Zelle',
	'this_month' => 'Este mes',
	'last_month' => 'Mes pasado',
	'this_year' =>  'Este año',
	'amount_max_withdrawal' =>  'Cantidad máxima de retiro',
	'info_max_withdrawal' =>  'Dejar en cero para no establecer límites',
	'edit_comment' =>  'Editar comentario',
	'like_likes' =>  ':total me gusta|:total me gustas',// Not remove :total
	'comment_comments' =>  ':total comentario|:total comentarios',// Not remove :total
	'view_views' =>  ':total visualización|:total visualizaciones',// Not remove :total
	'clear_cache' => 'Limpiar Caché',
	'successfully_cleaned' => '¡Limpiado con éxito!',
	'earnings_net_subscriptions' => 'Ganancias netas de suscripciones',
	'earnings_net_tips' => 'Ganancias netas de propinas',
	'earnings_net_ppv' => 'Ganancias netas de Pago Por Ver',
	'show_only_free' => 'Mostrar solo gratis',
	'show_all' => 'Mostrar todo',
	'payment_method_configured_disabled' => 'El :payment que has configurado fue desactivado, por favor selecciona otro.',// Not remove :payment
	'posts_privacy' => 'Personas no registradas pueden ver mi posts',
	'alert_posts_privacy' => 'Solo las personas registradas pueden ver los posts de :user',// Not remove :user
	'close_all_sessions' => 'Cerrar todas las sesiones',
	'active_now' => 'Activo ahora',
	'stories' => 'Historias',
	'backgrounds' => 'Imágenes de fondo',
	'settings' => 'Configuraciones',
	'story_image' => 'Historia con Imagen/Vídeo',
	'story_text' => 'Historia con texto',
	'add_story'=> 'Agregar historia',
	'choose_type_story' => 'Elige el tipo de historia',
	'add_story_image_subtitle' => 'Crea una historia con un vídeo o foto.',
	'add_story_text_subtitle' => 'Crea una historia con solo texto',
	'select_media_story' => 'Por favor seleccione una imagen o vídeo',
	'story_max_videos_length' => 'Duración máxima del video',
	'seconds' => 'segundos',
	'error_story_max_videos_length' => 'Los videos no pueden durar más de :length segundos.',// Not remove :length
	'story_on_way' => 'Historia en camino...',
	'story_successfully_posted' => '¡Tu historia ya está disponible!',
	'see_story' => 'Ver historia',
	'my_stories' => 'Mis historias',
	'my_stories_subtitle' => 'Todas las historias que has creado',
	'start_typing' => 'Comienza a escribir',
	'text' => 'Texto',
	'touch_unmute' => 'Toca para activar audio',
	'ago' => 'hace',
	'hour' => 'hora',
	'hours' => 'horas',
	'minute' => 'minuto',
	'minutes' => 'minutos',
	'fromnow' => 'desde hora',
	'seconds' => 'segundos',
	'yesterday' => 'ayer',
	'people_seen_story' => 'Personas que han visto la historia',
	'no_one_seen_story_yet' => 'Nadie ha visto tu historia todavía',
);";
				$fileLangES = 'resources/lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn(
					'admin_settings',
					'push_notification_status',
					'onesignal_appid',
					'onesignal_restapi',
					'status_pwa',
					'zip_verification_creator',
					'amount_max_withdrawal',
					'story_status',
					'story_image',
					'story_text',
					'story_max_videos_length'
				)) {
					Schema::table('admin_settings', function ($table) {
						$table->boolean('push_notification_status')->default(0);
						$table->string('onesignal_appid', 150);
						$table->string('onesignal_restapi', 150);
						$table->boolean('status_pwa')->default(1);
						$table->boolean('zip_verification_creator')->default(1);
						$table->unsignedInteger('amount_max_withdrawal');
						$table->boolean('story_status')->default(0);
						$table->boolean('story_image')->default(1);
						$table->boolean('story_text')->default(1);
						$table->unsignedInteger('story_max_videos_length')->default(30);
					});
				} // End Schema

				if (!Schema::hasTable('user_devices')) {
					Schema::create('user_devices', function ($table) {
						$table->id();
						$table->unsignedBigInteger('user_id');
						$table->string('player_id')->unique();
						$table->char('device_type', 5)->nullable();
						$table->timestamps();
					});
				} // <<--- End Create Table

				if (!Schema::hasTable('login_sessions')) {
					Schema::create('login_sessions', function ($table) {
						$table->id();
						$table->unsignedBigInteger('user_id');
						$table->string('ip', 100);
						$table->string('device', 100)->nullable();
						$table->string('device_type', 100)->nullable();
						$table->string('browser', 100)->nullable();
						$table->string('platform', 100)->nullable();
						$table->string('country', 100)->nullable();
						$table->timestamps();
					});
				} // <<--- End Create Table

				if (!Schema::hasTable('replies')) {
					Schema::create('replies', function ($table) {
						$table->id();
						$table->unsignedBigInteger('user_id')->index();
						$table->unsignedBigInteger('updates_id')->index();
						$table->unsignedBigInteger('comments_id')->index();
						$table->longText('reply')->collation('utf8mb4_unicode_ci');
						$table->timestamps();
					});
				} // <<--- End Create Table

				if (!Schema::hasColumn('comments_likes', 'replies_id')) {
					Schema::table('comments_likes', function ($table) {
						$table->unsignedBigInteger('replies_id')->after('comments_id')->index();
					});
				} // End Schema

				if (!Schema::hasColumn('reserved', 'home')) {
					\DB::table('reserved')->insert(
						['name' => 'home']
					);
				} // End Schema

				if (!Schema::hasTable('video_views')) {
					Schema::create('video_views', function ($table) {
						$table->id();
						$table->unsignedBigInteger('user_id')->index();
						$table->unsignedBigInteger('updates_id')->index();
						$table->string('ip', 100)->index();
						$table->timestamps();
					});
				} // <<--- End Create Table

				if (!Schema::hasColumn('updates', 'video_views')) {
					Schema::table('updates', function ($table) {
						$table->unsignedBigInteger('video_views')->index();
					});
				} // End Schema

				if (!Schema::hasColumn('users', 'posts_privacy')) {
					Schema::table('users', function ($table) {
						$table->boolean('posts_privacy')->default(1);
					});
				} // End Schema

				if (!Schema::hasTable('stories')) {
					Schema::create('stories', function ($table) {
						$table->id();
						$table->unsignedBigInteger('user_id')->index();
						$table->string('title', 255)->nullable();
						$table->string('status', 50)->default('active')->index();
						$table->timestamps();
					});
				} // <<--- End Create Table

				if (!Schema::hasTable('media_stories')) {
					Schema::create('media_stories', function ($table) {
						$table->bigIncrements('id');
						$table->unsignedBigInteger('stories_id')->index();
						$table->string('name', 255)->index();
						$table->string('type', 100)->index();
						$table->string('video_length', 20)->nullable();
						$table->string('video_poster', 100)->nullable();
						$table->text('text')->collation('utf8mb4_unicode_ci')->nullable();
						$table->boolean('status')->default(0);
						$table->timestamps();
					});
				} // <<--- End Create Table

				if (!Schema::hasTable('story_views')) {
					Schema::create('story_views', function ($table) {
						$table->id();
						$table->unsignedBigInteger('user_id')->index();
						$table->unsignedBigInteger('media_stories_id')->index();
						$table->timestamps();
					});
				} // <<--- End Create Table

				if (!Schema::hasTable('story_backgrounds')) {
					Schema::create('story_backgrounds', function ($table) {
						$table->id();
						$table->string('name', 50)->index();
					});
				} // <<--- End Create Table

				if (Schema::hasTable('story_backgrounds')) {
					\DB::table('story_backgrounds')->insert([
						['name' => '01.jpg'],
						['name' => '02.png'],
						['name' => '03.jpg'],
						['name' => '04.jpg'],
						['name' => '05.jpg'],
						['name' => '06.png'],
						['name' => '07.jpg'],
						['name' => '08.png'],
						['name' => '09.jpg'],
						['name' => '10.png'],
						['name' => '11.jpg'],
						['name' => '12.jpg'],
						['name' => '13.jpg'],
						['name' => '14.jpg'],
						['name' => '15.png']
					]);
				} // <<--- End

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.2 ----->>

		if ($version == '4.3') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES, //v4.3

				'Helper.php' => $APP, //v4.3

				'UserDelete.php' => $TRAITS, //v4.3

				'app-functions.js' => $PUBLIC_JS, //v4.3

				'zuck.min.css' => public_path('js' . $DS . 'story') . $DS, //v4.2
				'zuck.min.js' => public_path('js' . $DS . 'story') . $DS, //v4.2

				//============ CONTROLLERS =================//
				'AddFundsController.php' => $CONTROLLERS, //v4.3
				'AdminController.php' => $CONTROLLERS, //v4.3
				'HomeController.php' => $CONTROLLERS, //v4.3
				'StoriesController.php' => $CONTROLLERS, //v4.3
				'UploadMediaStoryController.php' => $CONTROLLERS, //v4.3

				'Stories.php' => $MODELS, //v4.3
				'MediaStories.php' => $MODELS, //v4.3
				'StoryBackgrounds.php' => $MODELS, //v4.3
				'StoryFonts.php' => $MODELS, //v4.3

				'home-session.blade.php' => $VIEWS_INDEX, //v4.3

				'products.blade.php' => $VIEWS_SHOP, //v4.3

				'css_general.blade.php' => $VIEWS_INCLUDES, //v4.3
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //v4.3
				'updates.blade.php' => $VIEWS_INCLUDES, //v4.3

				'create-story-text.blade.php' => $VIEWS_USERS, //v4.3
				'my-stories.blade.php' => $VIEWS_USERS, //v4.3
				'profile.blade.php' => $VIEWS_USERS, //v4.3

			];

			$filesAdmin = [
				'storage.blade.php' => $VIEWS_ADMIN, //v4.3
				'stories-fonts.blade.php' => $VIEWS_ADMIN, //v4.3
				'layout.blade.php' => $VIEWS_ADMIN, //v4.3
				'settings.blade.php' => $VIEWS_ADMIN, //v4.3
				'payments-settings.blade.php' => $VIEWS_ADMIN, //v4.3
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 4.3
		'font_color' => 'Font color',
		'google_fonts' => 'Google Fonts',
		'alert_story_fonts' => 'Important: Do not add too many fonts as this will cause your site to load slowly, we recommend max 3 fonts.',
);";
				$fileLangEN = 'resources/lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 4.3
	'font_color' => 'Color de la fuente',
	'google_fonts' => 'Fuentes Google',
	'alert_story_fonts' => 'Importante: No agregue demasiadas fuentes ya que esto provacará que  su sitio cargue lento, recomendamos  máxmo 3 fuentes.',
);";
				$fileLangES = 'resources/lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn('media_stories', 'posts_privacy')) {
					Schema::table('media_stories', function ($table) {
						$table->string('font_color', 50)->after('text')->default('#ffffff');
						$table->string('font', 50)->after('font_color')->default('Arial');
					});
				} // End Schema

				if (!Schema::hasTable('story_fonts')) {
					Schema::create('story_fonts', function ($table) {
						$table->id();
						$table->string('name', 50);
					});
				} // <<--- End Create Table

				//============ End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.3 ----->>

		if ($version == '4.4') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				//============ CONTROLLERS =================//
				'StripeWebHookController.php' => $CONTROLLERS, //v4.4

				'home-session.blade.php' => $VIEWS_INDEX, //v4.4

			];

			$filesAdmin = [
				'stories-settings.blade.php' => $VIEWS_ADMIN, //v4.4
				'stories-posts.blade.php' => $VIEWS_ADMIN, //v4.4
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.4 ----->>

		if ($version == '4.5') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'Helper.php' => $APP, //v4.5

				'SocialAccountService.php' => $APP, //v4.5

				'UserDelete.php' => $TRAITS, //v4.5

				'zuck.min.css' => public_path('js' . $DS . 'story') . $DS, //v4.5
				'snapssenger.css' => public_path('js' . $DS . 'story') . $DS, //v4.5
				'zuck.min.js' => public_path('js' . $DS . 'story') . $DS, //v4.5

				'fileuploader-post.js' => public_path('js' . $DS . 'fileuploader') . $DS, //v4.5
				'fileuploader-msg.js' => public_path('js' . $DS . 'fileuploader') . $DS, //v4.5
				'fileuploader-story-file.js' => public_path('js' . $DS . 'fileuploader') . $DS, //v4.5

				'western.png' => public_path('img' . $DS . 'payments') . $DS, //v4.5

				//============ CONTROLLERS =================//
				'RegisterController.php' => $CONTROLLERS_AUTH, //v4.5
				'AdminController.php' => $CONTROLLERS, //v4.5
				'HomeController.php' => $CONTROLLERS, //v4.5
				'StoriesController.php' => $CONTROLLERS, //v4.5
				'MessagesController.php' => $CONTROLLERS, //v4.5
				'UploadMediaController.php' => $CONTROLLERS, //v4.5
				'UploadMediaMessageController.php' => $CONTROLLERS, //v4.5
				'UploadMediaStoryController.php' => $CONTROLLERS, //v4.5
				'UserController.php' => $CONTROLLERS, //v4.5

				'User.php' => $MODELS, //v4.5

				'creators.blade.php' => $VIEWS_INDEX, //v4.5
				'creators-live.blade.php' => $VIEWS_INDEX, //v4.5
				'categories.blade.php' => $VIEWS_INDEX, //v4.5
				'home-session.blade.php' => $VIEWS_INDEX, //v4.5

				'account_verification.blade.php' => $VIEWS_EMAILS, //v4.5

				'css_general.blade.php' => $VIEWS_INCLUDES, //v4.5

				'payout_method.blade.php' => $VIEWS_USERS, //v4.5
				'edit_my_page.blade.php' => $VIEWS_USERS, //v4.5
			];

			$filesAdmin = [
				'members.blade.php' => $VIEWS_ADMIN, //v4.5
				'withdrawal-view.blade.php' => $VIEWS_ADMIN, //v4.5
				'storage.blade.php' => $VIEWS_ADMIN, //v4.5
				'transactions.blade.php' => $VIEWS_ADMIN, //v4.5
				'payments-settings.blade.php' => $VIEWS_ADMIN, //v4.5
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 4.5
		'document_id' => 'Document ID',
		'new_msg_from' => 'New message from',
);";
				$fileLangEN = 'resources/lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 4.5
	'document_id' => 'Documento de identificación',
	'new_msg_from' => 'Nuevo mensaje de',
);";
				$fileLangES = 'resources/lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn('admin_settings', 'payout_method_western_union')) {
					Schema::table('admin_settings', function ($table) {
						$table->enum('payout_method_western_union', ['on', 'off'])->default('off');
					});
				} // End Schema

				if (!Schema::hasColumn('users', 'document_id')) {
					Schema::table('users', function ($table) {
						$table->string('document_id', 100);
					});
				} // End Schema

				//============ End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.5 ----->>

		if ($version == '4.6') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'Kernel.php' => app_path('Console') . $DS, //v4.6

				'queue.php' => $CONFIG, //v4.6

				'Helper.php' => $APP, //v4.6

				'web.php' => $ROUTES, //v4.6

				'Role.php' => $MIDDLEWARE, //v4.6

				'Functions.php' => $TRAITS, //v4.6

				'payments-ppv.js' => $PUBLIC_JS, //v4.6
				'payment.js' => $PUBLIC_JS, //v4.6

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //v4.6
				'CCBillController.php' => $CONTROLLERS, //v4.6
				'PushNotificationsController.php' => $CONTROLLERS, //v4.6
				'UpdatesController.php' => $CONTROLLERS, //v4.6

				// Models
				'LoginSessions.php' => $MODELS, //v4.6

				'comments-live.blade.php' => $VIEWS_INCLUDES, //v4.6
				'modal-tip.blade.php' => $VIEWS_INCLUDES, //v4.6
				'modal-pay-live.blade.php' => $VIEWS_INCLUDES, //v4.6
				'modal-payperview.blade.php' => $VIEWS_INCLUDES, //v4.6
				'modal-taxes.blade.php' => $VIEWS_INCLUDES, //v4.6
				'messages-chat.blade.php' => $VIEWS_INCLUDES, //v4.6
				'media-messages.blade.php' => $VIEWS_INCLUDES, //v4.6
				'modal-live-stream.blade.php' => $VIEWS_INCLUDES, //v4.6
				'listing-creators.blade.php' => $VIEWS_INCLUDES, //v4.6
				'listing-creators-live.blade.php' => $VIEWS_INCLUDES, //v4.6
				'updates.blade.php' => $VIEWS_INCLUDES, //v4.6

				'profile.blade.php' => $VIEWS_USERS, //v4.6
				'wallet.blade.php' => $VIEWS_USERS, //v4.6

			];

			$filesAdmin = [
				'reports.blade.php' => $VIEWS_ADMIN, //v4.6
				'dashboard.blade.php' => $VIEWS_ADMIN, //v4.6
				'ccbill-settings.blade.php' => $VIEWS_ADMIN, //v4.6
				'members.blade.php' => $VIEWS_ADMIN, //v4.6
				'payments-settings.blade.php' => $VIEWS_ADMIN, //v4.6
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 4.6
		'error_active_captcha' => 'To activate the Captcha please add the credentials in Panel Admin > Google',
		'see_all_transactions' => 'See all transactions',
		'one_time_payments' => 'One-time payments',
		'money_format' => 'Money format',
);";
				$fileLangEN = 'resources/lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 4.6
	'error_active_captcha' => 'Para activar el Captcha por favor agregue las credenciales en Panel Admin > Google',
	'see_all_transactions' => 'Ver todas las transacciones',
	'one_time_payments' => 'Pagos únicos',
	'money_format' => 'Formato de dinero',
);";
				$fileLangES = 'resources/lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn('payment_gateways', 'ccbill_subacc_subscriptions')) {
					Schema::table('payment_gateways', function ($table) {
						$table->string('ccbill_subacc_subscriptions', 200);
					});
				} // End Schema

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.6 ----->>


		if ($version == '4.7') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES, //v4.7

				'Kernel.php' => app_path('Http') . $DS, //v4.7

				'Helper.php' => $APP, //v4.7

				'ViewServiceProvider.php' => $PROVIDERS, //v4.7

				'AdminSettingsMiddleware.php' => $MIDDLEWARE, //v4.7

				'Functions.php' => $TRAITS, //v4.7

				//============ PUBLIC ===============//
				'add-funds.js' => $PUBLIC_JS, //v4.7
				'app-functions.js' => $PUBLIC_JS, //v4.7
				'core.min.js' => $PUBLIC_JS, //v4.7
				'payments-ppv.js' => $PUBLIC_JS, //v4.7
				'payment.js' => $PUBLIC_JS, //v4.7
				'qrcode.min.js' =>  $PUBLIC_JS, //v4.7
				'paginator-creators.js' =>  $PUBLIC_JS, //v4.7

				'AgoraRTCSDK-v4.js' => public_path('js' . $DS . 'agora') . $DS, //v4.7

				'plyr.min.js' => public_path('js' . $DS . 'plyr') . $DS, //v4.7
				'plyr.css' => public_path('js' . $DS . 'plyr') . $DS, //v4.7
				'plyr.polyfilled.min.js' => public_path('js' . $DS . 'plyr') . $DS, //v4.7

				'coinbase.png' => public_path('img' . $DS . 'payments') . $DS, //v4.7
				'coinbase-white.png' => public_path('img' . $DS . 'payments') . $DS, //v4.7

				'nowpayments.png' => public_path('img' . $DS . 'payments') . $DS, //v4.7
				'nowpayments-white.png' => public_path('img' . $DS . 'payments') . $DS, //v4.7

				//============ CONTROLLERS =================//
				'AddFundsController.php' => $CONTROLLERS, //v4.7
				'AdminController.php' => $CONTROLLERS, //v4.7
				'CommentsController.php' => $CONTROLLERS, //v4.7
				'HomeController.php' => $CONTROLLERS, //v4.7
				'LangController.php' => $CONTROLLERS, //v4.7
				'LoginController.php' => $CONTROLLERS_AUTH, //v4.7
				'MessagesController.php' => $CONTROLLERS, //v4.7
				'TipController.php' => $CONTROLLERS, //v4.7
				'PayPerViewController.php' => $CONTROLLERS, //v4.7
				'RepliesController.php' => $CONTROLLERS, //v4.7
				'SubscriptionsController.php' => $CONTROLLERS, //v4.7
				'StripeController.php' => $CONTROLLERS, //v4.7
				'CCBillController.php' => $CONTROLLERS, //v4.7
				'PayPalController.php' => $CONTROLLERS, //v4.7
				'UpdatesController.php' => $CONTROLLERS, //v4.7
				'UserController.php' => $CONTROLLERS, //v4.7

				//============ Models ============//
				'Messages.php' => $MODELS, //v4.7
				'Conversations.php' => $MODELS, //v4.7
				'Comments.php' => $MODELS, //v4.7
				'Replies.php' => $MODELS, //v4.7
				'Stories.php' => $MODELS, //v4.7
				'Notifications.php' => $MODELS, //v4.7
				'Subscriptions.php' => $MODELS, //v4.7
				'Updates.php' => $MODELS, //v4.7
				'User.php' => $MODELS, //v4.7

				//============ Views ============//
				'app.blade.php' => $VIEWS_LAYOUTS, //v4.7

				'register.blade.php' => $VIEWS_AUTH, //v4.7
				'login.blade.php' => $VIEWS_AUTH, //v4.7

				'404.blade.php' => $VIEWS_ERRORS, //v4.7
				'503.blade.php' => $VIEWS_ERRORS, //v4.7

				'ajax-listing-creators.blade.php' => $VIEWS_INCLUDES, //v4.7
				'comments.blade.php' => $VIEWS_INCLUDES, //v4.7
				'css_general.blade.php' => $VIEWS_INCLUDES, //v4.7
				'cards-settings.blade.php' => $VIEWS_INCLUDES, //v4.7
				'explore_creators.blade.php' => $VIEWS_INCLUDES, //v4.7
				'footer.blade.php' => $VIEWS_INCLUDES, //v4.7
				'footer-tiny.blade.php' => $VIEWS_INCLUDES, //v4.7
				'modal-tip.blade.php' => $VIEWS_INCLUDES, //v4.7
				'modal-pay-live.blade.php' => $VIEWS_INCLUDES, //v4.7
				'modal-payperview.blade.php' => $VIEWS_INCLUDES, //v4.7
				'modal-taxes.blade.php' => $VIEWS_INCLUDES, //v4.7
				'messages-chat.blade.php' => $VIEWS_INCLUDES, //v4.7
				'messages-inbox.blade.php' => $VIEWS_INCLUDES, //v4.7
				'media-messages.blade.php' => $VIEWS_INCLUDES, //v4.7
				'menu-mobile.blade.php' => $VIEWS_INCLUDES, //v4.7
				'menu-filters-creators.blade.php' => $VIEWS_INCLUDES, //v4.7
				'media-post.blade.php' => $VIEWS_INCLUDES, //v4.7
				'modal-login.blade.php' => $VIEWS_INCLUDES, //v4.7
				'navbar.blade.php' => $VIEWS_INCLUDES, //v4.7
				'listing-creators.blade.php' => $VIEWS_INCLUDES, //v4.7
				'listing-creators-live.blade.php' => $VIEWS_INCLUDES, //v4.7
				'paginator-creators.blade.php' => $VIEWS_INCLUDES, //v4.7
				'replies.blade.php' => $VIEWS_INCLUDES, //v4.7
				'updates.blade.php' => $VIEWS_INCLUDES, //v4.7

				'categories.blade.php' => $VIEWS_INDEX, //v4.7
				'creators.blade.php' => $VIEWS_INDEX, //v4.7
				'explore.blade.php' => $VIEWS_INDEX, //v4.7
				'home-session.blade.php' => $VIEWS_INDEX, //v4.7
				'home-login.blade.php' => $VIEWS_INDEX, //v4.7

				'bookmarks.blade.php' => $VIEWS_USERS, //v4.7
				'dashboard.blade.php' => $VIEWS_USERS, //v4.7
				'notifications.blade.php' => $VIEWS_USERS, //v4.7
				'my_posts.blade.php' => $VIEWS_USERS, //v4.7
				'messages-show.blade.php' => $VIEWS_USERS, //v4.7
				'my_subscriptions.blade.php' => $VIEWS_USERS, //v4.7
				'my-purchases.blade.php' => $VIEWS_USERS, //v4.7
				'likes.blade.php' => $VIEWS_USERS, //v4.7
				'my_subscribers.blade.php' => $VIEWS_USERS, //v4.7
				'profile.blade.php' => $VIEWS_USERS, //v4.7
				'post-detail.blade.php' => $VIEWS_USERS, //v4.7
				'subscription.blade.php' => $VIEWS_USERS, //v4.7
				'wallet.blade.php' => $VIEWS_USERS, //v4.7
			];

			$filesAdmin = [
				'coinbase-settings.blade.php' => $VIEWS_ADMIN, //v4.7
				'ccbill-settings.blade.php' => $VIEWS_ADMIN, //v4.7
				'charts.blade.php' => $VIEWS_ADMIN, //v4.7
				'dashboard.blade.php' => $VIEWS_ADMIN, //v4.7
				'members.blade.php' => $VIEWS_ADMIN, //v4.7
				'nowpayments-settings.blade.php' => $VIEWS_ADMIN, //v4.7
				'layout.blade.php' => $VIEWS_ADMIN, //v4.7
				'pwa.blade.php' => $VIEWS_ADMIN, //v4.7
				'settings.blade.php' => $VIEWS_ADMIN, //v4.7
				'social-login.blade.php' => $VIEWS_ADMIN, //v4.7
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 4.7
		'total_revenue' => 'Total revenue',
		'paid_to_creators' => 'Paid to Creators',
		'paid_last_month' => 'Paid last month',
		'last_week' => 'Last week',
);";
				$fileLangEN = 'resources/lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 4.7
	'equivalent_money_format' => '1 crédito, punto o token equivale a',
	'total_revenue' => 'Ingresos totales',
	'paid_to_creators' => 'Pagado a Creadores',
	'paid_last_month' => 'Pagado el mes pasado',
	'last_week' => 'La semana pasada',
);";
				$fileLangES = 'resources/lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn('payment_gateways', 'project_id', 'project_secret', 'ccbill_datalink_username', 'ccbill_datalink_password', 'ccbill_skip_subaccount_cancellations')) {
					Schema::table('payment_gateways', function ($table) {
						$table->string('project_id', 255)->nullable();
						$table->string('project_secret', 255)->nullable();
						$table->string('ccbill_datalink_username', 255)->nullable();
						$table->string('ccbill_datalink_password', 255)->nullable();
						$table->boolean('ccbill_skip_subaccount_cancellations')->default(0);
					});
				}

				if (!Schema::hasColumn('subscriptions', 'payment_id',)) {
					Schema::table('subscriptions', function ($table) {
						$table->string('payment_id', 255)->index('payment_id')->nullable();
					});
				}

				if (Schema::hasTable('payment_gateways')) {
					\DB::table('payment_gateways')->insert(
						[
							[
								'name' => 'Coinbase',
								'type' => 'normal',
								'enabled' => '0',
								'fee' => 0.0,
								'fee_cents' => 0.00,
								'email' => '',
								'key' => '',
								'key_secret' => '',
								'recurrent' => 'no',
								'logo' => 'coinbase.png',
								'subscription' => 'no',
								'bank_info' => '',
								'token' => str_random(150),
							]
						]
					);
				}

				if (Schema::hasTable('payment_gateways')) {
					\DB::table('payment_gateways')->insert(
						[
							[
								'name' => 'NowPayments',
								'type' => 'normal',
								'enabled' => '0',
								'fee' => 0.0,
								'fee_cents' => 0.00,
								'email' => '',
								'key' => '',
								'key_secret' => '',
								'recurrent' => 'no',
								'logo' => 'nowpayments.png',
								'subscription' => 'no',
								'bank_info' => '',
								'token' => str_random(150),
							]
						]
					);
				}

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			// Index Table Messages
			DB::statement('ALTER TABLE `messages` DROP INDEX `from`');
			DB::statement('ALTER TABLE `messages` DROP INDEX `messages_mode_index`');

			DB::statement('ALTER TABLE `messages` ADD INDEX (`from_user_id`)');
			DB::statement('ALTER TABLE `messages` ADD INDEX (`to_user_id`)');
			DB::statement('ALTER TABLE `messages` ADD INDEX (`status`)');
			DB::statement('ALTER TABLE `messages` ADD INDEX (`mode`)');

			sleep(2);

			// Index Table Notifications
			DB::statement('ALTER TABLE `notifications` DROP INDEX `destination`');

			DB::statement('ALTER TABLE `notifications` ADD INDEX (`destination`)');
			DB::statement('ALTER TABLE `notifications` ADD INDEX (`author`)');
			DB::statement('ALTER TABLE `notifications` ADD INDEX (`target`)');
			DB::statement('ALTER TABLE `notifications` ADD INDEX (`status`)');

			sleep(2);

			// Index Table Updates
			DB::statement('ALTER TABLE `updates` DROP INDEX `author_id`');
			DB::statement('ALTER TABLE `updates` DROP INDEX `category_id`');
			DB::statement('ALTER TABLE `updates` DROP INDEX `updates_status_index`');
			DB::statement('ALTER TABLE `updates` DROP INDEX `updates_video_views_index`');

			DB::statement('ALTER TABLE `updates` ADD INDEX (`video_views`)');
			DB::statement('ALTER TABLE `updates` ADD INDEX (`status`)');
			DB::statement('ALTER TABLE `updates` ADD INDEX (`user_id`)');

			sleep(2);

			// Index Table Transactions
			DB::statement('ALTER TABLE `transactions` ADD INDEX (`earning_net_user`)');
			DB::statement('ALTER TABLE `transactions` ADD INDEX (`earning_net_admin`)');
			DB::statement('ALTER TABLE `transactions` ADD INDEX (`created_at`)');
			DB::statement('ALTER TABLE `transactions` ADD INDEX (`amount`)');
			DB::statement('ALTER TABLE `transactions` ADD INDEX (`approved`)');

			sleep(2);

			// Index Table Subscriptions
			DB::statement('ALTER TABLE `subscriptions` DROP INDEX `subscriptions_user_id_stripe_status_index`');

			DB::statement('ALTER TABLE `subscriptions` ADD INDEX (`user_id`)');
			DB::statement('ALTER TABLE `subscriptions` ADD INDEX (`stripe_status`)');
			DB::statement('ALTER TABLE `subscriptions` ADD INDEX (`stripe_id`)');
			DB::statement('ALTER TABLE `subscriptions` ADD INDEX (`stripe_price`)');
			DB::statement('ALTER TABLE `subscriptions` ADD INDEX (`free`)');

			sleep(2);

			// Index Table Users
			DB::statement('ALTER TABLE `users` DROP INDEX `username`');
			DB::statement('ALTER TABLE `users` DROP INDEX `username_2`');

			try {
				DB::statement('ALTER TABLE `users` DROP INDEX `users_permissions_index`');
			} catch (\Exception $e) {
				//Exception $e;
			}

			DB::statement('ALTER TABLE `users` ADD INDEX (`username`)');
			DB::statement('ALTER TABLE `users` ADD INDEX (`status`)');
			DB::statement('ALTER TABLE `users` ADD INDEX (`permissions`)');
			DB::statement('ALTER TABLE `users` ADD INDEX (`countries_id`)');

			sleep(2);

			return $upgradeDone;
		} //<<---- End Version 4.7 ----->>

		if ($version == '4.8') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES,

				'Flutterwave.php' => app_path('Library') . $DS,

				'AddFundsController.php' => $CONTROLLERS,
				'AdminController.php' => $CONTROLLERS,
				'CommentsController.php' => $CONTROLLERS,
				'PayPalController.php' => $CONTROLLERS,
				'RepliesController.php' => $CONTROLLERS,

				'Notifications.php' => $MODELS,

				'email.blade.php' => $VIEWS_AUTH_PASS,
				'reset.blade.php' => $VIEWS_AUTH_PASS,
				'register.blade.php' => $VIEWS_AUTH,
				'login.blade.php' => $VIEWS_AUTH,

				'css_general.blade.php' => $VIEWS_INCLUDES,
				'javascript_general.blade.php' => $VIEWS_INCLUDES,
				'modal-login.blade.php' => $VIEWS_INCLUDES,

				'contact.blade.php' => $VIEWS_INDEX,

				'messages-show.blade.php' => $VIEWS_USERS,
				'profile.blade.php' => $VIEWS_USERS,

			];

			$filesAdmin = [
				'google.blade.php' => $VIEWS_ADMIN,
				'pwa.blade.php' => $VIEWS_ADMIN,
				'maintenance_mode.blade.php' => $VIEWS_ADMIN,
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.8 ----->>

		if ($version == '4.9') {

			//============ Starting moving files...
			$oldVersion = '4.7';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT,
				'composer.lock' => $ROOT,

				'api.php' => $ROUTES,

				'AdminController.php' => $CONTROLLERS,
				'CommentsController.php' => $CONTROLLERS,
				'HomeController.php' => $CONTROLLERS,

				'EncodeVideoStory.php' => $JOBS,

				'css_general.blade.php' => $VIEWS_INCLUDES,

			];

			$filesAdmin = [
				'google.blade.php' => $VIEWS_ADMIN,
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				DB::table('reserved')->insert([
					['name' => 'lang']
				]);

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.9 ----->>

		if ($version == '5.0') {
			//============ Starting moving files...
			$oldVersion = '4.9';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'ffmpeg.php' => $ROOT, //5.0

				'web.php' => $ROUTES, //5.0

				'captcha.php' => $CONFIG, //5.0
				'livewire.php' => $CONFIG, //5.0
				'path.php' => $CONFIG, //5.0

				'Helper.php' => $APP, //5.0

				'ViewServiceProvider.php' => $PROVIDERS, //5.0

				'Kernel.php' => app_path('Console') . $DS, //5.0

				//======== JOBS ==========//
				'DeleteMedia.php' => $JOBS, //5.0
				'RebillCardinity.php' => $JOBS, //5.0
				'EncodeVideo.php' => $JOBS, //5.0
				'EncodeVideoMessages.php' => $JOBS, //5.0
				'EncodeVideoWelcomeMessage.php' => $JOBS, //5.0
				'EncodeVideoStory.php' => $JOBS, //5.0
				'ExpiredAdvertising.php' => $JOBS, //5.0
				'PostScheduled.php' => $JOBS, //5.0
				'SalesRefund.php' => $JOBS, //5.0
				'LiveStreamingPrivateExpired.php' => $JOBS, //5.0


				'MassMessagesListener.php' => $LISTENERS, //5.0

				//======= MODELS ============//
				'Advertising.php' => $MODELS, //5.0
				'AdClickImpression.php' => $MODELS, //5.0
				'Updates.php' => $MODELS, //5.0
				'Reports.php' => $MODELS, //5.0
				'LoginSessions.php' => $MODELS, //5.0
				'LiveStreamings.php' => $MODELS, //5.0
				'LiveStreamingPrivateRequest.php' => $MODELS, //5.0
				'Replies.php' => $MODELS, //5.0
				'Notifications.php' => $MODELS, //5.0
				'Transactions.php' => $MODELS, //5.0
				'Subscriptions.php' => $MODELS, //5.0
				'SubscriptionDeleted.php' => $MODELS, //5.0
				'MediaMessages.php' => $MODELS, //5.0
				'MediaWelcomeMessage.php' => $MODELS, //5.0

				'User.php' => $MODELS, //5.0

				'Functions.php' => $TRAITS, //5.0

				//======= CONTROLLERS ============//
				'RegisterController.php' => $CONTROLLERS_AUTH, //5.0

				'AdvertisingController.php' => $CONTROLLERS, //5.0
				'AddFundsController.php' => $CONTROLLERS, //5.0
				'AdminController.php' => $CONTROLLERS, //5.0
				'CCBillController.php' => $CONTROLLERS, //5.0
				'CoconutController.php' => $CONTROLLERS, //5.0
				'CardinityController.php' => $CONTROLLERS, //5.0
				'HomeController.php' => $CONTROLLERS, //5.0
				'LiveStreamingsController.php' => $CONTROLLERS, //5.0
				'LiveStreamingPrivateController.php' => $CONTROLLERS, //5.0
				'TipController.php' => $CONTROLLERS, //5.0
				'PayPerViewController.php' => $CONTROLLERS, //5.0
				'PaystackController.php' => $CONTROLLERS, //5.0
				'PayPalController.php' => $CONTROLLERS, //5.0
				'ProductsController.php' => $CONTROLLERS, //5.0
				'MessagesController.php' => $CONTROLLERS, //5.0
				'StripeWebHookController.php' => $CONTROLLERS, //5.0
				'StripeController.php' => $CONTROLLERS, //5.0
				'StoriesController.php' => $CONTROLLERS, //5.0
				'SocialAuthController.php' => $CONTROLLERS, //5.0
				'SubscriptionsController.php' => $CONTROLLERS, //5.0
				'UpdatesController.php' => $CONTROLLERS, //5.0
				'UploadMediaController.php' => $CONTROLLERS, //5.0
				'UploadMediaMessageController.php' => $CONTROLLERS, //5.0
				'UploadMediaWelcomeMessageController.php' => $CONTROLLERS, //5.0
				'UserController.php' => $CONTROLLERS, //5.0

				//======= MIDDLEWARE ============//
				'UserOnline.php' => $MIDDLEWARE, //5.0

				//======= PUBLIC ============//
				'AgoraRTCSDK-v4.js' => public_path('js' . $DS . 'agora') . $DS, //5.0

				'plyr.min.js' => public_path('js' . $DS . 'plyr') . $DS, //5.0
				'plyr.polyfilled.min.js' => public_path('js' . $DS . 'plyr') . $DS, //5.0

				'app-functions.js' => $PUBLIC_JS, //5.0
				'core.min.js' => $PUBLIC_JS, //5.0
				'live.js' => $PUBLIC_JS, //5.0
				'live-private-request.js' => $PUBLIC_JS, //5.0
				'upload-avatar-cover.js' => $PUBLIC_JS, //5.0

				'styles.css' => $PUBLIC_CSS, //5.0

				'admin-styles.css' => $PUBLIC_ADMIN, //5.0
				'bootstrap.min.css' => $PUBLIC_ADMIN, //5.0
				'bootstrap.min.css.map' => $PUBLIC_ADMIN, //5.0
				'bootstrap.min.js' => $PUBLIC_ADMIN, //5.0
				'bootstrap.bundle.min.js.map' => $PUBLIC_ADMIN, //5.0
				'bootstrap-icons.css' => $PUBLIC_CSS, //5.0
				'bootstrap-icons.woff' => $PUBLIC_FONTS, //5.0
				'bootstrap-icons.woff2' => $PUBLIC_FONTS, //5.0

				'watermak-video.png' => $PUBLIC_IMG, //5.0
				'plyr.svg' => $PUBLIC_IMG, //5.0
				'bitcoin.png' => public_path('img' . $DS . 'payments') . $DS, //5.0
				'bitcoin-white.png' => public_path('img' . $DS . 'payments') . $DS, //5.0

				'fileuploader-story-file.js' => public_path('js' . $DS . 'fileuploader') . $DS, //5.0
				'fileuploader-welcome-msg.js' => public_path('js' . $DS . 'fileuploader') . $DS, //5.0

				//============================= VIEWS ===============================//
				'app.blade.php' => $VIEWS_LAYOUTS, //5.0

				'register.blade.php' => $VIEWS_AUTH, //5.0
				'login.blade.php' => $VIEWS_AUTH, //5.0

				'email.blade.php' => $VIEWS_AUTH_PASS, //5.0
				'reset.blade.php' => $VIEWS_AUTH_PASS, //5.0

				'contact.blade.php' => $VIEWS_INDEX, //5.0
				'home.blade.php' => $VIEWS_INDEX, //5.0
				'home-login.blade.php' => $VIEWS_INDEX, //5.0
				'home-session.blade.php' => $VIEWS_INDEX, //5.0
				'blog.blade.php' => $VIEWS_INDEX, //5.0
				'post.blade.php' => $VIEWS_INDEX, //5.0


				'advertising.blade.php' => $VIEWS_INCLUDES, //5.0
				'alert-payment-disabled.blade.php' => $VIEWS_INCLUDES, //5.0
				'site-billing-info.blade.php' => $VIEWS_INCLUDES, //5.0
				'cards-settings.blade.php' => $VIEWS_INCLUDES, //5.0
				'css_general.blade.php' => $VIEWS_INCLUDES, //5.0
				'media-post.blade.php' => $VIEWS_INCLUDES, //5.0
				'media-messages.blade.php' => $VIEWS_INCLUDES, //5.0
				'messages-chat.blade.php' => $VIEWS_INCLUDES, //5.0
				'modal-custom-content.blade.php' => $VIEWS_INCLUDES, //5.0
				'modal-scheduled-posts.blade.php' => $VIEWS_INCLUDES, //5.0
				'modal-new-message.blade.php' => $VIEWS_INCLUDES, //5.0
				'modal-taxes.blade.php' => $VIEWS_INCLUDES, //5.0
				'modal-tip.blade.php' => $VIEWS_INCLUDES, //5.0
				'modal-pay-live.blade.php' => $VIEWS_INCLUDES, //5.0
				'modal-payperview.blade.php' => $VIEWS_INCLUDES, //5.0
				'modal-live-private-request.blade.php' => $VIEWS_INCLUDES, //5.0
				'modal-login.blade.php' => $VIEWS_INCLUDES, //5.0
				'modal-transfer.blade.php' => $VIEWS_INCLUDES, //5.0
				'navbar.blade.php' => $VIEWS_INCLUDES, //5.0
				'listing-creators.blade.php' => $VIEWS_INCLUDES, //5.0
				'listing-creators-live.blade.php' => $VIEWS_INCLUDES, //5.0
				'listing-explore-creators.blade.php' => $VIEWS_INCLUDES, //5.0
				'footer.blade.php' => $VIEWS_INCLUDES, //5.0
				'footer-tiny.blade.php' => $VIEWS_INCLUDES, //5.0
				'form-post.blade.php' => $VIEWS_INCLUDES, //5.0
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //5.0
				'updates.blade.php' => $VIEWS_INCLUDES, //5.0

				'listing-products.blade.php' => $VIEWS_SHOP, //5.0
				'modal-buy.blade.php' => $VIEWS_SHOP, //5.0
				'show.blade.php' => $VIEWS_SHOP, //5.0

				'404.blade.php' => $VIEWS_ERRORS, //5.0
				'503.blade.php' => $VIEWS_ERRORS, //5.0

				'conversations.blade.php' => $VIEWS_USERS, //5.0
				'delete_account.blade.php' => $VIEWS_USERS, //5.0
				'notifications.blade.php' => $VIEWS_USERS, //5.0
				'profile.blade.php' => $VIEWS_USERS, //5.0
				'live.blade.php' => $VIEWS_USERS, //5.0
				'live-streaming-private-requests.blade.php' => $VIEWS_USERS, //5.0
				'live-streaming-private-settings.blade.php' => $VIEWS_USERS, //5.0
				'live-streaming-private-requests-sent.blade.php' => $VIEWS_USERS, //5.0
				'privacy_security.blade.php' => $VIEWS_USERS, //5.0
				'payout_method.blade.php' => $VIEWS_USERS, //5.0
				'password.blade.php' => $VIEWS_USERS, //5.0
				'edit_my_page.blade.php' => $VIEWS_USERS, //5.0
				'my_posts.blade.php' => $VIEWS_USERS, //5.0
				'my_subscribers.blade.php' => $VIEWS_USERS, //5.0
				'my-sales.blade.php' => $VIEWS_USERS, //5.0
				'messages-show.blade.php' => $VIEWS_USERS, //5.0
				'messages.blade.php' => $VIEWS_USERS, //5.0
				'invoice.blade.php' => $VIEWS_USERS, //5.0
				'wallet.blade.php' => $VIEWS_USERS, //5.0
			];

			$filesAdmin = [
				'advertising.blade.php' => $VIEWS_ADMIN, //5.0
				'add-advertising.blade.php' => $VIEWS_ADMIN, //5.0
				'billing.blade.php' => $VIEWS_ADMIN, //5.0
				'cardinity-settings.blade.php' => $VIEWS_ADMIN, //5.0
				'edit-advertising.blade.php' => $VIEWS_ADMIN, //5.0
				'stories-posts.blade.php' => $VIEWS_ADMIN, //5.0
				'stories-settings.blade.php' => $VIEWS_ADMIN, //5.0
				'settings.blade.php' => $VIEWS_ADMIN, //5.0
				'google.blade.php' => $VIEWS_ADMIN, //5.0
				'stripe-settings.blade.php' => $VIEWS_ADMIN, //5.0
				'reports.blade.php' => $VIEWS_ADMIN, //5.0
				'members.blade.php' => $VIEWS_ADMIN, //5.0
				'edit-member.blade.php' => $VIEWS_ADMIN, //5.0
				'withdrawal-view.blade.php' => $VIEWS_ADMIN, //5.0
				'payments-settings.blade.php' => $VIEWS_ADMIN, //5.0
				'posts.blade.php' => $VIEWS_ADMIN, //5.0
				'products.blade.php' => $VIEWS_ADMIN, //5.0
				'profiles-social.blade.php' => $VIEWS_ADMIN, //5.0
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, //5.0
				'replies.blade.php' => $VIEWS_ADMIN, //5.0
				'messages.blade.php' => $VIEWS_ADMIN, //5.0
				'comments.blade.php' => $VIEWS_ADMIN, //5.0
				'layout.blade.php' => $VIEWS_ADMIN, //5.0
				'live_streaming.blade.php' => $VIEWS_ADMIN, //5.0
				'live-streaming-private-requests.blade.php' => $VIEWS_ADMIN, //5.0
				'theme.blade.php' => $VIEWS_ADMIN, //5.0
				'transactions.blade.php' => $VIEWS_ADMIN, //5.0
				'deposits-view.blade.php' => $VIEWS_ADMIN, //5.0
				'video_encoding.blade.php' => $VIEWS_ADMIN, //5.0
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy Folders

			// Actions
			$pathActions = $path . 'Actions';
			$appPathActions = app_path('Actions');
			$this->moveDirectory($pathActions, $appPathActions, $copy);

			// Services
			$pathServices = $path . 'Services';
			$appPathServices = app_path('Services');
			$this->moveDirectory($pathServices, $appPathServices, $copy);

			// splide
			$filePathFolderSplide = $path . 'splide';
			$pathFolderSplide = public_path('js' . $DS . 'splide') . $DS;
			$this->moveDirectory($filePathFolderSplide, $pathFolderSplide, $copy);

			// Enums
			$filePathFolderEnums = $path . 'Enums';
			$pathFolderEnums = app_path('Enums') . $DS;
			$this->moveDirectory($filePathFolderEnums, $pathFolderEnums, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 5.0
		'report_live_stream' => 'Report Live Stream',
	'started' => 'Started',
	'last_login' => 'Last login',
	'on' => 'on',
	'transfer_balance' => 'Transfer Balance',
	'transfer' => 'Transfer',
	'transfer_successful' => 'Transfer successful!',
	'sender' => 'Sender',
	'receiver' => 'Receiver',
	'bitcoin_wallet' => 'Bitcoin Wallet',
	'subscription_for_creator' => 'Subscription for creator',
	'comments_replies' => 'Comments and replies',
	'replies' => 'Replies',
	'watermak_video' => 'Video Watermark',
	'error_video_encoding_post' => 'There was an error encoding the video in your post',
	'error_video_encoding_message' => 'There was an error encoding the video in a private message',
	'error_video_encoding_story' => 'There was an error encoding the video in your story',
	'or_activate_coconut' => 'or activate the Coconut service',
	'encoding_method' => 'Encoding method',
	'allow_payments_alipay' => 'Allow payments with Alipay',
	'only_wallet' => 'Only in wallet',
	'ffprobe_path' => 'FFPROBE path',
	'verify_ffmpeg_path' => 'Verify FFMPEG path',
	'alert_paystack_delay' => '(Important: Your payment is being processed, in a few minutes you will be able to access the content)',
	'action_not_allowed' => 'Action not allowed',
	'allow_scheduled_posts' => 'Allow scheduled posts',
	'schedule' => 'Schedule',
	'scheduled' => 'Scheduled',
	'confirm' => 'Confirm',
	'date_schedule' => 'Will be published on',
	'at' => 'at',
	'error_post_schedule' => 'You can\'t schedule a post to send in the past.',
	'alert_post_schedule' => 'Your publication was successfully scheduled, you can see it in',
	'files_plural' => 'file|files',
	'advertising' => 'Advertising',
	'clicks' => 'Clicks',
	'impressions' => 'Impressions',
	'months' => 'month|months',
	'success_add_ad' => 'Ad successfully created!',
	'url_ad' => 'Url Ad',
	'update_expiration_date' => 'Update expiration date',
	'live_streaming_private' => 'Live streaming private',
	'accepted' => 'Accepted',
	'rejected' => 'Rejected',
	'price_per_minute' => 'Price per minute',
	'limit_live_streaming_private' => 'Maximum duration of private live stream',
	'allow_live_streaming_private' => 'Allow live streaming private',
	'price_live_streaming_private' => 'Price per minute of private live streaming',
	'live_streaming_private_requests' => 'Live Streaming Requests Private',
	'live_stream_private_settings' => 'Live Stream (Private) Settings',
	'subtitle_live_stream_private_settings' => 'Set private live stream price and status',
	'subtitle_live_streaming_private_requests' => 'All your received requests',
	'subtitle_live_streaming_private_requests_sent' => 'All your sent requests',
	'received' => 'Received',
	'sent' => 'Sent',
	'accept_request' => 'Accept request',
	'reject_request' => 'Reject request',
	'accept' => 'Accept',
	'requests_received' => 'Requests received',
	'requests_sent' => 'Requests sent',
	'offline' => 'Offline',
	'info_live_streaming_private_requests' => '* All Pending requests expire within 48 hours',
	'info_live_streaming_private_requests_send' => '* All Pending requests expire within 48 hours (Your money is refunded to your wallet)',
	'user_online_accept_request' => 'User must be online to accept the request',
	'join_private_live_stream_link' => 'Join the private live stream from this link :link',// IMPORTANT: Not remove :link
	'request_not_available' => 'The request is not available',
	'request_private_live_stream' => 'Request private live stream',
	'select_the_minutes' => 'Select the minutes...',
	'send_request' => 'Send request',
	'request_cannot_processed' => 'The request cannot be processed',
	'has_sent_private_live_stream_request' => 'has sent you a private live stream request',
	'go_received_requests' => 'Go to received requests',
	'conversations' => 'Conversations',
	'subtitle_conversations' => 'Setting up your conversations',
	'receive_private_messages' => 'Receive private messages',
	'send_welcome_message_new_subscribers' => 'Send welcome message to new subscribers',
	'price_welcome_message' => 'Welcome message price',
	'welcome_message_new_subs' => 'Welcome message to new subscribers',
	'add_file' => 'Add a file',
	'chat_unavailable' => 'Chat is unavailable',
	'info_video_encode_welcome_msg' => 'Important: If you add a video, activate the function of sending a welcome message when the video is encoded.',
	'error_video_encoding_welcome_msg' => 'There was an error encoding the video of your welcome message',
	'video_processed_successfully_welcome_message' => 'Your video has been processed successfully (Welcome Message)',
	'go_to_conversations' => 'Go to Conversations',
	'show_address_company_name' => 'Show address and company name on site',
	'in_currency' => 'in :currency_code currency',// IMPORTANT: Not remove :currency_code
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 5.0
	'report_live_stream' => 'Reportar Transmisión en vivo',
	'started' => 'Comenzó',
	'last_login' => 'Último inicio de sesión',
	'on' => 'en',
	'transfer_balance' => 'Transferir Balance',
	'transfer' => 'Transferir',
	'transfer_successful' => '¡Transferencia exitosa!',
	'sender' => 'Remitente',
	'receiver' => 'Receptor',
	'bitcoin_wallet' => 'Billetera Bitcoin',
	'subscription_for_creator' => 'Suscripción al creador',
	'comments_replies' => 'Comentarios y respuestas',
	'replies' => 'Respuestas',
	'watermak_video' => 'Marca de agua de Vídeo',
	'error_video_encoding_post' => 'Hubo un error en la codificación del video en tu publicación',
	'error_video_encoding_message' => 'Hubo un error en la codificación del video en un mensaje privado',
	'error_video_encoding_story' => 'Hubo un error en la codificación del video en tu historia',
	'or_activate_coconut' => 'o activar el servicio de Coconut',
	'encoding_method' => 'Método de codificación',
	'allow_payments_alipay' => 'Permitir pagos con Alipay',
	'only_wallet' => 'Sólo en billetera',
	'ffprobe_path' => 'Ruta FFPROBE',
	'verify_ffmpeg_path' => 'Verificar ruta FFMPEG',
	'alert_paystack_delay' => '(Importante: Su pago se está procesando, en unos minutos podrás tener acceso al contenido)',
	'action_not_allowed' => 'Acción no permitida',
	'allow_scheduled_posts' => 'Permitir posts programados',
	'schedule' => 'Programar',
	'scheduled' => 'Programado',
	'confirm' => 'Confirmar',
	'date_schedule' => 'Se publicará el',
	'at' => 'a la/s',
	'error_post_schedule' => 'No puedes programar una publicación para enviarla en el pasado.',
	'alert_post_schedule' => 'Tu publicación fue programada con éxito, puedes verla en',
	'files_plural' => 'archivo|archivos',
	'advertising' => 'Publicidad',
	'clicks' => 'Clics',
	'impressions' => 'Impresiones',
	'months' => 'mes|meses',
	'success_add_ad' => '¡Anuncio creado exitosamente!',
	'url_ad' => 'Url del anuncio',
	'update_expiration_date' => 'Actualizar fecha de vencimiento',
	'subscribed_to' => 'se suscribió a',
	'live_streaming_private' => 'Transmisión en vivo privada',
	'accepted' => 'Aceptado',
	'rejected' => 'Rechazado',
	'price_per_minute' => 'Precio por minuto',
	'limit_live_streaming_private' => 'Duración máxima de la transmisión en vivo privada',
	'allow_live_streaming_private' => 'Permitir transmisión en vivo privada',
	'price_live_streaming_private' => 'Precio por minuto de transmisión en vivo privada',
	'live_streaming_private_requests' => 'Solicitudes de transmisión en vivo privada',
	'live_stream_private_settings' => 'Configuración de transmisión en vivo privada',
	'subtitle_live_stream_private_settings' => 'Establecer el precio y el estado de la transmisión en vivo privada',
	'subtitle_live_streaming_private_requests' => 'Todas tus solicitudes recibidas',
	'subtitle_live_streaming_private_requests_sent' => 'Todas tus solicitudes enviadas',
	'received' => 'Recibidas',
	'sent' => 'Enviadas',
	'accept_request' => 'Aceptar solicitud',
	'reject_request' => 'Rechazar solicitud',
	'requests_received' => 'Solicitudes recibidas',
	'requests_sent' => 'Solicitudes enviadas',
	'offline' => 'Desconectado',
	'info_live_streaming_private_requests' => '* Todas las solicitudes Pendientes caducan en 48 horas',
	'info_live_streaming_private_requests_send' => '* Todas las solicitudes Pendientes caducan en 48 horas (Su dinero se reembolsa en su billetera)',
	'user_online_accept_request' => 'El usuario debe estar en línea para aceptar la solicitud',
	'join_private_live_stream_link' => 'Únete a la transmisión privada en vivo desde este enlace :link',// IMPORTANT: Not remove :link
	'request_not_available' => 'La solicitud no está disponible',
	'request_private_live_stream' => 'Solicitar transmisión en vivo privada',
	'select_the_minutes' => 'Selecciona los minutos...',
	'send_request' => 'Enviar solicitud',
	'request_cannot_processed' => 'La solicitud no se puede procesar',
	'has_sent_private_live_stream_request' => 'te ha enviado un solicitud de transmision en vivo privada',
	'go_received_requests' => 'Ir a solicitudes recibidas',
	'conversations' => 'Conversaciones',
	'subtitle_conversations' => 'Configuración de tus conversaciones',
	'receive_private_messages' => 'Recibir mensajes privados',
	'send_welcome_message_new_subscribers' => 'Enviar mensaje de bienvenida a nuevos suscriptores',
	'price_welcome_message' => 'Precio del mensaje de bienvenida',
	'welcome_message_new_subs' => 'Mensaje de bienvenida a nuevos suscriptores',
	'add_file' => 'Agrega un archivo',
	'chat_unavailable' => 'El chat no está disponible',
	'info_video_encode_welcome_msg' => 'Importante: Si agregas un video, activa la función de enviar un mensaje de bienvenida cuando el video esté codificado.',
	'error_video_encoding_welcome_msg' => 'Hubo un error al codificar el video de tu mensaje de bienvenida.',
	'video_processed_successfully_welcome_message' => 'Tu video ha sido procesado con éxito (Mensaje de bienvenida)',
	'go_to_conversations' => 'Ir a Conversaciones',
	'show_address_company_name' => 'Muestre la dirección y el nombre de la empresa en el sitio',
	'in_currency' => 'en moneda :currency_code',// IMPORTANT: Not remove :currency_code
	'video_encoding' => 'Codificación de videos',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				@file_put_contents(
					'.env',
					"\n\nFFMPEG_BINARIES=\nFFPROBE_BINARIES=",
					FILE_APPEND
				);

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn(
					'admin_settings',
					'payout_method_crypto',
					'threads',
					'watermak_video',
					'coconut_key',
					'encoding_method',
					'allow_scheduled_posts',
					'google_tag_manager_head',
					'google_tag_manager_body',
					'live_streaming_private',
					'live_streaming_minimum_price_private',
					'live_streaming_max_price_private',
					'limit_live_streaming_private',
					'show_address_company_footer'
				)) {
					Schema::table('admin_settings', function (Blueprint $table) {
						$table->enum('payout_method_crypto', ['on', 'off'])->default('off');
						$table->string('threads', 200);
						$table->string('watermak_video', 100);
						$table->string('coconut_key', 255);
						$table->string('encoding_method', 255)->default('ffmpeg');
						$table->boolean('allow_scheduled_posts')->nullable()->default(false);
						$table->text('google_tag_manager_head');
						$table->text('google_tag_manager_body');
						$table->enum('live_streaming_private', ['on', 'off'])->default('off');
						$table->decimal('live_streaming_minimum_price_private', 10, 2)->nullable();
						$table->decimal('live_streaming_max_price_private', 10, 2)->nullable();
						$table->unsignedInteger('limit_live_streaming_private');
						$table->boolean('show_address_company_footer')->nullable()->default(false);
					});

					$this->settings->update([
						'watermak_video' => 'watermak-video.png'
					]);
				}

				if (!Schema::hasColumn('payment_gateways', 'allow_payments_alipay')) {
					Schema::table('payment_gateways', function (Blueprint $table) {
						$table->boolean('allow_payments_alipay')->default(0);
					});
				}

				if (!Schema::hasColumn('reports', 'message')) {
					Schema::table('reports', function (Blueprint $table) {
						$table->text('message')->after('reason')->nullable();
					});
				}

				if (!Schema::hasColumn('media', 'job_id')) {
					Schema::table('media', function (Blueprint $table) {
						$table->string('job_id', 200)->index('job_id')->nullable();
					});
				}

				if (!Schema::hasColumn('media_messages', 'job_id')) {
					Schema::table('media_messages', function (Blueprint $table) {
						$table->string('job_id', 200)->index('job_id')->nullable();
					});
				}

				if (!Schema::hasColumn('media_stories', 'job_id')) {
					Schema::table('media_stories', function (Blueprint $table) {
						$table->string('job_id', 200)->index('job_id')->nullable();
					});
				}

				if (!Schema::hasColumn(
					'users',
					'crypto_wallet',
					'threads',
					'allow_live_streaming_private',
					'price_live_streaming_private',
					'allow_dm',
					'welcome_message_new_subs',
					'send_welcome_message',
					'price_welcome_message',
				)) {
					Schema::table('users', function (Blueprint $table) {
						$table->string('crypto_wallet', 255);
						$table->string('threads', 200);
						$table->enum('allow_live_streaming_private', ['on', 'off'])->default('off');
						$table->decimal('price_live_streaming_private', 10, 2)->nullable();
						$table->boolean('allow_dm')->default(true);
						$table->text('welcome_message_new_subs')->collation('utf8mb4_unicode_ci');
						$table->boolean('send_welcome_message')->default(false);
						$table->decimal('price_welcome_message', 10, 2)->nullable();
					});
				}

				if (!Schema::hasColumn('updates', 'ip', 'scheduled_date', 'schedule')) {
					Schema::table('updates', function (Blueprint $table) {
						$table->string('ip', 200)->nullable();
						$table->timestamp('scheduled_date')->nullable();
						$table->boolean('schedule')->default(false);
					});
				}

				\DB::table('payment_gateways')->insert(
					[
						[
							'name' => 'Cardinity',
							'type' => 'card',
							'enabled' => '0',
							'fee' => 0.0,
							'fee_cents' => 0.00,
							'email' => '',
							'key' => '',
							'key_secret' => '',
							'logo' => '',
							'bank_info' => '',
							'token' => str_random(150),
						]
					]
				);

				if (!Schema::hasTable('advertisings')) {
					Schema::create('advertisings', function (Blueprint $table) {
						$table->id();
						$table->string('title', 100);
						$table->string('description', 200);
						$table->string('url', 200);
						$table->string('image', 150);
						$table->unsignedInteger('clicks')->index('clicks');
						$table->unsignedInteger('impressions')->index('impressions');
						$table->boolean('status')->default(true);
						$table->timestamp('expired_at')->index('expired_at')->nullable();
						$table->timestamps();
					});
				}

				if (!Schema::hasColumn('purchases', 'expired_at')) {
					Schema::table('purchases', function (Blueprint $table) {
						$table->timestamp('expired_at')->index('expired_at')->nullable();
					});
				}

				if (!Schema::hasTable('ad_click_impressions')) {
					Schema::create('ad_click_impressions', function (Blueprint $table) {
						$table->id();
						$table->unsignedBigInteger('advertisings_id')->index('advertisings_id');
						$table->char('type', 20)->index('type');
						$table->string('ip', 100)->index('ip');
						$table->timestamps();
					});
				}

				if (!Schema::hasColumn(
					'live_streamings',
					'type',
					'buyer',
					'minutes',
					'joined_at',
					'ends_at',
					'token'
				)) {
					Schema::table('live_streamings', function (Blueprint $table) {
						$table->string('type', 100)->index('type')->default('normal')->after('id');
						$table->unsignedInteger('buyer_id')->index('buyer_id')->nullable()->after('user_id');
						$table->unsignedInteger('minutes')->index('minutes')->after('channel');
						$table->timestamp('joined_at')->index('joined_at')->nullable()->after('status');
						$table->timestamp('ends_at')->nullable()->after('updated_at');
						$table->string('token', 100)->index('token')->nullable();
					});
				}

				if (!Schema::hasTable('live_streaming_private_requests')) {
					Schema::create('live_streaming_private_requests', function (Blueprint $table) {
						$table->id();
						$table->unsignedBigInteger('transaction_id')->index('transaction_id');
						$table->unsignedInteger('user_id')->index('user_id');
						$table->unsignedInteger('creator_id')->index('creator_id');
						$table->unsignedInteger('minutes')->index('minutes');
						$table->boolean('status')->default(false);
						$table->timestamps();
					});
				}

				DB::statement('ALTER TABLE `live_streamings` ADD INDEX(`updated_at`)');


				Schema::table('transactions', function (Blueprint $table) {
					$table->string('approved', 50)->change();
				});

				if (!Schema::hasTable('media_welcome_messages')) {
					Schema::create('media_welcome_messages', function (Blueprint $table) {
						$table->id();
						$table->unsignedInteger('creator_id')->index();
						$table->string('type', 100)->index();
						$table->string('file');
						$table->string('width', 5)->nullable();
						$table->string('height', 5)->nullable();
						$table->string('video_poster')->nullable();
						$table->string('duration_video', 50)->nullable();
						$table->string('quality_video', 20)->nullable();
						$table->string('file_name');
						$table->string('file_size');
						$table->string('file_size_bytes')->nullable();
						$table->string('mime_type')->nullable();
						$table->enum('encoded', ['yes', 'no'])->default('no')->index();
						$table->string('token')->index();
						$table->string('status')->default('active');
						$table->string('job_id', 200)->index('job_id')->nullable();
						$table->timestamps();
					});
				}

				if (!Schema::hasTable('subscription_deleteds')) {
					Schema::create('subscription_deleteds', function (Blueprint $table) {
						$table->id();
						$table->unsignedBigInteger('creator_id')->index();
						$table->unsignedBigInteger('user_id')->index();
						$table->timestamps();
					});
				}


				if (!Schema::hasColumn('subscriptions', 'creator_id')) {
					Schema::table('subscriptions', function (Blueprint $table) {
						$table->unsignedBigInteger('creator_id')->after('id')->index('creator_id');
					});
				}

				if (Schema::hasColumn('subscriptions', 'creator_id')) {
					$subscriptions = Subscriptions::select('id', 'stripe_price')->get();

					foreach ($subscriptions->chunk(1000) as $chunk) {
						$cases = [];
						$ids = [];
						$params = [];

						foreach ($chunk as $subscription) {
							$explode = explode('_', $subscription->stripe_price);
							$creatorId = $explode[1];

							$cases[] = "WHEN {$subscription->id} then ?";
							$params[] = $creatorId;
							$ids[] = $subscription->id;
						}

						$ids = implode(',', $ids);
						$cases = implode(' ', $cases);

						if (!empty($ids)) {
							DB::update("UPDATE subscriptions SET `creator_id` = CASE `id` {$cases} END WHERE `id` in ({$ids})", $params);
						}
					}
				}

				//============ Ends Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.0 ----->>


		if ($version == '5.1') {

			//============ Starting moving files...
			$oldVersion = '5.0';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'filesystems.php' => $CONFIG, //5.1

				'app-functions.js' => $PUBLIC_JS, //5.1

				'CoconutVideoService.php' => $SERVICES, //5.1

				'RebillWallet.php' => $JOBS, //5.1
				'RebillCardinity.php' => $JOBS, //5.1

				'Subscriptions.php' => $MODELS, //5.1

				'AdminController.php' => $CONTROLLERS, //5.1
				'AdvertisingController.php' => $CONTROLLERS, //5.1
				'CardinityController.php' => $CONTROLLERS, //5.1
				'CCBillController.php' => $CONTROLLERS, //5.1
				'PayPalController.php' => $CONTROLLERS, //5.1
				'PaystackController.php' => $CONTROLLERS, //5.1
				'StripeController.php' => $CONTROLLERS, //5.1
				'UserController.php' => $CONTROLLERS, //5.1

				'my_subscribers.blade.php' => $VIEWS_USERS, //5.1
				'my_subscriptions.blade.php' => $VIEWS_USERS, //5.1

				'show.blade.php' => $VIEWS_SHOP, //5.1
			];

			$filesAdmin = [
				'advertising.blade.php' => $VIEWS_ADMIN, //5.1
				'dashboard.blade.php' => $VIEWS_ADMIN, //5.1
				'video_encoding.blade.php' => $VIEWS_ADMIN, //5.1
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 5.1
		'watermark_position' => 'Watermark position',
		'position_center' => 'Center',
		'position_bottomleft' => 'Bottom left',
		'position_bottomright' => 'Bottom right',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 5.1
	'watermark_position' => 'Posición de la marca de agua',
	'position_center' => 'Centro',
	'position_bottomleft' => 'Abajo a la izquierda',
	'position_bottomright' => 'Abajo a la derecha',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				if (!Schema::hasColumn('admin_settings', 'watermark_position')) {
					Schema::table('admin_settings', function (Blueprint $table) {
						$table->string('watermark_position', 50)->default('center');
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.1 ----->>


		if ($version == '5.2') {

			//============ Starting moving files...
			$oldVersion = '5.1';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'Helper.php' => $APP, //5.2

				'currencies.php' => $CONFIG, //5.2

				'CoconutVideoService.php' => $SERVICES, //5.2

				'ExpiredAdvertising.php' => $JOBS, //5.2
				'PostScheduled.php' => $JOBS, //5.2
				'RebillCardinity.php' => $JOBS, //5.2

				'AdvertisingController.php' => $CONTROLLERS, //5.2
				'AdminController.php' => $CONTROLLERS, //5.2
				'AddFundsController.php' => $CONTROLLERS, //5.2
				'StripeController.php' => $CONTROLLERS, //5.2
				'StripeWebHookController.php' => $CONTROLLERS, //5.2
				'TipController.php' => $CONTROLLERS, //5.2
				'PayPerViewController.php' => $CONTROLLERS, //5.2
				'UpdatesController.php' => $CONTROLLERS, //5.2
				'UserController.php' => $CONTROLLERS, //5.2

				'css_general.blade.php' => $VIEWS_INCLUDES, //5.2
				'form-post.blade.php' => $VIEWS_INCLUDES, //5.2

				'wallet.blade.php' => $VIEWS_USERS, //5.2
			];

			$filesAdmin = [
				'advertising.blade.php' => $VIEWS_ADMIN,
				'video_encoding.blade.php' => $VIEWS_ADMIN, //5.2
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				if (!Schema::hasColumn('admin_settings', 'coconut_region')) {
					Schema::table('admin_settings', function (Blueprint $table) {
						$table->string('coconut_region', 100)->default('Virginia');
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.2 ----->>

		if ($version == '5.3') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'filesystems.php' => $CONFIG, //v5.3

				'app-functions.js' => $PUBLIC_JS, //5.3

				'AgoraRTCSDK-v4.js' => public_path('js' . $DS . 'agora') . $DS, //5.3

				'admin-functions.js' => $PUBLIC_ADMIN, //5.3
				'bootstrap.min.css' => $PUBLIC_ADMIN, //5.3
				'bootstrap.min.css.map' => $PUBLIC_ADMIN, //5.3
				'bootstrap.min.js' => $PUBLIC_ADMIN, //5.3
				'bootstrap.bundle.min.js.map' => $PUBLIC_ADMIN, //5.3

				'web.php' => $ROUTES, //5.3

				'Functions.php' => $TRAITS, //5.3

				'Helper.php' => $APP, //5.3

				'StoryViews.php' => $MODELS, //5.3
				'Comments.php' => $MODELS, //5.3

				'RebillWallet.php' => $JOBS, //5.3
				'SalesRefund.php' => $JOBS, //5.3

				'CoconutUploadService.php' => $SERVICES, //5.3
				'CoconutVideoService.php' => $SERVICES, //5.3

				'UserDelete.php' => $TRAITS, //5.3

				'RegisterController.php' => $CONTROLLERS_AUTH, //5.3

				'AddFundsController.php' => $CONTROLLERS, //5.3
				'AdminController.php' => $CONTROLLERS, //5.3
				'AdvertisingController.php' => $CONTROLLERS, //5.3
				'CCBillController.php' => $CONTROLLERS, //5.3
				'HomeController.php' => $CONTROLLERS, //5.3
				'StorageCoconutController.php' => $CONTROLLERS, //5.3
				'PaystackController.php' => $CONTROLLERS, //5.3
				'StoriesController.php' => $CONTROLLERS, //5.3
				'SubscriptionsController.php' => $CONTROLLERS, //5.3
				'UserController.php' => $CONTROLLERS, //5.3
				'UpdatesController.php' => $CONTROLLERS, //5.3
				'WebhookCoconutController.php' => $CONTROLLERS, //5.3

				'profile.blade.php' => $VIEWS_USERS, //5.3

				'css_general.blade.php' => $VIEWS_INCLUDES, //5.3
				'modal-scheduled-posts.blade.php' => $VIEWS_INCLUDES, //5.3
			];

			$filesAdmin = [
				'dashboard.blade.php' => $VIEWS_ADMIN, //5.3
				'deposits.blade.php' => $VIEWS_ADMIN, //5.3
				'comments.blade.php' => $VIEWS_ADMIN, //5.3
				'replies.blade.php' => $VIEWS_ADMIN, //5.3
				'storage.blade.php' => $VIEWS_ADMIN, //5.3
				'posts.blade.php' => $VIEWS_ADMIN, //5.3
				'video_encoding.blade.php' => $VIEWS_ADMIN, //5.3
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.3 ----->>

		if ($version == '5.4') {
			//============ Starting moving files...
			$oldVersion = '5.3';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'Functions.php' => $TRAITS, //5.4

				'User.php' => $MODELS, //5.4

				'AdminController.php' => $CONTROLLERS, //5.4
				'UserController.php' => $CONTROLLERS, //5.4
				'RegisterController.php' => $CONTROLLERS_AUTH, //5.4
				'HomeController.php' => $CONTROLLERS, //5.4

				'profile.blade.php' => $VIEWS_USERS, //5.4
				'payout_method.blade.php' => $VIEWS_USERS, //5.4

				'modal-scheduled-posts.blade.php' => $VIEWS_INCLUDES, //5.4
				'listing-explore-creators.blade.php' => $VIEWS_INCLUDES, //5.4
			];

			$filesAdmin = [
				'payments-settings.blade.php' => $VIEWS_ADMIN, //5.4
				'withdrawal-view.blade.php' => $VIEWS_ADMIN, //5.4
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Copy Folder Rules
			$pathRules = $path . 'Rules';
			$appPathRules = app_path('Rules');
			$this->moveDirectory($pathRules, $appPathRules, $copy);

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 5.4
		'only_payments_for_argentina' => 'Only payments for Argentina',
		'server_time_zone' => 'Server time zone',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 5.4
	'only_payments_for_argentina' => 'Solamente pagos para Argentina',
	'server_time_zone' => 'Zona horaria del servidor',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn('admin_settings', 'payout_method_mercadopago')) {
					Schema::table('admin_settings', function ($table) {
						$table->enum('payout_method_mercadopago', ['on', 'off'])->default('off');
					});
				} // End Schema

				if (!Schema::hasColumn('users', 'alias_mp', 'cvu')) {
					Schema::table('users', function ($table) {
						$table->string('alias_mp', 100);
						$table->string('cvu', 100);
					});
				} // End Schema

				//============ End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.4 ----->>

		if ($version == '5.5') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'Helper.php' => $APP, //5.5
				'web.php' => $ROUTES, //5.5


				'Functions.php' => $TRAITS, //5.5

				'filesystems.php' => $CONFIG, //5.5

				'User.php' => $MODELS, //5.5
				'Gift.php' => $MODELS, //5.5
				'Media.php' => $MODELS, //5.5
				'Messages.php' => $MODELS, //5.5
				'MediaMessages.php' => $MODELS, //5.5
				'Notifications.php' => $MODELS, //5.5
				'Transactions.php' => $MODELS, //5.5
				'LiveComments.php' => $MODELS, //5.5


				'CoconutVideoService.php' => $SERVICES, //5.5
				'SocialAccountService.php' => $SERVICES, //5.5

				'MassMessagesEvent.php' => $EVENTS, //5.5

				'EncodeVideo.php' => $JOBS, //5.5
				'RebillWallet.php' => $JOBS, //5.5

				'MassMessagesListener.php' => $LISTENERS, //5.5

				'ViewServiceProvider.php' => $PROVIDERS, //5.5

				'RegisterController.php' => $CONTROLLERS_AUTH, //5.5
				'LoginController.php' => $CONTROLLERS_AUTH, //5.5

				'AdminController.php' => $CONTROLLERS, //5.5
				'AddFundsController.php' => $CONTROLLERS, //5.5
				'CCBillController.php' => $CONTROLLERS, //5.5
				'GiftController.php' => $CONTROLLERS, //5.5
				'TipController.php' => $CONTROLLERS, //5.5
				'MessagesController.php' => $CONTROLLERS, //5.5
				'LiveStreamingsController.php' => $CONTROLLERS, //5.5
				'LiveStreamingPrivateController.php' => $CONTROLLERS, //5.5
				'HomeController.php' => $CONTROLLERS, //5.5
				'WebhookCoconutController.php' => $CONTROLLERS, //5.5
				'UserController.php' => $CONTROLLERS, //5.5
				'UploadMediaController.php' => $CONTROLLERS, //5.5
				'UpdatesController.php' => $CONTROLLERS, //5.5

				'TempEmail.php' => $RULES, //5.5

				'admin-styles.css' => $PUBLIC_ADMIN, //5.5
				'admin-functions.js' => $PUBLIC_ADMIN, //5.5

				'styles.css' => $PUBLIC_CSS, //5.5
				'epub-viewer.css' => $PUBLIC_CSS, //5.5

				'app-functions.js' => $PUBLIC_JS, //5.5
				'jszip.min.js' => $PUBLIC_JS, //5.5
				'epub.min.js' => $PUBLIC_JS, //5.5
				'messages.js' => $PUBLIC_JS, //5.5
				'send-gift.js' => $PUBLIC_JS, //5.5
				'live.js' => $PUBLIC_JS, //5.5

				'fileuploader-post.js' => public_path('js' . $DS . 'fileuploader') . $DS, //5.5
				'jquery.fileuploader-theme-thumbnails.css' => public_path('js' . $DS . 'fileuploader') . $DS, //5.5

				'binance.png' => public_path('img' . $DS . 'payments') . $DS, //5.5

				'app.blade.php' => $VIEWS_LAYOUTS, //5.5

				'referrals.blade.php' => $VIEWS_USERS, //5.5
				'epub-viewer.blade.php' => $VIEWS_USERS, //5.5
				'wallet.blade.php' => $VIEWS_USERS, //5.5
				'messages-show.blade.php' => $VIEWS_USERS, //5.5
				'my_posts.blade.php' => $VIEWS_USERS, //5.5
				'edit_my_page.blade.php' => $VIEWS_USERS, //5.5
				'edit-update.blade.php' => $VIEWS_USERS, //5.5
				'privacy_security.blade.php' => $VIEWS_USERS, //5.5
				'profile.blade.php' => $VIEWS_USERS, //5.5
				'notifications.blade.php' => $VIEWS_USERS, //5.5
				'invoice.blade.php' => $VIEWS_USERS, //5.5
				'my_payments.blade.php' => $VIEWS_USERS, //5.5
				'live.blade.php' => $VIEWS_USERS, //5.5

				'css_general.blade.php' => $VIEWS_INCLUDES, //5.5
				'comments-live.blade.php' => $VIEWS_INCLUDES, //5.5
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //5.5
				'messages-inbox.blade.php' => $VIEWS_INCLUDES, //5.5
				'messages-chat.blade.php' => $VIEWS_INCLUDES, //5.5
				'media-messages.blade.php' => $VIEWS_INCLUDES, //5.5
				'form-post.blade.php' => $VIEWS_INCLUDES, //5.5
				'footer.blade.php' => $VIEWS_INCLUDES, //5.5
				'modal-new-message.blade.php' => $VIEWS_INCLUDES, //5.5
				'modal-gifts.blade.php' => $VIEWS_INCLUDES, //5.5
				'menu-sidebar-home.blade.php' => $VIEWS_INCLUDES, //5.5
				'menu-mobile.blade.php' => $VIEWS_INCLUDES, //5.5
				'explore_creators.blade.php' => $VIEWS_INCLUDES, //5.5
				'updates.blade.php' => $VIEWS_INCLUDES, //5.5
				'navbar.blade.php' => $VIEWS_INCLUDES, //5.5

				'profile-disabled.blade.php' => $VIEWS_ERRORS, //5.5

				'home.blade.php' => $VIEWS_INDEX, //5.5
				'home-login.blade.php' => $VIEWS_INDEX, //5.5
				'creators.blade.php' => $VIEWS_INDEX, //5.5	
				'categories.blade.php' => $VIEWS_INDEX, //5.5	
				'creators-live.blade.php' => $VIEWS_INDEX, //5.5	
			];

			$filesAdmin = [
				'add-gift.blade.php' => $VIEWS_ADMIN, //5.5
				'binance-settings.blade.php' => $VIEWS_ADMIN, //5.5
				'edit-gift.blade.php' => $VIEWS_ADMIN, //5.5
				'gifts.blade.php' => $VIEWS_ADMIN, //5.5
				'settings.blade.php' => $VIEWS_ADMIN, //5.5
				'members.blade.php' => $VIEWS_ADMIN, //5.5
				'edit-member.blade.php' => $VIEWS_ADMIN, //5.5
				'posts.blade.php' => $VIEWS_ADMIN, //5.5
				'withdrawal-view.blade.php' => $VIEWS_ADMIN, //5.5
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, //5.5
				'layout.blade.php' => $VIEWS_ADMIN, //5.5
				'storage.blade.php' => $VIEWS_ADMIN, //5.5
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 5.5
		'default_theme' => 'Default theme',
		'deactivate_your_account' => 'Deactivate your account',
		'deactivate_your_account_alert' => 'Your data will not be deleted and will be activated when you log in again.',
		'yes_confirm_deactivate' => 'Yes, deactivate',
		'allow_creators_deactivate_profile' => 'Allow creators to deactivate their profile',
		'profile_deactivated_error' => 'This profile has been deactivated',
		'profile_disabled' => 'Profile disabled',
		'user_unavailable' => 'User unavailable',
		'allow_epub_files' => 'Allow upload EPUB files (Post and Messages)',
		'upload_epub_file' => 'Upload EPUB file',
		'invalid_format' => 'Invalid format, only :formats are allowed.', // Not remove/edit :formats
		'view_online' => 'View online',
		'epub' => 'Epub',
		'allow_sending_gifts' => 'Allow sending Gifts',
		'gifts' => 'Gifts',
		'gift' => 'Gift',
		'send_a_gift' => 'Send a gift',
		'send_gift' => 'Send gift',
		'send_gift_desc_payment' => 'The amount will be deducted from your Wallet',
		'gift_sent_success' => 'Gift sent successfully',
		'has_sent_you_gift' => 'has sent you a gift',
		'sent_a_gift' => 'sent a gift',
		'sent_a_gift_for' => 'Sent a gift for',
		'please_select_gift' => 'Please select a gift',
		'currency' => 'Currency',
		'disable_free_post' => 'Disable free post option',
		'disable_explore_section' => 'Disable Explore section',
		'disable_creators_section' => 'Disable Creators section',
		'gift_for' => 'Gift for',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 5.5
	'default_theme' => 'Tema predeterminado',
	'deactivate_your_account' => 'Desactive su cuenta',
	'deactivate_your_account_alert' => 'Sus datos no se eliminarán y se activarán cuando inicie sesión nuevamente.',
	'yes_confirm_deactivate' => 'Sí, desactivar',
	'allow_creators_deactivate_profile' => 'Permitir a creadores desactivar su perfil',
	'profile_deactivated_error' => 'Este perfil ha sido desactivado',
	'profile_disabled' => 'Perfil desactivado',
	'user_unavailable' => 'Usuario no disponible',
	'allow_epub_files' => 'Permitir subir archivos EPUB (Post y Mensajes)',
	'upload_epub_file' => 'Subir archivo EPUB',
	'invalid_format' => 'Formato no válido, solo se permiten :formats', // Not remove/edit :formats
	'view_online' => 'Ver en línea',
	'epub' => 'Epub',
	'allow_sending_gifts' => 'Permitir enviar Regalos',
	'gifts' => 'Regalos',
	'gift' => 'Regalo',
	'send_a_gift' => 'Enviar un regalo',
	'send_gift' => 'Enviar regalo',
	'send_gift_desc_payment' => 'El monto se deducirá de tu Billetera',
	'gift_sent_success' => 'Regalo enviado con éxito',
	'has_sent_you_gift' => 'te ha enviado un regalo',
	'sent_a_gift' => 'envió un regalo',
	'sent_a_gift_for' => 'Envió un regalo por',
	'please_select_gift' => 'Por favor selecciona un regalo',
	'currency' => 'Moneda',
	'disable_free_post' => 'Deshabilitar la opción de publicación gratuita',
	'disable_explore_section' => 'Deshabilitar la sección Explorar',
	'disable_creators_section' => 'Deshabilitar la sección Creadores',
	'gift_for' => 'Regalo por',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				file_put_contents(
					'.env',
					"\n\nPUSHR_ACCESS_KEY=\nPUSHR_SECRET_KEY=\nPUSHR_BUCKET=\nPUSHR_URL=\nPUSHR_ENDPOINT=\n",
					FILE_APPEND
				);

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn(
					'admin_settings',
					'theme',
					'allow_creators_deactivate_profile',
					'allow_epub_files',
					'gifts',
					'disable_free_post',
					'disable_explore_section',
					'disable_creators_section',
				)) {
					Schema::table('admin_settings', function ($table) {
						$table->enum('theme', ['light', 'dark'])->default('light');
						$table->boolean('allow_creators_deactivate_profile')->default(false);
						$table->boolean('allow_epub_files')->default(false);
						$table->boolean('gifts')->default(false);
						$table->boolean('disable_free_post')->default(false);
						$table->boolean('disable_explore_section')->default(false);
						$table->boolean('disable_creators_section')->default(false);
					});
				} // End Schema

				Schema::table('users', function (Blueprint $table) {
					$table->string('status', 200)->change();
				});

				Schema::table('live_comments', function (Blueprint $table) {
					$table->decimal('tip_amount', 10, 2)->change();
				});

				Schema::table('live_comments', function (Blueprint $table) {
					$table->renameColumn('tip_amount', 'earnings');
				});

				if (!Schema::hasColumn('transactions', 'gift_id')) {
					Schema::table('transactions', function (Blueprint $table) {
						$table->unsignedInteger('gift_id')->nullable()->index('gift_id');
					});
				}

				if (!Schema::hasColumn('messages', 'gift_id', 'gift_amount')) {
					Schema::table('messages', function (Blueprint $table) {
						$table->unsignedInteger('gift_id')->nullable()->index('gift_id');
						$table->decimal('gift_amount', 10, 2);
					});
				}

				if (!Schema::hasColumn('live_comments', 'gift_id')) {
					Schema::table('live_comments', function (Blueprint $table) {
						$table->unsignedInteger('gift_id')->nullable()->index('gift_id');
					});
				}

				if (!Schema::hasColumn('media', 'bytes', 'mime')) {
					Schema::table('media', function ($table) {
						$table->string('bytes', 255)->nullable()->after('file_size');
						$table->string('mime', 255)->nullable()->after('bytes');
					});
				} // End Schema

				if (!Schema::hasColumn('updates', 'editing', 'can_media_edit')) {
					Schema::table('updates', function (Blueprint $table) {
						$table->boolean('editing')->default(false);
						$table->boolean('can_media_edit')->index('can_media_edit')->default(false);
					});
				} // End Schema

				if (!Schema::hasTable('gifts')) {
					Schema::create('gifts', function (Blueprint $table) {
						$table->id();
						$table->string('image', 50);
						$table->decimal('price', 10, 2);
						$table->boolean('status')->default(true)->index('status');
						$table->timestamps();
					});
				}

				if (!Schema::hasColumn('payment_gateways', 'crypto_currency')) {
					Schema::table('payment_gateways', function (Blueprint $table) {
						$table->string('crypto_currency', 50)->nullable();
					});
				}

				if (Schema::hasTable('payment_gateways')) {
					\DB::table('payment_gateways')->insert(
						[
							[
								'name' => 'Binance',
								'type' => 'normal',
								'enabled' => '0',
								'fee' => 0.0,
								'fee_cents' => 0.00,
								'email' => '',
								'key' => '',
								'key_secret' => '',
								'recurrent' => 'no',
								'logo' => 'binance.png',
								'subscription' => 'no',
								'bank_info' => '',
								'token' => str_random(150),
							]
						]
					);
				}

				DB::statement('ALTER TABLE `subscriptions` ADD INDEX (`rebill_wallet`)');
				DB::statement('ALTER TABLE `subscriptions` ADD INDEX (`cancelled`)');

				//============ End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.5 ----->>

		if ($version == '5.6') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'messages.js' => $PUBLIC_JS, //5.6

				'ViewServiceProvider.php' => $PROVIDERS, //5.6

				'AddFundsController.php' => $CONTROLLERS, //5.6
				'AdminController.php' => $CONTROLLERS, //5.6
				'HomeController.php' => $CONTROLLERS, //5.6
				'InstallScriptController.php' => $CONTROLLERS, //5.6
				'UpdatesController.php' => $CONTROLLERS, //5.6
				'UploadMediaController.php' => $CONTROLLERS, //5.6
				'UserController.php' => $CONTROLLERS, //5.6

				'messages-show.blade.php' => $VIEWS_USERS, //5.6
				'edit-update.blade.php' => $VIEWS_USERS, //5.6
				'invoice-deposits.blade.php' => $VIEWS_USERS, //5.6
				'invoice.blade.php' => $VIEWS_USERS, //5.6
				'edit_my_page.blade.php' => $VIEWS_USERS, //5.6
				'profile.blade.php' => $VIEWS_USERS, //5.6
				'wallet.blade.php' => $VIEWS_USERS, //5.6

				'modal-tip.blade.php' => $VIEWS_INCLUDES, //5.6
			];


			$filesAdmin = [
				'billing.blade.php' => $VIEWS_ADMIN, //5.6
				'messages.blade.php' => $VIEWS_ADMIN, //5.6
				'storage.blade.php' => $VIEWS_ADMIN, //5.6
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 5.6
		'increase_decrease_amount' => 'to increase/decrease amount',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 5.6
	'increase_decrease_amount' => 'para incrementar / disminuir monto',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				if (!Schema::hasColumn('admin_settings', 'phone')) {
					Schema::table('admin_settings', function ($table) {
						$table->string('phone', 100)->nullable();
					});
				}

				if (!Schema::hasColumn('users', 'kick')) {
					Schema::table('users', function (Blueprint $table) {
						$table->string('kick', 200);
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.6 ----->>

		if ($version == '5.7') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //5.7
				'composer.lock' => $ROOT, //5.7

				'core.min.js' => $PUBLIC_JS, //5.7
				'messages.js' => $PUBLIC_JS, //5.7

				'Helper.php' => $APP, //5.7

				'RebillCardinity.php' => $JOBS, //5.7
				'EncodeVideo.php' => $JOBS, //5.7
				'EncodeVideoMessages.php' => $JOBS, //5.7
				'EncodeVideoWelcomeMessage.php' => $JOBS, //5.7
				'EncodeVideoStory.php' => $JOBS, //5.7

				'SocialAccountService.php' => $SERVICES, //5.7

				'AdminController.php' => $CONTROLLERS, //5.7
				'MessagesController.php' => $CONTROLLERS, //5.7
				'UpdatesController.php' => $CONTROLLERS, //5.7
				'SocialAuthController.php' => $CONTROLLERS, //5.7
				'UserController.php' => $CONTROLLERS, //5.7

				'login.blade.php' => $VIEWS_AUTH, //5.7
				'register.blade.php' => $VIEWS_AUTH, //5.7

				'verify_account.blade.php' => $VIEWS_USERS, //5.7
				'referrals.blade.php' => $VIEWS_USERS, //5.7

				'javascript_general.blade.php' => $VIEWS_INCLUDES, //5.7
				'media-messages.blade.php' => $VIEWS_INCLUDES, //5.7
				'modal-login.blade.php' => $VIEWS_INCLUDES, //5.7
				'cards-settings.blade.php' => $VIEWS_INCLUDES, //5.7
				'navbar.blade.php' => $VIEWS_INCLUDES, //5.7
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.7 ----->>

		if ($version == '5.8') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //5.8
				'composer.lock' => $ROOT, //5.8

				'UserDelete.php' => $TRAITS, //5.8

				'EncodeVideo.php' => $JOBS, //5.8
				'EncodeVideoMessages.php' => $JOBS, //5.8
				'EncodeVideoWelcomeMessage.php' => $JOBS, //5.8
				'EncodeVideoStory.php' => $JOBS, //5.8

				'PrivateContent.php' => $MIDDLEWARE, //5.8

				'AdminController.php' => $CONTROLLERS, //5.8
				'ProductsController.php' => $CONTROLLERS, //5.8
				'UserController.php' => $CONTROLLERS, //5.8

				'login.blade.php' => $VIEWS_AUTH, //5.8

				'dashboard.blade.php' => $VIEWS_USERS, //5.8

				'home-login.blade.php' => $VIEWS_INDEX, //5.8
			];

			$filesAdmin = [
				'comments.blade.php' => $VIEWS_ADMIN, //5.8
				'messages.blade.php' => $VIEWS_ADMIN, //5.8
				'replies.blade.php' => $VIEWS_ADMIN, //5.8
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.8 ----->>

		if ($version == '5.9') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //5.9
				'composer.lock' => $ROOT, //5.9

				'web.php' => $ROUTES, //5.9

				'Functions.php' => $TRAITS, //5.9

				'Helper.php' => $APP, //5.9

				'UserDelete.php' => $TRAITS, //5.9

				'User.php' => $MODELS, //5.9
				'Updates.php' => $MODELS, //5.9

				'Kernel.php' => app_path('Console') . $DS, //5.9

				'NewMessage.php' => $NOTIFICATIONS, //5.9

				'DeleteInactiveUsers.php' => $JOBS, //5.9

				'AdminController.php' => $CONTROLLERS, //5.9
				'MessagesController.php' => $CONTROLLERS, //5.9
				'HomeController.php' => $CONTROLLERS, //5.9
				'UserController.php' => $CONTROLLERS, //5.9
				'PagesController.php' => $CONTROLLERS, //5.9
				'SubscriptionsController.php' => $CONTROLLERS, //5.9
				'ProductsController.php' => $CONTROLLERS, //5.9
				'LiveStreamingsController.php' => $CONTROLLERS, //5.9
				'GiftController.php' => $CONTROLLERS, //5.9

				'app.blade.php' => $VIEWS_LAYOUTS, //5.9

				'notifications.blade.php' => $VIEWS_USERS, //5.9
				'messages-show.blade.php' => $VIEWS_USERS, //5.9
				'privacy_security.blade.php' => $VIEWS_USERS, //5.9

				'css_general.blade.php' => $VIEWS_INCLUDES, //5.9
				'comments-live.blade.php' => $VIEWS_INCLUDES, //5.9
				'messages-chat.blade.php' => $VIEWS_INCLUDES, //5.9
				'messages-inbox.blade.php' => $VIEWS_INCLUDES, //5.9
				'media-messages.blade.php' => $VIEWS_INCLUDES, //5.9
				'modal-gifts.blade.php' => $VIEWS_INCLUDES, //5.9
				'menu-sidebar-home.blade.php' => $VIEWS_INCLUDES, //5.9
				'navbar.blade.php' => $VIEWS_INCLUDES, //5.9
				'updates.blade.php' => $VIEWS_INCLUDES, //5.9

				'explore.blade.php' => $VIEWS_INDEX, //5.9

				'add-physical-product.blade.php' => $VIEWS_SHOP, //5.9
				'add-product.blade.php' => $VIEWS_SHOP, //5.9
				'show.blade.php' => $VIEWS_SHOP, //5.9
				'modal-edit.blade.php' => $VIEWS_SHOP, //5.9
				'listing-products.blade.php' => $VIEWS_SHOP, //5.9

				'AgoraRTCSDK-v4.js' => public_path('js' . $DS . 'agora') . $DS, //5.9
				'fileuploader-shop-file.js' => public_path('js' . $DS . 'fileuploader') . $DS, //5.9
				'fileuploader-welcome-msg.js' => public_path('js' . $DS . 'fileuploader') . $DS, //5.9
				'fileuploader-story-file.js' => public_path('js' . $DS . 'fileuploader') . $DS, //5.9

				'homepage-explore.jpg' => $PUBLIC_IMG, //5.9
			];

			$filesAdmin = [
				'edit-page.blade.php' => $VIEWS_ADMIN, //5.9
				'ffmpeg.blade.php' => $VIEWS_ADMIN, //5.9
				'shop.blade.php' => $VIEWS_ADMIN, //5.9
				'video_encoding.blade.php' => $VIEWS_ADMIN, //5.9
				'maintenance_mode.blade.php' => $VIEWS_ADMIN, //5.9
				'layout.blade.php' => $VIEWS_ADMIN, //5.9
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, //5.9
				'settings.blade.php' => $VIEWS_ADMIN, //5.9
				'theme.blade.php' => $VIEWS_ADMIN, //5.9
				'edit-member.blade.php' => $VIEWS_ADMIN, //5.9
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 5.9
		'go_to_messages' => 'Go to Messages',
		'someone_sent_message' => 'Someone sent me a message',
		'choose_file_to_upload' => 'Choose file to upload',
		'browse_file' => 'Browse file',
		'one_file_chosen' => 'file chosen',
		'more_files_chosen' => 'files chosen',
		'size' => 'Size',
		'dimensions' => 'Dimensions',
		'duration' => 'Duration',
		'crop' => 'Crop',
		'rotate' => 'Rotate',
		'drop_files' => 'Drop the files here to Upload',
		'paste_files' => 'Pasting a file, click here to cancel.',
		'removeConfirmation' => 'Are you sure you want to remove this file?',
		'filesLimit' => 'You can only upload',
		'filesType' => 'Only these extensions are allowed:',
		'fileSize' => 'is too large, please choose a file up to',
		'filesSizeAll' => 'The chosen files are too large! Please select files up to',
		'fileName' => 'A file with the same name is already selected.',
		'remoteFile' => 'Remote files are not allowed.',
		'folderUpload' => 'Folders are not allowed.',
		'allow_free_items_shop' => 'Allow selling free items (digital products only)',
		'allow_external_links_shop' => 'Allow external links for purchases (physical products only)',
		'free_item_shop' => 'Set the price to 0 if you want the item to be free',
		'downloads' => 'Downloads',
		'external_link_buy' => 'External link to buy',
		'info_external_link' => 'You will be redirected to an external URL',
		'error_file_not_found' => 'File not found',
		'users_can_delete_messages' => 'Users can delete messages and conversations',
		'delete_old_users_inactive' => 'Delete inactive users via cron job',
		'write_short_message' => 'Write a short message (optional)',
		'allow_comments' => 'Allow comments on your posts',
		'comments_disabled' => 'Comments have been disabled',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 5.9
	'go_to_messages' => 'Ir a Mensajes',
	'someone_sent_message' => 'Alguien me ha enviado un mensaje',
	'choose_file_to_upload' => 'Selecciona el archivo para subir',
	'browse_file' => 'Examinar archivo',
	'one_file_chosen' => 'archivo seleccionado',
	'more_files_chosen' => 'archivos seleccionados',
	'size' => 'Tamaño',
	'dimensions' => 'Dimensiones',
	'duration' => 'Duración',
	'crop' => 'Cortar',
	'rotate' => 'Rotar',
	'drop_files' => 'Suelta los archivos aquí para subir',
	'paste_files' => 'Pegando un archivo, haz clic aquí para cancelar.',
	'removeConfirmation' => '¿Está seguro de que desea eliminar este archivo?',
	'filesLimit' => 'Solo se puede subir',
	'filesType' => 'Solo se permiten estas extensiones:',
	'fileSize' => 'es demasiado grande, por favor, elija un archivo menor a',
	'filesSizeAll' => '¡Los archivos elegidos son demasiado grandes! Por favor, selecciona archivos de hasta',
	'fileName' => 'Un archivo con el mismo nombre ya ha sido seleccionado.',
	'remoteFile' => 'No se permiten archivos remotos.',
	'folderUpload' => 'No se permiten carpetas.',
	'allow_free_items_shop' => 'Permitir vender Items gratis (solo productos digitales)',
	'allow_external_links_shop' => 'Permitir enlaces externos para compras (solo en productos físicos)',
	'free_item_shop'	=> 'Coloque el precio en 0 si desea que el Item sea gratuito',
	'downloads' => 'Descargas',
	'external_link_buy' => 'Enlace externo para comprar',
	'info_external_link' => 'Serás redireccionado a una URL externa',
	'error_file_not_found' => 'Archivo no encontrado',
	'users_can_delete_messages' => 'Usuarios pueden eliminar mensajes y conversaciones',
	'delete_old_users_inactive' => 'Eliminar usuarios inactivos vía cron job',
	'write_short_message' => 'Escribe un mensaje corto (opcional)',
	'allow_comments' => 'Permitir comentarios en tus publicaciones',
	'comments_disabled' => 'Los comentarios han sido desactivados',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				if (!Schema::hasColumn('users', 'email_new_message', 'allow_comments')) {
					Schema::table('users', function (Blueprint $table) {
						$table->boolean('email_new_message')->default(1);
						$table->boolean('allow_comments')->default(1);
					});
				}

				if (!Schema::hasColumn(
					'admin_settings',
					'allow_free_items_shop',
					'allow_external_links_shop',
					'users_can_delete_messages',
					'delete_old_users_inactive',
				)) {
					Schema::table('admin_settings', function (Blueprint $table) {
						$table->boolean('allow_free_items_shop')->default(0);
						$table->boolean('allow_external_links_shop')->default(0);
						$table->boolean('users_can_delete_messages')->default(1);
						$table->boolean('delete_old_users_inactive')->default(0);
					});
				}

				if (!Schema::hasColumn('products', 'downloads', 'external_link')) {
					Schema::table('products', function (Blueprint $table) {
						$table->unsignedInteger('downloads');
						$table->text('external_link')->nullable();
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");

				File::delete('ffmpeg.php');
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.9 ----->>

		if ($version == '6.0') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //6.0
				'composer.lock' => $ROOT, //6.0

				'web.php' => $ROUTES, //6.0

				'image.php' => $CONFIG, //6.0
				'filesystems.php' => $CONFIG, //6.0
				'path.php' => $CONFIG, //6.0
				'broadcasting.php' => $CONFIG, //6.0

				'class.fileuploader.php' => $LIBRARY, //6.0

				'VideoCallStatus.php' => $ENUMS, //6.0

				'Functions.php' => $TRAITS, //6.0
				'HasRandomSelection.php' => $TRAITS, //6.0

				'AppServiceProvider.php' => $PROVIDERS, //6.0

				'Handler.php' => $EXCEPTIONS, //6.0

				'AdminSettings.php' => $MODELS, //6.0
				'VerificationRequests.php' => $MODELS, //6.0
				'Transactions.php' => $MODELS, //6.0
				'Withdrawals.php' => $MODELS, //6.0
				'Notifications.php' => $MODELS, //6.0
				'Messages.php' => $MODELS, //6.0
				'User.php' => $MODELS, //6.0
				'VideoCall.php' => $MODELS, //6.0
				'Reel.php' => $MODELS, //6.0
				'MediaReel.php' => $MODELS, //6.0
				'ReelReply.php' => $MODELS, //6.0
				'CommentReel.php' => $MODELS, //6.0
				'CommentLikeReel.php' => $MODELS, //6.0
				'LikeReel.php' => $MODELS, //6.0
				'LiveStreamings.php' => $MODELS, //6.0

				'DeleteMedia.php' => $JOBS, //6.0
				'DeleteInactiveUsers.php' => $JOBS, //6.0
				'PostScheduled.php' => $JOBS, //6.0
				'RebillCardinity.php' => $JOBS, //6.0
				'EncodeVideoReel.php' => $JOBS, //6.0
				'EncodeVideoStory.php' => $JOBS, //6.0	

				'AdminController.php' => $CONTROLLERS, //6.0
				'AdvertisingController.php' => $CONTROLLERS, //6.0
				'CCBillController.php' => $CONTROLLERS, //6.0
				'AddFundsController.php' => $CONTROLLERS, //6.0
				'HomeController.php' => $CONTROLLERS, //6.0
				'MessagesController.php' => $CONTROLLERS, //6.0
				'ProductsController.php' => $CONTROLLERS, //6.0
				'UpdatesController.php' => $CONTROLLERS, //6.0
				'UserController.php' => $CONTROLLERS, //6.0
				'WebhookCoconutController.php' => $CONTROLLERS, //6.0
				'StripeConnectController.php' => $CONTROLLERS, //6.0
				'StripeController.php' => $CONTROLLERS, //6.0
				'UploadMediaController.php' => $CONTROLLERS, //6.0	
				'UploadMediaMessageController.php' => $CONTROLLERS, //6.0	
				'UploadMediaStoryController.php' => $CONTROLLERS, //6.0	
				'UploadMediaFileShopController.php' => $CONTROLLERS, //6.0	
				'UploadMediaPreviewShopController.php' => $CONTROLLERS, //6.0
				'VideoCallController.php' => $CONTROLLERS, //6.0
				'TimerVideoCallController.php' => $CONTROLLERS, //6.0
				'TwoFactorAuthController.php' => $CONTROLLERS, //6.0
				'ReelPreviewController.php' => $CONTROLLERS, //6.0
				'ReelController.php' => $CONTROLLERS, //6.0
				'LikeReelController.php' => $CONTROLLERS, //6.0
				'StorageCoconutController.php' => $CONTROLLERS, //6.0
				'UploadMediaReelController.php' => $CONTROLLERS, //6.0
				'CommentReelController.php' => $CONTROLLERS, //6.0
				'StoriesController.php' => $CONTROLLERS, //6.0
				'UploadMediaWelcomeMessageController.php' => $CONTROLLERS, //6.0

				'CoconutUploadService.php' => $SERVICES, //6.0
				'CoconutVideoService.php' => $SERVICES, //6.0


				// Views
				'app.blade.php' => $VIEWS_LAYOUTS, //6.0

				'edit_my_page.blade.php' => $VIEWS_USERS, //6.0
				'profile.blade.php' => $VIEWS_USERS, //6.0
				'wallet.blade.php' => $VIEWS_USERS, //6.0
				'messages-show.blade.php' => $VIEWS_USERS, //6.0
				'referrals.blade.php' => $VIEWS_USERS, //6.0
				'verify_account.blade.php' => $VIEWS_USERS, //6.0
				'notifications.blade.php' => $VIEWS_USERS, //6.0
				'withdrawals.blade.php' => $VIEWS_USERS, //6.0
				'video-call-live.blade.php' => $VIEWS_USERS, //6.0
				'video-call-settings.blade.php' => $VIEWS_USERS, //6.0
				'my-reels.blade.php' => $VIEWS_USERS, //6.0
				'my_posts.blade.php' => $VIEWS_USERS, //6.0
				'create-reel.blade.php' => $VIEWS_USERS, //6.0

				'css_general.blade.php' => $VIEWS_INCLUDES, //6.0
				'css_admin.blade.php' => $VIEWS_INCLUDES, //6.0
				'sidebar-messages-inbox.blade.php' => $VIEWS_INCLUDES, //6.0
				'modal-logout-devices.blade.php' => $VIEWS_INCLUDES, //6.0
				'cards-settings.blade.php' => $VIEWS_INCLUDES, //6.0
				'modal-video-call.blade.php' => $VIEWS_INCLUDES, //6.0
				'modal-video-call-incoming.blade.php' => $VIEWS_INCLUDES, //6.0
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //6.0
				'modal-2fa.blade.php' => $VIEWS_INCLUDES, //6.0
				'form-post.blade.php' => $VIEWS_INCLUDES, //6.0
				'menu-sidebar-home.blade.php' => $VIEWS_INCLUDES, //6.0
				'navbar.blade.php' => $VIEWS_INCLUDES, //6.0
				'menu-mobile.blade.php' => $VIEWS_INCLUDES, //6.0
				'updates.blade.php' => $VIEWS_INCLUDES, //6.0

				'account_verification.blade.php' => $VIEWS_EMAILS, //6.0
				'transfer_verification.blade.php' => $VIEWS_EMAILS, //6.0

				'explore.blade.php' => $VIEWS_INDEX, //6.0
				'home-session.blade.php' => $VIEWS_INDEX, //6.0

				'withdrawal-reject.blade.php' => $VIEWS_EMAILS, //6.0

				// Public
				'app-functions.js' => $PUBLIC_JS, //6.0	
				'admin-functions.js' => $PUBLIC_ADMIN, //6.0
				'messages.js' => $PUBLIC_JS, //6.0
				'AgoraRTCSDK-v4.js' => public_path('js' . $DS . 'agora') . $DS, //6.0
				'fileuploader-reel-file.js' => public_path('js' . $DS . 'fileuploader') . $DS, //6.0
				'calls.js' => $PUBLIC_JS, //6.0
				'jszip.min.js' => $PUBLIC_JS, //6.0
				'epub.min.js' => $PUBLIC_JS, //6.0

				'styles.css' => $PUBLIC_CSS, //6.0
				'bootstrap.min.css' => $PUBLIC_BOOTSTRAP_CSS, //6.0
				'bootstrap.min.css.map' => $PUBLIC_BOOTSTRAP_CSS, //6.0
				'bootstrap.rtl.min.css' => $PUBLIC_BOOTSTRAP_CSS, //6.0
				'bootstrap.rtl.min.css.map' => $PUBLIC_BOOTSTRAP_CSS, //6.0
				'bootstrap.bundle.min.js' => $PUBLIC_BOOTSTRAP_JS, //6.0
				'bootstrap.bundle.min.js.map' => $PUBLIC_BOOTSTRAP_JS, //6.0

			];

			$filesAdmin = [
				'edit-member.blade.php' => $VIEWS_ADMIN, //6.0
				'mercadopago-settings.blade.php' => $VIEWS_ADMIN, //6.0
				'payments-settings.blade.php' => $VIEWS_ADMIN, //6.0
				'verification.blade.php' => $VIEWS_ADMIN, //6.0
				'transactions.blade.php' => $VIEWS_ADMIN, //6.0
				'withdrawals.blade.php' => $VIEWS_ADMIN, //6.0
				'withdrawal-view.blade.php' => $VIEWS_ADMIN, //6.0
				'reports.blade.php' => $VIEWS_ADMIN, //6.0
				'settings.blade.php' => $VIEWS_ADMIN, //6.0
				'pwa.blade.php' => $VIEWS_ADMIN, //6.0
				'theme.blade.php' => $VIEWS_ADMIN, //6.0
				'maintenance_mode.blade.php' => $VIEWS_ADMIN, //6.0
				'storage.blade.php' => $VIEWS_ADMIN, //6.0
				'cron-job.blade.php' => $VIEWS_ADMIN, //6.0
				'layout.blade.php' => $VIEWS_ADMIN, //6.0
				'blog.blade.php' => $VIEWS_ADMIN, //6.0
				'deposits-view.blade.php' => $VIEWS_ADMIN, //6.0
				'posts.blade.php' => $VIEWS_ADMIN, //6.0
				'members.blade.php' => $VIEWS_ADMIN, //6.0
				'comments.blade.php' => $VIEWS_ADMIN, //6.0
				'replies.blade.php' => $VIEWS_ADMIN, //6.0
				'countries.blade.php' => $VIEWS_ADMIN, //6.0
				'states.blade.php' => $VIEWS_ADMIN, //6.0
				'products.blade.php' => $VIEWS_ADMIN, //6.0
				'stories-posts.blade.php' => $VIEWS_ADMIN, //6.0
				'pages.blade.php' => $VIEWS_ADMIN, //6.0
				'languages.blade.php' => $VIEWS_ADMIN, //6.0
				'profiles-social.blade.php' => $VIEWS_ADMIN, //6.0
				'social-login.blade.php' => $VIEWS_ADMIN, //6.0
				'live_streaming.blade.php' => $VIEWS_ADMIN, //6.0
				'websockets.blade.php' => $VIEWS_ADMIN, //6.0
				'video-call-settings.blade.php' => $VIEWS_ADMIN, //6.0
				'reels.blade.php' => $VIEWS_ADMIN, //6.0
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, //6.0
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Reels
			$filePathFolderReels = $path . 'reels';
			$pathFolderReels = public_path('js' . $DS . 'reels') . $DS;

			$this->moveDirectory($filePathFolderReels, $pathFolderReels, $copy);

			// Reels Resources
			$filePathFolderReelsResources = $pathAdmin . 'reels';
			$pathFolderReelsResources = resource_path('views' . $DS . 'reels') . $DS;

			$this->moveDirectory($filePathFolderReelsResources, $pathFolderReelsResources, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				file_put_contents(
					'.env',
					"\n\nFORCE_HTTPS=0\n",
					FILE_APPEND
				);

				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 6.0
		'delete_old_messages' => 'Delete old messages older than 6 months via Cron Job',
		'service_fee' => 'Service fee',
		'new_transaction_from' => 'New Transaction from @:user',// Not remove :user
		'info_reject' => 'Please write the reason for the rejection.',
		'withdrawal_reject' => 'Your withdrawal request could not be processed',
		'theme_color_pwa' => 'Theme Color PWA',
		'maintenance_mode_alert' => 'IMPORTANT: Close other windows on your website before activating maintenance mode.',
		'alert_maintenance_mode' => 'Important: At the time of activation only have one tab of your website open.',
		'force_https' => 'Force HTTPS',
		'custom_profit_referral' => 'Custom referral profit percentage',
		'copy' => 'Copy',
		'copied' => 'Copied!',
		'command' => 'Command',
		'command_only_cpanel' => 'Command only for cPanel',
		'only_super_admin_command' => 'Only the Super Admin can see the command',
		'account_verification_automatically' => 'Your account will be verified automatically.',
		'create_reel' => 'Create a Reel',
		'create_reel_subtitle' => 'Create a reel and interact with your subscribers.',
		'video_calls' => 'Video Calls',
		'video_call_max_price' => 'Maximum price of video call',
		'video_call_min_price' => 'Minimum price of video call',
		'video_call_max_duration' => 'Maximum duration of video call',
		'video_call_settings' => 'Video Call Settings',
		'subtitle_video_call_settings' => 'Set the price and duration of video calls',
		'price_video_call' => 'Price of video call',
		'unanswered' => 'Unanswered',
		'new_video_call' => 'New video call',
		'is_calling' => 'is calling you...',
		'per_minutes' => 'per :minutes minutes', // Important: Not remove :minutes
		'video_call_notice' => 'If you accept the call, the amount will be deducted from your Wallet.',
		'calling' => 'Calling',
		'please_wait_answer' => 'Please wait for the response.',
		'rejected_video_call' => 'Video call rejected',
		'rejected_video_call_info' => 'Your video call has been rejected',
		'video_call_unanswered' => 'Unanswered video call',
		'video_call_unanswered_info' => 'Your video call has not been answered',
		'video_call_not_found' => 'Video call not found',
		'm' => 'm',// Minutes abbreviation
		'connecting' => 'Connecting...',
		'error_twofa_too_many_attempts' => 'Too many attempts. Please try again later.',
		'allow_reels' => 'Allow Reels',
		'please_select_video' => 'Please select a video',
		'available_only_for_subscribers' => ' Available only for subscribers',
		'available_everyone' => 'Available for everyone',
		'reel_successfully_posted' => 'Your reel is now available!',
		'go_to_my_reels' => 'Go to my reels',
		'reels' => 'Reels',
		'reel' => 'Reel',
		'my_reels' => 'My Reels',
		'my_reels_subtitle' => 'All the reels you\'ve created',
		'error_video_encoding_reel' => 'There was an error encoding the video of your reel',
		'not_reels_created' => 'You have not created any reel so far',
		'stripe_connect_setup_incomplete' => 'Your Stripe Connect account is not fully set up. Please complete the setup process.',
		'error_proportion_video_reel' => 'The video must be in vertical format (9:16 aspect ratio recommended)',
		'liked_your_reel' => 'liked your reel',
		'go_to_reel' => 'Go to reel',
		'commented_your_reel' => 'commented your reel',
		'has_mentioned_you_reel' => 'has mentioned you in a reel',
		'liked_your_comment_reel' => 'liked your comment in a reel',
		'hide_replies' => 'Hide replies',
		'resource_not_found' => 'Resource not found',
		'someone_commented_reel' => 'Someone commented my reel',
		'someone_liked_reel' => 'Someone liked my reel',
		'edit_reel' => 'Edit reel',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 6.0
	'delete_old_messages' => 'Eliminar mensajes antiguos con más de 6 meses de antigüedad via Cron Job',
	'service_fee' => 'Tarifa de servicio',
	'new_transaction_from' => 'Nueva transacción de @:user',// Not remove :user
	'info_reject' => 'Por favor escribe el motivo del rechazo.',
	'withdrawal_reject' => 'Tu solicitud de retiro no pudo ser procesada',
	'theme_color_pwa' => 'Color del tema PWA',
	'maintenance_mode_alert' => '¡IMPORTANTE: Cierre todas las ventanas de tu sitio web antes de activar el modo de mantenimiento.',
	'alert_maintenance_mode' => '¡Importante: Al momento de la activación solo tenga abierta una pestaña de su sitio web.',
	'force_https' => 'Forzar HTTPS',
	'custom_profit_referral' => 'Porcentaje de ganancia por referido personalizado',
	'copy' => 'Copiar',
	'copied' => '¡Copiado!',
	'command' => 'Comando',
	'command_only_cpanel' => 'Solo para cPanel',
	'only_super_admin_command' => 'Solo el Super Admin puede ver el comando',
	'account_verification_automatically' => 'Tu cuenta se verificará automáticamente.',
	'create_reel' => 'Crear un Reel',
	'create_reel_subtitle' => 'Crea un reel y interactúa con tus suscriptores.',
	'video_calls' => 'Videollamadas',
	'video_call_max_price' => 'Precio máximo de videollamada',
	'video_call_min_price' => 'Precio mínimo de videollamada',
	'video_call_max_duration' => 'Duración máxima de videollamada',
	'subtitle_video_call_settings' => 'Establecer el precio y duración de las videollamadas',
	'video_call_settings' => 'Configuración de videollamadas',
	'price_video_call' => 'Precio de videollamada',
	'unanswered' => 'Sin respuesta',
	'new_video_call' => 'Nueva videollamada',
	'is_calling' => 'te esta llamando...',
	'per_minutes' => 'por :minutes minutos', // Important: No eliminar :minutes
	'video_call_notice' => 'Si aceptas la llamada, el monto se deducirá de tu Billetera.',
	'calling' => 'Llamando a',
	'please_wait_answer' => 'Por favor espera la respuesta.',
	'rejected_video_call' => 'Videollamada rechazada',
	'rejected_video_call_info' => 'Videollamada ha sido rechazada',
	'video_call_unanswered' => 'Videollamada no respondida',
	'video_call_unanswered_info' => 'Videollamada no ha sido respondida',
	'video_call_not_found' => 'Videollamada no encontrada',
	'm' => 'm',// Minutes abbreviation
	'connecting' => 'Conectando...',
	'error_twofa_too_many_attempts' => 'Demasiados intentos. Por favor, inténtalo de nuevo más tarde.',
	'allow_reels' => 'Permitir Reels',	
	'please_select_video' => 'Por favor selecciona un video',
	'available_only_for_subscribers' => ' Disponible solo para suscriptores',
	'available_everyone' => 'Disponible para todos',
	'reel_successfully_posted' => '¡Tu reel ahora está disponible!',
	'go_to_my_reels' => 'Ir a mis reels',
	'reels' => 'Reels',
	'reel' => 'Reel',
	'my_reels' => 'Mis Reels',
	'my_reels_subtitle' => 'Todos los reels que has creado',
	'error_video_encoding_reel' => 'Hubo un error al codificar video de tu reel',
	'not_reels_created' => 'No has creado ningún reel',
	'stripe_connect_setup_incomplete' => 'Tu cuenta de Stripe Connect no está completamente configurada. Completa el proceso de configuración.',
	'error_proportion_video_reel' => 'El video debe tener un formato vertical (se recomienda un aspecto de 16:9)',
	'liked_your_reel' => 'le gusta tu reel',
	'go_to_reel' => 'Ir al reel',
	'commented_your_reel' => 'comentó tu reel',
	'has_mentioned_you_reel' => 'te ha mencionado en un reel',
	'liked_your_comment_reel' => 'le gusta tu comentario en un reel',
	'hide_replies' => 'Ocultar respuestas',
	'resource_not_found' => 'Recurso no encontrado',
	'someone_commented_reel' => 'Alguien comentó mi reel',
	'someone_liked_reel' => 'Alguien le gustó mi reel',
	'edit_reel' => 'Editar reel',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				Helper::envUpdate('BROADCAST_DRIVER', 'pusher');

				Schema::table('subscriptions', function (Blueprint $table) {
					$table->renameColumn('name', 'type');
				});

				if (!Schema::hasColumn(
					'admin_settings',
					'delete_old_messages',
					'theme_color_pwa',
					'video_call_status',
					'video_call_max_price',
					'video_call_min_price',
					'video_call_max_duration',
					'websockets',
					'allow_reels'
				)) {
					Schema::table('admin_settings', function (Blueprint $table) {
						$table->boolean('delete_old_messages')->default(0);
						$table->string('theme_color_pwa')->default($this->settings->color_default);
						$table->boolean('video_call_status')->default(0);
						$table->unsignedInteger('video_call_max_price')->default(0);
						$table->unsignedInteger('video_call_min_price')->default(0);
						$table->unsignedInteger('video_call_max_duration')->default(10);
						$table->boolean('websockets')->default(0);
						$table->boolean('allow_reels')->default(0);
					});
				}

				if (!Schema::hasColumn('subscriptions', 'gateway', 'cofTransactionId', 'merchantIdentifier')) {
					Schema::table('subscriptions', function ($table) {
						$table->string('gateway', 255)->index('gateway')->nullable();
						$table->string('cofTransactionId', 255)->nullable();
						$table->string('merchantIdentifier', 255)->nullable();
					});
				}

				if (!Schema::hasColumn(
					'users',
					'custom_profit_referral',
					'price_video_call',
					'video_call_duration',
					'notify_commented_reel',
					'notify_liked_reel'
				)) {
					Schema::table('users', function (Blueprint $table) {
						$table->unsignedInteger('custom_profit_referral');
						$table->decimal('price_video_call', 10, 2)->nullable();
						$table->unsignedInteger('video_call_duration')->nullable();
						$table->boolean('notify_commented_reel')->default(1);
						$table->boolean('notify_liked_reel')->default(1);
					});
				}

				if (!Schema::hasTable('video_calls')) {
					Schema::create('video_calls', function (Blueprint $table) {
						$table->id();
						$table->unsignedBigInteger('seller_id')->index('seller_id');
						$table->unsignedBigInteger('buyer_id')->index('buyer_id');
						$table->decimal('price', 10, 2);
						$table->string('status', 20)->index('status')->default('calling');
						$table->unsignedInteger('minutes');
						$table->string('token', 200)->index('token')->nullable();
						$table->timestamp('started_at')->nullable();
						$table->timestamp('joined_at')->index('joined_at')->nullable();
						$table->timestamp('ended_at')->nullable();
						$table->boolean('paid')->default(0);
						$table->timestamps();
					});
				}

				if (!Schema::hasTable('reels')) {
					Schema::create('reels', function (Blueprint $table) {
						$table->id();
						$table->unsignedInteger('user_id')->index('user_id');
						$table->string('title')->nullable();
						$table->unsignedInteger('views')->default(0);
						$table->unsignedInteger('likes')->default(0);
						$table->unsignedInteger('comments_count')->default(0);
						$table->string('type')->index('type')->default('private');
						$table->string('status')->index('status')->default('pending');
						$table->timestamps();
					});
				}

				if (!Schema::hasTable('media_reels')) {
					Schema::create('media_reels', function (Blueprint $table) {
						$table->id();
						$table->unsignedInteger('reels_id')->index('reels_id');
						$table->string('name')->index('name');
						$table->string('video_poster')->nullable();
						$table->string('duration_video')->nullable();
						$table->string('job_id')->index('job_id')->nullable();
						$table->boolean('status')->default(0);
						$table->timestamps();
					});
				}

				if (!Schema::hasTable('like_reels')) {
					Schema::create('like_reels', function (Blueprint $table) {
						$table->id();
						$table->unsignedBigInteger('user_id')->index('user_id');
						$table->unsignedBigInteger('reels_id')->index('reels_id');
						$table->timestamps();
					});
				}

				if (!Schema::hasTable('comment_reels')) {
					Schema::create('comment_reels', function (Blueprint $table) {
						$table->id();
						$table->unsignedBigInteger('user_id')->index('user_id');
						$table->unsignedBigInteger('reels_id')->index('reels_id');
						$table->longText('reply')->collation('utf8mb4_unicode_ci');
						$table->timestamps();
					});
				}

				if (!Schema::hasTable('reel_replies')) {
					Schema::create('reel_replies', function ($table) {
						$table->id();
						$table->unsignedBigInteger('user_id')->index('user_id');
						$table->unsignedBigInteger('reels_id')->index('reels_id');
						$table->unsignedBigInteger('comment_reels_id')->index('comment_reels_id');
						$table->longText('reply')->collation('utf8mb4_unicode_ci');
						$table->timestamps();
					});
				}

				if (!Schema::hasTable('comment_like_reels')) {
					Schema::create('comment_like_reels', function ($table) {
						$table->increments('id');
						$table->unsignedInteger('user_id')->index('user_id');
						$table->unsignedInteger('comment_reels_id')->index('comment_reels_id');
						$table->unsignedInteger('reel_replies_id')->index('reel_replies_id');
						$table->timestamps();
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");

				if (DB::table('reserved')->where('name', 'reels')->doesntExist()) {
					DB::table('reserved')->insert(
						['name' => 'reels']
					);
				}

				try {
					DB::statement('ALTER TABLE `users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
				} catch (\Throwable $th) {
					info('Error altering tables users: ' . $th->getMessage());
				}
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 6.0 ----->>

		if ($version == '6.1') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES, //6.1

				'EncodeVideoReel.php' => $JOBS, //6.1

				'CoconutVideoService.php' => $SERVICES, //6.1

				'Updates.php' => $MODELS, //6.1

				'AdminController.php' => $CONTROLLERS, //6.1
				'HomeController.php' => $CONTROLLERS, //6.1
				'ReelController.php' => $CONTROLLERS, //6.1
				'StoriesController.php' => $CONTROLLERS, //6.1
				'WebhookCoconutController.php' => $CONTROLLERS, //6.1
				'UserController.php' => $CONTROLLERS, //6.1

				'updates.blade.php' => $VIEWS_INCLUDES, //6.1

				'admin-styles.css' => $PUBLIC_ADMIN, //6.1
			];

			$filesAdmin = [
				'reels.blade.php' => $VIEWS_ADMIN, //6.1
				'posts.blade.php' => $VIEWS_ADMIN, //6.1
				'names-reserved.blade.php' => $VIEWS_ADMIN, //6.1
				'layout.blade.php' => $VIEWS_ADMIN, //6.1
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 6.1
		'increase_number_likes' => 'Increase number of likes',
		'likes_added_successfully' => 'Likes added successfully',
		'edit_date_post' => 'Edit date of post',
		'edit_date_success' => 'The date of post has been edited successfully',
		'two_days_ago' => '2 days ago',
		'one_month_ago' => '1 month ago',
		'three_months_ago' => '3 months ago',
		'six_months_ago' => '6 months ago',
		'one_year_ago' => '1 year ago',
		'names_reserved' => 'Names reserved',
		'alert_names_reserved' => 'These names will not be available for usernames',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 6.1
	'increase_number_likes' => 'Incrementar el número de me gustas',
	'likes_added_successfully' => 'Me gustas agregados exitosamente',
	'edit_date_post' => 'Editar fecha de publicación',
	'edit_date_success' => 'La fecha de publicación ha sido editada con éxito',
	'two_days_ago' => 'Hace 2 días',
	'one_month_ago' => 'Hace 1 mes',
	'three_months_ago' => 'Hace 3 meses',
	'six_months_ago' => 'Hace 6 meses',
	'one_year_ago' => 'Hace 1 año',
	'names_reserved' => 'Nombres reservados',
	'alert_names_reserved' => 'Estos nombres no estarán disponibles para nombres de usuario',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				if (!Schema::hasColumn('updates', 'likes_extras')) {
					Schema::table('updates', function (Blueprint $table) {
						$table->unsignedBigInteger('likes_extras')->default(0);
					});
				}

				if (!Schema::hasColumn('reserved', 'editable')) {
					Schema::table('reserved', function (Blueprint $table) {
						$table->boolean('editable')->default(0);
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 6.1 ----->>

		if ($version == '6.2') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'User.php' => $MODELS, //6.2
				'VideoCall.php' => $MODELS, //6.2

				'AdminController.php' => $CONTROLLERS, //6.2
				'AdvertisingController.php' => $CONTROLLERS, //6.2
				'ReelController.php' => $CONTROLLERS, //6.2
				'StripeWebHookController.php' => $CONTROLLERS, //6.2

				'create-reel.js' => public_path('js' . $DS . 'reels') . $DS, //6.2

				'video-call-live.blade.php' => $VIEWS_USERS, //6.2

				'messages-inbox.blade.php' => $VIEWS_INCLUDES, //6.2
			];

			$filesAdmin = [
				'reels.blade.php' => $VIEWS_ADMIN, //6.2
				'messages.blade.php' => $VIEWS_ADMIN, //6.2
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 6.2
		'error_connection_permission' => 'Permission denied to access microphone or camera.',
		'agora_error' => 'Agora Error',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 6.2
	'error_connection_permission' => 'Permiso denegado para acceder al micrófono o cámara.',
	'agora_error' => 'Error de Agora',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================


				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 6.2 ----->>


		if ($version == '6.3') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'User.php' => $MODELS, //6.3
				'Subscriptions.php' => $MODELS, //6.3
				'Plans.php' => $MODELS, //6.3

				'Functions.php' => $TRAITS, //6.3

				'AdminController.php' => $CONTROLLERS, //6.3
				'StripeWebHookController.php' => $CONTROLLERS, //6.3
				'SubscriptionsController.php' => $CONTROLLERS, //6.3
				'StripeController.php' => $CONTROLLERS, //6.3
				'HomeController.php' => $CONTROLLERS, //6.3
				'UserController.php' => $CONTROLLERS, //6.3

				// Views
				'my_subscribers.blade.php' => $VIEWS_USERS, //6.3
				'my_subscriptions.blade.php' => $VIEWS_USERS, //6.3
			];

			$filesAdmin = [
				'subscriptions.blade.php' => $VIEWS_ADMIN, //6.3
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 6.3 ----->>

		if ($version == '6.4') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //6.4
				'composer.lock' => $ROOT, //6.4

				'app.php' => $CONFIG, //6.4
				'services.php' => $CONFIG, //6.4

				'Helper.php' => $APP, //6.4

				'OpenPixServiceProvider.php' => $PROVIDERS, //6.4

				'web.php' => $ROUTES, //6.4

				'AdminController.php' => $CONTROLLERS, //6.4
				'AddFundsController.php' => $CONTROLLERS, //6.4
				'RegisterController.php' => $CONTROLLERS_AUTH, //6.4
				'HomeController.php' => $CONTROLLERS, //6.4
				'WebhookOpenPixController.php' => $CONTROLLERS, //6.4

				// Public
				'reels.js' => $PUBLIC_JS_REELS, //6.4

				'openpix.png' => public_path('img' . $DS . 'payments') . $DS, //5.5

				// Views
				'css_general.blade.php' => $VIEWS_INCLUDES, //6.4
			];

			$filesAdmin = [
				'openpix-settings.blade.php' => $VIEWS_ADMIN,
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				file_put_contents(
					'.env',
					"\nOPENPIX_APP_ID=\"\"\nOPENPIX_BASE_URI=\"https://api.openpix.com.br\"\n",
					FILE_APPEND
				);

				if (Schema::hasTable('payment_gateways')) {
					\DB::table('payment_gateways')->insert(
						[
							[
								'name' => 'OpenPix',
								'type' => 'normal',
								'enabled' => '0',
								'fee' => 0.0,
								'fee_cents' => 0.00,
								'email' => '',
								'key' => '',
								'key_secret' => '',
								'recurrent' => 'no',
								'logo' => 'openpix.png',
								'subscription' => 'no',
								'bank_info' => '',
								'token' => str_random(150),
							]
						]
					);
				}

				Schema::table('deposits', function (Blueprint $table) {
					$table->string('status', 200)->default('active')->change();
				});

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 6.4 ----->>

		if ($version == '6.5') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //6.5
				'composer.lock' => $ROOT, //6.5

				'filesystems.php' => $CONFIG, //6.5

				'web.php' => $ROUTES, //6.5

				'AudioCallStatus.php' => $ENUMS, //6.5

				'AudioCall.php' => $MODELS, //6.5
				'User.php' => $MODELS, //6.5
				'Reel.php' => $MODELS, //6.5

				'UserDelete.php' => $TRAITS, //6.5

				'AdminController.php' => $CONTROLLERS, //6.5
				'AudioCallController.php' => $CONTROLLERS, //6.5
				'HomeController.php' => $CONTROLLERS, //6.5
				'UpdatesController.php' => $CONTROLLERS, //6.5
				'ReelController.php' => $CONTROLLERS, //6.5
				'VideoCallController.php' => $CONTROLLERS, //6.5
				'UserController.php' => $CONTROLLERS, //6.5
				'TimerAudioCallController.php' => $CONTROLLERS, //6.5

				'NoCache.php' => $MIDDLEWARE, //6.5

				'Kernel.php' => app_path('Http') . $DS, //6.5

				// Views
				'app.blade.php' => $VIEWS_LAYOUTS, // 6.5

				'updates.blade.php' => $VIEWS_INCLUDES, // 6.5
				'footer.blade.php' => $VIEWS_INCLUDES, // 6.5
				'css_general.blade.php' => $VIEWS_INCLUDES, // 6.5
				'form-post.blade.php' => $VIEWS_INCLUDES, // 6.5
				'listing-creators.blade.php' => $VIEWS_INCLUDES, // 6.5
				'listing-categories.blade.php' => $VIEWS_INCLUDES, // 6.5
				'cards-settings.blade.php' => $VIEWS_INCLUDES, // 6.5
				'modal-audio-call-incoming.blade.php' => $VIEWS_INCLUDES, // 6.5
				'modal-audio-call.blade.php' => $VIEWS_INCLUDES, // 6.5

				'profile.blade.php' => $VIEWS_USERS, // 6.5
				'messages-show.blade.php' => $VIEWS_USERS, // 6.5
				'notifications.blade.php' => $VIEWS_USERS, // 6.5
				'audio-call-settings.blade.php' => $VIEWS_USERS, // 6.5
				'video-call-settings.blade.php' => $VIEWS_USERS, // 6.5
				'invoice.blade.php' => $VIEWS_USERS, // 6.5
				'audio-call.blade.php' => $VIEWS_USERS, // 6.5

				// Public
				'app-functions.js' => $PUBLIC_JS, // 6.5
				'add-payment-card.js' => $PUBLIC_JS, // 6.5
				'calls.js' => $PUBLIC_JS, // 6.5

				'AgoraRTCSDK-v4.js' => public_path('js' . $DS . 'agora') . $DS, //6.5

				'bootstrap.min.css' => $PUBLIC_BOOTSTRAP_CSS, //6.5
				'bootstrap.min.css.map' => $PUBLIC_BOOTSTRAP_CSS, //6.5
				'bootstrap.rtl.min.css' => $PUBLIC_BOOTSTRAP_CSS, //6.5
				'bootstrap.rtl.min.css.map' => $PUBLIC_BOOTSTRAP_CSS, //6.5
				'bootstrap.bundle.min.js' => $PUBLIC_BOOTSTRAP_JS, //6.5
				'bootstrap.bundle.min.js.map' => $PUBLIC_BOOTSTRAP_JS, //6.5

			];

			$filesAdmin = [
				'settings.blade.php' => $VIEWS_ADMIN, // 6.5
				'storage.blade.php' => $VIEWS_ADMIN, // 6.5
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, // 6.5
				'layout.blade.php' => $VIEWS_ADMIN, // 6.5
				'audio-call-settings.blade.php' => $VIEWS_ADMIN, // 6.5
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 6.5
		'disable_audio' => 'Disable Audio Uploads',
		'photo_video' => 'Photo or Video',
		'audio_calls' => 'Audio Calls',
		'audio_call_max_price' => 'Maximum price of audio call',
		'audio_call_min_price' => 'Minimum price of audio call', 
		'audio_call_max_duration' => 'Maximum duration of audio call',
		'audio_call_settings' => 'Audio Call Settings',
		'subtitle_audio_call_settings' => 'Set the price and duration of audio calls',
		'price_audio_call' => 'Price of audio call',
		'new_audio_call' => 'New audio call',
		'rejected_audio_call' => 'Audio call rejected',
		'rejected_audio_call_info' => 'Your audio call has been rejected',
		'audio_call_unanswered' => 'Unanswered audio call',
		'audio_call_unanswered_info' => 'Your audio call has not been answered',
		'audio_call_not_found' => 'Audio call not found',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 6.5
	'disable_audio' => 'Deshabilitar subida de audio',
	'photo_video' => 'Foto o Vídeo',
	'audio_calls' => 'Llamadas de audio',
	'audio_call_max_price' => 'Precio máximo de llamada de audio',
	'audio_call_min_price' => 'Precio mínimo de llamada de audio',
	'audio_call_max_duration' => 'Duración máxima de llamada de audio',
	'audio_call_settings' => 'Configuración de llamadas de audio',
	'subtitle_audio_call_settings' => 'Establecer el precio y duración de las llamadas de audio',
	'price_audio_call' => 'Precio de llamada de audio',
	'new_audio_call' => 'Nueva llamada de audio',
	'rejected_audio_call' => 'Llamada de audio rechazada',
	'rejected_audio_call_info' => 'Tu llamada de audio ha sido rechazada',
	'audio_call_unanswered' => 'Llamada de audio no respondida',
	'audio_call_unanswered_info' => 'Tu llamada de audio no ha sido respondida',
	'audio_call_not_found' => 'Llamada de audio no encontrada',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				file_put_contents(
					'.env',
					"\n\nR2_ACCESS_KEY_ID=\nR2_SECRET_ACCESS_KEY=\nR2_BUCKET=\nR2_ENDPOINT=\n\nIDRIVE_KEY=\nIDRIVE_SECRET=\nIDRIVE_REGION=\nIDRIVE_BUCKET=\nIDRIVE_ENDPOINT=\n",
					FILE_APPEND
				);

				if (!Schema::hasColumn(
					'admin_settings',
					'disable_audio',
					'audio_call_status',
					'audio_call_max_price',
					'audio_call_min_price',
					'audio_call_max_duration',
				)) {
					Schema::table('admin_settings', function (Blueprint $table) {
						$table->boolean('disable_audio')->default(0);
						$table->boolean('audio_call_status')->default(0);
						$table->unsignedInteger('audio_call_max_price')->default(0);
						$table->unsignedInteger('audio_call_min_price')->default(0);
						$table->unsignedInteger('audio_call_max_duration')->default(10);
					});
				}

				if (!Schema::hasColumn(
					'users',
					'price_audio_call',
					'audio_call_duration',
				)) {
					Schema::table('users', function (Blueprint $table) {
						$table->decimal('price_audio_call', 10, 2)->nullable();
						$table->unsignedInteger('audio_call_duration')->nullable();
					});
				}

				if (!Schema::hasTable('audio_calls')) {
					Schema::create('audio_calls', function (Blueprint $table) {
						$table->id();
						$table->unsignedBigInteger('seller_id')->index('seller_id');
						$table->unsignedBigInteger('buyer_id')->index('buyer_id');
						$table->decimal('price', 10, 2);
						$table->string('status', 20)->index('status')->default('calling');
						$table->unsignedInteger('minutes');
						$table->string('token', 200)->index('token')->nullable();
						$table->timestamp('started_at')->nullable();
						$table->timestamp('joined_at')->index('joined_at')->nullable();
						$table->timestamp('ended_at')->nullable();
						$table->boolean('paid')->default(0);
						$table->timestamps();
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 6.5 ----->>

		if ($version == '6.6') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES, //6.6

				'User.php' => $MODELS, //6.6
				'Sticker.php' => $MODELS, //6.6
				'Reports.php' => $MODELS, //6.6

				'NoCache.php' => $MIDDLEWARE, //6.6

				'EncodeVideoReel.php' => $JOBS, //6.6

				'GiphyService.php' => $SERVICES, //6.6

				'ViewServiceProvider.php' => $PROVIDERS, //6.6

				'LoginController.php' => $CONTROLLERS_AUTH, //6.6
				'RegisterController.php' => $CONTROLLERS_AUTH, //6.6

				'AddFundsController.php' => $CONTROLLERS, //6.6
				'AtlosWebhookController.php' => $CONTROLLERS, //6.6
				'AdminController.php' => $CONTROLLERS, //6.6
				'AudioCallController.php' => $CONTROLLERS, //6.6
				'CommentsController.php' => $CONTROLLERS, //6.6
				'GifController.php' => $CONTROLLERS, //6.6
				'StickerController.php' => $CONTROLLERS, //6.6
				'HomeController.php' => $CONTROLLERS, //6.6
				'StripeWebHookController.php' => $CONTROLLERS, //6.6
				'PayPalController.php' => $CONTROLLERS, //6.6
				'MessagesController.php' => $CONTROLLERS, //6.6
				'VideoCallController.php' => $CONTROLLERS, //6.6
				'UserController.php' => $CONTROLLERS, //6.6
				'ReelController.php' => $CONTROLLERS, //6.6

				// Views
				'profile.blade.php' => $VIEWS_USERS, //6.6
				'my-reels.blade.php' => $VIEWS_USERS, //6.6
				'messages-show.blade.php' => $VIEWS_USERS, //6.6
				'edit-update.blade.php' => $VIEWS_USERS, //6.6

				'css_general.blade.php' => $VIEWS_INCLUDES, //6.6
				'form-post.blade.php' => $VIEWS_INCLUDES, //6.6
				'sticker-items.blade.php' => $VIEWS_INCLUDES, //6.6
				'item-gif.blade.php' => $VIEWS_INCLUDES, //6.6
				'gifs-items.blade.php' => $VIEWS_INCLUDES, //6.6
				'comments.blade.php' => $VIEWS_INCLUDES, //6.6
				'replies.blade.php' => $VIEWS_INCLUDES, //6.6
				'updates.blade.php' => $VIEWS_INCLUDES, //6.6
				'menu-sidebar-home.blade.php' => $VIEWS_INCLUDES, //6.6
				'modal-report-reel.blade.php' => $VIEWS_INCLUDES, //6.6

				'reels.blade.php' => $VIEWS_REELS, //6.6
				'all-comments-reel.blade.php' => $VIEWS_REELS, //6.6
				'comment-single-reel.blade.php' => $VIEWS_REELS, //6.6
				'video-full-screen.blade.php' => $VIEWS_REELS, //6.6
				'wallet.blade.php' => $VIEWS_USERS, //6.6

				// public
				'app-functions.js' => $PUBLIC_JS, //6.6
				'payment.js' => $PUBLIC_JS, //6.6
				'reels.js' => $PUBLIC_JS_REELS, //6.6
				'reels.css' => $PUBLIC_JS_REELS, //6.6

				'atlos.png' => public_path('img' . $DS . 'payments') . $DS, //6.6
				'atlos-white.png' => public_path('img' . $DS . 'payments') . $DS, //6.6

			];

			$filesAdmin = [
				'layout.blade.php' => $VIEWS_ADMIN, //6.6
				'members.blade.php' => $VIEWS_ADMIN, //6.6
				'edit-member.blade.php' => $VIEWS_ADMIN, //6.6
				'settings.blade.php' => $VIEWS_ADMIN, //6.6
				'posts.blade.php' => $VIEWS_ADMIN, //6.6
				'stickers.blade.php' => $VIEWS_ADMIN, //6.6
				'giphy-api.blade.php' => $VIEWS_ADMIN, //6.6
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, //6.6
				'reports.blade.php' => $VIEWS_ADMIN, //6.6
				'atlos-settings.blade.php' => $VIEWS_ADMIN, //6.6
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// Ennglish
				$replaceLangEN = "
		// Version 6.6
		'ppv_only_free_accounts' => 'Pay-Per-View only for free accounts',
		'url_sticker' => 'URL of the Sticker',
		'search_gif' => 'Search GIFs',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 6.6
	'ppv_only_free_accounts' => 'Pago por ver solo en cuentas gratuitas',
	'url_sticker' => 'URL del Sticker',
	'search_gif' => 'Buscar GIFs',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				if (!Schema::hasColumn('admin_settings', 'ppv_only_free_accounts', 'giphy_status', 'giphy_api_key')) {
					Schema::table('admin_settings', function (Blueprint $table) {
						$table->boolean('ppv_only_free_accounts')->default(0);
						$table->boolean('giphy_status')->default(0);
						$table->string('giphy_api_key')->nullable();
					});
				}

				if (!Schema::hasTable('stickers')) {
					Schema::create('stickers', function (Blueprint $table) {
						$table->id();
						$table->text('url');
						$table->timestamps();
					});
				}

				if (!Schema::hasColumn('comments', 'sticker', 'gif_image')) {
					Schema::table('comments', function (Blueprint $table) {
						$table->text('sticker')->nullable();
						$table->text('gif_image')->nullable();
					});
				}

				if (!Schema::hasColumn('replies', 'sticker', 'gif_image')) {
					Schema::table('replies', function (Blueprint $table) {
						$table->text('sticker')->nullable();
						$table->text('gif_image')->nullable();
					});
				}

				if (\DB::table('payment_gateways')->where('name', 'Atlos')->doesntExist()) {
					\DB::table('payment_gateways')->insert(
						[
							[
								'name' => 'Atlos',
								'type' => 'normal',
								'enabled' => '0',
								'fee' => 0.0,
								'fee_cents' => 0.00,
								'recurrent' => 'no',
								'logo' => 'atlos.png',
								'subscription' => 'no',
								'token' => str_random(150),
							]
						]
					);
				}

				if (Schema::hasTable('stickers')) {
					Sticker::insert([
						[
							'url' => 'https://media3.giphy.com/media/hVlZnRT6QW1DeYj6We/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media0.giphy.com/media/McVAzihiFCpV5N5HZE/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media4.giphy.com/media/lRXY41yFFi9RfNXyPN/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media0.giphy.com/media/L3X2BTnSL6kGpPmJtr/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media2.giphy.com/media/SpnN24fEeWGLPlhs8b/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media1.giphy.com/media/J2awouDsf23R2vo2p5/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media2.giphy.com/media/S3nZ8V9uemShxiWX8g/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media1.giphy.com/media/YondZW6AMjgTEHevF0/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media4.giphy.com/media/dalJ0CpF7hwmN1nZXe/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media2.giphy.com/media/Ss0X9wziu1NHEPXe5c/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media3.giphy.com/media/h86PVmFNq6f2l3gMWI/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media0.giphy.com/media/h4OGa0npayrJX2NRPT/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media3.giphy.com/media/h2TL9kJUgFoaeuEVR9/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media2.giphy.com/media/USUIWSteF8DJoc5Snd/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media2.giphy.com/media/ehC4SqtNcEeLAiu66w/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media1.giphy.com/media/YNDLZBTq8hGPDJkmYo/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media3.giphy.com/media/RlktKWfBX1RAwSTPxz/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media1.giphy.com/media/4tSHBpzJw7R3rrKUeo/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media4.giphy.com/media/QBSPma5jP9ReSAdxKw/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
						[
							'url' => 'https://media2.giphy.com/media/LOnt6uqjD9OexmQJRB/200w.gif',
							'created_at' => now(),
							'updated_at' => now()
						],
					]);
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 6.6 ----->>

		if ($version == '6.7') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES, //6.7

				'Helper.php' => $APP, //6.7

				'Notifications.php' => $MODELS, //6.7

				'SightEngineService.php' => $SERVICES, //6.7
				'SightEngineValidatorService.php' => $SERVICES, //6.7
				'SightEngineVideoValidatorService.php' => $SERVICES, //6.7

				'AdminController.php' => $CONTROLLERS, //6.7
				'UserController.php' => $CONTROLLERS, //6.7
				'WebhookCoconutController.php' => $CONTROLLERS, //6.7
				'HomeController.php' => $CONTROLLERS, //6.7
				'UploadMediaController.php' => $CONTROLLERS, //6.7
				'UpdatesController.php' => $CONTROLLERS, //6.7

				'EncodeVideo.php' => $JOBS, //6.7
				'MediaModeration.php' => $JOBS, //6.7

				// Views
				'edit-update.blade.php' => $VIEWS_USERS, //6.7
				'notifications.blade.php' => $VIEWS_USERS, //6.7
				'video-call-live.blade.php' => $VIEWS_USERS, //6.7
				'audio-call.blade.php' => $VIEWS_USERS, //6.7
				'profile.blade.php' => $VIEWS_USERS, //6.7

				'form-post.blade.php' => $VIEWS_INCLUDES, //6.7
				'css_general.blade.php' => $VIEWS_INCLUDES, //6.7

				'video-full-screen.blade.php' => $VIEWS_REELS, //6.7

				'app-functions.js' => $PUBLIC_JS, //6.7
			];

			$filesAdmin = [
				'layout.blade.php' => $VIEWS_ADMIN, //6.7
				'moderation-image-video.blade.php' => $VIEWS_ADMIN, //6.7
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, //6.7
				'email-settings.blade.php' => $VIEWS_ADMIN, //6.7
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// English
				$replaceLangEN = "
		// Version 6.7
		'moderation_image_video' => 'Moderation Image/Video',
		'content_blocked_moderation' => 'The :name file does not comply with our platform policies',// Not translate :name (File name)
		'moderation_status_info' => 'Images and Videos that are uploaded will be reviewed before being published',
		'error_moderation_file' => 'There was an error in the moderation process with the file :name, try uploading it again',// Not translate :name (File name)
		'password_email_admin_warning' => 'The password must not contain pad (#) because the rest of the characters will be ignored',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 6.7
	'moderation_image_video' => 'Moderación Imágenes/Vídeos',
	'content_blocked_moderation' => 'El archivo :name no cumple con las politicas de nuestra plataforma',// Not translate :name (File name)
	'moderation_status_info' => 'Las Imágenes y Vídeos que sean subidos serán revisados antes de ser publicados',
	'error_moderation_file' => 'Hubo un error en el proceso de moderación con el archivo :name intenta subirlo de nuevo',// Not translate :name (File name)
	'password_email_admin_warning' => 'La contraseña no debe contener Almohadilla (#) porque los otros caracteres serán ignorados',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================


				if (!Schema::hasColumn('admin_settings', 'moderation_status', 'sightengine_api_user', 'sightengine_api_api_secret')) {
					Schema::table('admin_settings', function (Blueprint $table) {
						$table->boolean('moderation_status')->default(0);
						$table->string('sightengine_api_user')->nullable();
						$table->string('sightengine_api_api_secret')->nullable();
					});
				}

				if (!Schema::hasColumn('notifications', 'context')) {
					Schema::table('notifications', function (Blueprint $table) {
						$table->text('context')->nullable();
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 6.7 ----->>

		if ($version == '6.8') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'AtlosWebhookController.php' => $CONTROLLERS, //6.8
				'UpdatesController.php' => $CONTROLLERS, //6.8

				// Views
				'css_general.blade.php' => $VIEWS_INCLUDES, //6.8

				// Public
				'payments-ppv.js' => $PUBLIC_JS, //6.8
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 6.8 ----->>


		if ($version == '6.9') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES, //6.9

				'Helper.php' => $APP, //6.9

				'services.php' => $CONFIG, //6.9

				'VideoModerationSightengineService.php' => $SERVICES, //6.9
				'SightEngineService.php' => $SERVICES, //6.9

				'EncodeVideo.php' => $JOBS, //6.9
				'EncodeVideoMessages.php' => $JOBS, //6.9
				'EncodeVideoWelcomeMessage.php' => $JOBS, //6.9
				'EncodeVideoStory.php' => $JOBS, //6.9
				'EncodeVideoReel.php' => $JOBS, //6.9

				'AudioCallController.php' => $CONTROLLERS, //6.9
				'AtlosWebhookController.php' => $CONTROLLERS, //6.9
				'StripeWebHookController.php' => $CONTROLLERS, //6.9
				'SocialAuthController.php' => $CONTROLLERS, //6.9
				'AdminController.php' => $CONTROLLERS, //6.9
				'HomeController.php' => $CONTROLLERS, //6.9
				'WebhookSightengineController.php' => $CONTROLLERS, //6.9
				'VideoCallController.php' => $CONTROLLERS, //6.9

			];

			$filesAdmin = [
				'verification.blade.php' => $VIEWS_ADMIN, //6.9
				'moderation-image-video.blade.php' => $VIEWS_ADMIN, //6.9
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 6.9 ----->>

		if ($version == '7.0') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.lock' => $ROOT, //7.0

				'web.php' => $ROUTES, //7.0

				'path.php' => $CONFIG, //7.0

				'CoconutVideoService.php' => $SERVICES, //7.0

				'Notifications.php' => $MODELS, //7.0
				'MediaMessages.php' => $MODELS, //7.0
				'Messages.php' => $MODELS, //7.0
				'Vault.php' => $MODELS, //7.0
				'User.php' => $MODELS, //7.0

				'MassMessagesListener.php' => $LISTENERS, // 7.0

				'EncodeVideoVault.php' => $JOBS, //7.0
				'EncodeVideoReel.php' => $JOBS, //7.0
				'EncodeVideoStory.php' => $JOBS, //7.0
				'EncodeVideoMessages.php' => $JOBS, //7.0
				'EncodeVideoWelcomeMessage.php' => $JOBS, //7.0

				'AdminController.php' => $CONTROLLERS, //7.0
				'HomeController.php' => $CONTROLLERS, //7.0
				'StripeWebHookController.php' => $CONTROLLERS, //7.0
				'StorageCoconutController.php' => $CONTROLLERS, //7.0
				'MessagesController.php' => $CONTROLLERS, //7.0
				'UpdatesController.php' => $CONTROLLERS, //7.0
				'UploadMediaVaultController.php' => $CONTROLLERS, //7.0
				'VaultController.php' => $CONTROLLERS, //7.0
				'WebhookCoconutController.php' => $CONTROLLERS, //7.0

				'placeholder.png' => $PUBLIC_IMG, //7.0

				'app-functions.js' => $PUBLIC_JS, //7.0
				'vault.js' => $PUBLIC_JS, //7.0

				'fileuploader-vault.js' => public_path('js' . $DS . 'fileuploader') . $DS, // 7.0
				'jquery.fileuploader-theme-gallery.css' => public_path('js' . $DS . 'fileuploader') . $DS, // 7.0

				'AgoraRTCSDK-v4.js' => public_path('js' . $DS . 'agora') . $DS, // 7.0

				'bootstrap.min.css' => $PUBLIC_BOOTSTRAP_CSS, //6.5
				'bootstrap.min.css.map' => $PUBLIC_BOOTSTRAP_CSS, //6.5
				'bootstrap.rtl.min.css' => $PUBLIC_BOOTSTRAP_CSS, //6.5
				'bootstrap.rtl.min.css.map' => $PUBLIC_BOOTSTRAP_CSS, //6.5
				'bootstrap.bundle.min.js' => $PUBLIC_BOOTSTRAP_JS, //6.5
				'bootstrap.bundle.min.js.map' => $PUBLIC_BOOTSTRAP_JS, //6.5

				// Views
				'app.blade.php' => $VIEWS_LAYOUTS, //7.0

				'css_general.blade.php' => $VIEWS_INCLUDES, //7.0
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //7.0
				'messages-chat.blade.php' => $VIEWS_INCLUDES, //7.0
				'media-messages.blade.php' => $VIEWS_INCLUDES, //7.0
				'vault-files.blade.php' => $VIEWS_INCLUDES, //7.0
				'modal-vault.blade.php' => $VIEWS_INCLUDES, //7.0
				'modal-new-message.blade.php' => $VIEWS_INCLUDES, //7.0
				'navbar.blade.php' => $VIEWS_INCLUDES, //7.0

				'my-vault.blade.php' => $VIEWS_USERS, //7.0
				'messages-show.blade.php' => $VIEWS_USERS, //7.0
				'notifications.blade.php' => $VIEWS_USERS, //7.0
			];

			$filesAdmin = [
				'settings.blade.php' => $VIEWS_ADMIN, //7.0
				'verification.blade.php' => $VIEWS_ADMIN, //7.0
				'deposits-view.blade.php' => $VIEWS_ADMIN, //7.0
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// English
				$replaceLangEN = "
		// Version 7.0
		'allow_vault' => 'Allow Vault',
		'vault' => 'Vault',
		'all_vault_uploaded' => 'All the vault files uploaded',
		'not_files_uploaded' => 'You have not uploaded any files yet',
		'video_processed_successfully_vault' => 'Your video :name has been processed successfully (Vault)',// Not translate :name (File name)
		'error_video_encoding_vault' => 'There was an error encoding the video of your vault file: :name',// Not translate :name (File name)
		'add_media_from_vault' => 'Add media from Vault',
		'files_can_be_selected_msg' => 'Files you can select',
		'information' => 'Information',
		'following_files_already_added' => 'The following files are already added:',
		'new_files_have_been_added_following_already_existed' => 'New files have been added. The following already existed:',
		'video_encoding_warning' => 'Videos will be encoded, so it may take a little while to be available in the vault',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 7.0
	'allow_vault' => 'Permitir bóveda',
	'vault' => 'Bóveda',
	'all_vault_uploaded' => 'Todos los archivos de bóveda subidos',
	'not_files_uploaded' => 'Aún no has subido ningún archivo',
	'video_processed_successfully_vault' => 'Tu video :name ha sido procesado exitosamente (Bóveda)',// Not translate :name (File name)
	'error_video_encoding_vault' => 'Hubo un error al codificar el video de tu archivo de bóveda: :name',// Not translate :name (File name)
	'add_media_from_vault' => 'Agregar medios desde la bóveda',
	'files_can_be_selected_msg' => 'Archivos que puedes seleccionar',
	'information' => 'Información',
	'following_files_already_added' => 'Los siguientes archivos ya están agregados:',
	'new_files_have_been_added_following_already_existed' => 'Se agregaron los archivos nuevos. Los siguientes ya existían:',
	'video_encoding_warning' => 'Los videos serán codificados, por lo que puede tardar un poco en estar disponibles en la bóveda',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				if (!Schema::hasColumn('admin_settings', 'allow_vault')) {
					Schema::table('admin_settings', function (Blueprint $table) {
						$table->boolean('allow_vault')->default(0);
					});
				}

				if (!Schema::hasColumn('media_messages', 'vault_id')) {
					Schema::table('media_messages', function (Blueprint $table) {
						$table->unsignedBigInteger('vault_id')->index('vault_id')->nullable();
					});
				}

				if (!Schema::hasTable('vaults')) {
					Schema::create('vaults', function (Blueprint $table) {
						$table->id();
						$table->unsignedBigInteger('user_id')->index('user_id');
						$table->string('type', 100)->index('type');
						$table->string('image', 255);
						$table->string('width', 5)->nullable();
						$table->string('height', 5)->nullable();
						$table->string('img_type', 255);
						$table->string('video', 255);
						$table->enum('encoded', ['yes', 'no'])->default('no');
						$table->string('video_poster', 255)->nullable();
						$table->string('duration_video', 50)->nullable();
						$table->string('quality_video', 20)->nullable();
						$table->string('video_embed', 200);
						$table->string('music', 255);
						$table->string('file', 255);
						$table->string('file_name', 255);
						$table->string('file_size', 255);
						$table->string('bytes', 255)->nullable();
						$table->string('mime', 255)->nullable();
						$table->string('job_id', 200)->nullable();
						$table->enum('status', ['active', 'pending'])->default('active');
						$table->timestamps();
					});
				}

				try {
					// Move file htaccess
					$sourceFile = $path . '.htaccess';

					// Move the file...
					Storage::putFileAs(config('path.verification'), new File($sourceFile), '.htaccess');

					// Delete file
					unlink($sourceFile);
				} catch (\Exception $e) {
					\Log::info('Moving .htaccess file failed');
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 7.0 ----->>

		if ($version == '7.1') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'messages-chat.blade.php' => $VIEWS_INCLUDES, //7.1
				'navbar.blade.php' => $VIEWS_INCLUDES, //7.1
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 7.1 ----->>

		if ($version == '7.2') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //7.2
				'composer.lock' => $ROOT, //7.2

				'ProductsController.php' => $CONTROLLERS, //7.2
				'profile.blade.php' => $VIEWS_USERS, //7.2

				'modal-tip.blade.php' => $VIEWS_INCLUDES, //7.2
				'vault-files.blade.php' => $VIEWS_INCLUDES, //7.2
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 7.2 ----->>


		if ($version == '7.3') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //7.3
				'composer.lock' => $ROOT, //7.3

				'web.php' => $ROUTES, //7.3 L:58:643:645:646:1019:1020

				'Kernel.php' => app_path('Http') . $DS, //7.3

				'User.php' => $MODELS, //7.3
				'Notifications.php' => $MODELS, //7.3

				'SubscriptionUpcomingRenewals.php' => $NOTIFICATIONS, //7.3
				'WelcomeMessageRegistered.php' => $NOTIFICATIONS, //7.3

				'UpcomingRenewals.php' => $JOBS, //7.3

				'AgeVerificationStatus.php' => $MIDDLEWARE, //7.3

				'AdminController.php' => $CONTROLLERS, //7.3 L:258:328:369:2827
				'AgeVerificationController.php' => $CONTROLLERS, //7.3
				'HomeController.php' => $CONTROLLERS, //7.3 L:196:258:269
				'RegisterController.php' => $CONTROLLERS_AUTH, //7.3 L:212
				'ReelController.php' => $CONTROLLERS, //7.3
				'UserController.php' => $CONTROLLERS, //7.3 L:627

				// Views
				'app.blade.php' => $VIEWS_LAYOUTS, //7.3 L:57:73

				'notifications.blade.php' => $VIEWS_USERS, //7.3
				'verify-your-age.blade.php' => $VIEWS_USERS, //7.3

				'css_general.blade.php' => $VIEWS_INCLUDES, //7.3 L:891
				'media-messages.blade.php' => $VIEWS_INCLUDES, //7.3
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //7.3

				'home-session.blade.php' => $VIEWS_INDEX, //7.3

				// Public
				'app-functions.js' => $PUBLIC_JS, //7.3 L:81

				'public-key.pem' => $PUBLIC_ADMIN, //7.3


			];

			$filesAdmin = [
				'ffmpeg.blade.php' => $VIEWS_ADMIN, //7.3
				'settings.blade.php' => $VIEWS_ADMIN, //7.3
				'age-verification.blade.php' => $VIEWS_ADMIN, //7.3
				'role-and-permissions-member.blade.php' => $VIEWS_ADMIN, //7.3
				'layout.blade.php' => $VIEWS_ADMIN, //7.3
				'edit-member.blade.php' => $VIEWS_ADMIN, //7.3
			];

			// Copy kernel
			$this->moveFile($pathAdmin . 'Kernel.php', app_path('Console') . $DS . 'Kernel.php', $copy);

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// English
				$replaceLangEN = "
		// Version 7.3
		'upcoming_renewals' => 'Upcoming renewals',
		'upcoming_renewal_for' => 'Upcoming renewal for @:creator',// Not translate @:creator
		'detail_upcoming_renewal' => 'Your subscription to @:creator will renew in the next 24 hours. Make sure your payment method is up to date so you don\'t lose access to their content.', // Not translate @:creator
		'manage_subscriptions' => 'Manage subscriptions',
		'welcome_message_registered_subject' => 'Welcome to :title',// Not translate :title
		'welcome_message_registered_body' => 'Welcome and thank you for registering on our platform. We are excited to have you on board.',
		'send_welcome_email_new_users' => 'Send welcome email to new users',
		'age_verification' => 'Age verification',
		'age_verification_desc' => 'In order to use our platform, we need you to verify your age. Click the button below and you\'ll be redirected to the verification service.',
		'verify_age' => 'Verify age',
		'threshold_age_verification' => 'Threshold age for verification',
		'alert_error_age_verification' => 'You have already verified your age or your age verification failed.',
		'threshold_age_verification_desc' => 'By default it is set in 18 years, you can choose an age above for age verification.',
		'success_age_verification' => 'Your age verification has been successful.',
		'failed_age_verification' => 'Your age verification failed',
		'alert_age_verification_title' => 'You must be 18 or older to use our platform',
		'alert_age_verification' => 'To comply with the laws of your state, we have set up a process for verifying the ages of users who visit our website.',
		'start_age_verification' => 'Start age verification',
		'age_verification_verified' => 'Your age has been verified!',
		'error_age_verification' => 'Your age verification failed, if you think it is a mistake, contact us at :email', // Not remove :email
		'age_verification_progress' => 'Your age verification is in progress, you will receive a notification when it is ready.',
		'show_modal_age_verification' => 'Show age verification modal',
		'in_progress' => 'In progress',

);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 7.3
	'upcoming_renewals' => 'Próximas renovaciones',
	'upcoming_renewal_for' => 'Próxima renovación para @:creator',// Not translate @:creator
	'detail_upcoming_renewal' => 'Tu suscripción a @:creator renovará en las próximas 24 horas. Asegúrate de que tu método de pago está actualizado para que no pierdas el acceso a su contenido.', // Not translate @:creator
	'manage_subscriptions' => 'Administrar suscripciones',
	'welcome_message_registered_subject' => 'Bienvenido a :title',// Not translate :title
	'welcome_message_registered_body' => 'Bienvenido y gracias por registrarte en nuestra plataforma. Estamos emocionados de tenerte a bordo.',
	'send_welcome_email_new_users' => 'Enviar correo de bienvenida a los nuevos usuarios',
	'age_verification' => 'Verificación de edad',
	'age_verification_desc' => 'Para poder usar nuestra plataforma necesitamos que verifiques tu edad, haz clic en botón de abajo y serás redirigido al servicio de verificacion.',
	'verify_age' => 'Verificar edad',
	'threshold_age_verification' => 'Límite de edad para verificación',
	'alert_error_age_verification' => 'Ya has verificado tu edad o tú verificación de edad ha fallado.',
	'threshold_age_verification_desc' => 'Por defecto está configurado desde 18 años, puedes elegir una edad superior para la comprobacion de edades.',
	'success_age_verification' => 'Tu verificación de edad ha sido exitosa.',
	'failed_age_verification' => 'Tu verificación de edad ha fallado',
	'alert_age_verification_title' => 'Debes ser de 18 años o más para usar nuestra plataforma',
	'alert_age_verification' => 'Para cumplir con las leyes de su estado, hemos establecido un proceso para verificar las edades de los usuarios que visitan nuestro sitio web.',
	'start_age_verification' => 'Comenzar verificación de edad',
	'age_verification_verified' => '¡Tu edad ha sido verificada!',
	'error_age_verification' => 'Tu verificación de edad ha fallado, si crees que es un error, contactanos al correo :email', // Not remove :email
	'age_verification_progress' => 'Tu verificación de edad está en progreso, recibirá una notificación cuando esté listo.',
	'show_modal_age_verification' => 'Mostrar modal de verificación de edad',
	'in_progress' => 'En progreso',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				if (!Schema::hasColumn('users', 'phone', 'email_upcoming_renewals', 'age_verification')) {
					Schema::table('users', function (Blueprint $table) {
						$table->string('phone')->nullable();
						$table->boolean('email_upcoming_renewals')->default(true);
						$table->boolean('age_verification')->index('age_verification')->default(false);
					});
				}

				if (!Schema::hasColumn('admin_settings', 'send_welcome_email_new_users')) {
					Schema::table('admin_settings', function (Blueprint $table) {
						$table->boolean('send_welcome_email_new_users')->default(0);
						$table->boolean('age_verification_status')->default(0);
						$table->string('age_verification_yoti_api_key')->nullable();
						$table->string('age_verification_yoti_sdk_id')->nullable();
						$table->unsignedInteger('threshold_age_verification')->default(19);
						$table->boolean('show_modal_age_verification')->default(1);
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 7.3 ----->>

		if ($version == '7.4') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'filesystems.php' => $CONFIG, //7.4

				'EncodeVideoReel.php' => $JOBS, //7.4

				'AgeVerificationService.php' => $SERVICES, //7.4
				'DiditService.php' => $SERVICES, //7.4
				'YotiService.php' => $SERVICES, //7.4
				'AgeVerificationInterface.php' => $SERVICES, //7.4

				'AdminController.php' => $CONTROLLERS, //7.4
				'AgeVerificationController.php' => $CONTROLLERS, //7.4
				'AudioCallController.php' => $CONTROLLERS, //7.4
				'VideoCallController.php' => $CONTROLLERS, //7.4
				'CommentReelController.php' => $CONTROLLERS, //7.4
				'UserController.php' => $CONTROLLERS, //7.4

				// Views
				'css_general.blade.php' => $VIEWS_INCLUDES, //7.4
				'media-messages.blade.php' => $VIEWS_INCLUDES, //7.4
				'media-post.blade.php' => $VIEWS_INCLUDES, //7.4

				'home-session.blade.php' => $VIEWS_INDEX, //7.4
			];

			$filesAdmin = [
				'age-verification.blade.php' => $VIEWS_ADMIN, //7.4
				'storage.blade.php' => $VIEWS_ADMIN, //7.4
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				file_put_contents(
					'.env',
					"\n\nR2_URL=\n",
					FILE_APPEND
				);

				if (!Schema::hasColumn('admin_settings', 'age_verification_provider')) {
					Schema::table('admin_settings', function (Blueprint $table) {
						$table->string('age_verification_provider')->default('yoti');
						$table->string('age_verification_didit_api_key')->nullable();
						$table->string('age_verification_didit_webhook_secret')->nullable();
						$table->string('age_verification_didit_workflow_id')->nullable();
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 7.4 ----->>

		if ($version == '7.5') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //7.5
				'composer.lock' => $ROOT, //7.5

				'MassMessagesListener.php' => $LISTENERS, //7.5

				'AgeVerificationStatus.php' => $MIDDLEWARE, //7.5

				'AdminController.php' => $CONTROLLERS, //7.5

				// Views
				'app.blade.php' => $VIEWS_LAYOUTS, //7.5
			];

			$filesAdmin = [
				'age-verification.blade.php' => $VIEWS_ADMIN, //7.5
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				if (!Schema::hasColumn('admin_settings', 'age_verification_countries')) {
					Schema::table('admin_settings', function ($table) {
						$table->longText('age_verification_countries')->nullable();
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 7.5 ----->>


		if ($version == '7.6') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES, //7.6 L:653-655-1031

				'Notifications.php' => $MODELS, //7.6
				'Updates.php' => $MODELS, //7.6

				'AdminController.php' => $CONTROLLERS, //7.6 L:371-683
				'UserController.php' => $CONTROLLERS, //7.6 L:1965
				'UpdatesController.php' => $CONTROLLERS, //7.6 L:109-121
				'InstallAddonController.php' => $CONTROLLERS, //7.6

				'app-functions.js' => $PUBLIC_JS, //7.6
				'payment.js' => $PUBLIC_JS, //7.6

				// Views
				'app.blade.php' => $VIEWS_LAYOUTS, //7.6 L:703-724 L:3592 to 3644

				'notifications.blade.php' => $VIEWS_USERS, //7.6
				'privacy_security.blade.php' => $VIEWS_USERS, //7.6
				'my_posts.blade.php' => $VIEWS_USERS, //7.6

				'form-post.blade.php' => $VIEWS_INCLUDES, //7.6
				'updates.blade.php' => $VIEWS_INCLUDES, //7.6
				'css_general.blade.php' => $VIEWS_INCLUDES, //7.6 L:326
			];

			$filesAdmin = [
				'settings.blade.php' => $VIEWS_ADMIN, //7.6
				'payments-settings.blade.php' => $VIEWS_ADMIN, //7.6
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				if (!Schema::hasColumn('updates', 'funds_raised')) {
					Schema::table('updates', function ($table) {
						$table->decimal('funds_raised', 10, 2);
						$table->decimal('crowdfund_goal', 10, 2);
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 7.6 ----->>


		if ($version == '7.7') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //7.7 
				'composer.lock' => $ROOT, //7.7 

				'web.php' => $ROUTES, //7.7 L:658

				'User.php' => $MODELS, //7.7
				'Updates.php' => $MODELS, //7.7
				'Transactions.php' => $MODELS, //7.7

				'UpcomingRenewals.php' => $JOBS, //7.7

				'CoconutVideoService.php' => $SERVICES, //7.7
				'SightEngineVideoValidatorService.php' => $SERVICES, //7.7
				'CoinPaymentsService.php' => $SERVICES, //7.7

				'SubscriptionUpcomingRenewals.php' => $NOTIFICATIONS, //7.7

				'AddFundsController.php' => $CONTROLLERS, //7.7 L:18,435,494
				'AdminController.php' => $CONTROLLERS, //7.7 L:93,607:cancelTransaction(),approveDeposits,deleteDeposits,withdrawalsPaid
				'StorageCoconutController.php' => $CONTROLLERS, //7.7

				'UserDelete.php' => $TRAITS, //7.7

				'app-functions.js' => $PUBLIC_JS, //7.7 L:795,798,3646
				'AgoraRTCSDK-v4.js' => public_path('js' . $DS . 'agora') . $DS, // 7.7

				'css_general.blade.php' => $VIEWS_INCLUDES, //7.7 L:326
				'media-messages.blade.php' => $VIEWS_INCLUDES, //7.7
				'updates.blade.php' => $VIEWS_INCLUDES, //7.7 L:128,487,492,644
				'listing-creators.blade.php' => $VIEWS_INCLUDES, //7.7

				'profile.blade.php' => $VIEWS_USERS, //7.7 L:102
			];

			$filesAdmin = [
				'transactions.blade.php' => $VIEWS_ADMIN, //7.7
				'limits.blade.php' => $VIEWS_ADMIN, //7.7
				'coinpayments-settings.blade.php' => $VIEWS_ADMIN, //7.7
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// English
				$replaceLangEN = "
		// Version 7.7
		'detail_upcoming_renewal_three_days_before' => 'Your subscription to @:creator will renew in the next 3 days. Make sure your payment method is up to date so you don\'t lose access to their content.', // Not translate @:creator
		'detail_upcoming_renewal_six_months_before' => 'Your subscription to @:creator will renew in the next 6 months. Make sure your payment method is up to date so you don\'t lose access to their content.', // Not translate @:creator
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 7.7
	'detail_upcoming_renewal_three_days_before' => 'Tu suscripción a @:creator se renovará en los próximos 3 días. Asegúrate de que tu método de pago está actualizado para que no pierdas el acceso a su contenido.', // Not translate @:creator
	'detail_upcoming_renewal_six_months_before' => 'Tu suscripción a @:creator se renovará en los próximos 6 meses. Asegúrate de que tu método de pago está actualizado para que no pierdas el acceso a su contenido.', // Not translate @:creator
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				if (!Schema::hasColumn('users', 'display_list_donors')) {
					Schema::table('users', function ($table) {
						$table->boolean('display_list_donors')->default(true);
					});
				}

				if (!Schema::hasColumn('updates', 'finalized')) {
					Schema::table('updates', function ($table) {
						$table->boolean('finalized')->default(0);
					});
				}

				if (!Schema::hasColumn('payment_gateways', 'base_url')) {
					Schema::table('payment_gateways', function ($table) {
						$table->text('base_url')->nullable();
					});
				}


				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 7.7 ----->>

		if ($version == '7.8') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'AvatarCoverModeration.php' => $JOBS, //7.8

				'Notifications.php' => $MODELS, //7.8
				'PostView.php' => $MODELS, //7.8
				'Messages.php' => $MODELS, //7.8
				'Updates.php' => $MODELS, //7.8

				'DiditService.php' => $SERVICES, //7.8
				'SightEngineService.php' => $SERVICES, //7.8

				'UploadMediaWelcomeMessageController.php' => $CONTROLLERS, //7.8
				'UploadMediaStoryController.php' => $CONTROLLERS, //7.8
				'UploadMediaMessageController.php' => $CONTROLLERS, //7.8
				'UploadMediaFileShopController.php' => $CONTROLLERS, //7.8
				'UploadMediaController.php' => $CONTROLLERS, //7.8
				'UploadMediaPreviewShopController.php' => $CONTROLLERS, //7.8
				'UploadMediaVaultController.php' => $CONTROLLERS, //7.8
				'UserController.php' => $CONTROLLERS, //7.8 L:32-1254-1301-AvatarCoverModeration() - downloadFile() | L:2453

				'AgeVerificationStatus.php' => $MIDDLEWARE, //7.8

				// Views
				'css_general.blade.php' => $VIEWS_INCLUDES, //7.8 L:255-256-259-260
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //7.8
				'menu-sidebar-home.blade.php' => $VIEWS_INCLUDES, //7.8
				'navbar.blade.php' => $VIEWS_INCLUDES, //7.8
				'modal-scheduled-posts.blade.php' => $VIEWS_INCLUDES, //7.8
				'sidebar-messages-inbox.blade.php' => $VIEWS_INCLUDES, //7.8
				'messages-inbox.blade.php' => $VIEWS_INCLUDES, //7.8 
				'updates.blade.php' => $VIEWS_INCLUDES, //7.8 L:79-text-break | L:315-insertPostView()

				'profile.blade.php' => $VIEWS_USERS, //7.8 L:87-text-break
				'notifications.blade.php' => $VIEWS_USERS, //7.8
				'my_subscriptions.blade.php' => $VIEWS_USERS, //7.8 L:120
				'my_posts.blade.php' => $VIEWS_USERS, //7.8
				'edit_my_page.blade.php' => $VIEWS_USERS, //7.8
				'block_countries.blade.php' => $VIEWS_USERS, //7.8

				'home.blade.php' => $VIEWS_INDEX, //7.8 L:12-213

				'paginator-messages.js' =>  $PUBLIC_JS, //7.8
				'messages.js' =>  $PUBLIC_JS, //7.8

				'styles.css' => $PUBLIC_CSS, //7.8
				'bootstrap.min.css' => $PUBLIC_CSS, //7.8
				'bootstrap-dark.min.css' => $PUBLIC_CSS, //7.8
				'bootstrap.bundle.min.js' => $PUBLIC_JS, //7.8
			];

			$filesAdmin = [
				'live_streaming.blade.php' => $VIEWS_ADMIN, //7.8
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// English
				$replaceLangEN = "
		// Version 7.8
		'failed_verification_avatar' => 'Your avatar has failed the verification process',
		'failed_verification_cover' => 'Your cover has failed the verification process',
		'newest' => 'Newest',
		'unread' => 'Unread',
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 7.8
	'failed_verification_avatar' => 'Tu foto de perfil ha sido rechazada por el servicio de verificación',
	'failed_verification_cover' => 'Tu imagen de portada ha sido rechazada por el servicio de verificación',
	'newest' => 'Nuevo',
	'unread' => 'No leído',
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				if (!Schema::hasColumn('admin_settings', 'age_verification_countries')) {
					Schema::table('admin_settings', function ($table) {
						$table->longText('age_verification_countries')->nullable();
					});
				}

				if (!Schema::hasTable('post_views')) {
					Schema::create('post_views', function ($table) {
						$table->id();
						$table->unsignedBigInteger('user_id')->index();
						$table->unsignedBigInteger('updates_id')->index();
						$table->string('ip', 100)->index();
						$table->timestamps();
					});
				} // <<--- End Create Table

				if (!Schema::hasColumn('updates', 'post_views')) {
					Schema::table('updates', function ($table) {
						$table->unsignedBigInteger('post_views')->index();
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 7.8 ----->>

		if ($version == '7.9') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$pathPublic = "v$version/public/";
			$copy       = false;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //7.9
				'composer.lock' => $ROOT, //7.9

				'AdminController.php' => $CONTROLLERS, //7.9
				'AudioCallController.php' => $CONTROLLERS, //7.9
				'VideoCallController.php' => $CONTROLLERS, //7.9
				'LiveStreamingsController.php' => $CONTROLLERS, //7.9
				'LiveStreamingPrivateController.php' => $CONTROLLERS, //7.9

				'privacy_security.blade.php' => $VIEWS_USERS, //7.9
				'audio-call.blade.php' => $VIEWS_USERS, //7.9
				'video-call-live.blade.php' => $VIEWS_USERS, //7.9
				'live.blade.php' => $VIEWS_USERS, //7.9

				'javascript_general.blade.php' => $VIEWS_INCLUDES, //7.9

				'styles.css' => $PUBLIC_CSS, //7.9

				'app-functions.js' => $PUBLIC_JS, //7.9
				'agora-broadcast-client-v4.js' => public_path('js' . $DS . 'agora') . $DS, //7.9
			];

			$filesAdmin = [
				'settings.blade.php' => $VIEWS_ADMIN, //7.9
				'audio-call-settings.blade.php' => $VIEWS_ADMIN, //7.9
				'video-call-settings.blade.php' => $VIEWS_ADMIN, //7.9
				'live_streaming.blade.php' => $VIEWS_ADMIN, //7.9
			];

			// Files
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Files Admin
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Translate ==================
				// Replace String
				$findStringLang = ');';

				// English
				$replaceLangEN = "
		// Version 7.9
		'allow_delete_account' => 'Allow delete account',
		'allow_delete_account_notice' => 'To delete your account you have to send us a private message',
		'send_message_admin' => 'Send message to administrator',
		
);";
				$fileLangEN = 'lang/en/general.php';
				@file_put_contents($fileLangEN, str_replace($findStringLang, $replaceLangEN, file_get_contents($fileLangEN)));

				// Español
				$replaceLangES = "
	// Version 7.9
	'allow_delete_account' => 'Permitir eliminar cuenta',
	'allow_delete_account_notice' => 'Para eliminar tu cuenta tienes que enviarnos un mensaje privado',
	'send_message_admin' => 'Enviar mensaje al administrador',
	
);";
				$fileLangES = 'lang/es/general.php';
				@file_put_contents($fileLangES, str_replace($findStringLang, $replaceLangES, file_get_contents($fileLangES)));
				//============== End Translate ====================

				if (!Schema::hasColumn('admin_settings', 'allow_delete_account')) {
					Schema::table('admin_settings', function ($table) {
						$table->boolean('allow_delete_account')->default(1);
						$table->text('primary_certificate_agora')->nullable();
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //============ End $copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 7.9 ----->>


		if ($version == '7.9.1') {
			$oldVersion = $this->settings->version;
			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			if (!Schema::hasColumn('payment_gateways', 'coinpayments_legacy')) {
				Schema::table('payment_gateways', function (Blueprint $table) {
					$table->boolean('coinpayments_legacy')->default(0);
				});
			}

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 7.9.1 ----->>

		if ($version == '7.9.2') {
			$oldVersion = $this->settings->version;
			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion  || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('optimize:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 7.9.2 ----->>

	} //<--- End Method version
}
