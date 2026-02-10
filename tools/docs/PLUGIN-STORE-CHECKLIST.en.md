# Plugin Store Submission Checklist

**Last updated:** 2025-01-18  
**Version:** 1.0.0

This checklist is aligned with **validate-plugin.sh** and the [WoltLab Plugin Store guidelines](https://www.woltlab.com/pluginstore/en/guidelines/).

---

## Before submission: Automatic validation

Run the automatic validation first:

```bash
./tools/validate-plugin.sh [PLUGIN_DIR]
```

Or in the main menu: **Option 6) Plugin Validation**

### ✅ Criteria checked automatically

The following are checked by the `validate-plugin.sh` script:

- [ ] **PHP syntax:** All PHP files syntactically correct
- [ ] **XML syntax:** package.xml and all PIP XMLs valid
- [ ] **File completeness:** All files declared in package.xml present
- [ ] **Translations:** German (de.xml) AND English (en.xml) present
- [ ] **Min version:** Supported WoltLab Core version (6.0+)
- [ ] **No package servers:** No packageUpdateServer PIP
- [ ] **SQL injection:** No dangerous query patterns
- [ ] **XSS risks:** Templates use escaping (|escape, |encodeJS)
- [ ] **Debug code:** No var_dump(), print_r(), console.log()
- [ ] **Test credentials:** No hardcoded passwords
- [ ] **API usage:** HTTPRequest/Guzzle instead of file_get_contents/curl
- [ ] **WoltLab Cloud:** No exec(), shell_exec(), system(), passthru()
- [ ] **Archive:** No junk files (.DS_Store, Thumbs.db) in the package

---

## Manual checks (before submission)

### Documentation & description

- [ ] **German description:** Complete and meaningful
- [ ] **English description:** Same information as DE
- [ ] **Screenshots:** Meaningful and up to date (required for styles)
- [ ] **Release notes:** Changelog documents changes
- [ ] **External links:** Only at the end, only for relevant info

### Security & authorization

- [ ] **Permission checks:** All admin functions check permissions
- [ ] **User input validation:** All inputs validated
- [ ] **SQL queries:** Prepared statements with parameter binding only
- [ ] **Template output:** User data escaped
- [ ] **File uploads:** Validate type, size, name

### Performance & code quality

- [ ] **DB queries:** Efficient (no N+1)
- [ ] **Caching:** Expensive operations cached
- [ ] **Lazy loading:** Large data sets paginated
- [ ] **Code duplication:** Reusable logic in shared functions

### WoltLab Cloud compatibility

- [ ] **HTTP requests:** Use HTTPRequest/Guzzle (proxy support)
- [ ] **No custom ports:** Standard HTTP/HTTPS (80/443) only
- [ ] **No bulk email:** No mass email sending
- [ ] **No system commands:** No exec(), shell_exec(), system()

### Package quality

- [ ] **Package name:** Format com.domain.pluginname correct
- [ ] **Version:** Semantic versioning (MAJOR.MINOR.PATCH)
- [ ] **Date:** Current release date
- [ ] **Dependencies:** All requiredpackages correct
- [ ] **Excluded packages:** WoltLab 7.0 Alpha excluded (recommended)

---

## Workflow: From development to Plugin Store

### 1. Development complete

```bash
cd /path/to/mein-plugin
./tools/build.sh mein-plugin
```

### 2. Run validation

```bash
./tools/validate-plugin.sh mein-plugin
# Expected: ✅ Validation successful! No errors or warnings.
```

Fix any errors or warnings before continuing.

### 3. Create package

The build step creates the release package; the .tar file is in the plugin directory.

### 4. Manual testing

- [ ] Test plugin on a real WoltLab installation
- [ ] Test all features
- [ ] Test permissions (as user and admin)
- [ ] Test uninstall/reinstall
- [ ] Test in different browsers

### 5. Plugin Store submission

1. Go to https://www.woltlab.com/pluginstore/
2. Click “Upload new plugin”
3. Upload the TAR.GZ file
4. Fill in description (DE + EN identical)
5. Upload screenshots
6. Choose category
7. Submit for review

### 6. Wait for review

- **Typical:** About one in three submissions is rejected on first try
- **Common reasons:** Security, missing translations, API usage
- **Review time:** A few days to a week

---

## Common rejection reasons

### Critical (errors)

1. **Missing EN translation** → Add language/en.xml
2. **SQL injection risks** → Use prepared statements
3. **XSS in templates** → Use {|escape} for user data
4. **Test credentials** → Remove all dummy passwords
5. **Package server installation** → Remove packageUpdateServer PIP

### Important (warnings)

1. **file_get_contents() for HTTP** → Use HTTPRequest/Guzzle
2. **Missing permission checks** → Check permissions explicitly
3. **Inefficient DB queries** → Fix N+1 issues
4. **Debug code** → Remove var_dump(), console.log()
5. **Outdated min version** → Upgrade to 6.0.0+

---

## Resources

- **Plugin Store guidelines:** https://www.woltlab.com/pluginstore/en/guidelines/
- **WoltLab Docs 6.0+:** https://docs.woltlab.com/
- **Security best practices:** https://docs.woltlab.com/6.0/php/database-access/
- **API reference:** https://docs.woltlab.com/6.0/php/api/
- **Template security:** https://docs.woltlab.com/6.0/view/templates/

---

This checklist is based on the official Plugin Store guidelines and WoltLab community best practices.
