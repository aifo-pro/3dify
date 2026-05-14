<?php

namespace App\Console\Commands;

use Database\Seeders\BlogDemoPetgGuideSeeder;
use Illuminate\Console\Command;

class BlogInstallPetgDemoCommand extends Command
{
    protected $signature = 'blog:install-petg-demo';

    protected $description = 'Create/update the PETG demo article and sync its content blocks (for servers without seeded blocks)';

    public function handle(): int
    {
        $this->call(BlogDemoPetgGuideSeeder::class);

        return self::SUCCESS;
    }
}
