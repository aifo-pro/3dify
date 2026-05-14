<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BlogDoctorCommand extends Command
{
    protected $signature = 'blog:doctor';

    protected $description = 'Print blog-related schema and migration rows (debug 500 / empty blocks)';

    public function handle(): int
    {
        $this->line('PHP '.PHP_VERSION);
        $this->line('blog_posts table: '.(Schema::hasTable('blog_posts') ? 'yes' : 'no'));
        $this->line('blog_post_blocks table: '.(Schema::hasTable('blog_post_blocks') ? 'yes' : 'no'));
        $this->line('BlogPost::hasBlogPostBlocksTable(): '.(BlogPost::hasBlogPostBlocksTable() ? 'yes' : 'no'));

        if (Schema::hasTable('blog_posts')) {
            $this->line('blog_posts count: '.DB::table('blog_posts')->count());
        }
        if (Schema::hasTable('blog_post_blocks')) {
            $this->line('blog_post_blocks count: '.DB::table('blog_post_blocks')->count());
        }

        if (Schema::hasTable('migrations')) {
            $this->newLine();
            $this->info('Migrations rows containing "blog":');
            $rows = DB::table('migrations')
                ->where('migration', 'like', '%blog%')
                ->orderBy('id')
                ->pluck('migration');
            foreach ($rows as $m) {
                $this->line('  - '.$m);
            }
            if ($rows->isEmpty()) {
                $this->warn('  (none)');
            }
        }

        return self::SUCCESS;
    }
}
