-- Shr1nkr local test data (wsc.local) – idempotent-ish via DELETE + INSERT

DELETE FROM shrinkr1_featured_link WHERE linkID IN (SELECT linkID FROM shrinkr1_link WHERE hash IN ('disc20','theme1','fullfx','plain1','pwd01'));
DELETE FROM shrinkr1_custom_button WHERE linkID IN (SELECT linkID FROM shrinkr1_link WHERE hash IN ('disc20','theme1','fullfx','plain1','pwd01'));
DELETE FROM shrinkr1_special WHERE linkID IN (SELECT linkID FROM shrinkr1_link WHERE hash IN ('disc20','theme1','fullfx','plain1','pwd01'));
DELETE FROM shrinkr1_link WHERE hash IN ('disc20','theme1','fullfx','plain1','pwd01','nodisc');
DELETE FROM shrinkr1_discount WHERE discountValue = '20% Test-Rabatt' AND isDemo = 0;
DELETE FROM shrinkr1_description WHERE title = 'Test Beschreibung QA';
DELETE FROM shrinkr1_voucher_permanent WHERE token IN ('vpermqa1', 'vpwdqa1');
DELETE FROM shrinkr1_voucher_once WHERE token = 'vonceqa1';
DELETE FROM shrinkr1_onetime_message WHERE token = 'otmqa001';

INSERT INTO shrinkr1_discount (
  discountValue, hosts, codes, special,
  primaryColor, secondaryColor, primaryTextColor, secondaryTextColor,
  countdownStart, countdownEnd, isDemo
) VALUES (
  '20% Test-Rabatt', 'example.com', 'SHRINKR', 0,
  'rgba(58, 109, 156, 1)', 'rgba(44, 62, 80, 1)', 'rgba(255, 255, 255, 1)', 'rgba(255, 255, 255, 1)',
  0, 0, 0
);

INSERT INTO shrinkr1_link (url, hash, linkTitle, showDescription, isDemo) VALUES
('https://example.com/shop', 'disc20', 'Discount Test', 1, 0),
('https://example.com/theme', 'theme1', 'Theme Special Test', 1, 0),
('https://example.com/full', 'fullfx', 'Full Features Test', 1, 0),
('https://example.com/plain', 'plain1', 'Plain Link', 1, 0),
('https://wikipedia.org/wiki/Test', 'nodisc', 'No Discount Link', 1, 0),
('https://example.com/secret', 'pwd01', 'Password Link QA', 1, 0);

SET @lid_disc = (SELECT linkID FROM shrinkr1_link WHERE hash = 'disc20');
SET @lid_theme = (SELECT linkID FROM shrinkr1_link WHERE hash = 'theme1');
SET @lid_full = (SELECT linkID FROM shrinkr1_link WHERE hash = 'fullfx');

INSERT INTO shrinkr1_special (
  linkID, theme, title, discount, discountID, codes,
  primaryColor, secondaryColor, primaryTextColor, secondaryTextColor,
  additionalText, startTime, endTime, isActive, isDemo
) VALUES (
  @lid_theme, 'halloween', 'Halloween Aktion', '15% Spooky', 0, 'SPOOKY',
  'var(--wcfHeaderBackground)', 'var(--wcfHeaderMenuBackground)', 'var(--wcfHeaderMenuLink)', 'var(--wcfHeaderMenuLink)',
  '<p>Special Halloween Text</p>', 0, 0, 1, 0
);

INSERT INTO shrinkr1_featured_link (linkID, url, title, sortOrder, isDemo, isDisabled) VALUES
(@lid_full, 'https://example.com/featured', 'Featured QA Link', 1, 0, 0);

INSERT INTO shrinkr1_custom_button (linkID, targetUrl, title, sortOrder, isDemo, isDisabled) VALUES
(@lid_full, 'https://example.com/custom', 'Custom QA Button', 1, 0, 0);

INSERT INTO shrinkr1_description (title, descriptionText, isActive, isDemo) VALUES
('Test Beschreibung QA', '<p>QA Beschreibungstext für Weiterleitungsseite</p>', 1, 0);

INSERT INTO shrinkr1_voucher_permanent (
  token, code, title, discountValue, theme, startTime, endTime, isActive, isDemo, passwordHash
) VALUES (
  'vpermqa1', 'PERM-QA', 'Permanenter QA-Gutschein', '10% QA', 'christmas', 0, 0, 1, 0, NULL
), (
  'vpwdqa1', 'PWD-QA', 'Passwort-Gutschein QA', '8% QA', '', 0, 0, 1, 0,
  '$2y$10$Ufhb2mJtOg71VxLRh/Y5iezV9EpYt6GUnEYx0KtO1l2Rs.L2jBmpa'
);

INSERT INTO shrinkr1_voucher_once (
  token, code, title, discountValue, theme, startTime, endTime, isActive, isDemo, createdTime, passwordHash
) VALUES (
  'vonceqa1', 'ONCE-QA', 'Einmal-QA-Gutschein', '5% QA', '', 0, 0, 1, 0, UNIX_TIMESTAMP(),
  '$2y$10$Ufhb2mJtOg71VxLRh/Y5iezV9EpYt6GUnEYx0KtO1l2Rs.L2jBmpa'
);

INSERT INTO shrinkr1_onetime_message (token, messageText, expiryTime, createdTime) VALUES
('otmqa001', 'Einmalnachricht QA – nur einmal sichtbar', 0, UNIX_TIMESTAMP());

UPDATE wcf1_option SET optionValue = '1' WHERE optionName IN ('shrinkr_enable_descriptions', 'shrinkr_counter_active');
