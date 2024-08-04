<?php

namespace Igniter\System\Console\Commands;

use Igniter\Flame\Igniter;
use Igniter\Flame\Support\ConfigRewrite;
use Igniter\Local\Models\Location;
use Igniter\Main\Models\Theme;
use Igniter\System\Classes\ComposerManager;
use Igniter\System\Database\Seeds\DatabaseSeeder;
use Igniter\System\Helpers\SystemHelper;
use Igniter\System\Models\Language;
use Igniter\System\Models\Settings;
use Igniter\User\Facades\AdminAuth;
use Igniter\User\Models\User;
use Igniter\User\Models\UserGroup;
use Igniter\User\Models\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;

/**
 * Console command to install TastyIgniter.
 * This sets up TastyIgniter for the first time. It will prompt the user for several
 * configuration items, including application URL and database config, and then
 * perform a database migration.
 */
class IgniterInstall extends Command
{
    use \Illuminate\Console\ConfirmableTrait;

    protected const CONFIRM_CREATE_STORAGE_LINK = 'Create a symbolic link of <options=bold>storage/app/public</> at <options=bold>public/storage</> to make uploaded files publicly available?';

    protected const LOGIN_TO_ADMIN_DASHBOARD = 'You can now login to the TastyIgniter Admin at %s with credentials provided during installation.';

    protected const CONFIRM_SHOW_LOVE = 'Do you want to show some love by starring the TastyIgniter repository on GitHub?';

    /**
     * The console command name.
     */
    protected $name = 'igniter:install';

    /**
     * The console command description.
     */
    protected $description = 'Set up TastyIgniter for the first time.';

    protected ConfigRewrite $configRewrite;

    protected array $dbConfig = [];

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->configRewrite = new ConfigRewrite;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        resolve(ComposerManager::class)->assertSchema();

        if ($this->shouldSkipSetup()) {
            return false;
        }

        $this->alert('INSTALLATION STARTED');

        $this->setSeederProperties();

        $this->rewriteEnvFile();

        $this->migrateDatabase();

        $this->createSuperUser();

        $this->addSystemValues();

        if ($this->confirm(self::CONFIRM_CREATE_STORAGE_LINK, true)) {
            $this->call('storage:link');
        }

        $this->activateAndPublishDefaultTheme();

        $this->alert('INSTALLATION COMPLETE');

        if ($this->confirm(self::CONFIRM_SHOW_LOVE)) {
            $this->openBrowser('https://github.com/tastyigniter/TastyIgniter');
        }

        $this->alert(sprintf(self::LOGIN_TO_ADMIN_DASHBOARD, admin_url('login')));
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }

    protected function rewriteEnvFile()
    {
        if (!file_exists(base_path().'/.env')) {
            $this->moveExampleFile('env', null, 'backup');
            $this->copyExampleFile('env', 'example', null);
        }

        if (!$this->laravel['config']['app.key']) {
            SystemHelper::replaceInEnv('APP_KEY=', 'APP_KEY='.$this->generateEncryptionKey());
        }

        SystemHelper::replaceInEnv('APP_NAME=', 'APP_NAME="'.DatabaseSeeder::$siteName.'"');
        SystemHelper::replaceInEnv('APP_URL=', 'APP_URL='.DatabaseSeeder::$siteUrl);

        $name = Config::get('database.default');
        foreach ($this->dbConfig as $key => $value) {
            Config::set("database.connections.$name.".strtolower($key), $value);

            if ($key === 'password') {
                $value = '"'.$value.'"';
            }
            SystemHelper::replaceInEnv('DB_'.strtoupper($key).'=', 'DB_'.strtoupper($key).'='.$value);
        }
    }

    protected function migrateDatabase()
    {
        DB::purge();

        $this->line('Migrating application and extensions...');

        $this->call('igniter:up', ['--force' => true]);

        $this->line('Done. Migrating application and extensions...');
    }

    protected function setSeederProperties()
    {
        $this->line('Enter a new value, or press ENTER for the default');

        $name = Config::get('database.default');
        $this->dbConfig['host'] = $this->ask('MySQL Host', Config::get("database.connections.$name.host"));
        $this->dbConfig['port'] = $this->ask('MySQL Port', Config::get("database.connections.$name.port") ?: false) ?: '';
        $this->dbConfig['database'] = $this->ask('MySQL Database', Config::get("database.connections.$name.database"));
        $this->dbConfig['username'] = $this->ask('MySQL Username', Config::get("database.connections.$name.username"));
        $this->dbConfig['password'] = $this->ask('MySQL Password', Config::get("database.connections.$name.password") ?: false) ?: '';
        $this->dbConfig['prefix'] = $this->ask('MySQL Table Prefix', Config::get("database.connections.$name.prefix") ?: false) ?: '';

        DatabaseSeeder::$siteName = $this->ask('Site Name', DatabaseSeeder::$siteName);
        DatabaseSeeder::$siteUrl = $this->ask('Site URL', Config::get('app.url'));

        DatabaseSeeder::$seedDemo = $this->confirm('Install demo data?', !Igniter::hasDatabase());
    }

    protected function createSuperUser()
    {
        DatabaseSeeder::$staffName = $this->ask('Admin Name', DatabaseSeeder::$staffName);
        DatabaseSeeder::$siteEmail = $this->output->ask('Admin Email', DatabaseSeeder::$siteEmail, function($answer) {
            if (User::whereEmail($answer)->first()) {
                throw new \RuntimeException('An administrator with that email already exists, please choose a different email.');
            }

            return $answer;
        });
        DatabaseSeeder::$password = $this->output->ask('Admin Password', '123456', function($answer) {
            if (!is_string($answer) || strlen($answer) < 6 || strlen($answer) > 32) {
                throw new \RuntimeException('Please specify the administrator password, at least 6 characters');
            }

            return $answer;
        });
        DatabaseSeeder::$username = $this->output->ask('Admin Username', 'admin', function($answer) {
            if (User::whereUsername($answer)->first()) {
                throw new \RuntimeException('An administrator with that username already exists, please choose a different username.');
            }

            return $answer;
        });

        $user = AdminAuth::getProvider()->register([
            'email' => DatabaseSeeder::$siteEmail,
            'name' => DatabaseSeeder::$staffName,
            'language_id' => Language::first()->language_id,
            'user_role_id' => UserRole::first()->user_role_id,
            'status' => true,
            'username' => DatabaseSeeder::$username,
            'password' => DatabaseSeeder::$password,
            'super_user' => true,
            'groups' => [UserGroup::first()->user_group_id],
            'locations' => [Location::first()->location_id],
        ], true);

        $this->line('Admin user '.$user->username.' created!');
    }

    protected function addSystemValues()
    {
        Settings::set('ti_setup', 'installed', 'prefs');

        Settings::set([
            'site_name' => DatabaseSeeder::$siteName,
            'site_email' => DatabaseSeeder::$siteEmail,
            'sender_name' => DatabaseSeeder::$siteName,
            'sender_email' => DatabaseSeeder::$siteEmail,
        ]);
    }

    protected function generateEncryptionKey()
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    protected function moveExampleFile(string $name, ?string $old, ?string $new)
    {
        // /$old.$name => /$new.$name
        if (file_exists(base_path().'/'.$old.'.'.$name)) {
            rename(base_path().'/'.$old.'.'.$name, base_path().'/'.$new.'.'.$name);
        }
    }

    protected function copyExampleFile(string $name, ?string $old, ?string $new)
    {
        // /$old.$name => /$new.$name
        if (file_exists(base_path().'/'.$old.'.'.$name)) {
            if (file_exists(base_path().'/'.$new.'.'.$name)) {
                unlink(base_path().'/'.$new.'.'.$name);
            }

            copy(base_path().'/'.$old.'.'.$name, base_path().'/'.$new.'.'.$name);
        }
    }

    protected function shouldSkipSetup()
    {
        if (!Igniter::hasDatabase() || $this->option('force')) {
            return false;
        }

        return !$this->confirm('Application appears to be installed already. Continue anyway?', false);
    }

    protected function openBrowser(string $url)
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            exec('open '.$url);
        } elseif (PHP_OS_FAMILY === 'Windows') {
            exec('start '.$url);
        } else {
            exec('xdg-open '.$url);
        }
    }

    protected function activateAndPublishDefaultTheme(): void
    {
        $this->call('igniter:theme-vendor-publish');

        Theme::syncAll();
        Theme::activateTheme(config('igniter-system.defaultTheme'), true);
    }
}
