# TYPO3 _ThumbHash_ Extension

[![CI](https://github.com/wazum/thumb-hash/actions/workflows/ci.yml/badge.svg)](https://github.com/wazum/thumb-hash/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3-blue.svg)](https://www.php.net/)
[![TYPO3](https://img.shields.io/badge/TYPO3-12.4%20|%2013.4-orange.svg)](https://typo3.org/)
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](LICENSE)

Enhance user experience with fast, ultra-compact, and appealing image placeholders for TYPO3 websites.

[ThumbHash](https://evanw.github.io/thumbhash/) creates placeholders in **just ~28 bytes** ✨ that accurately represent your images while they load. It seamlessly enhances native lazy loading (`loading="lazy"`) by showing a meaningful preview until the image appears.

## Benefits at a Glance

- Show a soft, on‑brand preview while images load — no more empty space.
- No extra image files, no extra requests — just a few characters embedded alongside your image.
- Works automatically with your existing TYPO3 images, templates and lazy loading (`loading="lazy"` or JS libraries).

### Perfect For
- Product listings and category grids (e‑commerce)
- News teasers and card layouts (publishers)
- Portfolios, galleries and hero images (agencies/creatives)
- Landing pages with many visuals (marketing)
- Any site where images load over slow networks or on mobile

### What Makes It Great
- **Tiny & Fast:** About 25–35 bytes per image; nothing else to download.
- **Smooth UX:** Beautiful blurred preview that matches the final image and prevents layout shift.
- **Automatic:** Generated on upload and for every cropped/processed variant.
- **Drop‑in:** A single Fluid attribute adds the placeholder to your images.

## Quick Start

```bash
composer require wazum/thumb-hash
```

```xml
<!-- The 'thumbhash' namespace is globally registered, no import needed -->
<f:image image="{image}"
         loading="lazy"
         additionalAttributes="{data-thumbhash: '{thumbhash:thumbHash(file: image)}'}" />
```

Output: `<img src="..." loading="lazy" data-thumbhash="3OcRJYB4d3h/iIeHeEh3eIhw+j3A" alt="">`

That's it! Hashes are generated automatically on upload. Add the [frontend JavaScript](#frontend-implementation) for the blur effect.

## Use Cases

- **E‑commerce lists:** Give shoppers instant visual context while product images stream in.
- **News portals:** Keep article cards readable without jarring pop‑in as thumbnails load.
- **Portfolios & galleries:** Maintain composition and color feel while high‑res shots load.
- **Hero banners:** Avoid a blank hero on slow connections; show a pleasant preview immediately.
- **Long pages with lazy images:** Reduce perceived wait time as users scroll.
- **Low‑bandwidth audiences:** Improve mobile experience in regions with spotty coverage.

## Why ThumbHash?

### The Problem
When images load on slow connections, users see:
- Empty gray rectangles that suddenly pop into images
- Layout shifts as images load with different aspect ratios
- Poor perceived performance

### The Solution
ThumbHash generates tiny placeholders that:
- Show a blurred preview of the actual image
- Preserve the exact aspect ratio
- Fade smoothly into the loaded image
- Work without additional network requests

## Visual Demo

<div align="center">

![Original Image](Documentation/lightning-strikes.jpg)

**↓** *Encoded to just 28 bytes (on the server)* **↓**

`3OcRJYB4d3h/iIeHeEh3eIhw+j3A`

**↓** *Decoded to placeholder (on the client)* **↓**

![ThumbHash Placeholder](Documentation/lightning-strikes-thumbhash.png)

What your users will see while the image is loading:

<img src="Documentation/lightning-strikes-thumbhash.png" width="300" height="200"/>

</div>

From a 200 KB image → to 28 characters → back to a beautiful placeholder that preserves colors, composition, and aspect ratio.

[**Try the interactive demo with your own images →**](https://evanw.github.io/thumbhash/#demo)

<sup>Photo by Frank Cone: https://www.pexels.com/photo/lightning-strikes-2258536/</sup>

## How It Compares

|                        | Size | Quality | Network Requests | Aspect Ratio |
|------------------------|------|---------|------------------|--------------|
| **ThumbHash**          | ~25-35 bytes¹ | Excellent | None | Preserved |
| BlurHash (alternative) | 20-30 chars² | Good | None | Not preserved |
| LQIP (webp)            | 150-350 bytes³ | Good | 1 per image | Preserved |
| Solid Color            | ~7 bytes | Poor | None | Not preserved |

<sup>¹[ThumbHash documentation](https://evanw.github.io/thumbhash/)</sup>  
<sup>²[BlurHash repository](https://github.com/woltapp/blurhash)</sup>  
<sup>³[LQIP Modern benchmarks](https://github.com/transitive-bullshit/lqip-modern)</sup>

## Features

- **Automatic generation** — Hashes are created when images are uploaded
- **`ProcessedFile` support** — Each cropped variant gets its own placeholder
- **Database storage** — Hashes are stored in `sys_file_metadata` and `sys_file_processedfile`
- **Multiple processors** — Supports both GD and ImageMagick
- **ViewHelper integration** — Simple Fluid template integration
- **Event-driven** — Responds to TYPO3 file events automatically
- **Console command** — Process existing files via CLI with `thumbhash:generate`
- **Scheduler support** — Run batch processing as scheduled tasks
- **Minified JavaScript** — Ready-to-use decoder included

## FAQ

- **Does this slow down my site?** No. The hash is embedded as a tiny string — there are no extra requests. Images load as usual; users just see a better preview.
- **What if JavaScript is disabled?** Images still load normally. The JS only paints the blurred background until the image finishes loading.
- **Does this replace lazy loading?** No. Keep using `loading="lazy"` (or your lazy‑loading library). ThumbHash complements it by filling the visual gap with a matching preview while the image is deferred.
- **Does this affect SEO or accessibility?** No. Your `alt` text, dimensions and markup remain unchanged; placeholders help prevent layout shifts that can harm Core Web Vitals.
- **Which image types are supported?** JPEG, PNG, GIF by default; you can adjust allowed MIME types. Works regardless of your output format (e.g., WebP/AVIF delivery).
- **Will it work with cropped images and variants?** Yes. Every processed variant gets its own accurate placeholder.
- **Can I turn it off for certain folders?** Yes. Use configuration to exclude folders for original files while still handling processed variants.

## Installation

```bash
composer require wazum/thumb-hash
```

The extension works out of the box with sensible defaults. After installation:
1. Go to **Admin Tools > Maintenance > Analyze Database Structure** to add the required database fields
2. New uploads generate ThumbHash values automatically
3. Existing images can be processed using the [command line tool](#command-line-tool)
4. Configuration can be adjusted in the **Admin Tools > Settings > Extension Configuration** section if needed

## Usage

### In Fluid Templates

The extension globally registers the `thumbhash` namespace, so you can use it immediately:

```xml
<f:image image="{image}" 
         loading="lazy"
         additionalAttributes="{data-thumbhash: '{thumbhash:thumbHash(file: image)}'}" />
```

If you prefer explicit namespace imports (optional, since it's globally registered):
```xml
<html xmlns:thumbhash="http://typo3.org/ns/Wazum/ThumbHash/ViewHelpers"
      data-namespace-typo3-fluid="true">
    <!-- Your template -->
</html>
```

This generates the following HTML output:

```html
<img src="/fileadmin/images/photo.jpg" 
     width="1200" 
     height="800" 
     loading="lazy"
     data-thumbhash="3OcRJYB4d3h/iIeHeEh3eIhw+j3A" 
     alt="">
```

The ViewHelper works with `File`, `FileReference`, and `ProcessedFile` objects.

### Command Line Tool

Generate ThumbHash values for existing files that don't have them yet:

```bash
# Process up to 100 files (default)
vendor/bin/typo3 thumbhash:generate

# Process a specific number of files
vendor/bin/typo3 thumbhash:generate --limit=500

# Process all files (use with caution on large installations)
vendor/bin/typo3 thumbhash:generate --limit=999999
```

The command:
- Processes both original files and their processed variants
- Respects the extension configuration (allowed MIME types, excluded folders)
- Shows progress for each processed file
- Skips files that already have hashes
- Can be run as a **scheduler task** for automatic processing

**Scheduler Configuration:**
1. Go to **System > Scheduler**
2. Create a new task of type "Execute console commands"
3. Select **thumbhash:generate** from the dropdown
4. Set options (e.g., `--limit=50`)
5. Configure the schedule (e.g., daily at night)

### Frontend Implementation

**Note:** ThumbHash uses progressive enhancement — images load normally even with JavaScript disabled. The placeholder effect is a visual enhancement only. It also complements native lazy loading and JS lazy‑loading libraries by providing an instant visual preview while the browser defers the image.

The extension includes a minified ThumbHash decoder at [`Resources/Public/JavaScript/thumb-hash.min.js`](Resources/Public/JavaScript/thumb-hash.min.js) that provides the `thumbHashToDataURL` function.

#### Add to your PAGE setup

Add your initialization code after the page (and all the images):

```typoscript
page {
    includeJS {
        thumbHash = EXT:thumb_hash/Resources/Public/JavaScript/thumb-hash.min.js
        thumbHash.forceOnTop = 1
    }

    10 = FLUIDTEMPLATE
    10 {
       …
    }

    20 = TEXT
    20.value (
        <script>

    document.querySelectorAll('[data-thumbhash]').forEach(function(img) {
        var hash = img.dataset.thumbhash;
        if (!hash) return;
        var bytes = new Uint8Array(atob(hash).split('').map(function(c) {
            return c.charCodeAt(0);
        }));
        var dataUrl = thumbHashToDataURL(bytes);
        img.style.background = 'url(' + dataUrl + ') center/cover no-repeat';
        img.addEventListener('load', function() {
            img.style.background = '';
        }, { once: true });
    });

        </script>
    )
}
```

The code above is the most straight-forward integration example. Of course,
you can use any other kind of integrating the required JavaScript to your
project, like:

*  Using a JavaScript/TypeScript module file with the code, and integrating it
   in your frontend build process (if you use vite, webpack, grunt or the likes).
*  Placing the code in your main layout Fluid file using the
   [`<f:asset.script>`](https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-asset-script)
   (or `<f:vite.asset>` when using vite integration).
*  When using EXT:contentblocks, adding that Code with the
   mentioned ViewHelpers into the specific content block fluid file.

These approaches are recommended if your project uses CSP (Content-Security-Policy)
to block inline JavaScript execution. You need to use proper prioritization so
that the code above is executed as one of the first JavaScript events in your code

## Configuration

Extension configuration allows you to customize:

- **autoGenerate** — Enable/disable automatic generation (default: true)
- **allowedMimeTypes** — Supported MIME types (default: image/jpeg,image/jpg,image/png,image/gif)
- **excludedFolders** — Folders to skip for original files only (default: fileadmin/_processed_/,fileadmin/_temp_/)
  - Note: Processed file variants are always handled regardless of folder exclusions, ensuring cropped/resized images get their own accurate placeholders
- **imageProcessor** — Select processing backend: `auto` | `imagick` | `gd`
  - `auto` tries `imagick` → `gd`
  - Explicit choices have no fallback. Ensure the dependency exists (e.g., PHP Imagick extension for `imagick`).
  - Set via Install Tool: Admin Tools → Settings → Extension Configuration → `thumb_hash`
    or in configuration: `$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['thumb_hash']['imageProcessor'] = 'gd';`

## Architecture

### Storage
- Original images: `sys_file_metadata.thumb_hash`
- Processed images: `sys_file_processedfile.thumb_hash`

### Event Listeners
- `AfterFileAddedEvent` — Generate for new uploads (respects folder exclusions)
- `AfterFileProcessingEvent` — Generate for processed variants (ignores folder exclusions)
- `AfterFileReplacedEvent` — Regenerate when files change (respects folder exclusions)

### Image Processing
Processor selection respects the `imageProcessor` setting. With `auto`, priority is:
1. **Imagick (PHP extension)** — High quality, good alpha handling, efficient memory usage
2. **GD** — Ubiquitous fallback

**Important: Memory Considerations**
- **Imagick is strongly recommended** for production use, especially with large images
- GD must load entire images into memory before processing, which can cause memory exhaustion on high-resolution images
- Imagick uses optimized decoding hints to load images at reduced resolution, significantly reducing memory usage
- The extension includes safeguards (20MB file size limit, dimension limits for GD), but Imagick handles edge cases more gracefully
- If using GD with large images, ensure adequate PHP memory_limit (512MB+ recommended)

Why no CLI (GraphicalFunctions) support?
- External process overhead is large (hundreds of ms/iter), dominated by process spawn and TXT I/O.
- Placeholder generation downsamples to ≤100px; color/alpha precision gains from CLI paths are negligible compared to GD/Imagick.
- Benchmarks showed GD/Imagick are orders of magnitude faster with indistinguishable results at this size.

## User Experience Benefits

ThumbHash enhances perceived performance:
- **Eliminates layout shift** — Placeholders reserve the exact space needed, preventing content jumping
- **Instant visual feedback** — Users see a meaningful preview immediately instead of empty rectangles
- **Smoother loading experience** — Images fade in gracefully from their blurred placeholders
- **Better perceived performance** — The page feels faster even though actual load times remain the same

## Requirements

- TYPO3 CMS 12.4+ or 13.4+
- PHP 8.2+
- GD PHP extension (required; fallback processor and part of standard TYPO3 installs)
- **Imagick PHP extension (strongly recommended for production)** — GD can cause memory exhaustion with large images
- Composer dependency: `srwiez/thumbhash ^1.4`

Notes:
- You can choose the processor via `imageProcessor` (auto|imagick|gd). Explicit choices have no fallback.
- When using GD with large images, increase PHP memory_limit to 512MB or higher

## License

GNU General Public License version 2 or later (GPL-2.0-or-later)

## Credits

ThumbHash algorithm by [Evan Wallace](https://github.com/evanw/thumbhash)
