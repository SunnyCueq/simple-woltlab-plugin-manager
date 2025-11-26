# WoltLab Suite Versions: 6.0, 6.1, 6.2 Compatibility

**Copyright (c) 2025 SunnyCueq**
**License:** MIT (Open Source)
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

---

## Summary: No Breaking Changes!

✅ **Good News:** WoltLab 6.0, 6.1, and 6.2 have **NO Breaking Changes** in the package format!

**What this means:**
- A plugin developed for 6.0 works on 6.1 and 6.2
- Package.xml format remains identical
- PIP structure remains unchanged
- API remains backward compatible

---

## Which Version Should I Choose as Minversion?

### ✅ Recommendation: 6.0.0 (Maximum Compatibility)

```xml
<requiredpackages>
    <requiredpackage minversion="6.0.0">com.woltlab.wcf</requiredpackage>
</requiredpackages>
<excludedpackages>
    <excludedpackage version="7.0.0 Alpha 1">com.woltlab.wcf</excludedpackage>
</excludedpackages>
```

**Advantages:**
- ✅ Maximum user reach (6.0, 6.1, 6.2)
- ✅ Plugin Store prefers broad compatibility
- ✅ Easy testing (only test against 6.0 needed)

### 🔸 Alternative: 6.1.0 (If New Features Needed)

Only if you use **specific 6.1 features**:

```xml
<requiredpackage minversion="6.1.0">com.woltlab.wcf</requiredpackage>
```

**When needed:**
- Using new 6.1 APIs
- Dependency on 6.1 bugfixes
- New template features from 6.1

### 🔹 Alternative: 6.2.0 (Rarely Needed)

Only if you use **specific 6.2 features**:

```xml
<requiredpackage minversion="6.2.0">com.woltlab.wcf</requiredpackage>
```

**When needed:**
- FileProcessor with image cropping
- New 6.2 editor features
- New image viewer API

---

## What Changes Between Versions?

### WoltLab 6.0 → 6.1

**User Interface:**
- Minor UI improvements
- Template updates (backward compatible)

**Developer:**
- New APIs (optionally usable)
- Bugfixes and performance improvements
- **No Breaking Changes**

**For Plugins:** ✅ No adjustments needed

---

### WoltLab 6.1 → 6.2

**User Interface:**
- Completely new image viewer
- WYSIWYG editor extended
- User profiles redesigned
- New FileProcessor with cropping

**Developer:**
- **jQuery deprecated** (Removal in 6.3)
  - ⚠️ If your plugin uses jQuery: Migrate to Vanilla JS
  - jQuery will be opt-in in 6.3
- **Redis discontinued**
  - File-based cache now uses OPcache
  - ⚠️ If you use Redis: Migrate to file cache
- New FileProcessor API (opt-in)
- Many template updates

**For Plugins:**
- ✅ Package format: No changes
- ⚠️ jQuery: Migrate to Vanilla JS (prepare for 6.3)
- ⚠️ Templates: Update own templates if needed

---

## Migration Guide: jQuery Deprecation (6.2 → 6.3)

### Problem

jQuery will be **opt-in** in WoltLab 6.3 and removed completely later.

### Solution: Migrate to Vanilla JavaScript

**Before (jQuery):**
```javascript
$('.my-element').on('click', function() {
    $(this).addClass('active');
});
```

**After (Vanilla JS):**
```javascript
document.querySelectorAll('.my-element').forEach(element => {
    element.addEventListener('click', function() {
        this.classList.add('active');
    });
});
```

**Resources:**
- [You Might Not Need jQuery](https://youmightnotneedjquery.com/)
- [WoltLab JavaScript API](https://docs.woltlab.com/6.2/javascript/)

---

## Testing Against Different Versions

### Recommended Workflow

1. **Develop against 6.0** (maximum compatibility)
2. **Test on 6.2** (latest features)
3. **Validate with validate-plugin.sh**

### Optional: Multi-Version Testing

If you have multiple WoltLab installations:

```bash
# Test against 6.0
./scripts/validate-plugin.sh /path/to/plugin-6.0

# Test against 6.2
./scripts/validate-plugin.sh /path/to/plugin-6.2
```

**Reality:** Not necessary, since there are no breaking changes!

---

## WoltLab 7.0 Preparation

### Add Excludedpackages

Exclude WoltLab 7.0 Alpha/Beta:

```xml
<excludedpackages>
    <excludedpackage version="7.0.0 Alpha 1">com.woltlab.wcf</excludedpackage>
</excludedpackages>
```

**Why:**
- WoltLab 7 will have breaking changes
- Your 6.x plugin should not run on 7.0 Alpha
- Prevents bug reports from alpha testers

---

## Summary & Best Practices

### ✅ Do's

- Use minversion="6.0.0" for maximum compatibility
- Add excludedpackages for 7.0 Alpha
- Migrate from jQuery to Vanilla JS (prepare for 6.3)
- Test on latest stable version (6.2)

### ❌ Don'ts

- No minversion < 6.0.0 (Plugin Store rejects)
- No minversion 7.0+ (not yet released)
- No jQuery dependency for new features
- No Redis usage (discontinued)

---

## Helpful Resources

- **WoltLab Docs 6.0:** https://docs.woltlab.com/6.0/
- **WoltLab Docs 6.1:** https://docs.woltlab.com/6.1/
- **WoltLab Docs 6.2:** https://docs.woltlab.com/6.2/
- **jQuery Migration:** https://youmightnotneedjquery.com/
- **WoltLab JavaScript API:** https://docs.woltlab.com/6.2/javascript/

---

**Last Updated:** 2025-11-26
**Version:** 1.0.0
