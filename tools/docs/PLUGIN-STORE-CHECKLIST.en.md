# Plugin Store Submission Checklist

**[Deutsche Version](PLUGIN-STORE-CHECKLIST.de.md)**

**Last updated:** 2026-06-26 (aligned with [WoltLab Plugin Store guidelines](https://www.woltlab.com/pluginstore/en/guidelines/))  
**Version:** 1.1.0

WoltLab reviews in **two stages**: automated pre-check on upload, then manual review. Our tools cover stage 1 extensively; stage 2 requires additional manual testing (functionality, UX, permissions).

**Required before every store upload:**

```bash
./tools/build.sh [PLUGIN_DIR]          # fails on template/build errors
./tools/validate-plugin.sh [PLUGIN_DIR]
```

Or in the main menu: **Option 6) Plugin Validation**

---

## 1. WoltLab pre-check (automatic on upload)

| WoltLab criterion | Our tool / action |
|-------------------|-------------------|
| PHP and XML files syntactically correct | `validate-plugin.sh` → PHP/XML syntax |
| Archive contains all files declared in `package.xml` | `validate-plugin.sh` → file completeness |
| No junk files (`.DS_Store`, `Thumbs.db`, …) | `validate-plugin.sh` + `build.sh` |
| Compatibility metadata complete and valid | `validate-plugin.sh` → `requiredpackages`, version |

**Add-ons:** State clearly in the store listing that the **base app** is required. Product-line builds: [PRODUCT-LINE.en.md](PRODUCT-LINE.en.md).
| Minimum `com.woltlab.wcf` version with security support | `validate-plugin.sh` → min version (6.0+; use current 6.2.x at release) |

---

## 2. WoltLab manual review (staff)

WoltLab priority: **security first**, then operability and UX.

| WoltLab criterion | Our tool / manual check |
|-------------------|-------------------------|
| **Security:** bad parameters, SQL, missing permissions, XSS | `validate-plugin.sh`; manual admin/user flows, uploads |
| **Operability:** install through smoke test; UI aligned with WoltLab | ACP upload via `prepare-acp-install.sh`; plugin E2E / QA checklist |
| **API usage:** e.g. Guzzle/HTTPRequest instead of `file_get_contents`/`curl` | `validate-plugin.sh` → HTTP API check |
| **No test/debug code or baked-in test API keys** | `validate-plugin.sh` → debug code, test credentials |
| **Efficiency:** no obviously expensive DB queries | Manual: N+1, pagination; verify raw SQL against schema |
| **Translations:** DE **and** EN complete, equivalent content | `validate-plugin.sh` + optional `check-language-keys.py`; store listing DE/EN |
| **Copyright:** apps on app pages only; styles may show on all pages | Manual: no banners on foreign pages |
| **No implicit/explicit package server installation** | `validate-plugin.sh` → no `packageUpdateServer` PIP |

### Raw SQL / schema pitfalls (plugin reviews)

Before release, verify every **custom SQL query** against `install.sql` / DBObject — not every table has `isDisabled`; WCF 6.2 box limits live in `additionalData`, not `wcf*_box.limit`.

Typical mistakes:

- App table: use columns only if they exist in the schema
- `wcf*_box`: **no** SQL column `limit` — read from `unserialize(additionalData)['limit']`

---

## 3. Automatically checked criteria (`validate-plugin.sh` + `build.sh`)

- [ ] **PHP syntax:** all `.php` valid
- [ ] **XML syntax:** `package.xml` and PIP XMLs valid
- [ ] **File completeness:** declared files present
- [ ] **Translations:** `language/de.xml` **and** `language/en.xml`
- [ ] **Language XML structure:** item name matches category (`check-language-categories.py`)
- [ ] **Min version:** supported WCF version (security support)
- [ ] **No package servers:** no `packageUpdateServer` PIP
- [ ] **SQL injection:** no request data in SQL string concatenation
- [ ] **LIKE escaping:** `escapeLikeValue()` instead of `addcslashes()` (WCF 6.2.5+)
- [ ] **XSS:** no `|encodeHTML`/`|escape`; in `<script>` only `{unsafe:$var|encodeJS}` (`check-template-xss.py`, **build-breaking** in `build.sh`)
- [ ] **Debug code:** no `var_dump()`, `print_r()`, `console.log()` in release
- [ ] **Test credentials:** no hardcoded passwords
- [ ] **HTTP:** HTTPRequest/Guzzle instead of `file_get_contents`/`curl` for HTTP(S)
- [ ] **WoltLab Cloud:** no `exec()`, `shell_exec()`, `system()`, `passthru()`
- [ ] **Event listeners:** no dynamic properties on `$eventObj` (PHP 8.2+)
- [ ] **Archive:** no junk files in package

See also: [SECURITY-CHECKS.de.md](SECURITY-CHECKS.de.md), [WOLTLAB-TEMPLATE-RULES.de.md](WOLTLAB-TEMPLATE-RULES.de.md), [LANGUAGE-XML.de.md](LANGUAGE-XML.de.md)

---

## 4. AI-assisted tools (WoltLab policy, new 2026)

WoltLab allows AI **only as support**. The vendor must **independently** understand, maintain, and fix review feedback.

**For our development:**

- Always verify AI output against local sources (`woltlab-docs/`, `wcfsetup/`) and `validate-plugin.sh`
- Do not ship unverified SQL columns, template modifiers, or API assumptions from model answers
- Store upload only after human review + green validator

---

## 5. Store listing requirements

- [ ] **German description:** complete, factual
- [ ] **English description:** same content as German
- [ ] **Screenshots:** meaningful (**required for styles**; recommended for plugins)
- [ ] **Changelog / release notes:** documented
- [ ] **External links:** only at the end, only if relevant (demo, help)
- [ ] **No advertising** for offers outside the Plugin Store / no third-party shop promotion
- [ ] Links to **your own** add-on packages in the Plugin Store are allowed
- [ ] **License:** via store fields or external link → `text/plain`; valid TLS cert for HTTPS

Maintain store listing and screenshots in your **plugin repo** under `docs/store/`.

---

## 6. WoltLab Cloud (technical approval)

- [ ] Compatible with current WSC version
- [ ] Outbound HTTP(S) via Guzzle / proxy configuration
- [ ] No connections to non-standard TCP/UDP ports
- [ ] No bulk email sending
- [ ] No privileges reserved in managed hosting (e.g. direct DB administration)

---

## 7. Workflow: development → store

Same steps as the German checklist: build → validate → manual tests → upload → wait for review.

---

## 8. Common rejection reasons

### Critical (errors)

1. **Missing EN translation** → add `language/en.xml`
2. **SQL injection / wrong schema in raw SQL** → prepared statements; verify columns in `install.sql`
3. **XSS in templates** → plain `{$var}` (auto-escape); `{unsafe:…|encodeJS}` in scripts — **not** `|escape`/`|encodeHTML`
4. **Test credentials / debug code** → remove
5. **Package server** → remove `packageUpdateServer` PIP
6. **Missing permission checks** → explicit checks in form/page/action

### Important (warnings / notes)

1. **`file_get_contents()` for HTTP** → HTTPRequest/Guzzle
2. **Inefficient DB queries** → pagination, caching, avoid N+1
3. **Outdated min version** → current 6.2.x with security support
4. **Listing DE ≠ EN** → align texts

---

## Resources

- **Plugin Store guidelines:** https://www.woltlab.com/pluginstore/en/guidelines/
- **WoltLab Docs:** https://docs.woltlab.com/6.2/
- **Security (DB):** https://docs.woltlab.com/6.0/php/database-access/
- **Templates:** https://docs.woltlab.com/6.0/view/templates/
- **Security checks (this repo):** [SECURITY-CHECKS.de.md](SECURITY-CHECKS.de.md)

---

When WoltLab updates their guidelines, update this checklist and `validate-plugin.sh` — the WoltLab page above is the source of truth.
