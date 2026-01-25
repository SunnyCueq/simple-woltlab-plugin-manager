<?php

namespace shrinkr\page;

use shrinkr\data\discount\AccessibleDiscountList;
use shrinkr\data\discount\Discount;
use shrinkr\data\discount\ViewableDiscount;
use shrinkr\data\description\AccessibleDescriptionList;
use shrinkr\data\special\Special;
use shrinkr\data\theme\Theme;
use shrinkr\data\shrinkrlink\ShrinkrLink;
use shrinkr\data\shrinkrlink\ShrinkrLinkAction;
use shrinkr\data\shrinkrlink\ShrinkrLinkEditor;
use shrinkr\system\favicon\FaviconHandler;
use shrinkr\util\GeoLite2Util;
use wcf\data\option\Option;
use wcf\page\AbstractPage;
use wcf\system\exception\IllegalLinkException;
use wcf\system\MetaTagHandler;
use wcf\system\reaction\ReactionHandler;
use wcf\system\request\LinkHandler;
use wcf\system\request\RequestHandler;
use wcf\system\WCF;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\user\authentication\password\PasswordAlgorithmManager;
use wcf\util\FileUtil;
use wcf\util\StringUtil;

/**
 * Handles the redirect page display for shortened links.
 * 
 * This page class manages the display of shortened link redirect pages, including
 * countdown timers, discount codes, special events, featured links, reactions,
 * and seasonal visual effects. It also handles meta tag generation for social 
 * media sharing.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  page
 */
class RedirectPage extends AbstractPage
{
    /**
     * The shortened link object being displayed.
     *
     * @var    ShrinkrLink|null
     */
    public ?ShrinkrLink $link = null;

    /**
     * Featured links associated with this shortened link.
     * 
     * Array indexed by featured link ID, containing title, host, and linkID.
     *
     * @var    array<string, array{title: string, host: string, linkID: int}>
     */
    public array $featuredLinks = [];

    /**
     * Custom buttons for this shortened link.
     * 
     * Array indexed by custom button ID, containing title, targetUrl, and customButtonID.
     *
     * @var    array<int, array{title: string, targetUrl: string, customButtonID: int}>
     */
    public array $customButtons = [];

    /**
     * Active discount for display (if applicable).
     *
     * @var    ViewableDiscount|null
     */
    public ?ViewableDiscount $discount = null;

    /**
     * Active special event configuration.
     *
     * @var    Special|null
     */
    public ?Special $special = null;

    /**
     * Theme associated with the active special.
     *
     * @var    Theme|null
     */
    public ?Theme $specialTheme = null;
    
    /**
     * Shortened theme name for display in promo badge.
     *
     * @var    string|null
     */
    public ?string $specialThemeShortName = null;

    /**
     * Whether the link is password protected.
     *
     * @var    bool
     */
    public bool $passwordProtected = false;

    /**
     * Whether the password has been unlocked.
     *
     * @var    bool
     */
    public bool $passwordUnlocked = false;

    /**
     * Whether to show the password form instead of content.
     *
     * @var    bool
     */
    public bool $showPasswordForm = false;

    /**
     * Whether there was a password error.
     *
     * @var    bool
     */
    public bool $passwordError = false;

    /**
     * Random description text to display on the page.
     *
     * @var    string
     */
    public string $randomDescription = '';

    /**
     * Extracted or auto-generated page title.
     *
     * @var    string
     */
    private string $extractedTitle = '';

    /**
     * Internal cache for loaded theme objects.
     *
     * @var    array<string, Theme|null>
     */
    private array $themeCache = [];

    /**
     * @inheritDoc
     */
    #[\Override]
    public function readParameters(): void
    {
        parent::readParameters();

        // Extract hash from request
        // URL formats supported:
        // - /r/{hash}/ (with URL rewriting + prefix removal)
        // - /shrinkr/r/{hash}/ (with URL rewriting)
        // - /shrinkr/index.php?r/{hash}/ (without URL rewriting)
        $hash = null;
        
        // Try to get hash from route parameter (if CMS Page Individual URL is configured)
        if (isset($_REQUEST['hash'])) {
            $hash = $_REQUEST['hash'];
        } elseif (isset($_REQUEST['r'])) {
            // Handle /shrinkr/index.php?r={hash} format (standard query parameter)
            $rParam = $_REQUEST['r'];
            if (is_string($rParam) && !empty($rParam)) {
                // Remove trailing slash and extract hash
                $hash = rtrim($rParam, '/');
            }
        }
        
        // If no hash found yet, try parsing from QUERY_STRING directly
        // This handles /shrinkr/index.php?r/DEMO-1/ format where r/DEMO-1/ is the key, not value
        if (!$hash && !empty($_SERVER['QUERY_STRING'])) {
            $queryString = $_SERVER['QUERY_STRING'];
            // Check for r/{hash}/ pattern in query string
            if (preg_match('#^r/([^/&]+)/?#', $queryString, $matches)) {
                $hash = $matches[1];
            }
        }
        
        // Fallback: Parse from REQUEST_URI
        if (!$hash && !empty($_SERVER['REQUEST_URI'])) {
            // Parse from REQUEST_URI: /r/{hash}/ or /shrinkr/r/{hash}/
            $requestUri = $_SERVER['REQUEST_URI'];
            if (preg_match('#/r/([^/?]+)/?#', $requestUri, $matches)) {
                $hash = $matches[1];
            }
        }
        
        if ($hash) {
            // Get URL by hash
            $this->link = ShrinkrLink::getLinkByHash($hash);
        } else {
            // No hash provided - link will be null, will throw IllegalLinkException in readData()
            $this->link = null;
        }

        // Handle POST request for password entry
        if ($this->link && $this->link->linkID && isset($_POST['password']) && !empty($_POST['password'])) {
            $password = $_POST['password'];
            
            // Check password
            if ($this->link->isPasswordProtected()) {
                $algorithm = PasswordAlgorithmManager::getInstance()->getDefaultAlgorithm();
                if ($algorithm->verify($password, $this->link->passwordHash)) {
                    // Password correct
                    if ($this->isSessionStorageEnabled()) {
                        // Session storage enabled - save and redirect
                        $userID = WCF::getUser()->userID ?: 0;
                        $storageKey = 'shrinkr_link_' . $this->link->linkID . '_unlocked';
                        UserStorageHandler::getInstance()->update($storageKey, TIME_NOW, $userID);
                        
                        // Redirect to same URL (GET, without POST parameters)
                        $redirectUrl = $this->getCurrentUrl();
                        \header('Location: ' . $redirectUrl, true, 303);
                        exit();
                    } else {
                        // Session storage disabled - unlock for this request only (no redirect)
                        $this->passwordUnlocked = true;
                        // No redirect - page will show content in same request
                    }
                } else {
                    // Password incorrect
                    $this->passwordError = true;
                }
            }
        }
    }

    /**
     * Checks if session storage is enabled (globally or per-link).
     *
     * @return  bool    True if session storage is enabled
     */
    protected function isSessionStorageEnabled(): bool
    {
        // Check global option
        $globalOption = \wcf\data\option\Option::getOptionByName('shrinkr_password_session_storage');
        if ($globalOption && ($globalOption->optionValue == '1' || $globalOption->optionValue == 1)) {
            return true;
        }
        
        // Check per-link setting
        if ($this->link && $this->link->hasSessionStorage()) {
            return true;
        }
        
        return false;
    }

    /**
     * Gets the current URL without query parameters.
     *
     * @return  string  Current URL
     */
    protected function getCurrentUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Keep query string (contains r/{hash}/)
        // REQUEST_URI already contains the full path with query string
        
        return $protocol . $host . $uri;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function readData(): void
    {
        parent::readData();

        // Extract page title (must be done before getRandomDescription)
        $url = $this->link->url ?? '';
        $host = $this->extractHostFromUrl($url);
        $extractedTitle = FaviconHandler::getInstance()->extractPageTitle($url);
        $this->extractedTitle = $extractedTitle ?: ($this->link->linkTitle ?: $host);

        // Get featured links
        $this->featuredLinks = $this->extractFeaturedLinks();

        // Load active special first (if exists, it overrides discount)
        $this->special = $this->loadActiveSpecial();
        
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
            $this->discount = $this->loadDiscount();
        } else {
            // Special is active - no discount needed, special fields are used
            $this->discount = null;
        }

        // Get random description
        $this->randomDescription = $this->getRandomDescription();

        // Check password protection
        if ($this->link && $this->link->isPasswordProtected()) {
            $this->passwordProtected = true;
            
            // If already unlocked in this request (from POST), don't override
            if ($this->passwordUnlocked) {
                // Already unlocked in readParameters() - keep it true
                // Don't show password form
                $this->showPasswordForm = false;
            } else {
                // Check if already unlocked via session storage
                if ($this->isSessionStorageEnabled()) {
                    $userID = WCF::getUser()->userID ?: 0;
                    $storageKey = 'shrinkr_link_' . $this->link->linkID . '_unlocked';
                    $unlockedTimestamp = UserStorageHandler::getInstance()->getField($storageKey, $userID);
                    
                    if ($unlockedTimestamp) {
                        // Check if session is still valid (24h default)
                        $sessionDuration = $this->getSessionDuration();
                        if ($sessionDuration > 0 && (TIME_NOW - $unlockedTimestamp) > $sessionDuration) {
                            // Session expired
                            $this->passwordUnlocked = false;
                        } else {
                            // Session still valid
                            $this->passwordUnlocked = true;
                        }
                    } else {
                        $this->passwordUnlocked = false;
                    }
                } else {
                    // Session storage disabled - always require password (unless already unlocked in this request)
                    $this->passwordUnlocked = false;
                }
                
                // Show password form if not unlocked
                if (!$this->passwordUnlocked) {
                    $this->showPasswordForm = true;
                }
            }
        }
    }

    /**
     * Gets the session duration in seconds.
     *
     * @return  int     Session duration in seconds (hardcoded: 86400 = 24 hours)
     */
    protected function getSessionDuration(): int
    {
        return 86400; // Hardcoded session duration (24 hours)
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function assignVariables(): void
    {
        parent::assignVariables();

        // Check if URL exists (WoltLab pattern: throw exception here, not in readData())
        if ($this->link === null || !$this->link->linkID) {
            throw new IllegalLinkException();
        }
        
        // Link exists - process it
        // Note: Visit tracking is now handled by RedirectPageVisitTrackerListener

        // Block forwarding if password protected and not unlocked
        if ($this->passwordProtected && !$this->passwordUnlocked) {
            // Do not redirect - show password form instead
        } elseif (!SHRINKR_FORWARDING_MUST_CONFIRMED && SHRINKR_TIME_UNTIL_FORWARDING == 0) {
            // Redirect
            \header('Location: ' . $this->link->url, true, 303);

            exit();
        }
        
        $url = $this->link->url ?? '';

            // Get option values for templates (read once, no redundancy)
            // Woltlab Option-Werte sind Strings "1" oder "0", nicht booleans
            $descriptionsOption = Option::getOptionByName('shrinkr_enable_descriptions');
            $enableDescriptions = $descriptionsOption ? ($descriptionsOption->optionValue == '1' || $descriptionsOption->optionValue == 1) : true;

            $shareButtonOption = Option::getOptionByName('shrinkr_enable_share_button');
            $enableShareButton = $shareButtonOption ? ($shareButtonOption->optionValue == '1' || $shareButtonOption->optionValue == 1) : true;

        // If special is active, use special data for discount display
        $displayDiscount = $this->discount;
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
                            if ($code === 'SHRINKR') {
                                $label = 'Standard';
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
            $shareUrl = $this->link->getShortedUrl(false);
            $shareUrl = \rtrim($shareUrl, '/') . '/';

            // Get favicon HTML for special (if active)
            $specialFaviconHtml = '';
            if ($this->special && $this->special->isCurrentlyActive()) {
                $specialFaviconHtml = $this->special->getImageTag(16, $url);
            }

            // Get title icon option and parse it
            $titleIconOption = Option::getOptionByName('shrinkr_title_icon');
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
            $this->customButtons = $this->extractCustomButtons();

            // Load reaction data
            $reactionData = [];
            $reactionObjectType = 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink';
            $reactionObjectID = $this->link->linkID;
            $enableReactions = false;
            $enableGuestReactions = false;
            $guestReactionTypeID = 0;

            if (MODULE_LIKE) {
                $reactionsOption = Option::getOptionByName('shrinkr_enable_reactions');
                $enableReactions = $reactionsOption ? ($reactionsOption->optionValue == '1' || $reactionsOption->optionValue == 1) : true;

                $guestReactionsOption = Option::getOptionByName('shrinkr_enable_guest_reactions');
                $enableGuestReactions = $guestReactionsOption ? ($guestReactionsOption->optionValue == '1' || $guestReactionsOption->optionValue == 1) : false;

                if ($enableReactions && $reactionObjectID) {
                    // Load reaction data including guest reactions
                    $reactionData = $this->loadReactionData($reactionObjectType, $reactionObjectID);

                    // Get guest reaction type ID for current session (if guest)
                    if ($enableGuestReactions && !WCF::getUser()->userID) {
                        $sessionID = WCF::getSession()->sessionID;
                        $sql = "SELECT reactionTypeID
                                FROM shrinkr" . WCF_N . "_guest_reaction
                                WHERE sessionID = ?
                                    AND objectType = ?
                                    AND objectID = ?
                                LIMIT 1";
                        $statement = WCF::getDB()->prepareStatement($sql);
                        $statement->execute([$sessionID, $reactionObjectType, $reactionObjectID]);
                        $guestReaction = $statement->fetchArray();
                        if ($guestReaction) {
                            $guestReactionTypeID = (int)$guestReaction['reactionTypeID'];
                        }
                    }
                }
            }

            // Set Open Graph Meta-Tags
            $this->setOpenGraphMetaTags();

            // Get copyright option
            $copyrightOption = Option::getOptionByName('shrinkr_copyright_enabled');
            $copyrightEnabled = $copyrightOption ? ($copyrightOption->optionValue == '1' || $copyrightOption->optionValue == 1) : true;

            WCF::getTPL()->assign([
                'link' => $this->link,
                'featuredLinks' => $this->featuredLinks,
                'customButtons' => $this->customButtons,
                'discount' => $displayDiscount,
                'special' => $this->special,
                'specialTheme' => $this->specialTheme, // Theme object for promo badge center text
                'specialThemeShortName' => $this->specialThemeShortName, // Short theme name without parenthesis
                'specialFaviconHtml' => $specialFaviconHtml, // Favicon HTML for special
                'randomDescription' => $this->randomDescription,
                'redirectUrl' => $url, // For auto-favicon fetching
                'shareUrl' => $shareUrl, // Cleaned URL for share button (without double slashes)
                'countdownSeconds' => $specialCountdownSeconds, // Only specials have countdowns
                'extractedTitle' => $this->extractedTitle, // Auto-extracted title or fallback
                'enableDescriptions' => $enableDescriptions,
                'activeThemeEffect' => $activeThemeEffect,
                'activeThemeIdentifier' => $activeThemeIdentifier, // Theme identifier for CSS file loading
                'titleIcon' => $titleIcon, // Title icon option value (raw)
                'titleIconName' => $titleIconName, // Parsed icon name
                'titleIconForceSolid' => $titleIconForceSolid, // Parsed forceSolid flag
                'reactionData' => $reactionData, // Reaction data for template
                'reactionObjectType' => $reactionObjectType, // Object type for reactions
                'reactionObjectID' => $reactionObjectID, // Object ID for reactions
                'enableReactions' => $enableReactions, // Whether reactions are enabled
                'enableGuestReactions' => $enableGuestReactions, // Whether guest reactions are enabled
                'guestReactionTypeID' => $guestReactionTypeID, // Guest reaction type ID (if guest has reacted)
                'enableShareButton' => $enableShareButton, // Whether share button is enabled
                'passwordProtected' => $this->passwordProtected, // Whether link is password protected
                'passwordUnlocked' => $this->passwordUnlocked, // Whether password has been unlocked
                'showPasswordForm' => $this->showPasswordForm, // Whether to show password form
                'passwordError' => $this->passwordError, // Whether there was a password error
                'copyrightEnabled' => $copyrightEnabled, // Whether copyright is enabled
            ]);
    }

    /**
     * Loads reaction data including guest reactions for a specific object.
     *
     * @param string $objectType Object type identifier
     * @param int $objectID Object ID
     * @return array Reaction data with wrapper object
     */
    private function loadReactionData(string $objectType, int $objectID): array
    {
        $reactionData = [];

        // Get regular reactions
        $objectTypeObj = ReactionHandler::getInstance()->getObjectType($objectType);
        if ($objectTypeObj === null) {
            return $reactionData;
        }

        ReactionHandler::getInstance()->loadLikeObjects($objectTypeObj, [$objectID]);
        $likeObject = ReactionHandler::getInstance()->getLikeObject($objectTypeObj, $objectID);

        // Get reaction data including guest reactions
        $reactionDataWithGuests = $this->getReactionDataWithGuests($objectType, $objectID);

        // Create reaction array with objects (like LikeObject->getReactions())
        $reactions = [];
        foreach ($reactionDataWithGuests['cachedReactions'] as $reactionTypeID => $reactionDataItem) {
            $reactions[$reactionTypeID] = $reactionDataItem;
        }

        // ReactionTypeID from LikeObject (if available)
        $reactionTypeID = ($likeObject !== null && isset($likeObject->reactionTypeID)) ? $likeObject->reactionTypeID : 0;

        // Create wrapper object
        $wrapper = new class($reactions, $reactionDataWithGuests['cumulativeLikes'], $reactionTypeID) {
            private $reactions;
            private $cumulativeLikes;
            private $reactionTypeID;

            public function __construct($reactions, $cumulativeLikes, $reactionTypeID) {
                $this->reactions = $reactions;
                $this->cumulativeLikes = $cumulativeLikes;
                $this->reactionTypeID = $reactionTypeID;
            }

            public function getReactions() {
                return $this->reactions;
            }

            public function getReactionsJson(): string {
                $data = [];
                foreach ($this->reactions as $reactionTypeID => $value) {
                    $data[] = [
                        $reactionTypeID, $value['reactionCount'],
                    ];
                }
                return \wcf\util\JSON::encode($data);
            }

            public function __get($name) {
                if ($name === 'reactionTypeID') {
                    return $this->reactionTypeID;
                }
                if ($name === 'cumulativeLikes') {
                    return $this->cumulativeLikes;
                }
                return null;
            }
        };

        $reactionData[$objectID] = $wrapper;

        return $reactionData;
    }

    /**
     * Gets reaction data including guest reactions
     *
     * @param string $objectType
     * @param int $objectID
     * @return array
     */
    private function getReactionDataWithGuests(string $objectType, int $objectID): array
    {
        // Get regular reactions
        $objectTypeObj = ReactionHandler::getInstance()->getObjectType($objectType);
        if ($objectTypeObj === null) {
            $cachedReactions = [];
            $cumulativeLikes = 0;
        } else {
            $likeObject = \wcf\data\like\object\LikeObject::getLikeObject($objectTypeObj->objectTypeID, $objectID);
            if ($likeObject === null) {
                $cachedReactions = [];
                $cumulativeLikes = 0;
            } else {
                $cachedReactions = $likeObject->getReactions();
                $cumulativeLikes = $likeObject->cumulativeLikes;
            }
        }

        // Get guest reactions
        $sql = "SELECT  reactionTypeID, COUNT(*) as count
                FROM    shrinkr" . WCF_N . "_guest_reaction
                WHERE   objectType = ?
                    AND objectID = ?
                GROUP BY reactionTypeID";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$objectType, $objectID]);

        while ($row = $statement->fetchArray()) {
            $reactionTypeID = $row['reactionTypeID'];
            $count = $row['count'];

            $reactionType = \wcf\data\reaction\type\ReactionTypeCache::getInstance()->getReactionTypeByID($reactionTypeID);
            if ($reactionType === null) {
                continue;
            }

            if (!isset($cachedReactions[$reactionTypeID])) {
                $cachedReactions[$reactionTypeID] = [
                    'reactionCount' => 0,
                    'renderedReactionIcon' => $reactionType->renderIcon(),
                    'renderedReactionIconEncoded' => \wcf\util\JSON::encode($reactionType->renderIcon()),
                    'reactionTitle' => $reactionType->getTitle(),
                ];
            }

            $cachedReactions[$reactionTypeID]['reactionCount'] += $count;
            $cumulativeLikes += $count;
        }

        return [
            'cachedReactions' => $cachedReactions,
            'cumulativeLikes' => $cumulativeLikes,
        ];
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
     * Loads featured links from database for the current URL.
     * Returns array with structure: ['url' => ['title' => 'Title', 'host' => 'HOST', 'linkID' => int]]
     */
    private function extractFeaturedLinks(): array
    {
        if (!isset($this->link->linkID) || !$this->link->linkID) {
            return [];
        }

        // Load featured links from database, sorted by sortOrder
        $sql = "SELECT linkID, url, title, sortOrder
                FROM shrinkr1_featured_link
                WHERE linkID = ?
                ORDER BY sortOrder ASC";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->link->linkID]);
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
    private function extractCustomButtons(): array
    {
        if (!isset($this->link->linkID) || !$this->link->linkID) {
            return [];
        }

        // Load custom buttons from database, sorted by sortOrder
        $sql = "SELECT customButtonID, targetUrl, title, sortOrder
                FROM shrinkr1_custom_button
                WHERE linkID = ?
                ORDER BY sortOrder ASC";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->link->linkID]);
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
     * Loads the active special for the current URL.
     *
     * @return Special|null The active special or null if none exists
     */
    private function loadActiveSpecial(): ?Special
    {
        if (!isset($this->link->linkID) || !$this->link->linkID) {
            return null;
        }

        $sql = "SELECT specialID
                FROM shrinkr1_special
                WHERE linkID = ?
                  AND isActive = 1
                  AND (startTime = 0 OR startTime <= ?)
                  AND (endTime = 0 OR endTime >= ?)
                ORDER BY specialID DESC
                LIMIT 1";
        $statement = WCF::getDB()->prepareStatement($sql);
        $now = TIME_NOW;
        $statement->execute([$this->link->linkID, $now, $now]);
        $specialID = $statement->fetchSingleColumn();

        if ($specialID) {
            return new Special($specialID);
        }

        return null;
    }

    /**
     * Loads discount for the current URL based on host matching.
     *
     * @return ViewableDiscount|null The matching discount or null if none found
     */
    private function loadDiscount(): ?ViewableDiscount
    {
        $host = ($this->link !== null && isset($this->link->url)) ? (parse_url($this->link->url, PHP_URL_HOST) ?? '') : '';
        $urlString = ($this->link !== null) ? ($this->link->url ?? '') : '';
        $discountList = new AccessibleDiscountList($urlString, $host);
        $discountList->readObjects();

        $discounts = $discountList->getObjects();
        if (empty($discounts)) {
            return null;
        }

        return array_values($discounts)[0];
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
                FROM shrinkr1_theme
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
     * Gets a random description from database (simple random selection).
     *
     * @return string The compiled description text or empty string if none available
     */
    private function getRandomDescription(): string
    {
        // Check if descriptions are enabled globally
        $descriptionsOption = Option::getOptionByName('shrinkr_enable_descriptions');
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
        $urlString = ($this->link !== null) ? ($this->link->url ?? '') : '';

        return $randomDescription->getDescriptionText([
            'url' => $urlString, // Full URL string (e.g., "https://google.de")
            'urlObject' => $this->link, // Link object for advanced usage
            'discount' => $this->discount,
            'special' => $this->special, // Special object for favicon
            'extractedTitle' => $this->extractedTitle
        ]);
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

        $globalToggle = Option::getOptionByName('shrinkr_halloween_leaves');
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
     * Ghost effect defaults.
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
     * Sets Open Graph Meta-Tags for the redirect page.
     *
     * @return void
     */
    private function setOpenGraphMetaTags(): void
    {
        if (!$this->link || !$this->link->linkID) {
            return;
        }

        $metaTagHandler = MetaTagHandler::getInstance();

        // og:title - Priority: linkTitle > autoExtractedTitle > extractedTitle > default
        $ogTitle = $this->link->linkTitle ?: $this->link->autoExtractedTitle ?: $this->extractedTitle ?: WCF::getLanguage()->get('shrinkr.redirect.headline');
        if (\defined('PAGE_TITLE')) {
            $ogTitle .= ' - ' . WCF::getLanguage()->get(PAGE_TITLE);
        }
        $metaTagHandler->addTag('og:title', 'og:title', $ogTitle, true);

        // og:description - Priority: randomDescription > special additionalText > default
        $ogDescription = '';
        if (!empty($this->randomDescription)) {
            // Strip HTML tags for meta description
            $ogDescription = StringUtil::stripHTML($this->randomDescription);
        } elseif ($this->special && $this->special->isCurrentlyActive() && !empty($this->special->additionalText)) {
            $ogDescription = StringUtil::stripHTML($this->special->additionalText);
        }
        if (empty($ogDescription) && \defined('META_DESCRIPTION')) {
            $ogDescription = WCF::getLanguage()->get(META_DESCRIPTION);
        }
        if (!empty($ogDescription)) {
            $metaTagHandler->addTag('og:description', 'og:description', $ogDescription, true);
        }

        // og:url - Canonical URL (the short URL)
        $canonicalURL = $this->link->getShortedUrl(false);
        $canonicalURL = \rtrim($canonicalURL, '/') . '/';
        // Make absolute URL
        if (!\preg_match('~^https?://~', $canonicalURL)) {
            $canonicalURL = WCF::getPath() . \ltrim($canonicalURL, '/');
        }
        $metaTagHandler->addTag('og:url', 'og:url', $canonicalURL, true);

        // og:type
        $metaTagHandler->addTag('og:type', 'og:type', 'website', true);

        // og:site_name - From WoltLab PAGE_TITLE
        if (\defined('PAGE_TITLE')) {
            $siteName = WCF::getLanguage()->get(PAGE_TITLE);
            $metaTagHandler->addTag('og:site_name', 'og:site_name', $siteName, true);
        }

        // og:locale - Language code
        $language = WCF::getLanguage();
        $locale = $language->languageCode ?? 'en';
        if ($locale === 'de') {
            $locale = 'de_DE';
        } elseif ($locale === 'en') {
            $locale = 'en_US';
        }
        $metaTagHandler->addTag('og:locale', 'og:locale', $locale, true);

        // og:image - Priority: link ogImage > WoltLab OG_IMAGE > favicon > special image
        $ogImage = null;
        
        // 1. Link-spezifisches Open Graph Bild
        if (!empty($this->link->ogImage)) {
            $ogImage = $this->link->ogImage;
        }
        // 2. WoltLab OG_IMAGE Option
        elseif (\defined('OG_IMAGE') && !empty(\OG_IMAGE)) {
            $ogImage = \OG_IMAGE;
        }
        // 3. Favicon aus Discount (if available)
        elseif ($this->discount && \method_exists($this->discount, 'getFaviconUrl') && $this->discount->getFaviconUrl()) {
            $ogImage = $this->discount->getFaviconUrl();
        }
        // 4. Special Image (if available)
        elseif ($this->special && $this->special->isCurrentlyActive()) {
            // Special images are not yet implemented
        }

        if ($ogImage) {
            // Make absolute URL if not already
            if (!\preg_match('~^https?://~', $ogImage)) {
                $ogImage = WCF::getPath() . \ltrim($ogImage, '/');
            }
            $metaTagHandler->addTag('og:image', 'og:image', $ogImage, true);
        }

        // Twitter Card Tags
        $metaTagHandler->addTag('twitter:card', 'twitter:card', 'summary_large_image', false);
        $metaTagHandler->addTag('twitter:title', 'twitter:title', $ogTitle, false);
        if (!empty($ogDescription)) {
            $metaTagHandler->addTag('twitter:description', 'twitter:description', $ogDescription, false);
        }
        if ($ogImage) {
            $metaTagHandler->addTag('twitter:image', 'twitter:image', $ogImage, false);
        }
    }
}
