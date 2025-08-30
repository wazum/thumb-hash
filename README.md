# TYPO3 _ThumbHash_ Extension

[![CI](https://github.com/wazum/thumb-hash/actions/workflows/ci.yml/badge.svg)](https://github.com/wazum/thumb-hash/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3-blue.svg)](https://www.php.net/)
[![TYPO3](https://img.shields.io/badge/TYPO3-12.4%20|%2013.4-orange.svg)](https://typo3.org/)
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](LICENSE)

Generate ultra-compact, beautiful image placeholders for TYPO3 websites. [ThumbHash](https://evanw.github.io/thumbhash/) creates placeholders in **just ~28 bytes** ✨ that accurately represent your images while they load.

## Quick Start

```bash
composer require wazum/thumb-hash
```

```xml
<!-- The 'thumbhash' namespace is globally registered, no import needed -->
<f:image image="{image}"
         additionalAttributes="{data-thumbhash: '{thumbhash:thumbHash(file: image)}'}" />
```

Output: `<img src="..." data-thumbhash="3OcRJYB4d3h/iIeHeEh3eIhw+j3A" alt="">`

That's it! Hashes are generated automatically on upload. Add the [frontend JavaScript](#frontend-implementation) for the blur effect.

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

**Note:** ThumbHash uses progressive enhancement — images load normally even with JavaScript disabled. The placeholder effect is a visual enhancement only.

The extension includes a minified ThumbHash decoder at [`Resources/Public/JavaScript/thumb-hash.min.js`](Resources/Public/JavaScript/thumb-hash.min.js) that provides the `thumbHashToDataURL` function.

#### Step 1: Include the JavaScript Library

Add to your TypoScript setup:

```typoscript
# In your site package or TypoScript template
page.includeJSFooter {
    thumbHash = EXT:thumb_hash/Resources/Public/JavaScript/thumb-hash.min.js
    thumbHash.defer = 1
}
```

Or via Fluid in your template:

```html
<f:asset.script 
    identifier="thumbhash" 
    src="EXT:thumb_hash/Resources/Public/JavaScript/thumb-hash.min.js" 
    defer="1" />
```

#### Step 2: Initialize ThumbHash Placeholders

Add your initialization code after the library:

```javascript
// In your site's JavaScript file or inline script
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-thumbhash]').forEach(function(img) {
        const hash = img.dataset.thumbhash;
        const hashArray = new Uint8Array(
            atob(hash).split('').map(function(c) { 
                return c.charCodeAt(0); 
            })
        );
        const dataUrl = thumbHashToDataURL(hashArray);
        
        img.style.background = 'url(' + dataUrl + ') center/cover no-repeat';
        img.addEventListener('load', function() {
            img.style.background = '';  // Note: This clears any existing background
        }, { once: true });
    });
});
```

#### Alternative: Using npm/Build Tools

If you prefer to use npm and a build process with the [thumbhash npm package](https://www.npmjs.com/package/thumbhash):

```bash
npm install thumbhash
```

```javascript
import { thumbHashToDataURL } from 'thumbhash'

document.querySelectorAll('[data-thumbhash]').forEach(img => {
    const hash = img.dataset.thumbhash
    const hashArray = Uint8Array.from(atob(hash), c => c.charCodeAt(0))
    const dataUrl = thumbHashToDataURL(hashArray)
    
    img.style.background = `url(${dataUrl}) center/cover no-repeat`
    img.addEventListener('load', () => {
        img.style.background = ''  // Note: This clears any existing background
    }, { once: true })
})
```

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
1. **Imagick (PHP extension)** — High quality, good alpha handling
2. **GD** — Ubiquitous fallback

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
- Imagick PHP extension (optional but recommended), otherwise GD is used
- Composer dependency: `srwiez/thumbhash ^1.4`

Notes:
- You can choose the processor via `imageProcessor` (auto|imagick|gd). Explicit choices have no fallback.

## License

GNU General Public License version 2 or later (GPL-2.0-or-later)

## Credits

ThumbHash algorithm by [Evan Wallace](https://github.com/evanw/thumbhash)
