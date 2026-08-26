<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SeoSitemapCommand extends Command
{
    protected $signature = 'growth:seo-sitemap';
    protected $description = 'Generate GoodTripLove sitemap index placeholder';

    public function handle(): int
    {
        $public = public_path('sitemaps');
        File::ensureDirectoryExists($public);

        $index = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <!-- Generate country/city/category/place/video sitemap chunks here -->
</sitemapindex>
XML;

        File::put($public.'/sitemap-index.xml', $index);

        $this->info('Sitemap index generated.');
        return self::SUCCESS;
    }
}
