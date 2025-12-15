# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

TYPO3 CMS extension that generates ThumbHash placeholders for images. ThumbHash creates ~28 byte image placeholders that show a blurred preview while images load, improving perceived performance.

## Common Commands

All commands run via composer scripts:

```bash
composer test              # Run PHPUnit tests
composer cs                # Run PHP_CodeSniffer
composer cs:fix            # Fix coding style issues
composer format            # Run php-cs-fixer
composer format:check      # Check formatting without fixing
composer psalm             # Run Psalm static analysis
composer check             # Run cs, psalm, and test together
```

Run a single test file:
```bash
vendor/bin/phpunit Tests/Unit/Service/ThumbHashGeneratorTest.php
```

## Architecture

### Core Flow
1. **Event Listeners** (`FileProcessingEventListener`) respond to TYPO3 file events (upload, replace, processing)
2. **ThumbHashGenerator** validates files and delegates to image processor
3. **Image Processors** (GD or Imagick) extract pixel data from images
4. **srwiez/thumbhash** library converts pixel data to hash string
5. Hashes stored in `sys_file_metadata.thumb_hash` (originals) or `sys_file_processedfile.thumb_hash` (variants)

### Key Classes
- `Classes/Service/ThumbHashGenerator.php` - Main hash generation service (20MB file limit)
- `Classes/EventListener/FileProcessingEventListener.php` - Responds to AfterFileAddedEvent, AfterFileReplacedEvent, AfterFileProcessingEvent
- `Classes/Image/ImageProcessorFactory.php` - Creates GD or Imagick processor based on config
- `Classes/ViewHelpers/ThumbHashViewHelper.php` - Fluid ViewHelper for templates
- `Classes/Command/GenerateHashesCommand.php` - CLI command for batch processing existing files

### Configuration
Extension settings in `ThumbHashConfiguration.php`:
- `autoGenerate` - Enable/disable automatic generation
- `allowedMimeTypes` - Supported image types
- `excludedFolders` - Paths to skip (original files only; processed variants always handled)
- `imageProcessor` - `auto`, `imagick`, or `gd`

## Testing

Unit tests in `Tests/Unit/` mirror the `Classes/` structure. Tests use PHPUnit 11.5.
