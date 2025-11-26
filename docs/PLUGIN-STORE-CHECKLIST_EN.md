# Plugin Store Submission Checklist

**Copyright (c) 2025 SunnyCueq**
**License:** MIT (Open Source)
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

---

## Before Submission: Automated Validation

Run the automated validation first:

```bash
./scripts/validate-plugin.sh /path/to/plugin
```

### ✅ Automatically Checked Criteria

The following criteria are automatically checked by the `validate-plugin.sh` script:

- [ ] **PHP Syntax:** All PHP files syntactically correct
- [ ] **XML Syntax:** package.xml and all PIP XMLs error-free
- [ ] **File Completeness:** All files declared in package.xml present
- [ ] **Translations:** German (de.xml) AND English (en.xml) present
- [ ] **Minversion:** Supported WoltLab Core version (6.0+)
- [ ] **No Package Server:** No packageUpdateServer PIP
- [ ] **SQL Injection:** No dangerous query patterns
- [ ] **XSS Risks:** Templates use escaping (|escape, |encodeJS)
- [ ] **Debug Code:** No var_dump(), print_r(), console.log()
- [ ] **Test Credentials:** No hardcoded passwords
- [ ] **API Usage:** HTTPRequest/Guzzle instead of file_get_contents/curl

---

## Manual Checks (Before Submission)

### 📝 Documentation & Description

- [ ] **German Description:** Complete and meaningful
- [ ] **English Description:** Identical information as DE
- [ ] **Screenshots:** Meaningful and current (mandatory for styles)
- [ ] **Version Notes:** Changelog documents changes
- [ ] **External Links:** Only at the end, only for relevant additional info

### 🛡️ Security & Authorization

- [ ] **Permission Checks:** All admin functions check permissions
- [ ] **User Input Validation:** All inputs are validated
- [ ] **SQL Queries:** Only prepared statements with parameter binding
- [ ] **Template Output:** User data is escaped
- [ ] **File Uploads:** Validation of type, size, name

### ⚡ Performance & Code Quality

- [ ] **DB Queries:** Efficient (no N+1 problems)
- [ ] **Caching:** Expensive operations are cached
- [ ] **Lazy Loading:** Large data sets are paginated
- [ ] **Code Duplication:** Reusable functions extracted

### 🌐 WoltLab Cloud Compatibility

- [ ] **HTTP Requests:** Use HTTPRequest/Guzzle (proxy support)
- [ ] **No Custom Ports:** Only standard HTTP/HTTPS (80/443)
- [ ] **No Bulk Email:** No mass email sends
- [ ] **No System Commands:** No exec(), shell_exec(), system()

### 📦 Package Quality

- [ ] **Package Name:** Format com.domain.pluginname correct
- [ ] **Version:** Semantic Versioning (MAJOR.MINOR.PATCH)
- [ ] **Date:** Current release date
- [ ] **Dependencies:** All requiredpackages correct
- [ ] **Excludedpackages:** WoltLab 7.0 Alpha excluded (recommended)

---

## Workflow: From Development to Plugin Store

### 1. Development Completed

```bash
# Navigate to plugin directory
cd /path/to/my-plugin

# Extract TAR archives for validation
./scripts/extract-plugin-files.sh
```

### 2. Run Validation

```bash
# Automated validation
./scripts/validate-plugin.sh

# Expected result:
# ✅ Validation successful! No errors or warnings found.
```

**If errors/warnings:** Fix them before the next step!

### 3. Create Package

```bash
# Create release package
./scripts/create-release.sh 1.0.0
```

### 4. Manual Tests

- [ ] Test plugin on real WoltLab installation
- [ ] Click through all functions
- [ ] Test permissions (as user + admin)
- [ ] Test uninstall/reinstall
- [ ] Test different browsers (Chrome, Firefox, Safari)

### 5. Plugin Store Submission

1. Go to: https://www.woltlab.com/pluginstore/
2. Click "Upload new plugin"
3. Upload TAR.GZ (com.example.myplugin-1.0.0.tar.gz)
4. Fill in description (DE + EN identical)
5. Upload screenshots
6. Select category
7. Submit for review

### 6. Wait for Review

- **Average:** Every third submission is rejected on first attempt
- **Typical reasons:** Security, missing translations, API usage
- **Review time:** Few days to 1 week

---

## Common Rejection Reasons

### 🔴 Critical (ERRORS)

1. **Missing EN Translation** → Add language/en.xml
2. **SQL Injection Risks** → Use prepared statements
3. **XSS in Templates** → Use {|escape} for user data
4. **Test Credentials** → Remove all dummy passwords
5. **Package Server Installation** → Remove packageUpdateServer PIP

### 🟡 Important (WARNINGS)

1. **file_get_contents() for HTTP** → Use HTTPRequest/Guzzle
2. **Missing Permission Checks** → Check permissions explicitly
3. **Inefficient DB Queries** → Optimize N+1 problems
4. **Debug Code** → Remove var_dump(), console.log()
5. **Outdated Minversion** → Upgrade to 6.0.0+

---

## Helpful Resources

- **Plugin Store Guidelines:** https://www.woltlab.com/pluginstore/guidelines/
- **WoltLab Docs 6.0:** https://docs.woltlab.com/6.0/
- **WoltLab Docs 6.1:** https://docs.woltlab.com/6.1/
- **WoltLab Docs 6.2:** https://docs.woltlab.com/6.2/
- **Security Best Practices:** https://docs.woltlab.com/6.0/php/database-access/
- **API Reference:** https://docs.woltlab.com/6.0/php/api/
- **Template Security:** https://docs.woltlab.com/6.0/view/templates/

---

**Last Updated:** 2025-11-26
**Version:** 1.0.0
