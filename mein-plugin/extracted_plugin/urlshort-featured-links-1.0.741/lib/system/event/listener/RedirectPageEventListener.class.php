<?php

namespace urlshort\system\event\listener;

use urlshort\data\discount\AccessibleDiscountList;
use urlshort\data\discount\Discount;
use urlshort\data\description\AccessibleDescriptionList;
use urlshort\data\special\Special;
use urlshort\data\theme\Theme;
use urlshort\data\visit\VisitEditor;
use urlshort\data\url\UrlEditor;
use urlshort\page\RedirectPage;
use urlshort\system\favicon\FaviconHandler;
use wcf\data\option\Option;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\reaction\ReactionHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;
use urlshort\util\UrlFeaturedLinksUtil;

/**
 * Event listener for the redirect page.
 * Handles loading of discounts, specials, featured links, descriptions, and theme effects.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
 * @subpackage system.event.listener
 */
final class RedirectPageEventListener extends AbstractEventListener
{
    /**
     * List of featured links for the current URL.
     *
     * @var array<string, array{title: string, host: string}>
     */
    public array $featuredLinks = [];

    /**
     * List of custom buttons for the current URL.
     *
     * @var array<int, array{title: string, targetUrl: string, customButtonID: int}>
     */
    public array $customButtons = [];

    /**
     * Discount to display on the redirect page.
     *
     * @var Discount|null
     */
    public ?object $discount = null;

    /**
     * Active special for this URL (if any).
     *
     * @var Special|null
     */
    public ?Special $special = null;

    /**
     * Theme object for the active special (if any).
     *
     * @var Theme|null
     */
    public ?Theme $specialTheme = null;
    
    /**
     * Short theme name (without parenthesis content) for promo badge center display.
     *
     * @var string|null
     */
    public ?string $specialThemeShortName = null;

    /**
     * Random description text to display.
     *
     * @var string
     */
    public string $randomDescription = '';

    /**
     * Extracted page title (or fallback).
     *
     * @var string
     */
    private string $extractedTitle = '';

    /**
     * Cache for loaded themes by identifier.
     *
     * @var array<string, Theme|null>
     */
    private array $themeCache = [];

    /**
     * @inheritDoc
     */
    protected function onAssignVariables(RedirectPage $eventObj): void
    {
        // Early return if URL is null
        if ($eventObj->url === null) {
            return;
        }
        
        $url = $eventObj->url->url ?? '';

        // Load reaction data if MODULE_LIKE is enabled and reactions are enabled via option
        $reactionData = [];
        $objectType = null;
        $reactionsOption = Option::getOptionByName('urlshort_featuredLinks_enableReactions');
        $enableReactions = $reactionsOption ? $reactionsOption->optionValue : 1;
        $guestReactionsOption = Option::getOptionByName('urlshort_featuredLinks_enableGuestReactions');
        $enableGuestReactions = $guestReactionsOption ? $guestReactionsOption->optionValue : 0;
        
        if (MODULE_LIKE && $enableReactions && $eventObj->url !== null && isset($eventObj->url->urlID) && $eventObj->url->urlID) {
            $objectType = ReactionHandler::getInstance()->getObjectType('info.benjaro.urlshort.affiliate.likeableUrl');
            if ($objectType !== null) {
                $likeObject = ReactionHandler::getInstance()->getLikeObject($objectType, $eventObj->url->urlID);
                if ($likeObject !== null) {
                    // Add guest reactions to likeObject if enabled
                    if ($enableGuestReactions) {
                        $this->addGuestReactionsToLikeObject($likeObject, $objectType->objectType, $eventObj->url->urlID);
                    }
                    $reactionData[$eventObj->url->urlID] = $likeObject;
                } elseif ($enableGuestReactions) {
                    // Create a minimal likeObject if only guest reactions exist
                    $likeObject = $this->createLikeObjectWithGuestReactions($objectType, $eventObj->url->urlID);
                    if ($likeObject !== null) {
                        $reactionData[$eventObj->url->urlID] = $likeObject;
                    }
                }
            }
        }

        // Get option values for templates
        $reactionsOption = Option::getOptionByName('urlshort_featuredLinks_enableReactions');
        $enableReactions = $reactionsOption ? $reactionsOption->optionValue : 1;
        $guestReactionsOption = Option::getOptionByName('urlshort_featuredLinks_enableGuestReactions');
        $enableGuestReactions = $guestReactionsOption ? $guestReactionsOption->optionValue : 0;
        $descriptionsOption = Option::getOptionByName('urlshort_featuredLinks_enableDescriptions');
        $enableDescriptions = $descriptionsOption ? $descriptionsOption->optionValue : 1;

        // If special is active, use special data for discount display
        $displayDiscount = $this->discount;
        $displayCodes = null;
        $displayAdditionalText = null;
        $displayColors = null;
        $specialCountdownSeconds = null;

        if ($this->special && $this->special->isCurrentlyActive()) {
            // Create a pseudo-discount object from special data
            // Use discount field (e.g. "30%") for display, not title (title is only for overview)
            $rawDiscountValue = $this->special->discount ?: $this->special->title;
            
            // Load theme if specified and get colors (for use in displayDiscount)
            // This also caches the theme for determineThemeEffect() later
            $themeColors = null;
            if (!empty($this->special->theme)) {
                $theme = $this->loadThemeByIdentifier($this->special->theme);
                if ($theme !== null && $theme->isActive) {
                    $themeColors = $theme->getColors();
                }
            }
            
            // Create anonymous class that mimics Discount behavior
            $displayDiscount = new class($rawDiscountValue, $this->special, $themeColors) {
                private string $discountValue;
                private Special $special;
                private ?array $themeColors;
                
                public function __construct(string $discountValue, Special $special, ?array $themeColors) {
                    $this->discountValue = $discountValue;
                    $this->special = $special;
                    $this->themeColors = $themeColors;
                }
                
                public function __isset(string $name): bool
                {
                    switch ($name) {
                        case 'discountID':
                        case 'discountValue':
                        case 'codes':
                        case 'additionalText':
                        case 'primaryColor':
                        case 'secondaryColor':
                        case 'primaryTextColor':
                        case 'secondaryTextColor':
                        case 'countdownEnd':
                        case 'countdownStart':
                            return true;
                        default:
                            return false;
                    }
                }
                
                public function __get(string $name) {
                    // Map special properties to discount-like properties
                    switch ($name) {
                        case 'discountID':
                            return $this->special->specialID;
                        case 'discountValue':
                            return $this->discountValue;
                        case 'codes':
                            return $this->special->codes;
                        case 'additionalText':
                            return $this->special->additionalText;
                        case 'primaryColor':
                            // Use theme color if available and not empty, otherwise fallback to special color
                            if ($this->themeColors !== null && !empty($this->themeColors['primaryColor'])) {
                                return $this->themeColors['primaryColor'];
                            }
                            // Fallback to default WoltLab CSS variable if no color is set
                            return $this->special->primaryColor ?? 'var(--wcfHeaderBackground)';
                        case 'secondaryColor':
                            if ($this->themeColors !== null && !empty($this->themeColors['secondaryColor'])) {
                                return $this->themeColors['secondaryColor'];
                            }
                            // Fallback to default WoltLab CSS variable if no color is set
                            return $this->special->secondaryColor ?? 'var(--wcfHeaderMenuBackground)';
                        case 'primaryTextColor':
                            if ($this->themeColors !== null && !empty($this->themeColors['primaryTextColor'])) {
                                return $this->themeColors['primaryTextColor'];
                            }
                            // Fallback to default WoltLab CSS variable if no color is set
                            return $this->special->primaryTextColor ?? 'var(--wcfHeaderMenuLink)';
                        case 'secondaryTextColor':
                            if ($this->themeColors !== null && !empty($this->themeColors['secondaryTextColor'])) {
                                return $this->themeColors['secondaryTextColor'];
                            }
                            // Fallback to default WoltLab CSS variable if no color is set
                            return $this->special->secondaryTextColor ?? 'var(--wcfHeaderMenuLink)';
                        case 'countdownEnd':
                            return $this->special->endTime;
                        case 'countdownStart':
                            return $this->special->startTime;
                        default:
                            return null;
                    }
                }
                
                public function getFormattedDiscountValue(): string {
                    $numberOnly = preg_replace('/[^\d]/', '', trim($this->discountValue));
                    return !empty($numberOnly) ? $numberOnly . '%' : trim($this->discountValue);
                }
                
                public function hasValidCodes(): bool {
                    return !empty($this->special->codes) && $this->special->codes !== 'n/a';
                }
                
                public function getCodesList(): array {
                    if (empty($this->special->codes) || $this->special->codes === 'n/a') {
                        return [];
                    }
                    
                    $codes = array_map('trim', explode(',', $this->special->codes));
                    $result = [];
                    
                    foreach ($codes as $code) {
                        if (empty($code)) {
                            continue;
                        }
                        
                        $label = 'Aktionscode';
                        if ($code === 'BENJARO') {
                            $label = 'Standard';
                        } elseif ($code === 'AD6EE065') {
                            $label = 'Regenbogenkreis';
                        }
                        
                        $result[] = [
                            'code' => $code,
                            'label' => $label,
                        ];
                    }
                    
                    return $result;
                }
                
                public function getCodesCount(): int {
                    return count($this->getCodesList());
                }
                
                public function getCodesLabel(): string {
                    return $this->getCodesCount() === 1 ? 'Rabattcode' : 'Rabattcodes';
                }
            };
            
            $specialCountdownSeconds = $this->special->getRemainingSeconds();
        }

        $activeThemeEffect = $this->determineThemeEffect();
        
        // Load active theme for CSS file loading
        $activeThemeIdentifier = null;
        if ($this->special && $this->special->isCurrentlyActive()) {
            $activeTheme = $this->loadThemeByIdentifier($this->special->theme);
            if ($activeTheme && $activeTheme->isActive) {
                $activeThemeIdentifier = $activeTheme->identifier;
            }
        }

        // Clean share URL: getShortedUrl(true) always adds a slash,
        // even if the URL already ends with one, which leads to double slashes.
        // Solution: Use getShortedUrl(false) and then add exactly one slash.
        if ($eventObj->url === null) {
            // URL not found, skip share URL generation
            $shareUrl = '';
        } else {
            $shareUrl = $eventObj->url->getShortedUrl(false);
            $shareUrl = \rtrim($shareUrl, '/') . '/';
        }

        // Get current guest reaction (if any)
        $guestReactionTypeID = 0;
        if ($enableGuestReactions && !WCF::getUser()->userID && $eventObj->url !== null && isset($eventObj->url->urlID)) {
            $sessionID = WCF::getSession()->sessionID;
            $sql = "SELECT  reactionTypeID
                    FROM    urlshort" . WCF_N . "_guest_reaction
                    WHERE   sessionID = ?
                        AND objectType = ?
                        AND objectID = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([$sessionID, 'info.benjaro.urlshort.affiliate.likeableUrl', $eventObj->url->urlID]);
            $row = $statement->fetchArray();
            if ($row) {
                $guestReactionTypeID = $row['reactionTypeID'];
            }
        }

        // Prepare reaction types for guest handler
        $reactionTypes = [];
        if ($enableGuestReactions && !WCF::getUser()->userID) {
            foreach (\wcf\data\reaction\type\ReactionTypeCache::getInstance()->getReactionTypes() as $reactionType) {
                if ($reactionType->isAssignable) {
                    $reactionTypes[] = [
                        'reactionTypeID' => $reactionType->reactionTypeID,
                        'title' => $reactionType->getTitle(),
                        'renderedIcon' => $reactionType->renderIcon(),
                    ];
                }
            }
        }

        // Get favicon HTML for special (if active)
        $specialFaviconHtml = '';
        if ($this->special && $this->special->isCurrentlyActive()) {
            $specialFaviconHtml = $this->special->getImageTag(16, $url);
        }

        // Get title icon option and parse it
        $titleIconOption = Option::getOptionByName('urlshort_featuredLinks_titleIcon');
        $titleIcon = '';
        $titleIconName = '';
        $titleIconForceSolid = false;
        if ($titleIconOption && !empty($titleIconOption->optionValue)) {
            $titleIcon = $titleIconOption->optionValue;
            // Parse icon string format: "iconName;true/false"
            $parts = explode(';', $titleIcon, 2);
            if (count($parts) >= 2) {
                $titleIconName = trim($parts[0]);
                $titleIconForceSolid = (trim($parts[1]) === 'true');
            } elseif (count($parts) === 1) {
                $titleIconName = trim($parts[0]);
            }
        }

        // Load custom buttons
        $this->customButtons = $this->extractCustomButtons($eventObj);

        WCF::getTPL()->assign([
            'featuredLinks' => $this->featuredLinks,
            'customButtons' => $this->customButtons,
            'discount' => $displayDiscount,
            'special' => $this->special,
            'specialTheme' => $this->specialTheme, // Theme object for promo badge center text
            'specialThemeShortName' => $this->specialThemeShortName, // Short theme name without parenthesis
            'specialFaviconHtml' => $specialFaviconHtml, // Favicon HTML for special
            'randomDescription' => $this->randomDescription,
            'redirectUrl' => $url, // For auto-favicon fetching
            'url' => $eventObj->url, // URL object for templates (Share/Report buttons)
            'shareUrl' => $shareUrl, // Cleaned URL for share button (without double slashes)
            'countdownSeconds' => $specialCountdownSeconds, // Only specials have countdowns
            'extractedTitle' => $this->extractedTitle, // Auto-extracted title or fallback
            'reactionData' => $reactionData,
            'reactionObjectType' => 'info.benjaro.urlshort.affiliate.likeableUrl',
            'reactionObjectID' => ($eventObj->url !== null && isset($eventObj->url->urlID)) ? $eventObj->url->urlID : 0,
            'guestReactionTypeID' => $guestReactionTypeID,
            'guestReactionTypes' => $reactionTypes,
            'enableReactions' => $enableReactions,
            'enableGuestReactions' => $enableGuestReactions,
            'enableDescriptions' => $enableDescriptions,
            'activeThemeEffect' => $activeThemeEffect,
            'activeThemeIdentifier' => $activeThemeIdentifier, // Theme identifier for CSS file loading
            'titleIcon' => $titleIcon, // Title icon option value (raw)
            'titleIconName' => $titleIconName, // Parsed icon name
            'titleIconForceSolid' => $titleIconForceSolid, // Parsed forceSolid flag
        ]);
    }


    /**
     * @inheritDoc
     */
    protected function onReadData(RedirectPage $eventObj): void
    {
        // Check if URL object is valid
        if (!isset($eventObj->url?->urlID) || !$eventObj->url->urlID) {
            return;
        }

        // Track visit (VOR assignVariables, damit Basis-Plugin Counter noch nicht erhöht wurde)
        $this->trackVisit($eventObj);

        // Extract page title (must be done before getRandomDescription)
        $url = $eventObj->url->url ?? '';
        $host = $this->extractHostFromUrl($url);
        $extractedTitle = FaviconHandler::getInstance()->extractPageTitle($url);
        $this->extractedTitle = $extractedTitle ?: ($eventObj->url->urlTitle ?: $host);

        // Get featured links
        $this->featuredLinks = $this->extractFeaturedLinks($eventObj);

        // Load active special first (if exists, it overrides discount)
        $this->special = $this->loadActiveSpecial($eventObj);
        
        // Load theme object for special if theme identifier exists
        $this->specialTheme = null;
        $this->specialThemeShortName = null;
        if ($this->special && !empty($this->special->theme)) {
            $this->specialTheme = $this->loadThemeByIdentifier($this->special->theme);
            
            // Extract short name (remove parenthesis content like " (Dunkel)" or " (Modern)")
            if ($this->specialTheme && $this->specialTheme->title) {
                // Remove content in parentheses: "Weihnachten (Dunkel)" → "Weihnachten"
                $this->specialThemeShortName = \preg_replace('/\s*\([^)]*\)\s*$/', '', $this->specialTheme->title);
            }
        }

        // Get discount (only if no active special)
        // Special uses its own fields and completely overrides the discount
        if (!$this->special) {
            $this->discount = $this->loadDiscount($eventObj);
        } else {
            // Special is active - no discount needed, special fields are used
            $this->discount = null;
        }

        // Get random description
        $this->randomDescription = $this->getRandomDescription($eventObj);
    }

    /**
     * Loads featured links from database for the current URL.
     * Returns array with structure: ['url' => ['title' => 'Title', 'host' => 'HOST']]
     */
    private function extractFeaturedLinks(RedirectPage $eventObj): array
    {
        if (!isset($eventObj->url->urlID) || !$eventObj->url->urlID) {
            return [];
        }

        // Load featured links from database, sorted by sortOrder
        $sql = "SELECT linkID, url, title, sortOrder
                FROM urlshort1_featured_link
                WHERE urlID = ?
                ORDER BY sortOrder ASC";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$eventObj->url->urlID]);
        $links = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($links)) {
            return [];
        }

        $featuredLinks = [];
        foreach ($links as $link) {
            $url = $link['url'];
            $title = $link['title'];

            // Extract host/domain from URL
            $host = $this->extractHostFromUrl($url);

            $featuredLinks[$url] = [
                'title' => $title,
                'host' => $host,
                'linkID' => $link['linkID'],
            ];
        }

        return $featuredLinks;
    }

    /**
     * Loads custom buttons from database for the current URL.
     * Returns array with structure: [['title' => 'Title', 'targetUrl' => 'URL', 'customButtonID' => int]]
     */
    private function extractCustomButtons(RedirectPage $eventObj): array
    {
        if (!isset($eventObj->url->urlID) || !$eventObj->url->urlID) {
            return [];
        }

        // Load custom buttons from database, sorted by sortOrder
        $sql = "SELECT customButtonID, targetUrl, title, sortOrder
                FROM urlshort1_custom_button
                WHERE urlID = ?
                ORDER BY sortOrder ASC";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$eventObj->url->urlID]);
        $buttons = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($buttons)) {
            return [];
        }

        $customButtons = [];
        foreach ($buttons as $button) {
            $customButtons[] = [
                'title' => $button['title'],
                'targetUrl' => $button['targetUrl'],
                'customButtonID' => $button['customButtonID'],
            ];
        }

        return $customButtons;
    }

    /**
     * Extracts domain name from URL (e.g., "waldkraft" from "www.waldkraft.bio").
     *
     * @param string $url The URL to extract the domain from
     * @return string Extracted domain name in uppercase
     */
    private function extractHostFromUrl(string $url): string
    {
        // Remove protocol
        $noProtocol = str_replace(['https://', 'http://'], '', $url);

        // Remove path and query parameters
        $domainPart = explode('/', $noProtocol)[0];
        $parts = explode('.', $domainPart);

        // Domain is the second part if subdomain exists, otherwise the first
        $domain = (count($parts) > 2 ? $parts[1] : $parts[0]);

        return strtoupper($domain);
    }

    /**
     * Loads the active special for the current URL.
     *
     * @param RedirectPage $eventObj The redirect page event object
     * @return Special|null The active special or null if none exists
     */
    private function loadActiveSpecial(RedirectPage $eventObj): ?Special
    {
        if (!isset($eventObj->url->urlID) || !$eventObj->url->urlID) {
            return null;
        }

        $sql = "SELECT specialID
                FROM urlshort1_special
                WHERE urlID = ?
                  AND isActive = 1
                  AND (startTime = 0 OR startTime <= ?)
                  AND (endTime = 0 OR endTime >= ?)
                ORDER BY specialID DESC
                LIMIT 1";
        $statement = WCF::getDB()->prepare($sql);
        $now = TIME_NOW;
        $statement->execute([$eventObj->url->urlID, $now, $now]);
        $specialID = $statement->fetchSingleColumn();

        if ($specialID) {
            return new Special($specialID);
        }

        return null;
    }

    /**
     * Loads discount for the current URL based on host matching.
     *
     * @param RedirectPage $eventObj The redirect page event object
     * @return Discount|null The matching discount or null if none found
     */
    private function loadDiscount(RedirectPage $eventObj): ?object
    {
        $host = ($eventObj->url !== null && isset($eventObj->url->url)) ? (parse_url($eventObj->url->url, PHP_URL_HOST) ?? '') : '';
        $urlString = ($eventObj->url !== null) ? ($eventObj->url->url ?? '') : '';
        $discountList = new AccessibleDiscountList($urlString, $host);
        $discountList->readObjects();

        $discounts = $discountList->getObjects();
        if (empty($discounts)) {
            return null;
        }

        return array_values($discounts)[0];
    }

    /**
     * Gets a random description from database (simple random selection).
     *
     * @param RedirectPage $eventObj The redirect page event object
     * @return string The compiled description text or empty string if none available
     */
    private function getRandomDescription(RedirectPage $eventObj): string
    {
        // Check if descriptions are enabled globally
        $descriptionsOption = Option::getOptionByName('urlshort_featuredLinks_enableDescriptions');
        $enableDescriptions = $descriptionsOption ? $descriptionsOption->optionValue : 1;
        if (!$enableDescriptions) {
            return '';
        }

        // Load all active descriptions from database
        $descriptionList = new AccessibleDescriptionList();
        $descriptionList->readObjects();

        $descriptions = $descriptionList->getObjects();

        if (empty($descriptions)) {
            return ''; // No descriptions available
        }

        // Simple random selection
        $randomDescription = $descriptions[array_rand($descriptions)];

        // Compile description text with Smarty variables
        $urlString = ($eventObj->url !== null) ? ($eventObj->url->url ?? '') : '';

        return $randomDescription->getDescriptionText([
            'url' => $urlString, // Full URL string (e.g., "https://google.de")
            'urlObject' => $eventObj->url, // URL object for advanced usage
            'discount' => $this->discount,
            'special' => $this->special, // Special object for favicon
            'extractedTitle' => $this->extractedTitle
        ]);
    }
    
    /**
     * Adds guest reactions to a LikeObject.
     *
     * @param object $likeObject The like object to add reactions to
     * @param string $objectType The object type identifier
     * @param int $objectID The object ID
     * @return void
     */
    private function addGuestReactionsToLikeObject($likeObject, $objectType, $objectID): void
    {
        // Get guest reactions for this object
        $sql = "SELECT  reactionTypeID, COUNT(*) as count
                FROM    urlshort" . WCF_N . "_guest_reaction
                WHERE   objectType = ?
                    AND objectID = ?
                GROUP BY reactionTypeID";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$objectType, $objectID]);

        // Get current reactions
        $reactions = $likeObject->getReactions();
        $cumulativeLikes = $likeObject->cumulativeLikes;

        // Add guest reactions
        while ($row = $statement->fetchArray()) {
            $reactionTypeID = $row['reactionTypeID'];
            $count = $row['count'];

            $reactionType = \wcf\data\reaction\type\ReactionTypeCache::getInstance()->getReactionTypeByID($reactionTypeID);
            if ($reactionType === null) {
                continue;
            }

            if (!isset($reactions[$reactionTypeID])) {
                $reactions[$reactionTypeID] = [
                    'reactionCount' => 0,
                    'renderedReactionIcon' => $reactionType->renderIcon(),
                    'renderedReactionIconEncoded' => \wcf\util\JSON::encode($reactionType->renderIcon()),
                    'reactionTitle' => $reactionType->getTitle(),
                ];
            }

            $reactions[$reactionTypeID]['reactionCount'] += $count;
            $cumulativeLikes += $count;
        }

        // Update likeObject using reflection to modify internal data
        $reflection = new \ReflectionClass($likeObject);

        // Update reactions property (private)
        $reactionsProperty = $reflection->getProperty('reactions');
        $reactionsProperty->setAccessible(true);
        $reactionsProperty->setValue($likeObject, $reactions);

        // Update data array with new cumulativeLikes
        $dataProperty = $reflection->getProperty('data');
        $dataProperty->setAccessible(true);
        $data = $dataProperty->getValue($likeObject);
        $data['cumulativeLikes'] = $cumulativeLikes;
        $dataProperty->setValue($likeObject, $data);
    }
    
    /**
     * Creates a minimal LikeObject with only guest reactions.
     *
     * @param object $objectType The object type
     * @param int $objectID The object ID
     * @return object|null The created like object or null if no reactions exist
     */
    private function createLikeObjectWithGuestReactions($objectType, $objectID)
    {
        // Get guest reactions for this object
        $sql = "SELECT  reactionTypeID, COUNT(*) as count
                FROM    urlshort" . WCF_N . "_guest_reaction
                WHERE   objectType = ?
                    AND objectID = ?
                GROUP BY reactionTypeID";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$objectType->objectType, $objectID]);
        
        $reactions = [];
        $cumulativeLikes = 0;
        
        while ($row = $statement->fetchArray()) {
            $reactionTypeID = $row['reactionTypeID'];
            $count = $row['count'];
            
            $reactionType = \wcf\data\reaction\type\ReactionTypeCache::getInstance()->getReactionTypeByID($reactionTypeID);
            if ($reactionType === null) {
                continue;
            }
            
            $reactions[$reactionTypeID] = [
                'reactionCount' => $count,
                'renderedReactionIcon' => $reactionType->renderIcon(),
                'renderedReactionIconEncoded' => \wcf\util\JSON::encode($reactionType->renderIcon()),
                'reactionTitle' => $reactionType->getTitle(),
            ];
            
            $cumulativeLikes += $count;
        }
        
        if (empty($reactions)) {
            return null;
        }
        
        // Create a minimal LikeObject
        $likeObject = new \wcf\data\like\object\LikeObject(null, [
            'likeObjectID' => 0,
            'objectTypeID' => $objectType->objectTypeID,
            'objectID' => $objectID,
            'objectUserID' => null,
            'likes' => $cumulativeLikes,
            'dislikes' => 0,
            'cumulativeLikes' => $cumulativeLikes,
            'cachedUsers' => '',
            'cachedReactions' => serialize($reactions),
        ]);
        
        // Set reactions property
        $reflection = new \ReflectionClass($likeObject);
        $reactionsProperty = $reflection->getProperty('reactions');
        $reactionsProperty->setAccessible(true);
        $reactionsProperty->setValue($likeObject, $reactions);
        
        return $likeObject;
    }

    /**
     * Determines the currently active theme effect.
     *
     * @return array{identifier:string,settings:array}
     */
    private function determineThemeEffect(): array
    {
        $default = [
            'identifier' => 'none',
            'settings' => [],
        ];

        $globalToggle = Option::getOptionByName('urlshort_halloween_leaves');
        $effectsEnabled = $globalToggle ? (bool)$globalToggle->optionValue : false;
        if (!$effectsEnabled) {
            return $default;
        }

        if (!$this->special || !$this->special->isCurrentlyActive()) {
            return $default;
        }

        $theme = $this->loadThemeByIdentifier($this->special->theme);
        if ($theme === null || !$theme->isActive) {
            return $default;
        }

        $settings = match ($theme->effectIdentifier) {
            'autumnLeaves' => $this->getAutumnLeavesEffectSettings(),
            'snow' => $this->getSnowEffectSettings(),
            'ghosts' => $this->getGhostEffectSettings(),
            default => null,
        };

        if ($settings === null) {
            return $default;
        }

        $result = [
            'identifier' => $theme->effectIdentifier,
            'settings' => $settings,
        ];

        // For Halloween theme with ghosts effect, also add autumn leaves effect
        if ($theme->identifier === 'halloween' && $theme->effectIdentifier === 'ghosts') {
            $result['additionalEffects'] = [
                [
                    'identifier' => 'autumnLeaves',
                    'settings' => $this->getAutumnLeavesEffectSettings(),
                ],
            ];
        }

        return $result;
    }

    /**
     * Helper to load and cache a theme by identifier.
     *
     * @param string $identifier The theme identifier
     * @return Theme|null The theme object or null if not found
     */
    private function loadThemeByIdentifier(string $identifier): ?Theme
    {
        if ($identifier === '') {
            return null;
        }

        if (array_key_exists($identifier, $this->themeCache)) {
            return $this->themeCache[$identifier];
        }

        $sql = "SELECT themeID
                FROM urlshort1_theme
                WHERE identifier = ?
                LIMIT 1";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$identifier]);
        $themeID = $statement->fetchSingleColumn();

        if (!$themeID) {
            return $this->themeCache[$identifier] = null;
        }

        $theme = new Theme($themeID);
        if (!$theme->themeID) {
            return $this->themeCache[$identifier] = null;
        }

        return $this->themeCache[$identifier] = $theme;
    }

    /**
     * Default configuration for the autumn leaves effect.
     */
    private function getAutumnLeavesEffectSettings(): array
    {
        return [
            'selector' => 'body',
            'enableMobile' => true,
            'desktop' => [
                'num' => 30,
                'speed' => 0.6,
                'minScale' => 5,
                'maxScale' => 45,
            ],
            'mobile' => [
                'num' => 15,
                'speed' => 0.45,
                'minScale' => 3,
                'maxScale' => 25,
            ],
            'opacity' => 1.0,
            'fadeScroll' => true,
            'enableInteraction' => true,
        ];
    }

    /**
     * Snow effect with gentle falling speed similar to autumn leaves.
     */
    private function getSnowEffectSettings(): array
    {
        return [
            'selector' => 'body',
            'enableMobile' => true,
            'desktop' => [
                'num' => 50,
                'speed' => 0.15,  // Slower than autumn leaves (0.6) for gentle snowfall
                'minScale' => 5,
                'maxScale' => 50,
            ],
            'mobile' => [
                'num' => 25,
                'speed' => 0.1,
                'minScale' => 3,
                'maxScale' => 30,
            ],
            'opacity' => 1.0,
            'fadeScroll' => true,
            'enableInteraction' => false,
        ];
    }

    /**
     * Ghost effect defaults inspired by the SoftCreatR demo.
     */
    private function getGhostEffectSettings(): array
    {
        return [
            'selector' => 'body',
            'enableMobile' => true,
            'desktop' => [
                'num' => 12,
                'minScale' => 70,  // Original default from HalloweenGhosts.js
                'maxScale' => 130, // Original default from HalloweenGhosts.js
                'speed' => 0.35,
            ],
            'mobile' => [
                'num' => 5,       // Fewer ghosts on mobile
                'minScale' => 40, // Smaller for mobile
                'maxScale' => 80, // Smaller for mobile
                'speed' => 0.25,
            ],
            'opacity' => 0.9,
            'fadeScroll' => true,
            'enableInteraction' => true,
        ];
    }

    /**
     * Tracks a visit to the redirect page.
     * Stores visit details including referrer and synchronizes the counter.
     * Prevents duplicate visits from the same session/user within 30 minutes.
     *
     * @param RedirectPage $eventObj The redirect page event object
     * @return void
     */
    private function trackVisit(RedirectPage $eventObj): void
    {
        if (!isset($eventObj->url->urlID) || !$eventObj->url->urlID) {
            return;
        }

        $urlID = $eventObj->url->urlID;
        $userID = null;
        $sessionID = null;

        if (WCF::getUser()->userID) {
            $userID = WCF::getUser()->userID;
        } else {
            $sessionID = WCF::getSession()->sessionID;
        }

        $timeThreshold = TIME_NOW - 1800;
        $conditions = "urlID = ? AND visitTime >= ?";
        $parameters = [$urlID, $timeThreshold];

        if ($userID) {
            $conditions .= " AND userID = ?";
            $parameters[] = $userID;
        } else {
            $conditions .= " AND sessionID = ?";
            $parameters[] = $sessionID;
        }

        $sql = "SELECT COUNT(*) FROM urlshort1_visit WHERE " . $conditions;
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($parameters);
        $existingVisitCount = $statement->fetchSingleColumn();

        // Wenn bereits ein Besuch innerhalb des Zeitfensters existiert, ignorieren
        if ($existingVisitCount > 0) {
            return;
        }

        // Get referrer (vollständige URL)
        $referrer = null;
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            $referrer = $_SERVER['HTTP_REFERER'];
            // Limit length to 512 characters
            if (strlen($referrer) > 512) {
                $referrer = substr($referrer, 0, 512);
            }
        }

        // Store visit in database
        VisitEditor::create([
            'urlID' => $urlID,
            'visitTime' => TIME_NOW,
            'referrer' => $referrer,
            'userID' => $userID,
            'sessionID' => $sessionID,
        ]);

        // Synchronize counter: Update urlshort1_url.counter from visit count
        $sql = "SELECT COUNT(*) FROM urlshort1_visit WHERE urlID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$urlID]);
        $visitCount = $statement->fetchSingleColumn();

        // Update counter in urlshort1_url
        $urlEditor = new UrlEditor($eventObj->url);
        $urlEditor->update(['counter' => $visitCount]);
    }
}
