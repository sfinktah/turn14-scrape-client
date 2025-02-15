<?php

namespace Sfinktah\Gearman;

# Note: it would be possible (but painful) to drive Selenium/Chromedriver directly from PHP and skip the gearman step.
# SEE:  https://github.com/php-webdriver/php-webdriver

require "MarkleCache.php";

use Exception;
use GearmanClient;
use MarkleCache;

class Turn14ScrapeClient
{

    public static function make(): Turn14ScrapeClient {
        return new self();
    }

    public function scrapeError($message): string {
        return json_encode([
            'success' => false,
            'message' => 'gearman_error',
            'data' => [
                'type' => 'gearman_error',
                'message' => $message
            ]
        ]);
    }

    public function callGearman($sku): string {
        $gmclient = new GearmanClient();
        $gmclient->addServer("localhost", 4730);
        $result = $gmclient->doNormal("turn14", trim($sku));
        switch ($gmclient->returnCode()) {
            case 0:
            case GEARMAN_WORK_DATA:
                return $result;
            case GEARMAN_WORK_FAIL:
                return $this->scrapeError("GEARMAN_WORK_FAIL");
            default:
                return $this->scrapeError("Unknown Code: " . $gmclient->returnCode() . " $result");
        }
    }

    public function scrape($sku, ?callable $callback, mixed $callbackData) {
        $cacheKey = "turn14-1-$sku";
        $result = MarkleCache::remember($cacheKey, 86400 * 7, function () use ($sku) {
            return $this->callGearman($sku);
        });
        try {
            $j = json_decode($result, true);
            if (empty($j['success'])) {
                MarkleCache::forget($cacheKey);
            } else {
                // reset timeout
                MarkleCache::put($cacheKey, $result, 86400 * 7);
            }
            $j['data'] = array_merge(...$j['data']);
        } catch (Exception $e) {
            return $this->scrapeError("Exception" . $e->getMessage());
        }

        if (!empty($callback) and is_callable($callback)) {
            return $callback($j, $callbackData);
        }

        return $j;
    }

    public function test(): void {
        $sku = 'ede1218';
        $result = $this->scrape($sku);
        // print_r($result);
        if (is_array($result) and array_key_exists('success', $result) and $result['success'] === true) {
            printf("data.productDetails: %s\n\n", json_encode($result['data']['productDetails'], JSON_PRETTY_PRINT));
            printf("data.productOverview: %s\n\n", $result['data']['productOverview']);
            printf("data.html.productOverview: %s\n\n", $result['data']['html']['productOverview']);
        }
    }
}
