# URL Shortener: Affiliate System

> **[🇩🇪 Deutsche Version](README.de.md)** | 🇬🇧 English Version

**Package:** `info.benjaro.urlshort.affiliate`  
**Version:** 2.1.5  
**Author:** Benjaro  
**Website:** https://benjaro.info

---

## 📋 Overview

**URL Shortener: Affiliate System** is a comprehensive extension for the [URL Shortener](https://www.woltlab.com/pluginstore/de/p/url-shortener/) plugin by dev.tkirch. It transforms your simple redirect pages into powerful marketing tools, perfect for affiliate marketing, promotional campaigns, and seasonal sales.

Instead of just redirecting users, you can now display discount codes, personalized messages, recommended links, and stunning visual effects - all while maintaining full control over timing, appearance, and targeting.

---

## ✨ Key Features

### 🎟️ Discount Codes Management

Display multiple discount codes directly on your redirect pages with full customization:

- **Host-Based Targeting:** Assign discount codes to specific websites (e.g., `amazon.de`, `ebay.com`)
- **Multiple Codes:** Support for multiple discount codes per discount entry
- **One-Click Copy:** Users can copy codes with a single click
- **Countdown Timers:** Create urgency with live countdown timers showing remaining time
- **Custom Colors:** Fully customizable colors (primary, secondary, text colors) for brand matching
- **Time-Limited Offers:** Set start and end dates for automatic activation/deactivation
- **Favicon Support:** Automatic favicon display for better visual recognition

**Example:** When someone clicks your Amazon link, they see your exclusive discount codes (e.g., "SAVE20", "BLACKFRIDAY") with a countdown timer before being redirected.

### 📝 Custom Descriptions

Add personalized, dynamic text content to your redirect pages:

- **Host-Based Display:** Show different descriptions for different websites
- **Dynamic Placeholders:** Use `{$extractedTitle}` to automatically include page titles
- **Priority System:** Control which description shows first when multiple match
- **Rich Text Support:** Full HTML support for formatting
- **Enable/Disable:** Toggle descriptions on/off per entry or globally
- **Multiple Descriptions:** Create unlimited descriptions with different priorities

**Example:** "Check out this amazing deal on {$extractedTitle}! Don't forget to use the discount code below."

### 🔗 Featured Links (Recommendations)

Show related links and recommendations on your redirect pages:

- **Smart Recommendations:** Display relevant links based on the target website
- **Custom Titles & Descriptions:** Add your own titles and descriptions for each recommendation
- **Priority Control:** Set display order with priority values
- **Host-Based Filtering:** Show recommendations only on specific websites
- **Reaction Support:** Users can react to recommendations
- **Guest Reactions:** Optional support for guest reactions (synchronized on registration)
- **Visual Badges:** Display host badges for better visual recognition

**Example:** When redirecting to an Amazon product, show links to similar products, better deals, or related items.

### 🎨 Seasonal Themes & Visual Effects

Create stunning themed redirect pages for special campaigns:

- **Pre-Built Themes:** Halloween, Christmas, Black Friday, and more
- **Custom Themes:** Create your own themes with custom colors and effects
- **Visual Effects:**
  - **Halloween Ghosts:** Animated floating ghosts effect
  - **Snow Effect:** Falling snowflakes animation
  - **Autumn Leaves:** Falling leaves animation
- **Theme Colors:** Automatic color application from themes to special promotions
- **URL-Based Activation:** Activate themes via URL parameter (e.g., `?special=halloween`)
- **Time-Limited:** Set start and end dates for automatic theme activation

**Example:** Add `?special=halloween` to any shortened link for a Halloween-themed redirect page with spooky colors and animated ghosts.

### ⏰ Special Promotions

Create time-limited special promotions with full customization:

- **Identifier-Based:** Use simple identifiers (e.g., `halloween`, `blackfriday`) for easy URL activation
- **Theme Integration:** Link specials to themes for automatic color and effect application
- **Discount Override:** Temporarily override regular discounts with special discount values
- **Countdown Timers:** Automatic countdown display for active specials
- **Custom Colors:** Override theme colors with special-specific colors if needed
- **Multiple Codes:** Support for multiple discount codes per special
- **Automatic Activation:** Specials activate/deactivate based on time settings

**Example:** Create a "Black Friday 2024" special that automatically activates on November 29th and shows special discount codes with a countdown timer.

### 👍 Reaction System

Let visitors interact with your links through reactions:

- **Multiple Reaction Types:** Like, Love, Thanks, Haha, Confused, Sad, OK
- **Guest Support:** Optional guest reactions (stored and synchronized on registration)
- **Reaction Display:** Show reaction counts on redirect pages
- **Admin Overview:** View all reactions in the admin panel
- **Analytics:** Understand which links are popular and engaging
- **WoltLab Integration:** Full integration with WoltLab's reaction system

**Example:** Visitors can react to your links, helping you understand which promotions are most effective.

### 📤 Share Functionality

Make it easy for visitors to share your links:

- **One-Click Sharing:** Share to social media platforms
- **Share Dialog:** Professional share dialog with link title
- **Increased Reach:** Organic growth through social sharing
- **WoltLab Integration:** Uses WoltLab's built-in share functionality

---

## 📦 Requirements

- **WoltLab Suite:** Version 6.0.16 or higher
- **Base Plugin:** [URL Shortener](https://www.woltlab.com/pluginstore/de/p/url-shortener/) v6.0.0 or higher
- **PHP:** Version 8.0 or higher

---

## 🚀 Installation

1. Download the latest version from [GitHub Releases](https://github.com/benjarogit/urlshort-featured-links/releases)
2. Log into your WoltLab Admin Panel (ACP)
3. Navigate to **System → Packages → Install Package**
4. Upload the downloaded `.tar.gz` file
5. Follow the installation wizard
6. Done! The plugin is now active

---

## 📖 Usage Guide

### Setting Up Discount Codes

1. **Navigate to Admin Panel:** URL Shortener → Discounts → Add New
2. **Enter Title:** Internal name (e.g., "Amazon Black Friday Sale")
3. **Enter Discount Value:** The discount to display (e.g., "30%", "50€", "SAVE20")
4. **Add Website Hosts:** Enter comma-separated hosts (e.g., `amazon.de, amazon.com, ebay.com`)
5. **Add Discount Codes:** Enter codes separated by commas (e.g., `SAVE20, BLACKFRIDAY, DEAL2024`)
6. **Customize Colors (Optional):**
   - Primary Background Color
   - Secondary Background Color
   - Primary Text Color
   - Secondary Text Color
7. **Set Countdown (Optional):** Set start and end dates for automatic countdown display
8. **Save:** Your discount will appear on matching redirect pages

**Result:** When someone clicks a link to `amazon.de`, they see your discount codes with a countdown timer before being redirected!

### Creating Custom Descriptions

1. **Navigate to Admin Panel:** URL Shortener → Descriptions → Add New
2. **Enter Title:** Internal name (e.g., "Amazon Product Description")
3. **Add Website Hosts:** Where should this description appear? (e.g., `amazon.de, amazon.com`)
4. **Write Your Text:** Your custom message with optional placeholders:
   - `{$extractedTitle}` - Automatically replaced with the page title
   - Full HTML support for formatting
5. **Set Priority:** Higher numbers display first (useful for multiple descriptions)
6. **Activate:** Toggle the description on/off
7. **Save:** Your description will appear on matching redirect pages

### Adding Featured Links

1. **Navigate to Admin Panel:** URL Shortener → Featured Links → Add New
2. **Enter Title:** What visitors will see (e.g., "Similar Products", "Better Deals")
3. **Add URL:** The destination URL for the recommendation
4. **Add Website Hosts:** On which websites should this appear? (e.g., `amazon.de`)
5. **Add Description (Optional):** Additional text explaining the recommendation
6. **Set Priority:** Control display order (higher = first)
7. **Enable Reactions (Optional):** Allow users to react to this recommendation
8. **Save:** Your recommendation will appear on matching redirect pages

### Creating Themes

1. **Navigate to Admin Panel:** URL Shortener → Themes → Add New
2. **Enter Title:** Display name (e.g., "Halloween 2024")
3. **Enter Identifier:** URL identifier (e.g., `halloween`, `christmas`, `blackfriday`)
4. **Select Effect:** Choose from available effects:
   - None
   - Halloween Ghosts
   - Snow
   - Autumn Leaves
5. **Set Colors:**
   - Primary Color (RGBA format)
   - Secondary Color (RGBA format)
   - Primary Text Color
   - Secondary Text Color
6. **Activate:** Toggle theme on/off
7. **Save:** Your theme is ready to use

### Creating Special Promotions

1. **Navigate to Admin Panel:** URL Shortener → Specials → Add New
2. **Enter Title:** Display name (e.g., "Halloween Sale 2024")
3. **Select URL:** Choose which shortened URL this special applies to
4. **Enter Identifier:** URL parameter (e.g., `halloween`)
5. **Select Theme (Optional):** Link to a theme for automatic colors and effects
6. **Enter Discount Value:** Override regular discount (e.g., "50%")
7. **Add Discount Codes (Optional):** Special discount codes for this promotion
8. **Set Time Period:** Start and end dates for automatic activation
9. **Customize Colors (Optional):** Override theme colors if needed
10. **Save:** Your special is ready!

**To Use:** Add `?special=halloween` to any shortened link:
```
https://yourdomain.com/r/abc123?special=halloween
```

The redirect page will display with your Halloween theme, colors, and effects!

---

## ⚙️ Configuration

All settings are located in the Admin Panel under:
**Configuration → Options → URL Shortener → Featured Links**

### 📱 Reactions

- **Enable Reactions** (Default: ✅ Enabled)
  - Enables the reaction system for short URLs on redirect pages
  - Users can react with emojis (👍 Like, ❤️ Love, etc.)

- **Enable Guest Reactions** (Default: ❌ Disabled)
  - Allows non-logged-in visitors to react
  - Guest reactions are stored and synchronized with user accounts upon registration

### 📝 Descriptions

- **Enable Descriptions** (Default: ✅ Enabled)
  - Global toggle for description display
  - Overrides individual description activation/deactivation

### ⏱️ Countdown

- **Enable Countdown** (Default: ✅ Enabled)
  - Enables countdown timers for discounts and specials
  - Shows remaining time until expiration

### 🎨 Visual Effects

- **Enable Halloween Effect** (Default: ✅ Enabled)
  - Enables animated effects when Halloween-themed specials are active
  - Includes ghosts, snow, and autumn leaves animations

---

## 💡 Best Practices

### For Affiliate Marketing

**Recommended Configuration:**
- ✅ Enable Reactions → Collect visitor feedback
- ✅ Enable Descriptions → Show personalized messages
- ✅ Enable Countdown → Create urgency
- ✅ Enable Visual Effects → Enhance engagement
- Use host-based targeting for maximum relevance

### For Seasonal Campaigns

- Plan ahead for major shopping events (Black Friday, Christmas, etc.)
- Create themed specials with matching visual effects
- Use countdown timers to build excitement
- Share themed links on social media for better engagement

### For Discount Codes

- Keep discount values clear and concise
- Use countdown timers to create urgency
- Test codes before publishing
- Update expired codes regularly
- Use multiple codes for A/B testing

---

## 🔗 Links

- **Download:** [GitHub Releases](https://github.com/benjarogit/urlshort-featured-links/releases)
- **Base Plugin:** [URL Shortener on WoltLab Plugin Store](https://www.woltlab.com/pluginstore/de/p/url-shortener/)
- **Support:** [GitHub Issues](https://github.com/benjarogit/urlshort-featured-links/issues)
- **Website:** https://benjaro.info

---

## 📄 License

This plugin extends the URL Shortener by dev.tkirch and follows WoltLab Plugin Store guidelines. No external services are used - everything runs on your WoltLab installation.

---

## 👤 Author

**Benjaro**  
Website: https://benjaro.info

---

## 💬 Support

- **Found a bug?** [Report it on GitHub](https://github.com/benjarogit/urlshort-featured-links/issues)
- **Have a question?** Visit https://benjaro.info
- **Want a new feature?** Suggest it on GitHub!

---

**Last Updated:** November 30, 2025  
**Status:** Production Ready ✅
