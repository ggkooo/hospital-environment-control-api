<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiKey extends Command
{
    protected $signature = 'api:generate-key {--show : Display the key instead of modifying files}';
    protected $description = 'Generate a new API key for sensor data access';

    public function handle()
    {
        $key = 'sk_' . Str::random(32);

        if ($this->option('show')) {
            $this->line('<comment>Generated API Key:</comment>');
            $this->line($key);
            $this->newLine();
            $this->info('You can use this key in your .env file or pass it directly to the API.');
            return;
        }

        $this->line('<comment>Generated API Key:</comment>');
        $this->line($key);
        $this->newLine();

        $this->info('Add this key to your .env file:');
        $this->line('API_KEY_1=' . $key);
        $this->newLine();

        $this->info('Or use it directly in API requests:');
        $this->line('Header: X-API-Key: ' . $key);
        $this->line('Query: ?api_key=' . $key);
    }
}
