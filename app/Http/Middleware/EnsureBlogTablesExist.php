<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Avoid SQL errors when blog migrations have not been run on the server yet.
 * GET /blog still renders an empty blog index; other blog URLs return 503.
 */
class EnsureBlogTablesExist
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        $isBlogPublic = $path === 'blog' || str_starts_with($path, 'blog/');
        $isBlogAdmin = str_starts_with($path, 'admin/blog');

        if (! $isBlogPublic && ! $isBlogAdmin) {
            return $next($request);
        }

        if (Schema::hasTable('blog_posts')) {
            return $next($request);
        }

        if ($path === 'blog' && $request->isMethod('GET')) {
            return $next($request);
        }

        abort(503, __('blog.migrations_required'));
    }
}
