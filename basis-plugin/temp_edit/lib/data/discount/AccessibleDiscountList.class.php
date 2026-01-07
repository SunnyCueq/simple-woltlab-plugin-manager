<?php

namespace shrinkr\data\discount;

use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Returns discounts accessible for a specific URL and host.
 * Filters discounts by host matching and URL validation.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.discount
 */
class AccessibleDiscountList extends ViewableDiscountList
{
    /**
     * @inheritDoc
     */
    public function __construct(
        protected string $url,
        protected string $host = '',
    ) {
        parent::__construct();

        // Validate hostname format
        if (!empty($this->host) && !$this->isValidHostname($this->host)) {
            $this->host = '';
        }

        $this->sqlLimit = 1;

        $specialDiscount = $this->getSpecial();
        if ($specialDiscount !== null) {
            $discountIDs = $specialDiscount->getObjectIDs();
            $this->getConditionBuilder()->add('discount.discountID IN (?)', [$discountIDs]);
        } elseif (!empty($this->host)) {
            // Normalize host: remove www. prefix for matching
            $normalizedHost = preg_replace('/^www\./i', '', $this->host);
            
            // Match either:
            // 1. URL host is contained in stored hosts (e.g. stored: "mediamarkt.de", URL: "mediamarkt.de")
            // 2. Stored host is contained in URL host (e.g. stored: "mediamarkt.de", URL: "www.mediamarkt.de")
            // Use normalized host without www for better matching
            $this->getConditionBuilder()->add(
                '(discount.hosts LIKE ? OR discount.hosts LIKE ? OR ? LIKE CONCAT(\'%\', discount.hosts, \'%\'))',
                [
                    '%' . $this->host . '%',           // Original host (mit www)
                    '%' . $normalizedHost . '%',       // Ohne www
                    $this->host                        // URL-Host enthält gespeicherten Host
                ]
            );
        }
    }

    /**
     * Validates if the given hostname is in a valid format.
     */
    private function isValidHostname(string $hostname): bool
    {
        return (bool)preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i', $hostname);
    }

    /**
     * Returns discount list for special identifier or null if not found.
     */
    protected function getSpecial(): ?DiscountList
    {
        $parsedUrl = parse_url($this->url);
        if (!isset($parsedUrl['query'])) {
            return null;
        }

        parse_str($parsedUrl['query'], $getParameters);

        if (!isset($getParameters['special']) || !is_string($getParameters['special'])) {
            return null;
        }

        $specialIdentifier = StringUtil::trim($getParameters['special']);
        if (empty($specialIdentifier) || mb_strlen($specialIdentifier) > 255) {
            return null;
        }

        $discountList = new DiscountList();
        $discountList->getConditionBuilder()->add('discount.specialIdentifier = ?', [$specialIdentifier]);
        $discountList->getConditionBuilder()->add('discount.hosts LIKE ?', ['%' . $this->host . '%']);
        $discountList->readObjects();

        return count($discountList) > 0 ? $discountList : null;
    }
}
