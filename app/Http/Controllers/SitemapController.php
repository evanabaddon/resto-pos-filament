<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $baseUrl = rtrim(config('app.url'), '/');

        $urls = [
            [
                'loc' => $baseUrl,
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '1.0'
            ],
            [
                'loc' => $baseUrl . '/menu',
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8'
            ]
        ];

        // Build XML manually to avoid Blade rendering issues
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "    <url>\n";
            $xml .= "        <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
            $xml .= "        <lastmod>" . $url['lastmod'] . "</lastmod>\n";
            $xml .= "        <changefreq>" . $url['changefreq'] . "</changefreq>\n";
            $xml .= "        <priority>" . $url['priority'] . "</priority>\n";
            $xml .= "    </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
