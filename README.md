# Unstoppable Share Buttons

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/unstoppable-share-buttons.svg)](https://wordpress.org/plugins/unstoppable-share-buttons/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/unstoppable-share-buttons.svg)](https://wordpress.org/plugins/unstoppable-share-buttons/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

Social sharing done properly. Inline, floating, sticky-mobile and popup share bars with 48 networks, first-party click analytics, UTM tagging, a block, an Elementor widget, and WooCommerce support.

> **End users:** install from the WordPress Plugin Directory — [wordpress.org/plugins/unstoppable-share-buttons](https://wordpress.org/plugins/unstoppable-share-buttons/)

## Features

- **48 share networks** — all the usual (X, Facebook, LinkedIn, WhatsApp, Reddit, Pinterest, Telegram) plus 40+ more
- **Four placements** — inline (in your post content), floating (side-rail), sticky mobile bottom bar, popup
- **First-party click analytics** — no third-party trackers; your data stays on your site
- **UTM tagging** — automatic campaign/source parameters on every outbound share link
- **Gutenberg block + Elementor widget** — drop share buttons into any layout
- **WooCommerce support** — share product pages with pre-filled product data
- **Zero external scripts** — no bloat, no cookie banner headaches

## Development

### Release workflow

Releases are deployed to WordPress.org automatically by GitHub Actions:

1. Finish the feature work on `main`
2. Bump the version in: `unstoppable-share-buttons.php` (header + constant), `readme.txt` (Stable tag), and add a Changelog entry
3. Commit and push `main`
4. On GitHub, create a new **Release** tagged `vX.Y.Z` — the deploy workflow fires and pushes to WordPress.org SVN

### CI

Every push and pull request runs the official WordPress Plugin Check against the codebase.

## License

GPL-2.0-or-later. See the `License` header in `unstoppable-share-buttons.php`.

## Author

Developed and maintained by [Unstoppable Solutions](https://unstoppablesolutions.app).
