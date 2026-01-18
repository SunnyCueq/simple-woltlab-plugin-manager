-- SQL-Script zum Bereinigen von Plugin-Resten in der Datenbank
-- Führt folgende Aktionen aus:
-- 1. Zeigt alle shrinkr-bezogenen Einträge in package_installation_file_log
-- 2. Löscht den problematischen app.config.inc.php Eintrag
-- 3. Zeigt alle verbleibenden shrinkr-Einträge

-- 1. Zeige alle shrinkr-bezogenen Einträge
SELECT '=== Alle shrinkr-bezogenen Einträge ===' as info;
SELECT packageID, filename, application, lastUpdated 
FROM wcf1_package_installation_file_log 
WHERE filename LIKE '%shrinkr%' 
   OR filename LIKE '%app.config.inc.php%'
   OR application = 'shrinkr'
ORDER BY lastUpdated DESC;

-- 2. Zeige den problematischen app.config.inc.php Eintrag
SELECT '=== Problematischer app.config.inc.php Eintrag ===' as info;
SELECT packageID, filename, application, lastUpdated 
FROM wcf1_package_installation_file_log 
WHERE filename LIKE '%app.config.inc.php%' 
  AND application = 'shrinkr';

-- 3. LÖSCHE den problematischen Eintrag (auskommentiert - erst prüfen!)
-- DELETE FROM wcf1_package_installation_file_log 
-- WHERE filename LIKE '%app.config.inc.php%' 
--   AND application = 'shrinkr';

-- 4. Zeige alle verbleibenden shrinkr-Einträge nach dem Löschen
-- SELECT '=== Verbleibende shrinkr-Einträge ===' as info;
-- SELECT COUNT(*) as count, application 
-- FROM wcf1_package_installation_file_log 
-- WHERE application = 'shrinkr' 
-- GROUP BY application;
