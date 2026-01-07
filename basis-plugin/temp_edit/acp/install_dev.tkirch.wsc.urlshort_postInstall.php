<?php

/**
 * Post-installation script for URL Shortener: Affiliate System
 * 
 * This script runs after installation and creates comprehensive test data:
 * 
 * 1. Sets urlshort_normal_page_mode to true (1) for full page mode
 * 2. Creates 10 example descriptions (psychologically optimized, gender-neutral)
 * 3. Creates 1 basic example discount (Google with 24h countdown)
 * 4. Creates default themes (Halloween, Black Week, Christmas, Autumn, Ostern, Valentinstag)
 * 5. Creates comprehensive test data (only if no DEMO-* URLs exist):
 * 
 * DEMO URLs - Each demonstrates a different use case:
 * ┌──────────┬────────────────────────────────────────────────────────────────┐
 * │ DEMO-1   │ Discount + Countdown + Featured Links (Amazon Echo)           │
 * │ DEMO-2   │ Special (Halloween) + Featured Links (Fire TV)                │
 * │ DEMO-3   │ Special (Black Week) + Countdown (iPhone)                     │
 * │ DEMO-4   │ Discount only (no countdown) + Featured Links (Samsung)       │
 * │ DEMO-5   │ Special (Autumn) + Future start (Nike)                        │
 * │ DEMO-6   │ Special (Christmas) + Expired (IKEA)                          │
 * │ DEMO-7   │ Featured Links only (no discount/special) (WoltLab)           │
 * └──────────┴────────────────────────────────────────────────────────────────┘
 * 
 * Created data:
 * - 7 test URLs (DEMO-1 to DEMO-7)
 * - 2 discounts (Amazon with countdown, MediaMarkt without)
 * - 4 specials (Halloween active, Black Week active, Summer future, Christmas expired)
 * - 13 featured links distributed across URLs
 * - 10 descriptions with placeholders (psychologically optimized)
 * 
 * Test data covers ALL use cases:
 * ✓ Active/expired/upcoming countdowns
 * ✓ All themes (Halloween, Black Week, Christmas, Autumn, Ostern, Valentinstag)
 * ✓ Multiple discount codes per discount
 * ✓ Featured links with descriptions
 * ✓ URLs with only discount, only special, or only featured links
 *
 * @author      Benjaro <https://benjaro.info>
 * @copyright   2025 Benjaro
 * @license     Commercial License
 * @package     dev.tkirch.wsc.urlshort
 */

use wcf\system\WCF;
use urlshort\data\theme\Theme;
use urlshort\data\theme\ThemeEditor;

// Helper function for logging
// Uses WoltLab's SystemException for error logging (appears in ACP → Management → Log → Errors)
// Also writes to demodata.log for debugging
// See: https://docs.woltlab.com/6.0/view/languages-naming-conventions/#error-texts
function logPostInstall($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [PostInstall] {$message}";
    
    // Always log to PHP error_log (visible in server logs)
    error_log($logMessage);
    
    // Try to write to demodata.log (same as Event Listener)
    try {
        $logFile = WCF_DIR . 'log/demodata.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        if (is_writable($logDir) || (file_exists($logFile) && is_writable($logFile))) {
            @file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    } catch (\Exception $e) {
        // Ignore file write errors
    }
    
    // Also try to log to WoltLab's error log system
    try {
        if (class_exists('wcf\system\exception\SystemException') && class_exists('wcf\system\WCF')) {
            // SystemException constructor: (string $message, int $code = 0, string $file = '', ?\Exception $previous = null)
            $exception = new \wcf\system\exception\SystemException('[Post-install] ' . $message, 0, '', null);
            // Log the exception using WoltLab's exception logger
            if (method_exists('wcf\system\WCF', 'getExceptionLogger')) {
                \wcf\system\WCF::getExceptionLogger()->logException($exception);
            }
        }
    } catch (\Exception $e) {
        // Ignore - already logged to error_log and demodata.log
    }
}

// ============================================================================
// 1. Set urlshort_normal_page_mode to 1 (true) for full page mode
// ============================================================================
$sql = "UPDATE  wcf" . WCF_N . "_option
        SET     optionValue = ?
        WHERE   optionName = ?";
$statement = WCF::getDB()->prepareStatement($sql);
$statement->execute([
    '1',
    'urlshort_normal_page_mode'
]);

// ============================================================================
// 2. Create example descriptions (if table exists and is empty)
// ============================================================================
$tableExists = false;
try {
    $sql = "SHOW TABLES LIKE 'urlshort1_description'";
    $statement = WCF::getDB()->prepareStatement($sql);
    $statement->execute();
    $tableExists = ($statement->fetchSingleColumn() !== false);
} catch (\Exception) {
    // Table doesn't exist, skip
}

if ($tableExists) {
    try {
        $sql = "SELECT COUNT(*) FROM urlshort1_description";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        $count = $statement->fetchSingleColumn();

        // Only insert if table is empty
        if ($count == 0) {
            $descriptions = [
                [
                    'title' => 'Vertrauen & Wertschätzung',
                    'text' => 'Danke für dein Vertrauen, dass du dir meine Empfehlung von [[FAV_URL_LINK]] anschauen und vielleicht ausprobieren möchtest. Ich nehme meine Verantwortung sehr ernst und wähle nur Produkte aus, von denen ich aus tiefster Überzeugung überzeugt bin. Dein Feedback ist mir unglaublich wichtig – teile gerne deine Erfahrungen mit uns in der Community, denn gemeinsam schaffen wir etwas Besonderes.',
                ],
                [
                    'title' => 'Persönlich & Herzlich',
                    'text' => 'Hey, danke von Herzen, dass du meiner Empfehlung eine Chance gibst! Ich stehe voll und ganz hinter {$extractedTitle}, weil es mich persönlich wirklich begeistert hat. Schau gerne bei [[FAV_URL_LINK]] vorbei und lass dich selbst überzeugen – ich würde mich riesig freuen, wenn du mir erzählst, wie es dir gefällt! Deine Meinung bedeutet mir sehr viel.',
                ],
                [
                    'title' => 'Einladend & Respektvoll',
                    'text' => 'Ich möchte dir von Herzen danken, dass du dir die Zeit nimmst, meine Empfehlung zu entdecken. Ich wähle meine Empfehlungen mit größter Sorgfalt aus – nur Produkte, die ich selbst liebe und von denen ich überzeugt bin, teile ich mit dir. Ich lade dich herzlich ein, [[FAV_URL_LINK]] zu entdecken und deine eigenen Erfahrungen zu sammeln. Dein Feedback hilft mir, noch besser zu werden.',
                ],
                [
                    'title' => 'Wertschätzend & Verbindend',
                    'text' => 'Ich schätze dein Vertrauen ungemein und freue mich, dass du dir meine Empfehlung anschaust. Jede Empfehlung, die ich teile, kommt aus meinem Herzen – ich wähle nur, was mich wirklich überzeugt und begeistert. Besuche gerne [[FAV_URL_LINK]] und entdecke, was mich daran so fasziniert. Deine Gedanken und Erfahrungen sind mir sehr wichtig – lass uns gemeinsam wachsen!',
                ],
                [
                    'title' => 'Authentisch & Direkt',
                    'text' => 'Danke, dass du mir vertraust! Ich empfehle nur, was ich selbst ausprobiert habe und wirklich liebe. Keine leeren Versprechungen, nur ehrliche Empfehlungen. Probier gerne [[FAV_URL_LINK]] aus und lass mich wissen, was du denkst – deine ehrliche Meinung ist mir das Wichtigste. Zusammen finden wir die besten Lösungen!',
                ],
                [
                    'title' => 'Professionell & Vertrauensvoll',
                    'text' => 'Ich empfehle dir [[FAV_URL_LINK]] mit vollster Überzeugung und auf Basis meiner persönlichen Erfahrung. Jede Empfehlung durchläuft bei mir einen sorgfältigen Auswahlprozess – nur was mich wirklich überzeugt, teile ich mit dir. Deine Meinung ist mir wichtig und hilft mir, noch präziser zu werden. Lass uns gemeinsam das Beste finden!',
                ],
                [
                    'title' => 'Enthusiasmus & Begeisterung',
                    'text' => 'Es freut mich riesig, dass du hier bist! {$extractedTitle} hat mich so begeistert, dass ich es dir unbedingt zeigen musste. Ich teste alles selbst gründlich, bevor ich es empfehle – Qualität und echte Begeisterung stehen für mich an erster Stelle. Schau dir gerne [[FAV_URL_LINK]] an und lass mich wissen, ob es dich genauso überzeugt wie mich. Ich bin gespannt auf deine Gedanken!',
                ],
                [
                    'title' => 'Gemeinschaftlich & Wertschätzend',
                    'text' => 'Von Herzen danke, dass du meiner Empfehlung vertraust! Ich nehme meine Rolle sehr ernst und teile nur, was ich selbst für wirklich wertvoll halte. Besuche gerne [[FAV_URL_LINK]] und teile deine Erfahrungen mit unserer wunderbaren Community – dein Feedback hilft nicht nur mir, sondern allen, die auf der Suche nach dem Besten sind. Gemeinsam sind wir stärker!',
                ],
                [
                    'title' => 'Prägnant & Überzeugend',
                    'text' => 'Kurz und ehrlich: Ich empfehle nur, was ich selbst nutze, liebe und von dem ich überzeugt bin. Keine Kompromisse bei Qualität und Vertrauen. Schau dir gerne [[FAV_URL_LINK]] an und lass mich wissen, was du denkst – deine Meinung zählt und hilft uns allen weiter!',
                ],
                [
                    'title' => 'Persönlich & Authentisch',
                    'text' => 'Hey! Ich bin wirklich überzeugt von {$extractedTitle} – sonst würde ich es dir nicht mit so viel Begeisterung zeigen. Ich teste alles selbst, bevor ich es empfehle, und teile nur, was mich wirklich überzeugt hat. Probier gerne [[FAV_URL_LINK]] aus und erzähl mir, wie es dir gefällt – ich freue mich auf deine ehrliche Meinung und darauf, gemeinsam das Beste zu finden!',
                ],
            ];

            $sql = "INSERT INTO urlshort1_description (title, descriptionText, isActive) VALUES (?, ?, 1)";
            $statement = WCF::getDB()->prepareStatement($sql);

            foreach ($descriptions as $description) {
                $statement->execute([
                    $description['title'],
                    $description['text'],
                ]);
            }
        }
    } catch (\Exception) {
        // Silent fail - non-critical
    }
}

// ============================================================================
// 3. Create example discount - MOVED TO DEMO DATA SECTION BELOW
// ============================================================================

// ============================================================================
// 4. Create default themes (if table exists and is empty)
// ============================================================================
$themeTableExists = false;
try {
    $sql = "SHOW TABLES LIKE 'urlshort1_theme'";
    $statement = WCF::getDB()->prepareStatement($sql);
    $statement->execute();
    $themeTableExists = ($statement->fetchSingleColumn() !== false);
} catch (\Exception) {
    // Table doesn't exist, skip
}

if ($themeTableExists) {
    try {
        $sql = "SELECT COUNT(*) FROM urlshort1_theme";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        $count = $statement->fetchSingleColumn();

        // Only insert if table is empty
        if ($count == 0) {
            $defaultThemes = [
                [
                    'identifier' => 'halloween',
                    'discountValue' => 'Halloween',
                    'effectIdentifier' => 'ghosts',
                    'primaryColor' => 'rgba(255, 117, 24, 1)', // Pumpkin Orange
                    'secondaryColor' => 'rgba(106, 13, 173, 1)', // Witchy Purple
                    'primaryTextColor' => 'rgba(0, 0, 0, 1)', // Midnight Black
                    'secondaryTextColor' => 'rgba(249, 246, 238, 1)', // Bone White
                ],
                [
                    'identifier' => 'blackweek',
                    'discountValue' => 'Black Week',
                    'effectIdentifier' => 'none',
                    'primaryColor' => 'rgba(0, 0, 0, 1)',
                    'secondaryColor' => 'rgba(255, 215, 0, 1)',
                    'primaryTextColor' => 'rgba(255, 255, 255, 1)',
                    'secondaryTextColor' => 'rgba(0, 0, 0, 1)',
                ],
                [
                    'identifier' => 'christmas',
                    'discountValue' => 'Weihnachten (Modern)',
                    'effectIdentifier' => 'snow',
                    'primaryColor' => 'rgba(209, 77, 83, 1)', // Dark Terra Cotta
                    'secondaryColor' => 'rgba(68, 49, 89, 1)', // Jacarta
                    'primaryTextColor' => 'rgba(46, 102, 84, 1)', // Dark Slate Gray
                    'secondaryTextColor' => 'rgba(250, 226, 183, 1)', // Banana Mania
                ],
                [
                    'identifier' => 'autumn',
                    'discountValue' => 'Herbst',
                    'effectIdentifier' => 'autumnLeaves',
                    'primaryColor' => 'rgba(205, 92, 92, 1)',
                    'secondaryColor' => 'rgba(244, 164, 96, 1)',
                    'primaryTextColor' => 'rgba(0, 0, 0, 1)',
                    'secondaryTextColor' => 'rgba(0, 0, 0, 1)',
                ],
                [
                    'identifier' => 'ostern',
                    'discountValue' => 'Ostern',
                    'effectIdentifier' => 'none',
                    'primaryColor' => 'rgba(255, 192, 203, 1)', // Pastellrosa
                    'secondaryColor' => 'rgba(255, 255, 153, 1)', // Pastellgelb
                    'primaryTextColor' => 'rgba(0, 0, 0, 1)',
                    'secondaryTextColor' => 'rgba(0, 0, 0, 1)',
                ],
                [
                    'identifier' => 'christmas-dark',
                    'discountValue' => 'Weihnachten (Dunkel)',
                    'effectIdentifier' => 'snow',
                    'primaryColor' => 'rgba(128, 42, 44, 1)', // Antique Ruby
                    'secondaryColor' => 'rgba(47, 74, 29, 1)', // Kombu Green
                    'primaryTextColor' => 'rgba(128, 122, 113, 1)', // Sonic Silver
                    'secondaryTextColor' => 'rgba(184, 179, 170, 1)', // Light Silver
                ],
                [
                    'identifier' => 'valentinstag',
                    'discountValue' => 'Valentinstag',
                    'effectIdentifier' => 'none',
                    'primaryColor' => 'rgba(192, 6, 69, 1)', // Rose red
                    'secondaryColor' => 'rgba(225, 168, 172, 1)', // Cherry blossom pink
                    'primaryTextColor' => 'rgba(255, 255, 255, 1)',
                    'secondaryTextColor' => 'rgba(255, 255, 255, 1)',
                ],
            ];

            $sql = "INSERT INTO urlshort1_theme
                    (identifier, title, effectIdentifier, primaryColor, secondaryColor, primaryTextColor, secondaryTextColor, isActive)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $statement = WCF::getDB()->prepareStatement($sql);

            foreach ($defaultThemes as $theme) {
                $statement->execute([
                    $theme['identifier'],
                    $theme['discountValue'],
                    $theme['effectIdentifier'],
                    $theme['primaryColor'],
                    $theme['secondaryColor'],
                    $theme['primaryTextColor'],
                    $theme['secondaryTextColor'],
                ]);
            }
        }
    } catch (\Exception) {
        // Silent fail - non-critical
    }
}

// ============================================================================
// 5. Create comprehensive test data (URLs, Discounts, Specials)
// ============================================================================

// Check if URL table exists (from base plugin)
$urlTableExists = false;
try {
    $sql = "SHOW TABLES LIKE 'urlshort1_url'";
    $statement = WCF::getDB()->prepareStatement($sql);
    $statement->execute();
    $urlTableExists = ($statement->fetchSingleColumn() !== false);
} catch (\Exception) {
    // Table doesn't exist - base plugin not installed yet
}

// Only proceed if base plugin is installed (URL table exists)
logPostInstall('Post-install: Starting test data creation...');
logPostInstall('Post-install: URL table exists: ' . ($urlTableExists ? 'yes' : 'no'));

// Check if demo data installation is enabled
// Default: false (do not install demo data)
$installDemoData = false;
try {
    $demoDataOption = \wcf\data\option\Option::getOptionByName('urlshort_install_demo_data');
    if ($demoDataOption && $demoDataOption->optionValue == '1') {
        $installDemoData = true;
        logPostInstall('Post-install: Demo data installation is enabled via option');
    } else {
        logPostInstall('Post-install: Demo data installation is disabled (option value: ' . ($demoDataOption ? $demoDataOption->optionValue : 'not found, using default)'));
    }
} catch (\Exception $e) {
    // Option might not exist yet, use default (do not install demo data)
    logPostInstall('Post-install: Could not check demo data option, using default (do not install demo data): ' . $e->getMessage());
}

// Check if we're being called from the event listener (not during initial installation)
$fromEvent = isset($GLOBALS['urlshort_demo_data_from_event']) && $GLOBALS['urlshort_demo_data_from_event'];

// If called from event listener, ALWAYS proceed (even if URLs exist, we might need to create Featured Links/Custom Buttons)
// If called during installation, only proceed if demo data installation is enabled
if ($urlTableExists && ($installDemoData || $fromEvent)) {
    if ($fromEvent) {
        logPostInstall('Post-install: Called from event listener - will create Featured Links/Custom Buttons even if URLs exist');
    }
    try {
        // ============================================================================
        // 3.1 Create example discount (if table exists and is empty) - PART OF DEMO DATA
        // ============================================================================
        $discountTableExists = false;
        try {
            $sql = "SHOW TABLES LIKE 'urlshort1_discount'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $discountTableExists = ($statement->fetchSingleColumn() !== false);
        } catch (\Exception) {
            // Table doesn't exist, skip
        }

        if ($discountTableExists) {
            try {
                // Check if example discount already exists (by hosts)
                $sql = "SELECT COUNT(*) FROM urlshort1_discount WHERE hosts = ?";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute(['google.de']);
                $count = $statement->fetchSingleColumn();

                // Only insert if example doesn't exist
                if ($count == 0) {
                    // Countdown: 24 hours from now
                    $countdownStart = TIME_NOW;
                    $countdownEnd = TIME_NOW + (24 * 60 * 60); // 24 hours

                    $sql = "INSERT INTO urlshort1_discount
                            (discountValue, favicon, hosts, special, specialIdentifier, additionalText, codes, primaryColor, secondaryColor, primaryTextColor, secondaryTextColor, countdownStart, countdownEnd)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $statement = WCF::getDB()->prepareStatement($sql);

                    $statement->execute([
                        '30%',                                        // discountValue
                        null,                                         // favicon (auto-fetched)
                        'google.de',                                  // hosts
                        0,                                            // special
                        null,                                         // specialIdentifier
                        '<p>Dies ist ein Beispiel-Rabatt für Google. Du kannst diesen Rabatt im ACP bearbeiten oder löschen.</p>', // additionalText
                        'DEMO-BEISPIELCODE2025',                      // codes (with DEMO- prefix for safe deletion)
                        'rgba(66, 133, 244, 1)',                      // primaryColor (Google Blue)
                        'rgba(251, 188, 5, 1)',                       // secondaryColor (Google Yellow)
                        'rgba(255, 255, 255, 1)',                     // primaryTextColor
                        'rgba(0, 0, 0, 1)',                           // secondaryTextColor
                        $countdownStart,                              // countdownStart
                        $countdownEnd,                                // countdownEnd (24h from now)
                    ]);
                    logPostInstall('Post-install: Created example discount for google.de');
                }
            } catch (\Exception $e) {
                logPostInstall('Post-install: Failed to create example discount: ' . $e->getMessage());
            }
        }
        
        // Check if test URLs already exist and get their IDs
        $sql = "SELECT urlID, hash FROM urlshort1_url WHERE hash LIKE 'DEMO-%'";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        $existingDemoUrls = [];
        while ($row = $statement->fetchArray()) {
            $existingDemoUrls[$row['hash']] = $row['urlID'];
        }
        $testUrlCount = count($existingDemoUrls);

        logPostInstall('Post-install: Found ' . $testUrlCount . ' existing DEMO URLs');
        logPostInstall('Post-install: Existing URLs: ' . print_r($existingDemoUrls, true));

        // Initialize URL IDs array (will be filled either by creating new URLs or using existing ones)
        $insertedUrlIDs = [];
        
        // First, try to get IDs for existing DEMO URLs
        // Check if isDemo column exists
        $isDemoColumnExists = false;
        try {
            $sql = "SHOW COLUMNS FROM urlshort1_url LIKE 'isDemo'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $isDemoColumnExists = ($statement->fetchSingleColumn() !== false);
        } catch (\Exception) {
            // Column doesn't exist
        }
        
        // Load existing demo URLs (by isDemo flag if available, otherwise by hash)
        if ($isDemoColumnExists) {
            $sql = "SELECT urlID, hash FROM urlshort1_url WHERE isDemo = 1";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            while ($row = $statement->fetchArray()) {
                $insertedUrlIDs[$row['hash']] = $row['urlID'];
                logPostInstall('Post-install: Found existing demo URL ' . $row['hash'] . ' with ID ' . $row['urlID'] . ' (by isDemo flag)');
            }
        }
        
        // Also check by hash pattern (for backwards compatibility and to mark existing URLs)
        $sql = "SELECT urlID, hash FROM urlshort1_url WHERE hash IN ('DEMO-1', 'DEMO-2', 'DEMO-3', 'DEMO-4', 'DEMO-5', 'DEMO-6', 'DEMO-7')";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            if (!isset($insertedUrlIDs[$row['hash']])) {
                $insertedUrlIDs[$row['hash']] = $row['urlID'];
                logPostInstall('Post-install: Found existing URL ' . $row['hash'] . ' with ID ' . $row['urlID'] . ' (by hash)');
            }
            
            // Mark existing demo URLs with isDemo flag if column exists
            if ($isDemoColumnExists) {
                try {
                    $updateSql = "UPDATE urlshort1_url SET isDemo = 1 WHERE urlID = ?";
                    $updateStatement = WCF::getDB()->prepareStatement($updateSql);
                    $updateStatement->execute([$row['urlID']]);
                    logPostInstall('Post-install: Marked existing URL ' . $row['hash'] . ' (ID: ' . $row['urlID'] . ') as demo (isDemo = 1)');
                } catch (\Exception $e) {
                    logPostInstall('Post-install: Failed to mark URL ' . $row['hash'] . ' as demo: ' . $e->getMessage());
                }
            }
        }
        
        // Define test URLs array (used for all scenarios)
        $testUrls = [
            [
                'hash' => 'DEMO-1',
                'url' => 'https://www.amazon.de/dp/B08N5WRWNW',
                'discountValue' => 'Amazon Echo Dot (5. Gen) - Mit Rabatt & Countdown',
            ],
            [
                'hash' => 'DEMO-2',
                'url' => 'https://www.amazon.de/dp/B0B8R5YZWZ',
                'discountValue' => 'Fire TV Stick 4K - Halloween Special',
            ],
            [
                'hash' => 'DEMO-3',
                'url' => 'https://www.ebay.de/itm/123456789',
                'discountValue' => 'iPhone 15 Pro - Black Week Special',
            ],
            [
                'hash' => 'DEMO-4',
                'url' => 'https://www.mediamarkt.de/de/product/_samsung-galaxy-s24-2812345.html',
                'discountValue' => 'Samsung Galaxy S24 - Nur Rabatt (kein Countdown)',
            ],
            [
                'hash' => 'DEMO-5',
                'url' => 'https://www.zalando.de/nike-air-max-sneaker-low-black-ni111a0gg-q11.html',
                'discountValue' => 'Nike Air Max - Sommer Special (startet bald)',
            ],
            [
                'hash' => 'DEMO-6',
                'url' => 'https://www.ikea.com/de/de/p/billy-buecherregal-weiss-00263850/',
                'discountValue' => 'BILLY Bücherregal - Weihnachts-Special (abgelaufen)',
            ],
            [
                'hash' => 'DEMO-7',
                'url' => 'https://www.google.de/search?q=woltlab+suite',
                'discountValue' => 'WoltLab Suite - Nur Featured Links',
            ],
        ];
        
        // Always try to create URLs (they will be skipped if they already exist)
        logPostInstall('Post-install: Starting URL creation process...');
        logPostInstall('Post-install: Will create ' . count($testUrls) . ' test URLs');
        
        // ========================================================================
        // 5.1 Create comprehensive test discounts (only if discount table exists)
            // Each discount demonstrates different features:
            // - Discount 1: For DEMO-1 (Amazon) - Active with countdown
            // - Discount 2: For DEMO-4 (MediaMarkt) - Active without countdown
            // ========================================================================
            if ($discountTableExists) {
            // Check if test discounts already exist
            $sql = "SELECT COUNT(*) FROM urlshort1_discount WHERE discountValue LIKE '%Special%' OR discountValue LIKE '%Deals%'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $testDiscountCount = $statement->fetchSingleColumn();
            
            // Only create if they don't exist
            if ($testDiscountCount == 0) {
            $testDiscounts = [
                // Discount for DEMO-1: Amazon Echo with countdown
                [
                    'discountValue' => '30%',
                    'hosts' => 'amazon.de,amazon.com',
                    'codes' => 'DEMO-ECHO2025,DEMO-ALEXA30,DEMO-AMAZON30',
                    'primaryColor' => 'rgba(255, 153, 0, 1)',
                    'secondaryColor' => 'rgba(35, 47, 62, 1)',
                    'primaryTextColor' => 'rgba(255, 255, 255, 1)',
                    'secondaryTextColor' => 'rgba(255, 255, 255, 1)',
                    'countdownStart' => TIME_NOW,
                    'countdownEnd' => TIME_NOW + (7 * 24 * 60 * 60), // 7 days
                    'additionalText' => '<p><strong>Amazon Echo Special!</strong> Spare bis zu 30% auf Echo Geräte. Nur für kurze Zeit!</p>',
                ],
                // Discount for DEMO-4: MediaMarkt without countdown
                [
                    'discountValue' => '50€',
                    'hosts' => 'mediamarkt.de',
                    'codes' => 'DEMO-TECH50,DEMO-MEDIAMARKT2025,DEMO-SAMSUNG50',
                    'primaryColor' => 'rgba(227, 6, 19, 1)',
                    'secondaryColor' => 'rgba(255, 255, 255, 1)',
                    'primaryTextColor' => 'rgba(255, 255, 255, 1)',
                    'secondaryTextColor' => 'rgba(0, 0, 0, 1)',
                    'countdownStart' => 0, // No countdown
                    'countdownEnd' => 0, // No countdown
                    'additionalText' => '<p><strong>Technik-Deals!</strong> Die besten Angebote für Elektronik. Dauerhaft gültig!</p>',
                ],
            ];

            $sql = "INSERT INTO urlshort1_discount
                    (discountValue, favicon, hosts, special, specialIdentifier, additionalText, codes, primaryColor, secondaryColor, primaryTextColor, secondaryTextColor, countdownStart, countdownEnd)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $statement = WCF::getDB()->prepareStatement($sql);

            $insertedDiscountIDs = [];
            foreach ($testDiscounts as $discount) {
                $statement->execute([
                    $discount['discountValue'],
                    null, // favicon (auto-fetched)
                    $discount['hosts'],
                    0, // special
                    null, // specialIdentifier
                    $discount['additionalText'],
                    $discount['codes'],
                    $discount['primaryColor'],
                    $discount['secondaryColor'],
                    $discount['primaryTextColor'],
                    $discount['secondaryTextColor'],
                    $discount['countdownStart'],
                    $discount['countdownEnd'],
                ]);
                $insertedDiscountIDs[] = WCF::getDB()->getInsertID('urlshort1_discount', 'discountID');
            }
            }
            }

            // ========================================================================
            // 5.2 Create test URLs with various configurations
            // Each URL demonstrates a different use case:
            // - DEMO-1: Discount + Countdown + Featured Links
            // - DEMO-2: Special (Halloween) + Featured Links
            // - DEMO-3: Special (Black Week) + Countdown
            // - DEMO-4: Discount only (no countdown)
            // - DEMO-5: Special (Summer) + Future start
            // - DEMO-6: Special (Christmas) + Expired
            // - DEMO-7: Featured Links only (no discount/special)
            // ========================================================================
            // Note: $testUrls is already defined above (line 375)

            // Use direct SQL to create URLs (UrlAction might not work during installation)
            foreach ($testUrls as $url) {
                try {
                    // Check if URL with this hash already exists
                    $sql = "SELECT urlID FROM urlshort1_url WHERE hash = ?";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute([$url['hash']]);
                    $existingUrlID = $statement->fetchSingleColumn();
                    
                    if ($existingUrlID) {
                        $insertedUrlIDs[$url['hash']] = $existingUrlID;
                        logPostInstall('Post-install: URL ' . $url['hash'] . ' already exists with ID ' . $existingUrlID);
                        
                        // Mark existing URL as demo if isDemo column exists
                        if (in_array('isDemo', $columns)) {
                            try {
                                $updateSql = "UPDATE urlshort1_url SET isDemo = 1 WHERE urlID = ?";
                                $updateStatement = WCF::getDB()->prepareStatement($updateSql);
                                $updateStatement->execute([$existingUrlID]);
                                logPostInstall('Post-install: Marked existing URL ' . $url['hash'] . ' (ID: ' . $existingUrlID . ') as demo (isDemo = 1)');
                            } catch (\Exception $e) {
                                logPostInstall('Post-install: Failed to mark existing URL as demo: ' . $e->getMessage());
                            }
                        }
                        continue;
                    }
                    
                    // Check which columns exist
                    $sql = "SHOW COLUMNS FROM urlshort1_url";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute();
                    $columns = [];
                    while ($row = $statement->fetchArray()) {
                        $columns[] = $row['Field'];
                    }
                    
                    // Build INSERT statement dynamically based on available columns
                    // Use ? placeholders for prepareStatement() (not :hash)
                    $insertColumns = ['hash', 'url'];
                    $insertValues = ['?', '?'];
                    $params = [
                        $url['hash'],
                        $url['url'],
                    ];
                    
                    // Add optional columns if they exist
                    if (in_array('urlTitle', $columns)) {
                        $insertColumns[] = 'urlTitle';
                        $insertValues[] = '?';
                        $params[] = $url['discountValue'];
                    }
                    if (in_array('userID', $columns)) {
                        $insertColumns[] = 'userID';
                        $insertValues[] = '?';
                        $params[] = 1;
                    }
                    if (in_array('time', $columns)) {
                        $insertColumns[] = 'time';
                        $insertValues[] = '?';
                        $params[] = TIME_NOW;
                    }
                    if (in_array('counter', $columns)) {
                        $insertColumns[] = 'counter';
                        $insertValues[] = '?';
                        $params[] = 0;
                    }
                    // Mark as demo URL for easy identification and cleanup
                    if (in_array('isDemo', $columns)) {
                        $insertColumns[] = 'isDemo';
                        $insertValues[] = '?';
                        $params[] = 1; // Mark as demo
                    }
                    
                    // Create URL using direct SQL with ? placeholders
                    $sql = "INSERT INTO urlshort1_url (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
                    logPostInstall('Post-install: SQL: ' . $sql);
                    logPostInstall('Post-install: Params: ' . print_r($params, true));
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute($params);
                    
                    $newUrlID = WCF::getDB()->getInsertID('urlshort1_url', 'urlID');
                    if ($newUrlID) {
                        $insertedUrlIDs[$url['hash']] = $newUrlID;
                        logPostInstall('Post-install: ✓ Created URL ' . $url['hash'] . ' with ID ' . $newUrlID);
                    } else {
                        logPostInstall('Post-install: ⚠ Created URL ' . $url['hash'] . ' but could not get ID');
                        // Try to get ID by hash
                        $sql = "SELECT urlID FROM urlshort1_url WHERE hash = ?";
                        $statement = WCF::getDB()->prepareStatement($sql);
                        $statement->execute([$url['hash']]);
                        $foundID = $statement->fetchSingleColumn();
                        if ($foundID) {
                            $insertedUrlIDs[$url['hash']] = $foundID;
                            logPostInstall('Post-install: ✓ Found URL ID ' . $foundID . ' for ' . $url['hash']);
                        }
                    }
                } catch (\Exception $e) {
                    logPostInstall('Post-install: ✗ Failed to create URL ' . $url['hash'] . ': ' . $e->getMessage());
                    logPostInstall('Post-install: SQL: ' . (isset($sql) ? $sql : 'N/A'));
                    logPostInstall('Post-install: Params: ' . (isset($params) ? print_r($params, true) : 'N/A'));
                    logPostInstall('Post-install: Stack trace: ' . $e->getTraceAsString());
                }
            }
            
            logPostInstall('Post-install: URL creation completed. Total URLs in array: ' . count($insertedUrlIDs));
            logPostInstall('Post-install: URL IDs after creation: ' . print_r($insertedUrlIDs, true));
            
            // Merge with existing URLs (if any)
            if (!empty($existingDemoUrls)) {
                logPostInstall('Post-install: Merging existing URL IDs...');
                $insertedUrlIDs = array_merge($insertedUrlIDs, $existingDemoUrls);
            }
            
            // Final check: Get IDs for any missing DEMO URLs and mark them as demo
            $sql = "SELECT urlID, hash FROM urlshort1_url WHERE hash IN ('DEMO-1', 'DEMO-2', 'DEMO-3', 'DEMO-4', 'DEMO-5', 'DEMO-6', 'DEMO-7')";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            while ($row = $statement->fetchArray()) {
                if (!isset($insertedUrlIDs[$row['hash']])) {
                    $insertedUrlIDs[$row['hash']] = $row['urlID'];
                    logPostInstall('Post-install: Found missing URL ' . $row['hash'] . ' with ID ' . $row['urlID']);
                }
                
                // Mark as demo if isDemo column exists
                if ($isDemoColumnExists) {
                    try {
                        $updateSql = "UPDATE urlshort1_url SET isDemo = 1 WHERE urlID = ?";
                        $updateStatement = WCF::getDB()->prepareStatement($updateSql);
                        $updateStatement->execute([$row['urlID']]);
                        logPostInstall('Post-install: Marked URL ' . $row['hash'] . ' (ID: ' . $row['urlID'] . ') as demo (isDemo = 1)');
                    } catch (\Exception $e) {
                        logPostInstall('Post-install: Failed to mark URL as demo: ' . $e->getMessage());
                    }
                }
            }
            
            logPostInstall('Post-install: Final URL IDs: ' . print_r($insertedUrlIDs, true));
            logPostInstall('Post-install: Required URLs for Featured Links: DEMO-1=' . (isset($insertedUrlIDs['DEMO-1']) ? $insertedUrlIDs['DEMO-1'] : 'MISSING') . ', DEMO-2=' . (isset($insertedUrlIDs['DEMO-2']) ? $insertedUrlIDs['DEMO-2'] : 'MISSING') . ', DEMO-3=' . (isset($insertedUrlIDs['DEMO-3']) ? $insertedUrlIDs['DEMO-3'] : 'MISSING') . ', DEMO-4=' . (isset($insertedUrlIDs['DEMO-4']) ? $insertedUrlIDs['DEMO-4'] : 'MISSING') . ', DEMO-5=' . (isset($insertedUrlIDs['DEMO-5']) ? $insertedUrlIDs['DEMO-5'] : 'MISSING') . ', DEMO-7=' . (isset($insertedUrlIDs['DEMO-7']) ? $insertedUrlIDs['DEMO-7'] : 'MISSING'));
        
        // Continue with creating Specials and Featured Links regardless of whether URLs were just created or already existed
        // IMPORTANT: Make sure we have all URL IDs before proceeding
        logPostInstall('Post-install: About to create Specials/Featured Links/Custom Buttons. Available URL IDs: ' . print_r($insertedUrlIDs, true));
        logPostInstall('Post-install: Total URL IDs found: ' . count($insertedUrlIDs));
        
        if (!empty($insertedUrlIDs)) {
            logPostInstall('Post-install: ✓ URL IDs available, proceeding with Featured Links/Custom Buttons creation...');

            // ========================================================================
            // 5.3 Create test specials with different themes and configurations
            // ========================================================================
            $specialTableExists = false;
            try {
                $sql = "SHOW TABLES LIKE 'urlshort1_special'";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute();
                $specialTableExists = ($statement->fetchSingleColumn() !== false);
            } catch (\Exception) {
                // Table doesn't exist, skip
            }

            if ($specialTableExists) {
                logPostInstall('Post-install: Special table exists, checking URL IDs...');
                logPostInstall('Post-install: Available URL IDs: ' . print_r($insertedUrlIDs, true));
                
                // Check if specials already exist for these URLs
                $sql = "SELECT COUNT(*) FROM urlshort1_special WHERE urlID IN (" . implode(',', array_values($insertedUrlIDs)) . ")";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute();
                $existingSpecialsCount = $statement->fetchSingleColumn();
                
                if ($existingSpecialsCount > 0) {
                    logPostInstall('Post-install: Found ' . $existingSpecialsCount . ' existing specials, skipping creation');
                } else if (isset($insertedUrlIDs['DEMO-2']) && isset($insertedUrlIDs['DEMO-3']) && isset($insertedUrlIDs['DEMO-5']) && isset($insertedUrlIDs['DEMO-6'])) {
                    logPostInstall('Post-install: Creating new specials...');
                $testSpecials = [
                    // DEMO-2: Halloween Special (active, with countdown)
                    [
                        'urlID' => $insertedUrlIDs['DEMO-2'],
                        'theme' => 'halloween',
                        'discountValue' => 'Halloween Special - Fire TV Stick',
                        'discount' => '30%',
                        'codes' => 'HALLOWEEN30,SPOOKY2025',
                        'additionalText' => '<p>🎃 Gruseliger Halloween-Rabatt! 30% auf Fire TV Stick!</p>',
                        'startTime' => TIME_NOW,
                        'endTime' => TIME_NOW + (14 * 24 * 60 * 60), // 14 days
                        'isActive' => 1,
                    ],
                    // DEMO-3: Black Week Special (active, with countdown)
                    [
                        'urlID' => $insertedUrlIDs['DEMO-3'],
                        'theme' => 'blackweek',
                        'discountValue' => 'Black Week - iPhone Deal',
                        'discount' => '25%',
                        'codes' => 'BLACKWEEK25,IPHONE25',
                        'additionalText' => '<p>Black Week Mega-Deal! iPhone 15 Pro mit 25% Rabatt!</p>',
                        'startTime' => TIME_NOW - (2 * 24 * 60 * 60), // Started 2 days ago
                        'endTime' => TIME_NOW + (5 * 24 * 60 * 60), // Ends in 5 days
                        'isActive' => 1,
                    ],
                    // DEMO-5: Autumn Special (future start - starts in 3 days)
                    [
                        'urlID' => $insertedUrlIDs['DEMO-5'],
                        'theme' => 'autumn',
                        'discountValue' => 'Herbst-Special - Nike Air Max',
                        'discount' => '20%',
                        'codes' => 'AUTUMN20,NIKE20',
                        'additionalText' => '<p>🍂 Herbst-Special! 20% auf Nike Air Max! Startet in Kürze!</p>',
                        'startTime' => TIME_NOW + (3 * 24 * 60 * 60), // Starts in 3 days (FUTURE)
                        'endTime' => TIME_NOW + (17 * 24 * 60 * 60), // Ends in 17 days
                        'isActive' => 1,
                    ],
                    // DEMO-6: Christmas Special (expired)
                    [
                        'urlID' => $insertedUrlIDs['DEMO-6'],
                        'theme' => 'christmas',
                        'discountValue' => 'Weihnachts-Special - IKEA Möbel',
                        'discount' => '15%',
                        'codes' => 'XMAS15',
                        'additionalText' => '<p>🎄 Frohe Weihnachten! 15% auf Möbel! (Leider abgelaufen)</p>',
                        'startTime' => TIME_NOW - (10 * 24 * 60 * 60), // Started 10 days ago
                        'endTime' => TIME_NOW - (3 * 24 * 60 * 60), // Ended 3 days ago (EXPIRED)
                        'isActive' => 1,
                    ],
                ];

                $sql = "INSERT INTO urlshort1_special
                        (urlID, theme, title, discount, discountID, codes, primaryColor, secondaryColor, primaryTextColor, secondaryTextColor, additionalText, startTime, endTime, isActive)
                        VALUES (?, ?, ?, ?, 0, ?, 'rgba(255, 255, 255, 1)', 'rgba(255, 255, 255, 1)', 'rgba(0, 0, 0, 1)', 'rgba(0, 0, 0, 1)', ?, ?, ?, ?)";
                $statement = WCF::getDB()->prepareStatement($sql);

                    foreach ($testSpecials as $special) {
                        try {
                            $statement->execute([
                                $special['urlID'],
                                $special['theme'],
                                $special['discountValue'],
                                $special['discount'],
                                $special['codes'],
                                $special['additionalText'],
                                $special['startTime'],
                                $special['endTime'],
                                $special['isActive'],
                            ]);
                            logPostInstall('Post-install: Created special for URL ID ' . $special['urlID']);
                        } catch (\Exception $e) {
                            logPostInstall('Post-install: Failed to create special for URL ID ' . $special['urlID'] . ': ' . $e->getMessage());
                            logPostInstall('Post-install: Stack trace: ' . $e->getTraceAsString());
                        }
                    }
                } else {
                    logPostInstall('Post-install: Missing required URL IDs for specials (DEMO-2, DEMO-3, DEMO-5, DEMO-6)');
                    logPostInstall('Post-install: Available IDs: ' . print_r(array_keys($insertedUrlIDs), true));
                }
            } else {
                logPostInstall('Post-install: Special table does not exist, skipping special creation');
            }

            // ========================================================================
            // 5.4 Create test featured links for URLs
            // ========================================================================
            $featuredLinkTableExists = false;
            try {
                $sql = "SHOW TABLES LIKE 'urlshort1_featured_link'";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute();
                $featuredLinkTableExists = ($statement->fetchSingleColumn() !== false);
            } catch (\Exception) {
                // Table doesn't exist, skip
            }

            if ($featuredLinkTableExists) {
                logPostInstall('Post-install: Featured link table exists, checking for existing links...');
                logPostInstall('Post-install: Available URL IDs: ' . print_r($insertedUrlIDs, true));
                
                // Check if featured links already exist for these URLs
                if (!empty($insertedUrlIDs)) {
                    // Check if we have all required URL IDs
                    $requiredUrls = ['DEMO-1', 'DEMO-2', 'DEMO-3', 'DEMO-4', 'DEMO-5', 'DEMO-7'];
                    $missingUrls = [];
                    foreach ($requiredUrls as $hash) {
                        if (!isset($insertedUrlIDs[$hash])) {
                            $missingUrls[] = $hash;
                        }
                    }
                    
                    if (!empty($missingUrls)) {
                        logPostInstall('Post-install: ⚠ Missing URL IDs for featured links: ' . implode(', ', $missingUrls) . ' - will try to create links for available URLs');
                    }
                    
                    // Check existing links count for logging
                    if (!empty($insertedUrlIDs)) {
                        $placeholders = str_repeat('?,', count($insertedUrlIDs) - 1) . '?';
                        $sql = "SELECT COUNT(*) FROM urlshort1_featured_link WHERE urlID IN ({$placeholders})";
                        $statement = WCF::getDB()->prepareStatement($sql);
                        $statement->execute(array_values($insertedUrlIDs));
                        $existingLinksCount = $statement->fetchSingleColumn();
                        logPostInstall('Post-install: Found ' . $existingLinksCount . ' existing featured links for ' . count($insertedUrlIDs) . ' demo URLs');
                    } else {
                        $existingLinksCount = 0;
                        logPostInstall('Post-install: WARNING - No URL IDs available for featured links!');
                    }
                    
                    // ALWAYS try to create featured links if URLs exist, even if some links already exist
                    // This ensures featured links are created even if they were deleted or never created
                    if ($existingLinksCount > 0) {
                        logPostInstall('Post-install: Some featured links already exist (' . $existingLinksCount . '), but will check and create missing ones');
                    }
                    
                    // Always proceed with creation (will skip if all already exist)
                    if (!empty($insertedUrlIDs)) {
                        if (!empty($missingUrls)) {
                            logPostInstall('Post-install: ⚠ Missing URL IDs for featured links: ' . implode(', ', $missingUrls) . ' - will try to create links for available URLs');
                        }
                        logPostInstall('Post-install: Creating new featured links...');
                        logPostInstall('Post-install: Proceeding with featured link creation for available URLs');
                        logPostInstall('Post-install: Available URL IDs: ' . print_r($insertedUrlIDs, true));
                        
                        // Only create featured links for URLs that actually exist
                        $availableUrlIDs = [];
                        foreach (['DEMO-1', 'DEMO-2', 'DEMO-3', 'DEMO-4', 'DEMO-5', 'DEMO-7'] as $hash) {
                            if (isset($insertedUrlIDs[$hash]) && $insertedUrlIDs[$hash]) {
                                $availableUrlIDs[$hash] = $insertedUrlIDs[$hash];
                            }
                        }
                        logPostInstall('Post-install: Available URL IDs for featured links: ' . print_r($availableUrlIDs, true));
                        $testFeaturedLinks = [];
                        
                        // Featured Links for DEMO-1 (Amazon Echo with Discount + Countdown)
                        if (isset($availableUrlIDs['DEMO-1']) && $availableUrlIDs['DEMO-1']) {
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-1'],
                                'url' => 'https://www.amazon.de/dp/B09B8V1LZ3',
                                'discountValue' => 'Amazon Echo Show 8',
                                'description' => 'Mit Display für Videoanrufe und Smart Home Steuerung',
                                'sortOrder' => 0,
                            ];
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-1'],
                                'url' => 'https://www.amazon.de/dp/B085HK34M2',
                                'discountValue' => 'Amazon Echo Studio',
                                'description' => 'Premium-Sound mit 3D-Audio und Alexa',
                                'sortOrder' => 1,
                            ];
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-1'],
                                'url' => 'https://www.amazon.de/dp/B09B8RXYM7',
                                'discountValue' => 'Amazon Echo Buds',
                                'description' => 'Kabellose Ohrhörer mit Alexa',
                                'sortOrder' => 2,
                            ];
                        }
                        
                        // Featured Links for DEMO-2 (Fire TV with Halloween Special)
                        if (isset($availableUrlIDs['DEMO-2']) && $availableUrlIDs['DEMO-2']) {
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-2'],
                                'url' => 'https://www.amazon.de/dp/B08XVYZ1Y5',
                                'discountValue' => 'Fire TV Stick 4K Max',
                                'description' => 'Noch schneller mit WiFi 6 Support',
                                'sortOrder' => 0,
                            ];
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-2'],
                                'url' => 'https://www.amazon.de/dp/B08XW3MFPG',
                                'discountValue' => 'Fire TV Cube',
                                'description' => 'Hands-free mit Alexa und 4K Ultra HD',
                                'sortOrder' => 1,
                            ];
                        }
                        
                        // Featured Links for DEMO-3 (iPhone with Black Week Special)
                        if (isset($availableUrlIDs['DEMO-3']) && $availableUrlIDs['DEMO-3']) {
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-3'],
                                'url' => 'https://www.ebay.de/itm/234567890',
                                'discountValue' => 'iPhone 15 Hülle',
                                'description' => 'Schütze dein neues iPhone mit einer hochwertigen Hülle',
                                'sortOrder' => 0,
                            ];
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-3'],
                                'url' => 'https://www.ebay.de/itm/345678901',
                                'discountValue' => 'AirPods Pro (2. Gen)',
                                'description' => 'Perfekte Ergänzung zu deinem iPhone',
                                'sortOrder' => 1,
                            ];
                        }
                        
                        // Featured Links for DEMO-4 (Samsung with Discount only)
                        if (isset($availableUrlIDs['DEMO-4']) && $availableUrlIDs['DEMO-4']) {
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-4'],
                                'url' => 'https://www.mediamarkt.de/de/product/_samsung-galaxy-watch-6-2812346.html',
                                'discountValue' => 'Samsung Galaxy Watch 6',
                                'description' => 'Smartwatch passend zu deinem Galaxy S24',
                                'sortOrder' => 0,
                            ];
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-4'],
                                'url' => 'https://www.mediamarkt.de/de/product/_samsung-galaxy-buds-2-pro-2812347.html',
                                'discountValue' => 'Samsung Galaxy Buds 2 Pro',
                                'description' => 'Kabellose Premium-Ohrhörer',
                                'sortOrder' => 1,
                            ];
                        }
                        
                        // Featured Links for DEMO-5 (Nike with Autumn Special)
                        if (isset($availableUrlIDs['DEMO-5']) && $availableUrlIDs['DEMO-5']) {
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-5'],
                                'url' => 'https://www.zalando.de/nike-air-force-1-sneaker-low-white-ni111a0gg-q11.html',
                                'discountValue' => 'Nike Air Force 1',
                                'description' => 'Klassischer Sneaker in Weiß',
                                'sortOrder' => 0,
                            ];
                        }
                        
                        // Featured Links for DEMO-7 (Only Featured Links, no discount/special)
                        if (isset($availableUrlIDs['DEMO-7']) && $availableUrlIDs['DEMO-7']) {
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-7'],
                                'url' => 'https://www.woltlab.com/',
                                'discountValue' => 'WoltLab Suite kaufen',
                                'description' => 'Die beste Community-Software für deine Website',
                                'sortOrder' => 0,
                            ];
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-7'],
                                'url' => 'https://www.woltlab.com/pluginstore/',
                                'discountValue' => 'WoltLab Plugin Store',
                                'description' => 'Erweitere deine Community mit Plugins',
                                'sortOrder' => 1,
                            ];
                            $testFeaturedLinks[] = [
                                'urlID' => $availableUrlIDs['DEMO-7'],
                                'url' => 'https://docs.woltlab.com/',
                                'discountValue' => 'WoltLab Dokumentation',
                                'description' => 'Lerne alles über WoltLab Suite',
                                'sortOrder' => 2,
                            ];
                        }
                        
                        logPostInstall('Post-install: Prepared ' . count($testFeaturedLinks) . ' featured links to create');

                $sql = "INSERT INTO urlshort1_featured_link
                        (urlID, url, title, sortOrder)
                        VALUES (?, ?, ?, ?)";
                $statement = WCF::getDB()->prepareStatement($sql);

                        $createdCount = 0;
                        $skippedCount = 0;
                        foreach ($testFeaturedLinks as $link) {
                            // Skip if URL ID is not available
                            if (!isset($link['urlID']) || !$link['urlID']) {
                                $skippedCount++;
                                logPostInstall('Post-install: ⚠ Skipping featured link - URL ID not available: ' . print_r($link, true));
                                continue;
                            }
                            
                            // Check if this exact featured link already exists (to avoid duplicates)
                            try {
                                $checkSql = "SELECT COUNT(*) FROM urlshort1_featured_link WHERE urlID = ? AND url = ? AND title = ?";
                                $checkStatement = WCF::getDB()->prepareStatement($checkSql);
                                $checkStatement->execute([$link['urlID'], $link['url'], $link['discountValue']]);
                                $exists = $checkStatement->fetchSingleColumn();
                                
                                if ($exists > 0) {
                                    $skippedCount++;
                                    logPostInstall('Post-install: ⚠ Featured link already exists, skipping: ' . $link['discountValue'] . ' for URL ID ' . $link['urlID']);
                                    continue;
                                }
                            } catch (\Exception $e) {
                                // If check fails, try to create anyway
                                logPostInstall('Post-install: ⚠ Could not check if featured link exists: ' . $e->getMessage());
                            }
                            
                            try {
                                $statement->execute([
                                    $link['urlID'],
                                    $link['url'],
                                    $link['discountValue'], // title (description field doesn't exist in table)
                                    $link['sortOrder'],
                                ]);
                                $createdCount++;
                                logPostInstall('Post-install: ✓ Created featured link "' . $link['discountValue'] . '" for URL ID ' . $link['urlID']);
                            } catch (\Exception $e) {
                                $skippedCount++;
                                logPostInstall('Post-install: ✗ Failed to create featured link for URL ID ' . $link['urlID'] . ': ' . $e->getMessage());
                                logPostInstall('Post-install: Stack trace: ' . $e->getTraceAsString());
                            }
                        }
                        logPostInstall('Post-install: Created ' . $createdCount . ' featured links, skipped ' . $skippedCount . ' (out of ' . count($testFeaturedLinks) . ' total)');
                    } else {
                        logPostInstall('Post-install: ERROR - No URL IDs available for featured links! $insertedUrlIDs is empty!');
                        logPostInstall('Post-install: $insertedUrlIDs content: ' . print_r($insertedUrlIDs, true));
                    }
                } else {
                    logPostInstall('Post-install: No URL IDs available for featured links (empty check failed)');
                }
            } else {
                logPostInstall('Post-install: Featured link table does not exist, skipping featured link creation');
            }
            
            // ========================================================================
            // 5.5 Create test custom buttons for URLs
            // ========================================================================
            $customButtonTableExists = false;
            try {
                $sql = "SHOW TABLES LIKE 'urlshort1_custom_button'";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute();
                $customButtonTableExists = ($statement->fetchSingleColumn() !== false);
            } catch (\Exception) {
                // Table doesn't exist, skip
            }
            
            if ($customButtonTableExists && !empty($insertedUrlIDs)) {
                logPostInstall('Post-install: Custom button table exists, checking for existing buttons...');
                logPostInstall('Post-install: Available URL IDs: ' . print_r($insertedUrlIDs, true));
                
                // Check if custom buttons already exist for these URLs
                if (!empty($insertedUrlIDs)) {
                    // Check existing buttons count for logging
                    if (!empty($insertedUrlIDs)) {
                        $placeholders = str_repeat('?,', count($insertedUrlIDs) - 1) . '?';
                        $sql = "SELECT COUNT(*) FROM urlshort1_custom_button WHERE urlID IN ({$placeholders})";
                        $statement = WCF::getDB()->prepareStatement($sql);
                        $statement->execute(array_values($insertedUrlIDs));
                        $existingButtonsCount = $statement->fetchSingleColumn();
                        logPostInstall('Post-install: Found ' . $existingButtonsCount . ' existing custom buttons for ' . count($insertedUrlIDs) . ' demo URLs');
                    } else {
                        $existingButtonsCount = 0;
                        logPostInstall('Post-install: WARNING - No URL IDs available for custom buttons!');
                    }
                    
                    // ALWAYS try to create custom buttons if URLs exist, even if some buttons already exist
                    // This ensures custom buttons are created even if they were deleted or never created
                    if ($existingButtonsCount > 0) {
                        logPostInstall('Post-install: Some custom buttons already exist (' . $existingButtonsCount . '), but will check and create missing ones');
                    }
                    
                    // Always proceed with creation (will skip if all already exist)
                    if (!empty($insertedUrlIDs)) {
                        logPostInstall('Post-install: Creating new custom buttons...');
                        if (!isset($insertedUrlIDs['DEMO-1']) || !isset($insertedUrlIDs['DEMO-4'])) {
                            logPostInstall('Post-install: ⚠ Missing URL IDs for custom buttons (DEMO-1 or DEMO-4) - will try to create buttons for available URLs');
                        }
                        $testCustomButtons = [];
                        
                        // Custom Buttons for DEMO-1 (Amazon Echo) - only if URL exists
                        if (isset($insertedUrlIDs['DEMO-1']) && $insertedUrlIDs['DEMO-1']) {
                            $testCustomButtons[] = [
                                'urlID' => $insertedUrlIDs['DEMO-1'],
                                'targetUrl' => 'https://www.amazon.de/gp/help/customer/display.html',
                                'title' => 'Hilfe & Support',
                                'sortOrder' => 0,
                            ];
                            $testCustomButtons[] = [
                                'urlID' => $insertedUrlIDs['DEMO-1'],
                                'targetUrl' => 'https://www.amazon.de/gp/help/customer/display.html?nodeId=G201910480',
                                'title' => 'Rückgabe & Umtausch',
                                'sortOrder' => 1,
                            ];
                        }
                        
                        // Custom Buttons for DEMO-4 (Samsung) - only if URL exists
                        if (isset($insertedUrlIDs['DEMO-4']) && $insertedUrlIDs['DEMO-4']) {
                            $testCustomButtons[] = [
                                'urlID' => $insertedUrlIDs['DEMO-4'],
                                'targetUrl' => 'https://www.samsung.com/de/support/',
                                'title' => 'Samsung Support',
                                'sortOrder' => 0,
                            ];
                        }
                        
                        $sql = "INSERT INTO urlshort1_custom_button
                                (urlID, targetUrl, title, sortOrder)
                                VALUES (?, ?, ?, ?)";
                        $statement = WCF::getDB()->prepareStatement($sql);
                        
                        $createdCount = 0;
                        $skippedCount = 0;
                        foreach ($testCustomButtons as $button) {
                            try {
                                // Verify URL ID exists before inserting
                                if (!isset($button['urlID']) || !$button['urlID']) {
                                    $skippedCount++;
                                    logPostInstall('Post-install: ⚠ Skipping custom button - invalid URL ID: ' . print_r($button, true));
                                    continue;
                                }
                                
                                // Check if this exact custom button already exists (to avoid duplicates)
                                try {
                                    $checkSql = "SELECT COUNT(*) FROM urlshort1_custom_button WHERE urlID = ? AND targetUrl = ? AND title = ?";
                                    $checkStatement = WCF::getDB()->prepareStatement($checkSql);
                                    $checkStatement->execute([$button['urlID'], $button['targetUrl'], $button['title']]);
                                    $exists = $checkStatement->fetchSingleColumn();
                                    
                                    if ($exists > 0) {
                                        $skippedCount++;
                                        logPostInstall('Post-install: ⚠ Custom button already exists, skipping: ' . $button['title'] . ' for URL ID ' . $button['urlID']);
                                        continue;
                                    }
                                } catch (\Exception $e) {
                                    // If check fails, try to create anyway
                                    logPostInstall('Post-install: ⚠ Could not check if custom button exists: ' . $e->getMessage());
                                }
                                
                                $statement->execute([
                                    $button['urlID'],
                                    $button['targetUrl'],
                                    $button['title'],
                                    $button['sortOrder'],
                                ]);
                                $createdCount++;
                                logPostInstall('Post-install: ✓ Created custom button "' . $button['title'] . '" for URL ID ' . $button['urlID']);
                            } catch (\Exception $e) {
                                $skippedCount++;
                                logPostInstall('Post-install: ✗ Failed to create custom button for URL ID ' . ($button['urlID'] ?? 'UNKNOWN') . ': ' . $e->getMessage());
                                logPostInstall('Post-install: Stack trace: ' . $e->getTraceAsString());
                            }
                        }
                        logPostInstall('Post-install: Created ' . $createdCount . ' custom buttons, skipped ' . $skippedCount . ' (out of ' . count($testCustomButtons) . ' total)');
                    } else {
                        logPostInstall('Post-install: ERROR - No URL IDs available for custom buttons! $insertedUrlIDs is empty!');
                        logPostInstall('Post-install: $insertedUrlIDs content: ' . print_r($insertedUrlIDs, true));
                    }
                } else {
                    logPostInstall('Post-install: No URL IDs available for custom buttons (empty check failed)');
                }
            } else {
                if (!$customButtonTableExists) {
                    logPostInstall('Post-install: Custom button table does not exist, skipping custom button creation');
                } else {
                    logPostInstall('Post-install: No URL IDs available for custom buttons');
                }
            }
        } else {
            logPostInstall('Post-install: No URL IDs available for creating Specials, Featured Links and Custom Buttons');
        }
    } catch (\Exception $e) {
        // Log error for debugging
        logPostInstall('Post-install: Test data creation failed: ' . $e->getMessage());
        logPostInstall('Post-install: Stack trace: ' . $e->getTraceAsString());
    }
} else {
    if (!$urlTableExists) {
        logPostInstall('Post-install: URL table does not exist, skipping test data creation');
    } else if (!$installDemoData) {
        logPostInstall('Post-install: Demo data installation disabled via option, skipping test data creation');
    }
}

// ============================================================================
// 6. Summary Logging
// ============================================================================
logPostInstall('========================================');
logPostInstall('Post-install script completed');
logPostInstall('URL table exists: ' . ($urlTableExists ? 'yes' : 'no'));
logPostInstall('========================================');

