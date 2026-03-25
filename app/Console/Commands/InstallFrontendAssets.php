<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class InstallFrontendAssets extends Command
{
    protected $signature = 'frontend:install {--force : Overwrite existing files}';
    protected $description = 'Download frontend CDN assets to public/ for offline use';

    public function handle()
    {
        $assets = [
            'alpine' => [
                'url' => 'https://cdn.jsdelivr.net/npm/@alpinejs/csp@3.x.x/dist/cdn.min.js',
                'path' => public_path('js/alpine.js'),
            ],
            'tailwind' => [
                'url' => 'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4',
                'path' => public_path('js/tailwind.js'),
            ],
        ];

        foreach ($assets as $name => $config) {
            if (File::exists($config['path']) && ! $this->option('force')) {
                $this->warn("✓ {$name} already exists. Use --force to overwrite.");
                continue;
            }

            $this->info("Downloading {$name}...");
            
            $response = Http::timeout(30)->get($config['url']);
            
            if ($response->successful()) {
                File::ensureDirectoryExists(dirname($config['path']));
                File::put($config['path'], $response->body());
                $this->info("✓ {$name} saved to {$config['path']}");
            } else {
                $this->error("✗ Failed to download {$name}: " . $response->status());
            }
        }

        $this->info('Frontend assets installation complete!');
        return 0;
    }
}