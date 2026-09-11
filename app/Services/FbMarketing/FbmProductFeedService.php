<?php

namespace App\Services\FbMarketing;

use Illuminate\Database\Eloquent\Builder;

class FbmProductFeedService
{
    public const CANONICAL_FLAG = FbmFeedProductProjectionService::CANONICAL_FLAG;
    public const LEGACY_FLAG = FbmFeedProductProjectionService::LEGACY_FLAG;

    public function __construct(
        private FbmProductFeedCacheService $feedCache,
        private FbmFeedProductProjectionService $products
    ) {
    }

    public function xml(): string
    {
        return $this->feedCache->rememberXml(fn(): string => $this->buildXml());
    }

    public function selectionColumn(): ?string
    {
        return $this->products->selectionColumn();
    }

    public function selectionMode(): string
    {
        return $this->products->selectionMode();
    }

    public function selectedProductsQuery(): Builder
    {
        return $this->products->selectedProductsQuery();
    }

    public function siteUrl(): string
    {
        return $this->products->siteUrl();
    }

    private function buildXml(): string
    {
        $settings = $this->feedCache->settings();
        $googleNamespace = 'http://base.google.com/ns/1.0';
        $feedName = trim((string) config('app.name', '')) ?: 'ERP';
        $rss = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss/>');
        $rss->addAttribute('version', '2.0');
        $rss->addAttribute('xmlns:g', $googleNamespace);
        $channel = $rss->addChild('channel');
        $channel->addChild('title', $this->xmlText($feedName . ' Product Feed'));
        $channel->addChild('link', $this->xmlText($this->siteUrl()));
        $channel->addChild('description', $this->xmlText($settings['feed_enabled'] ? 'Product feed' : 'Product feed disabled by application configuration'));

        if (!$settings['feed_enabled']) {
            return (string) $rss->asXML();
        }

        $this->products->feedReadyProductsForChunking()->chunkById(200, function ($products) use ($channel, $googleNamespace): void {
            foreach ($products as $product) {
                $row = $this->products->project($product);
                if (!$row['is_feed_ready']) {
                    continue;
                }

                $item = $channel->addChild('item');
                $item->addChild('g:id', $this->xmlText((string) $row['id']), $googleNamespace);
                $item->addChild('g:title', $this->xmlText($row['title']), $googleNamespace);
                $item->addChild('g:description', $this->xmlText($row['description']), $googleNamespace);
                $item->addChild('g:link', $this->xmlText($row['link']), $googleNamespace);
                $item->addChild('g:image_link', $this->xmlText((string) $row['image_link']), $googleNamespace);
                $item->addChild('g:availability', $row['availability'], $googleNamespace);
                $item->addChild('g:price', number_format((float) $row['price_amount'], 2, '.', '') . ' ' . $row['currency'], $googleNamespace);
                $item->addChild('g:condition', 'new', $googleNamespace);
                $item->addChild('g:brand', $this->xmlText($row['brand']), $googleNamespace);
            }
        });

        return (string) $rss->asXML();
    }

    private function xmlText(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
