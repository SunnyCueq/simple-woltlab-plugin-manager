<?php

namespace shrinkr\data\discount;

use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Returns discounts accessible for a specific URL and host.
 * 
 * Filters discounts by host matching and URL validation. Supports special
 * event identifiers via URL query parameters. Normalizes hostnames (removes
 * www. prefix) for better matching. Extends ViewableDiscountList to provide
 * filtered discount lists for frontend display.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.discount
 */
class AccessibleDiscountList extends ViewableDiscountList
{
    /**
     * Constructs a new AccessibleDiscountList.
     * 
     * Validates the hostname format, checks for special event identifiers
     * in URL query parameters, and applies host-based filtering. Limits
     * results to 1 discount per URL.
     *
     * @param   string  $url   Target URL to find discounts for
     * @param   string  $host  Hostname extracted from URL (optional, will be validated)
     * @return  void
     */
    public function __construct(
        protected string $url,
        protected string $host = '',
    ) {
        parent::__construct();

        if (!empty($this->host) && !$this->isValidHostname($this->host)) {
            $this->host = '';
        }

        $this->sqlLimit = 1;

        $specialDiscount = $this->getSpecial();
        if ($specialDiscount !== null) {
            $discountIDs = $specialDiscount->getObjectIDs();
            $this->getConditionBuilder()->add('discount.discountID IN (?)', [$discountIDs]);
        } elseif (!empty($this->host)) {
            $normalizedHost = preg_replace('/^www\./i', '', $this->host);
            
            $this->getConditionBuilder()->add(
                '(discount.hosts LIKE ? OR discount.hosts LIKE ? OR ? LIKE CONCAT(\'%\', discount.hosts, \'%\'))',
                [
                    '%' . $this->host . '%',
                    '%' . $normalizedHost . '%',
                    $this->host
                ]
            );
        }
    }

    /**
     * Validates if the given hostname is in a valid format.
     * 
     * Checks hostname format using RFC-compliant regex pattern. Allows
     * alphanumeric characters, hyphens, and dots.
     *
     * @param   string  $hostname  Hostname to validate
     * @return  bool              True if hostname format is valid, false otherwise
     */
    private function isValidHostname(string $hostname): bool
    {
        return (bool)preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i', $hostname);
    }

    /**
     * Returns discount list for special identifier or null if not found.
     * 
     * Parses URL query parameters for a "special" parameter and matches it
     * against stored special identifiers. Returns a DiscountList if a match
     * is found, null otherwise.
     *
     * @return  DiscountList|null  Discount list for special identifier, or null if not found
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
