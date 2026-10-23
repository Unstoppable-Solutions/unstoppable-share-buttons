# unstoppable-share-buttons

> This file is for Claude Code sessions working on this repo. For a human-oriented overview see `README.md`.

## What this plugin does

Social share bars for WordPress — inline, floating, sticky, popup. 48 networks, first-party click analytics, no third-party scripts.

- **Main PHP file:** `unstoppable-share-buttons.php`
- **Code prefix:** `USHB_`
- **WordPress.org slug:** `unstoppable-share-buttons`
- **WP.org URL:** https://wordpress.org/plugins/unstoppable-share-buttons/

## Base44 Pro mapping

- **Base44 app:** _(none yet — fill in when a Pro version is scaffolded there)_
- When updating code that affects both the free (this repo) and the Base44 Pro app, apply the matching change in both places in the same session.

## Release workflow

Releases are deployed to WordPress.org SVN automatically by GitHub Actions. The workflow is identical across every `unstoppable-solutions/*` plugin:

1. Finish feature work on `main`.
2. Bump the version in **all** of these:
   - `unstoppable-share-buttons.php` — `Version:` header
   - `unstoppable-share-buttons.php` — `USHB_VERSION` constant
   - `readme.txt` — `Stable tag:`
   - `readme.txt` — add a `== Changelog ==` entry
   - `readme.txt` — add an `== Upgrade Notice ==` entry if the release changes
     behaviour a site owner would want warning about
3. Regenerate the translation catalogue, whose header carries the version:
   `php wp-cli.phar i18n make-pot . languages/unstoppable-share-buttons.pot --slug=unstoppable-share-buttons`
4. Commit and push `main`. Wait for Plugin Check CI to go green.
5. On GitHub, create a new **Release** tagged `vX.Y.Z` (semver) with the changelog in the body. **A Claude session cannot do this step** — creating a release and pushing a tag both return `403 ... not permitted for this session type`, so a human clicks Publish. Claude takes it to a pushed, CI-green commit with the notes drafted.
6. The `deploy-to-wporg.yml` workflow fires and pushes the release to WP.org SVN. Confirm it landed against WP.org itself, not just a green run: `curl -s https://api.wordpress.org/plugins/info/1.0/unstoppable-share-buttons.json`

### Never add a `.wordpress-org/` directory to this repo

The deploy action rsyncs `ASSETS_DIR` (default `.wordpress-org`) over SVN
`assets/` **with `--delete`**. The directory is deliberately absent, so the step
is skipped and the live banner, icon and five screenshots stay as they are.
Creating it replaces all of them with whatever it contains.

The listing's artwork already exists and is good. `plugins/info/1.0` reports
`banners: null` and `icons: null` for this plugin anyway, so an API check looks
like proof they are missing when they are not — check
`https://plugins.svn.wordpress.org/unstoppable-share-buttons/assets/` instead.

## CI

Every push and PR runs the official WordPress Plugin Check against the codebase (`.github/workflows/plugin-check.yml`). CI must pass before merging. The deploy workflow (`.github/workflows/deploy-to-wporg.yml`) only fires on published Releases.

**Secrets used by deploy:** `SVN_USERNAME`, `SVN_PASSWORD` — org-level secrets on `unstoppable-solutions`. Nothing per-repo.

## Known phpcs:ignores specific to this plugin

_(none — this plugin passes Plugin Check cleanly without any ignores.)_

## Shared conventions across the Unstoppable Solutions org

- **GPL-2.0-or-later** on everything.
- **One author** for Contributors: in `readme.txt`: `paramedic192` (Dave Stevens).
- **Tested up to** is bumped as WordPress ships each new release. The header takes the **major version only** (`7.1`) — Plugin Check raises `invalid_tested_upto_minor` on a patch number such as `7.1.3`. Record the exact tested build in `changelog.txt`/`README.md` instead. Currently verified against 7.1.3.
- **Plugin Check CI config** (`.github/workflows/plugin-check.yml`) is standard across the org: `exclude-checks: late_escaping` + `exclude-files` lists the GitHub-only files (`.github`, `.distignore`, `.gitignore`, `.gitattributes`, `README.md`, `CLAUDE.md`, `SECURITY.md`, `CONTRIBUTING.md`, `CHANGELOG.md`).
- **`.distignore`** keeps GitHub-only files out of the WP.org zip (CI, docs, this file).
- **Prefer `phpcs:ignore` + inline rationale** over removing the ignored check from CI entirely — a specific ignore documents the decision at the call site; a check removal silently covers future code too.
- **Don't `-c user.email=` with a random identity** when committing — use `fieldmedics4@gmail.com` / `Dave Stevens` for authorship, with Claude as Co-Authored-By.

## Working with the plugin from Claude Code

- When Claude attaches this repo in a future session, this file loads automatically.
- Workflow for a bugfix / feature:
  1. Make the change on `main` (or a branch).
  2. Run `gh run watch` (or let Plugin Check CI post status) — do not merge red.
  3. For a release: follow the Release workflow section above.
- The free plugin and its Pro add-on live in **separate repos**. This repo is the free side; Pro has its own repo (private) when it exists.

## Repository layout

Standard WordPress plugin layout — `unstoppable-share-buttons.php` is the bootstrap, `includes/` holds the classes, `assets/` holds CSS/JS, `languages/` holds the `.pot`, `readme.txt` is the WP.org directory listing, `.github/` holds the CI and deploy workflows.
