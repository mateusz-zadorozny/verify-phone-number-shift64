# WordPress Plugin CI/CD Setup Guide

Complete guide for implementing automated releases, code quality checks, and GitHub-based auto-updates for WordPress plugins.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [File Structure](#file-structure)
4. [Step 1: Package Configuration](#step-1-package-configuration)
5. [Step 2: Semantic Release Configuration](#step-2-semantic-release-configuration)
6. [Step 3: Version Update Script](#step-3-version-update-script)
7. [Step 4: GitHub Workflows](#step-4-github-workflows)
8. [Step 5: PHPCS Configuration](#step-5-phpcs-configuration)
9. [Step 6: GitHub Updater Class](#step-6-github-updater-class)
10. [Step 7: Integration](#step-7-integration)
11. [Usage](#usage)
12. [Private Repositories](#private-repositories)
13. [Troubleshooting](#troubleshooting)

---

## Overview

This setup provides:

- **Automated Releases**: Semantic versioning based on commit messages
- **Code Quality**: PHPCS with WordPress Coding Standards on every PR
- **PR Validation**: Enforces conventional commit format in PR titles
- **Auto-Updates**: Plugin updates itself from GitHub releases (like wordpress.org plugins)

### How It Works

```
PR with "feat: add feature" title
        ↓
[PR Lint] Validates title format
[Code Quality] Runs PHPCS checks
        ↓
Merge to master
        ↓
[Release Workflow]
  → Analyzes commits
  → Bumps version (feat=minor, fix=patch)
  → Updates version in plugin files
  → Creates GitHub release with zip
        ↓
WordPress sites detect update via GitHubUpdater
        ↓
One-click update in WP Admin
```

---

## Prerequisites

- GitHub repository (public or private)
- Node.js 18+ (for semantic-release)
- PHP 7.4+ with Composer
- Git

---

## File Structure

```
your-plugin/
├── .github/
│   └── workflows/
│       ├── release.yml          # Automated releases
│       ├── code-quality.yml     # PHPCS checks
│       └── pr-lint.yml          # PR title validation
├── scripts/
│   └── update-version.sh        # Version bump script
├── src/
│   └── Admin/
│       └── GitHubUpdater.php    # Auto-update functionality
├── .phpcs.xml.dist              # PHPCS configuration
├── .releaserc.json              # Semantic release config
├── composer.json
├── package.json
├── readme.txt
└── your-plugin.php              # Main plugin file
```

---

## Step 1: Package Configuration

### package.json

This configures semantic-release for automated versioning.

```json
{
  "name": "your-plugin-slug",
  "version": "0.1.0",
  "description": "Your plugin description",
  "private": true,
  "scripts": {
    "release": "semantic-release"
  },
  "devDependencies": {
    "@semantic-release/changelog": "^6.0.3",
    "@semantic-release/commit-analyzer": "^13.0.0",
    "@semantic-release/exec": "^6.0.3",
    "@semantic-release/git": "^10.0.1",
    "@semantic-release/github": "^10.0.0",
    "@semantic-release/release-notes-generator": "^14.0.0",
    "semantic-release": "^24.0.0"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/YOUR_USERNAME/your-plugin-slug.git"
  }
}
```

**Customization:**
- Replace `your-plugin-slug` with your plugin's directory name
- Replace `YOUR_USERNAME` with your GitHub username
- Update description

### composer.json

```json
{
    "name": "your-vendor/your-plugin",
    "description": "Your plugin description",
    "type": "wordpress-plugin",
    "license": "GPL-2.0-or-later",
    "require": {
        "php": ">=7.4"
    },
    "require-dev": {
        "dealerdirect/phpcodesniffer-composer-installer": "^1.0",
        "phpcompatibility/phpcompatibility-wp": "^2.1",
        "squizlabs/php_codesniffer": "^3.10",
        "wp-coding-standards/wpcs": "^3.1"
    },
    "autoload": {
        "psr-4": {
            "YourVendor\\YourPlugin\\": "src/"
        }
    },
    "scripts": {
        "phpcs": "phpcs",
        "phpcbf": "phpcbf"
    },
    "config": {
        "allow-plugins": {
            "dealerdirect/phpcodesniffer-composer-installer": true
        },
        "optimize-autoloader": true,
        "platform": {
            "php": "8.1"
        },
        "sort-packages": true
    }
}
```

**Important:**
- `platform.php: "8.1"` ensures dependencies are compatible with CI (PHP 8.1)
- This prevents issues when your local PHP is newer than CI

---

## Step 2: Semantic Release Configuration

### .releaserc.json

```json
{
  "branches": ["master"],
  "plugins": [
    "@semantic-release/commit-analyzer",
    "@semantic-release/release-notes-generator",
    ["@semantic-release/changelog", { "changelogFile": "CHANGELOG.md" }],
    [
      "@semantic-release/exec",
      {
        "prepareCmd": "./scripts/update-version.sh ${nextRelease.version}"
      }
    ],
    [
      "@semantic-release/git",
      {
        "assets": [
          "CHANGELOG.md",
          "your-plugin.php",
          "readme.txt",
          "package.json",
          "package-lock.json"
        ],
        "message": "chore(release): ${nextRelease.version} [skip ci]\n\n${nextRelease.notes}"
      }
    ],
    "@semantic-release/github"
  ]
}
```

**Customization:**
- Replace `your-plugin.php` with your main plugin filename
- Change `master` to `main` if that's your default branch

### Version Triggers

| Commit Type | Version Bump | Example |
|-------------|--------------|---------|
| `feat:`     | Minor (1.0.0 → 1.1.0) | `feat: add export feature` |
| `fix:`      | Patch (1.0.0 → 1.0.1) | `fix: resolve checkout error` |
| `perf:`     | Patch | `perf: optimize database queries` |
| `docs:`     | No release | `docs: update readme` |
| `style:`    | No release | `style: fix code formatting` |
| `refactor:` | No release | `refactor: reorganize classes` |
| `test:`     | No release | `test: add unit tests` |
| `build:`    | No release | `build: update dependencies` |
| `ci:`       | No release | `ci: fix workflow` |
| `chore:`    | No release | `chore: cleanup files` |

---

## Step 3: Version Update Script

### scripts/update-version.sh

```bash
#!/bin/bash
# Script to update version numbers in WordPress plugin files
# Usage: ./scripts/update-version.sh <version>

set -e

VERSION=$1

if [ -z "$VERSION" ]; then
    echo "Usage: $0 <version>"
    exit 1
fi

echo "Updating version to: $VERSION"

# Update plugin header Version:
# Adjust the spacing to match your plugin header format
sed -i "s/^ \* Version:.*/ * Version:         $VERSION/" your-plugin.php

# Update version constant (adjust constant name)
sed -i "s/define( 'YOUR_PLUGIN_VERSION', '[^']*' );/define( 'YOUR_PLUGIN_VERSION', '$VERSION' );/" your-plugin.php

# Update readme.txt Stable tag:
sed -i "s/^Stable tag: .*/Stable tag: $VERSION/" readme.txt

echo "Version updated successfully!"

# Verify changes
echo ""
echo "Verification:"
grep "Version:" your-plugin.php | head -1
grep "YOUR_PLUGIN_VERSION" your-plugin.php
grep "Stable tag:" readme.txt
```

**Customization:**
- Replace `your-plugin.php` with your main plugin filename
- Replace `YOUR_PLUGIN_VERSION` with your version constant name
- Adjust spacing in sed commands to match your file format

**Make it executable:**
```bash
chmod +x scripts/update-version.sh
```

---

## Step 4: GitHub Workflows

### .github/workflows/release.yml

```yaml
# Automated release workflow
# Triggers on push to master, creates versioned releases with zip artifacts

name: Release

on:
  push:
    branches:
      - master  # Change to 'main' if needed

# Required permissions for creating releases and pushing commits
permissions:
  contents: write
  issues: write
  pull-requests: write

jobs:
  release:
    name: Release
    runs-on: ubuntu-latest
    steps:
      # Checkout with full history (needed for semantic-release)
      - name: Checkout
        uses: actions/checkout@v4
        with:
          fetch-depth: 0
          persist-credentials: false

      # Node.js for semantic-release
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: "lts/*"
          cache: npm

      # PHP for Composer (production dependencies)
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.1"
          tools: composer:v2

      # Cache Composer dependencies for faster builds
      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: |
            ${{ runner.os }}-composer-

      # Install dependencies
      - name: Install npm dependencies
        run: npm ci

      # IMPORTANT: --no-dev excludes PHPCS and other dev tools from release
      - name: Install Composer dependencies (production)
        run: composer install --no-dev --optimize-autoloader --prefer-dist

      # Run semantic-release (analyzes commits, bumps version, creates release)
      - name: Run Semantic Release
        id: semantic
        uses: cycjimmy/semantic-release-action@v4
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}

      # Build zip artifact only if a new release was created
      - name: Build release artifact
        if: steps.semantic.outputs.new_release_published == 'true'
        run: |
          # Create dist directory
          mkdir -p dist

          # Copy plugin files, excluding development files
          # IMPORTANT: Adjust exclusions for your project structure
          rsync -av --progress . dist/your-plugin-slug/ \
            --exclude='.git' \
            --exclude='.github' \
            --exclude='.circleci' \
            --exclude='.claude' \
            --exclude='node_modules' \
            --exclude='dist' \
            --exclude='tests' \
            --exclude='bin' \
            --exclude='*.zip' \
            --exclude='*.tar.gz' \
            --exclude='.git*' \
            --exclude='.editorconfig' \
            --exclude='.phpcs.xml.dist' \
            --exclude='.distignore' \
            --exclude='phpunit.xml.dist' \
            --exclude='composer.json' \
            --exclude='composer.lock' \
            --exclude='package.json' \
            --exclude='package-lock.json' \
            --exclude='.releaserc.json' \
            --exclude='CHANGELOG.md' \
            --exclude='docs'

          # Create zip with plugin slug as folder name
          cd dist
          zip -r your-plugin-slug.zip your-plugin-slug

          echo "Release artifact created: dist/your-plugin-slug.zip"
          echo "Version: ${{ steps.semantic.outputs.new_release_version }}"

      # Upload zip to GitHub release
      - name: Upload artifact to release
        if: steps.semantic.outputs.new_release_published == 'true'
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        run: |
          gh release upload "v${{ steps.semantic.outputs.new_release_version }}" \
            dist/your-plugin-slug.zip \
            --clobber
```

**Customization:**
- Replace `your-plugin-slug` (3 occurrences) with your plugin directory name
- Change `master` to `main` if needed
- Add/remove `--exclude` patterns as needed

### .github/workflows/code-quality.yml

```yaml
# Code quality checks
# Runs PHPCS with WordPress Coding Standards on PRs and pushes

name: Code Quality

on:
  pull_request:
    branches:
      - master  # Change to 'main' if needed
  push:
    branches:
      - master

jobs:
  phpcs:
    name: PHPCS
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      # PHP with cs2pr for GitHub annotations
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.1"
          tools: composer:v2, cs2pr

      # Cache Composer for faster builds
      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: |
            ${{ runner.os }}-composer-

      # Install WITH dev dependencies (includes PHPCS)
      - name: Install Composer dependencies
        run: composer install --prefer-dist

      # Run PHPCS and generate checkstyle report
      - name: Run PHPCS
        run: composer phpcs -- --report-full --report-checkstyle=./phpcs-report.xml

      # Convert checkstyle to GitHub annotations (shows errors inline in PR)
      - name: Show PHPCS results in PR
        if: always()
        run: cs2pr ./phpcs-report.xml
```

### .github/workflows/pr-lint.yml

```yaml
# PR title validation
# Ensures PR titles follow conventional commit format for semantic-release

name: "Lint PR"

on:
  pull_request:
    types:
      - opened
      - edited
      - synchronize
    branches:
      - master  # Change to 'main' if needed

permissions:
  pull-requests: read
  statuses: write

jobs:
  main:
    name: Validate PR title
    runs-on: ubuntu-latest
    steps:
      - uses: amannn/action-semantic-pull-request@v5
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          # Allowed PR title prefixes
          types: |
            feat
            fix
            docs
            style
            refactor
            perf
            test
            build
            ci
            chore
            revert
          # Set to true to require scope: "feat(api): add endpoint"
          requireScope: false
```

---

## Step 5: PHPCS Configuration

### .phpcs.xml.dist

```xml
<?xml version="1.0"?>
<ruleset name="Your Plugin Name">
    <description>PHPCS ruleset for Your Plugin.</description>

    <!-- What to scan -->
    <file>.</file>

    <!-- Exclude paths - adjust for your structure -->
    <exclude-pattern>/vendor/*</exclude-pattern>
    <exclude-pattern>/node_modules/*</exclude-pattern>
    <exclude-pattern>/dist/*</exclude-pattern>
    <exclude-pattern>/tests/*</exclude-pattern>
    <exclude-pattern>/assets/build/*</exclude-pattern>

    <!-- How to scan -->
    <arg value="sp"/> <!-- Show sniff and progress -->
    <arg name="basepath" value="."/> <!-- Strip paths to relevant bit -->
    <arg name="colors"/>
    <arg name="extensions" value="php"/>
    <arg name="parallel" value="8"/> <!-- Parallel processing -->

    <!-- Rules: WordPress Coding Standards -->
    <rule ref="WordPress-Extra">
        <!-- Allow short array syntax [] instead of array() -->
        <exclude name="Universal.Arrays.DisallowShortArraySyntax"/>
    </rule>

    <!-- Verify text domain matches your plugin -->
    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array">
                <element value="your-plugin-text-domain"/>
            </property>
        </properties>
    </rule>

    <!-- Check function/class prefixes to avoid conflicts -->
    <rule ref="WordPress.NamingConventions.PrefixAllGlobals">
        <properties>
            <property name="prefixes" type="array">
                <element value="your_prefix"/>
                <element value="YourPrefix"/>
                <element value="YOUR_PREFIX"/>
            </property>
        </properties>
    </rule>

    <!-- PHP Compatibility - adjust minimum version -->
    <rule ref="PHPCompatibilityWP"/>
    <config name="testVersion" value="7.4-"/>

    <!-- Allow PSR-4 file naming in src/ directory -->
    <rule ref="WordPress.Files.FileName">
        <exclude-pattern>/src/*</exclude-pattern>
    </rule>

    <!-- Exclude main plugin file from class naming requirement -->
    <rule ref="WordPress.Files.FileName.InvalidClassFileName">
        <exclude-pattern>/your-plugin.php</exclude-pattern>
    </rule>

    <!-- Allow unused parameters required by WordPress hook signatures -->
    <rule ref="Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed">
        <exclude-pattern>/src/*</exclude-pattern>
    </rule>

    <!-- Allow common parameter names that are reserved keywords -->
    <rule ref="Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound">
        <severity>0</severity>
    </rule>
</ruleset>
```

**Customization:**
- Replace `your-plugin-text-domain` with your text domain
- Replace `your_prefix`, `YourPrefix`, `YOUR_PREFIX` with your prefixes
- Replace `your-plugin.php` with your main plugin filename
- Adjust exclude patterns for your project structure

---

## Step 6: GitHub Updater Class

### src/Admin/GitHubUpdater.php

```php
<?php
/**
 * GitHub-based auto-updater for the plugin.
 *
 * This class integrates with WordPress plugin update system to check
 * for updates from GitHub releases and enable one-click updates.
 *
 * @package YourVendor\YourPlugin\Admin
 */

declare(strict_types=1);

namespace YourVendor\YourPlugin\Admin;

/**
 * Handles plugin updates from GitHub releases.
 *
 * How it works:
 * 1. Hooks into WordPress update check (pre_set_site_transient_update_plugins)
 * 2. Fetches latest release from GitHub API
 * 3. Compares versions and injects update info if newer version exists
 * 4. WordPress handles the actual update process
 *
 * Requirements:
 * - GitHub releases must have a zip asset named "{plugin-slug}.zip"
 * - Release tags should be semver format: v1.0.0 or 1.0.0
 */
class GitHubUpdater {

    /**
     * GitHub repository owner (username or organization).
     *
     * @var string
     */
    const GITHUB_OWNER = 'YOUR_GITHUB_USERNAME';

    /**
     * GitHub repository name.
     *
     * @var string
     */
    const GITHUB_REPO = 'your-plugin-slug';

    /**
     * Plugin slug (must match the plugin directory name).
     *
     * @var string
     */
    const PLUGIN_SLUG = 'your-plugin-slug';

    /**
     * Transient key for caching release info.
     * Use a unique key to avoid conflicts with other plugins.
     *
     * @var string
     */
    const TRANSIENT_KEY = 'yourprefix_github_release';

    /**
     * Cache duration in seconds (12 hours).
     * Reduces API calls to stay within GitHub rate limits.
     *
     * @var int
     */
    const CACHE_DURATION = 43200;

    /**
     * Error cache duration in seconds (1 hour).
     * Shorter duration so failed checks retry sooner.
     *
     * @var int
     */
    const ERROR_CACHE_DURATION = 3600;

    /**
     * Initialize updater hooks.
     *
     * Call this method from your plugin's main file during initialization.
     * Example: GitHubUpdater::init();
     *
     * @return void
     */
    public static function init(): void {
        // Hook into WordPress update check
        add_filter( 'pre_set_site_transient_update_plugins', array( self::class, 'check_for_update' ) );

        // Provide plugin info for "View details" modal
        add_filter( 'plugins_api', array( self::class, 'plugin_info' ), 10, 3 );

        // Fix GitHub's folder naming after extraction
        add_filter( 'upgrader_source_selection', array( self::class, 'fix_source_dir' ), 10, 4 );

        // Clear cache after update completes
        add_action( 'upgrader_process_complete', array( self::class, 'clear_cache' ), 10, 0 );
    }

    /**
     * Check for plugin updates from GitHub.
     *
     * This method is called by WordPress when checking for plugin updates.
     * If a newer version is available on GitHub, it adds the update info
     * to the transient so WordPress displays the update notice.
     *
     * @param object $transient The update_plugins transient value.
     * @return object Modified transient with our update info.
     */
    public static function check_for_update( $transient ) {
        // Don't check if WordPress hasn't checked installed versions yet
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        // Get release info from GitHub (cached)
        $release_info = self::get_release_info();
        if ( null === $release_info ) {
            return $transient;
        }

        // Extract version number (removes 'v' prefix if present)
        $new_version = self::normalize_version( $release_info['tag_name'] );

        // Plugin file path relative to plugins directory
        // Format: "plugin-slug/plugin-slug.php"
        $plugin_file = self::PLUGIN_SLUG . '/' . self::PLUGIN_SLUG . '.php';

        // Check if new version is actually newer
        if ( ! self::is_update_available( $new_version ) ) {
            return $transient;
        }

        // Get download URL for the zip asset
        $download_url = self::get_download_url( $release_info );
        if ( null === $download_url ) {
            return $transient;
        }

        // Add update info to transient
        // WordPress will display "There is a new version available"
        $transient->response[ $plugin_file ] = (object) array(
            'slug'         => self::PLUGIN_SLUG,
            'plugin'       => $plugin_file,
            'new_version'  => $new_version,
            'url'          => $release_info['html_url'],  // Link to GitHub release
            'package'      => $download_url,               // Zip download URL
            'icons'        => array(),
            'banners'      => array(),
            'tested'       => '',
            'requires'     => '5.0',      // Minimum WordPress version
            'requires_php' => '7.4',      // Minimum PHP version
        );

        return $transient;
    }

    /**
     * Provide plugin information for the "View details" modal.
     *
     * When users click "View details" on the plugins page, WordPress
     * calls this filter. We return plugin info from GitHub release.
     *
     * @param false|object|array $result The result object or array.
     * @param string             $action The type of information being requested.
     * @param object             $args   Plugin API arguments.
     * @return false|object Plugin information or false if not our plugin.
     */
    public static function plugin_info( $result, $action, $args ) {
        // Only handle plugin_information requests
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        // Only handle requests for our plugin
        if ( ! isset( $args->slug ) || self::PLUGIN_SLUG !== $args->slug ) {
            return $result;
        }

        $release_info = self::get_release_info();
        if ( null === $release_info ) {
            return $result;
        }

        $new_version  = self::normalize_version( $release_info['tag_name'] );
        $download_url = self::get_download_url( $release_info );

        // Return plugin info object
        // This data populates the "View details" modal
        return (object) array(
            'name'           => 'Your Plugin Name',  // Display name
            'slug'           => self::PLUGIN_SLUG,
            'version'        => $new_version,
            'author'         => '<a href="https://yourwebsite.com">Your Name</a>',
            'author_profile' => 'https://yourwebsite.com',
            'homepage'       => 'https://yourwebsite.com/plugins/your-plugin',
            'download_link'  => $download_url,
            'requires'       => '5.0',
            'tested'         => '',  // WordPress version tested up to
            'requires_php'   => '7.4',
            'sections'       => array(
                'description' => 'Your plugin description.',
                'changelog'   => self::format_changelog( $release_info['body'] ?? '' ),
            ),
            'banners'        => array(),
        );
    }

    /**
     * Fix the extracted folder name from GitHub releases.
     *
     * Problem: GitHub extracts zips into folders named "repo-version" or
     * with a hash, but WordPress expects the folder to match the plugin slug.
     *
     * Solution: Rename the extracted folder to match our plugin slug.
     *
     * @param string       $source        File source location.
     * @param string       $remote_source Remote file source location.
     * @param \WP_Upgrader $upgrader      WP_Upgrader instance.
     * @param array        $hook_extra    Extra arguments passed to hooked filters.
     * @return string|\WP_Error Corrected source path or WP_Error on failure.
     */
    public static function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
        global $wp_filesystem;

        // Only process plugin updates (not themes, etc.)
        if ( ! isset( $hook_extra['plugin'] ) ) {
            return $source;
        }

        // Only process our plugin
        $plugin_file = self::PLUGIN_SLUG . '/' . self::PLUGIN_SLUG . '.php';
        if ( $hook_extra['plugin'] !== $plugin_file ) {
            return $source;
        }

        // Check if source directory already has correct name
        $source_base = basename( $source );
        if ( self::PLUGIN_SLUG === $source_base ) {
            return $source;
        }

        // Rename to expected plugin slug
        $corrected_source = trailingslashit( $remote_source ) . self::PLUGIN_SLUG . '/';
        if ( $wp_filesystem->move( $source, $corrected_source ) ) {
            return $corrected_source;
        }

        return new \WP_Error(
            'rename_failed',
            __( 'Failed to rename plugin directory.', 'your-text-domain' )
        );
    }

    /**
     * Get release info from GitHub (cached).
     *
     * Uses WordPress transients to cache the response and reduce API calls.
     * GitHub API has rate limits: 60 requests/hour for unauthenticated requests.
     *
     * @return array|null Release data or null on failure.
     */
    public static function get_release_info(): ?array {
        $cached = get_transient( self::TRANSIENT_KEY );

        // Return cached data if available
        if ( false !== $cached ) {
            // 'error' is cached on API failures
            if ( 'error' === $cached ) {
                return null;
            }
            return $cached;
        }

        // Fetch fresh data from GitHub
        $release_info = self::fetch_github_release();

        if ( null === $release_info ) {
            // Cache error state for shorter duration
            // This prevents hammering the API on failures
            set_transient( self::TRANSIENT_KEY, 'error', self::ERROR_CACHE_DURATION );
            return null;
        }

        // Cache successful response
        set_transient( self::TRANSIENT_KEY, $release_info, self::CACHE_DURATION );

        return $release_info;
    }

    /**
     * Fetch latest release data from GitHub API.
     *
     * Uses the /releases/latest endpoint which returns the most recent
     * non-prerelease, non-draft release.
     *
     * @return array|null Release data or null on failure.
     */
    private static function fetch_github_release(): ?array {
        $api_url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            self::GITHUB_OWNER,
            self::GITHUB_REPO
        );

        // Make API request with proper headers
        $response = wp_safe_remote_get(
            $api_url,
            array(
                'timeout' => 10,
                'headers' => array(
                    // Required for GitHub API v3
                    'Accept'     => 'application/vnd.github.v3+json',
                    // User-Agent is required by GitHub API
                    'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
                ),
            )
        );

        // Check for request errors
        if ( is_wp_error( $response ) ) {
            return null;
        }

        // Check for successful response
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $response_code ) {
            return null;
        }

        // Parse JSON response
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        // Validate response has required data
        if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
            return null;
        }

        return $data;
    }

    /**
     * Extract download URL from release assets.
     *
     * Looks for a zip file named "{plugin-slug}.zip" in the release assets.
     * This is the distribution zip uploaded by the release workflow.
     *
     * @param array $release_info GitHub release data.
     * @return string|null Download URL or null if not found.
     */
    private static function get_download_url( array $release_info ): ?string {
        // Expected asset name: "your-plugin-slug.zip"
        $expected_asset = self::PLUGIN_SLUG . '.zip';

        if ( empty( $release_info['assets'] ) || ! is_array( $release_info['assets'] ) ) {
            return null;
        }

        // Search for our zip file in release assets
        foreach ( $release_info['assets'] as $asset ) {
            if ( isset( $asset['name'] ) && $expected_asset === $asset['name'] ) {
                return $asset['browser_download_url'] ?? null;
            }
        }

        return null;
    }

    /**
     * Normalize version string by removing 'v' prefix.
     *
     * GitHub tags often use 'v' prefix (v1.0.0) but WordPress expects
     * plain version numbers (1.0.0).
     *
     * @param string $version Version string (e.g., "v1.0.2").
     * @return string Normalized version (e.g., "1.0.2").
     */
    private static function normalize_version( string $version ): string {
        return ltrim( $version, 'vV' );
    }

    /**
     * Check if an update is available.
     *
     * Compares the GitHub version with currently installed version.
     *
     * @param string $new_version The new version from GitHub.
     * @return bool True if update is available.
     */
    private static function is_update_available( string $new_version ): bool {
        // YOUR_PLUGIN_VERSION should be a constant defined in your main plugin file
        $current_version = YOUR_PLUGIN_VERSION;
        return version_compare( $new_version, $current_version, '>' );
    }

    /**
     * Format changelog text from GitHub release body.
     *
     * Converts basic markdown to HTML for display in WordPress.
     *
     * @param string $body GitHub release body (markdown).
     * @return string Formatted changelog HTML.
     */
    private static function format_changelog( string $body ): string {
        if ( empty( $body ) ) {
            return '<p>' . __( 'No changelog available.', 'your-text-domain' ) . '</p>';
        }

        // Basic markdown to HTML conversion
        $changelog = esc_html( $body );
        $changelog = nl2br( $changelog );

        // Convert markdown headers
        $changelog = preg_replace( '/^### (.+)$/m', '<h4>$1</h4>', $changelog );
        $changelog = preg_replace( '/^## (.+)$/m', '<h3>$1</h3>', $changelog );

        // Convert markdown lists
        $changelog = preg_replace( '/^\* (.+)$/m', '<li>$1</li>', $changelog );
        $changelog = preg_replace( '/^- (.+)$/m', '<li>$1</li>', $changelog );

        return $changelog;
    }

    /**
     * Clear the cached release info.
     *
     * Called after successful update and on plugin deactivation.
     * Forces fresh check on next update check.
     *
     * @return void
     */
    public static function clear_cache(): void {
        delete_transient( self::TRANSIENT_KEY );
    }
}
```

**Customization (search and replace):**
- `YOUR_GITHUB_USERNAME` → Your GitHub username
- `your-plugin-slug` → Your plugin directory name (3 occurrences)
- `yourprefix_github_release` → Unique transient key
- `YourVendor\YourPlugin` → Your namespace
- `Your Plugin Name` → Your plugin display name
- `your-text-domain` → Your text domain
- `YOUR_PLUGIN_VERSION` → Your version constant name
- Update author info and URLs

---

## Step 7: Integration

### Main Plugin File

Add this to your main plugin file to initialize the updater:

```php
<?php
/**
 * Plugin Name: Your Plugin Name
 * Version:     1.0.0
 */

// Define version constant (used by GitHubUpdater)
define( 'YOUR_PLUGIN_VERSION', '1.0.0' );

// Load autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Initialize GitHub updater
add_action( 'plugins_loaded', function() {
    \YourVendor\YourPlugin\Admin\GitHubUpdater::init();
});
```

### Deactivation Hook (Optional)

Clear update cache on deactivation:

```php
register_deactivation_hook( __FILE__, function() {
    \YourVendor\YourPlugin\Admin\GitHubUpdater::clear_cache();
});
```

---

## Usage

### Daily Workflow

1. Create feature branch
2. Make changes
3. Create PR with conventional title (e.g., `feat: add new feature`)
4. PR checks run automatically (PHPCS, title validation)
5. Merge to master
6. Release workflow runs automatically
7. WordPress sites detect update within 12 hours (or immediately if cache cleared)

### Manual Commands

```bash
# Run PHPCS locally
composer phpcs

# Auto-fix PHPCS issues
composer phpcbf

# Clear update cache (WP-CLI)
wp transient delete yourprefix_github_release

# Check cached release info
wp transient get yourprefix_github_release
```

---

## Private Repositories

For private GitHub repos, you need authentication for the updater.

### Option 1: Fine-grained Personal Access Token (Recommended)

1. Create token at GitHub → Settings → Developer settings → Personal access tokens → Fine-grained tokens
2. Set permissions: Repository access → Only select repositories → Your plugin
3. Permissions: Contents → Read-only
4. Add token to wp-config.php:

```php
define( 'YOUR_PLUGIN_GITHUB_TOKEN', 'github_pat_xxxx' );
```

5. Modify `fetch_github_release()` in GitHubUpdater.php:

```php
$headers = array(
    'Accept'     => 'application/vnd.github.v3+json',
    'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
);

// Add authorization for private repos
if ( defined( 'YOUR_PLUGIN_GITHUB_TOKEN' ) && YOUR_PLUGIN_GITHUB_TOKEN ) {
    $headers['Authorization'] = 'Bearer ' . YOUR_PLUGIN_GITHUB_TOKEN;
}

$response = wp_safe_remote_get(
    $api_url,
    array(
        'timeout' => 10,
        'headers' => $headers,
    )
);
```

### CI for Private Composer Dependencies

If your plugin depends on other private repos via Composer:

```yaml
# In workflow file, before composer install
- name: Configure Composer auth
  run: composer config github-oauth.github.com ${{ secrets.COMPOSER_GITHUB_TOKEN }}
```

---

## Troubleshooting

### Release not triggered

- Check PR title follows conventional format
- Only `feat:`, `fix:`, `perf:` trigger releases
- Check GitHub Actions logs for errors

### PHPCS failing in CI

- Run `composer update` locally to sync lock file
- Check `platform.php` is set in composer.json config
- Run `composer phpcs` locally first

### Update not showing in WordPress

```bash
# Clear transient cache
wp transient delete yourprefix_github_release

# Force WordPress update check
wp plugin update --all --dry-run
```

### GitHub API rate limit

- Unauthenticated: 60 requests/hour
- With token: 5,000 requests/hour
- Check rate limit: `curl -I https://api.github.com/rate_limit`

### Zip not found in release

- Ensure release workflow completed successfully
- Check release has `your-plugin-slug.zip` asset
- Verify asset name matches `PLUGIN_SLUG . '.zip'` in GitHubUpdater

---

## Checklist

Before your first release:

- [ ] Replace all placeholders in files
- [ ] Run `npm install` and `composer install`
- [ ] Make `scripts/update-version.sh` executable
- [ ] Test PHPCS passes: `composer phpcs`
- [ ] Verify version constant exists in main plugin file
- [ ] Verify `readme.txt` has `Stable tag:` line
- [ ] Create initial release manually or push first `feat:` commit

After setup:

- [ ] Create test PR to verify checks work
- [ ] Merge and verify release is created
- [ ] Install plugin and verify update detection works
