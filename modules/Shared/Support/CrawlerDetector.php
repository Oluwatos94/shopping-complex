<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Shared\Support;

final class CrawlerDetector
{
    private const PATTERN = '/bot\b|bot\/|crawl|spider|slurp|mediapartners|adsbot|googleother|google-inspectiontool'
        .'|facebookexternalhit|facebookcatalog|meta-externalagent|whatsapp\/|telegram|twitterbot|linkedinbot|discordbot'
        .'|slackbot|skypeuripreview|embedly|preview|pinterest|redditbot|applebot|bytespider|petalbot|yandex|baidu'
        .'|semrush|ahrefs|mj12|dotbot|lighthouse|pingdom|uptime|monitor|headless|phantomjs|puppeteer|playwright|selenium'
        .'|curl|wget|python|httpclient|http-client|okhttp|go-http|java\/|axios|node-fetch|undici|libwww|scrapy|postman/i';

    /**
     * True for search crawlers, link previewers, uptime monitors and scripted
     * clients — anything that fetches a URL without a person behind it. An
     * empty user agent counts: real browsers always send one.
     */
    public static function isCrawler(?string $userAgent): bool
    {
        $userAgent = trim((string) $userAgent);

        return $userAgent === '' || preg_match(self::PATTERN, $userAgent) === 1;
    }
}
