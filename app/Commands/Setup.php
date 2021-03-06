<?php

namespace App\Commands;

use App\Helpers\SettingsHelper;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LaravelZero\Framework\Commands\Command;
use Storage;

class Setup extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'setup';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Well... It sets things up.';

    private $settings;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->confirm("This will destroy any existing doddns configuration. Is that ok?")) {
            $this->createDatabase();
        }

        $this->settings = new SettingsHelper();

        $token = $this->ask("What is your Digital Ocean peronal access token?");

        if ($this->settings->error !== null) {
            $this->insertToken($token);
        } else {
            $this->updateToken($token);
        }

        $this->info("All done! We're good to go!");
    }

    private function createDatabase()
    {
        $this->task("Creating local database", function () {
            if (!is_dir($_SERVER['HOME'].'/.doddns/')) {
                mkdir($_SERVER['HOME'].'/.doddns/', 0700);
                $this->info("Created .doddns directory in user's home.");
            }

            file_put_contents(config('database.connections.sqlite.database'), "");
            $this->info("Created or overwrited any actual databse");

            Artisan::call('migrate', ['--force' => true]);

            $this->info("Migrated tables");

            return true;
        });
    }

    private function updateToken($token)
    {
        if ($this->confirm('Do you wish to overwrite existing saved token?')) {
            DB::table('settings')->update(
                ['token' => $token]
            );
        }

        $this->info("Token updated!");
    }

    private function insertToken($token)
    {
        DB::table('settings')->insert(
            ['token' => $token]
        );

        $this->info("Token added!");
    }
}
