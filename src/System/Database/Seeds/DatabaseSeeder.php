<?php

namespace Igniter\System\Database\Seeds;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public static $siteUrl = 'http://localhost/';

    public static $siteName = 'TastyIgniter';

    public static $siteEmail = 'admin@domain.tld';

    public static $staffName = 'Chef Admin';

    public static $username = 'admin';

    public static $password = '123456';

    public static $seedInitial = true;

    public static $seedDemo = false;

    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {
        $this->call([
            InitialSchemaSeeder::class,
            DemoSchemaSeeder::class,
            UpdateRecordsSeeder::class,
        ]);
    }
}
