# Unstoppable Share Buttons — Development Roadmap

> Status of this document: living. Items move between phases as priorities change.
> Nothing here is implemented until it is marked **Completed** and appears in `changelog.txt`.
>
> **Last reviewed:** 2026-10-23 against commit `00c16b1` (v1.0.9)

## Legend

| Field | Values |
|---|---|
| **Priority** | High · Medium · Low |
| **Complexity** | Small (hours) · Medium (a day or two) · Large (a week+) |
| **Status** | Proposed · Planned · In Progress · Completed |
| **Source** | Previous Discussion · Code Review · New Suggestion |

---

## Phase 1: Immediate Priorities

### 1.1 Repository migration and verification — ✅ Completed
**Description:** Move the live plugin source into the Unstoppable Solutions org repo and confirm it matches what is published.
**Benefit:** GitHub becomes the single source of truth; releases stop being hand-assembled.
**Priority:** High · **Complexity:** Small · **Milestone:** v1.0.9 · **Status:** Completed · **Source:** Previous Discussion

Verified 2026-10-23: repo contents match the published 1.0.9, including the 1.0.8 shape/glow fixes and the 1.0.9 shortcode-discoverability change.

---

### 1.2 Missing `uninstall.php` — ✅ Completed (unreleased)
**Description:** The Tools screen offers "Delete settings and share history on uninstall", and the setting saves correctly — but no `uninstall.php` existed, so the option was a no-op. Deleting the plugin left `ushb_settings`, `ushb_version`, `ushb_install_date`, `ushb_settings_repaired`, the notice-dismissal options, the `{prefix}ushb_clicks` table and all `ushb_` transients behind.
**Benefit:** The plugin now honours a promise its own UI makes. Also a data-hygiene expectation of the WP.org guidelines.
**Priority:** High · **Complexity:** Small · **Milestone:** next release · **Status:** Completed on `main`, awaiting release · **Source:** Code Review

Both paths tested on WP 7.1.3: opted in → every option, the table and all transients removed; opted out (the default) → everything preserved, including click rows.

---

### 1.3 WordPress 7.1.3 compatibility — ✅ Completed (unreleased)
**Description:** Verify against the current WordPress release and update the compatibility headers.
**Benefit:** WP.org hides plugins that look untested against current WordPress; it also affects search placement.
**Priority:** High · **Complexity:** Small · **Milestone:** next release · **Status:** Completed on `main`, awaiting release · **Source:** Previous Discussion

Tested 2026-10-23 on a clean WP 7.1.3 install with `WP_DEBUG` and `SCRIPT_DEBUG` on: activation clean, all three admin screens and all five settings tabs render, every tab saved without disturbing the others, front-end bar renders, `debug.log` never created. Plugin Check and PHPCS (full WordPress standard) both clean.

---

### 1.4 Untested integrations — ✅ Completed
**Description:** The Elementor widget, WooCommerce product sharing, the editor block, the AJAX click-tracking round trip and settings import/export had never been exercised against the real dependency — only linted.
**Benefit:** These were the most likely source of the next user-visible bug. Four previous bugs in this plugin all lived in code that was lint-clean but unrun.
**Priority:** High · **Complexity:** Medium · **Milestone:** v1.0.9 · **Status:** Completed · **Source:** Previous Discussion

Verified 2026-10-23 against WooCommerce 11.2.1 and Elementor 4.3.5 on WordPress 7.1.3. **No code changes were needed — all five worked as written.** Details in the verification table below.

---

### 1.5 Directory assets (banner, icon)
**Description:** `banner-772x250.png`, `banner-1544x500.png`, `icon-128x128.png` and `icon-256x256.png` do not exist. The five screenshots do.
**Benefit:** The WP.org listing currently renders without artwork, which reads as abandoned in a crowded category.
**Priority:** High · **Complexity:** Small · **Milestone:** v1.1.0 · **Status:** Proposed · **Source:** Previous Discussion

These live in SVN `assets/` at the repo root, not in `trunk/`, and need no version bump or release.

---

### 1.6 Verify the deploy workflow on a real release
**Description:** `deploy-to-wporg.yml` is configured and correct on inspection, but has never fired — there are no GitHub Releases yet, and 1.0.9 reached WP.org by hand.
**Benefit:** Confirms the automation before it is relied on, rather than discovering a broken secret mid-release.
**Priority:** High · **Complexity:** Small · **Milestone:** v1.1.0 · **Status:** Proposed · **Source:** Code Review

Confirm `SVN_USERNAME` / `SVN_PASSWORD` org secrets resolve for this repo, then cut the next release as a tagged GitHub Release and watch the run.

---

## Phase 2: Short-Term Improvements

### 2.1 `placement` attribute on the share shortcode
**Description:** Every shortcode bar currently records `placement = 'shortcode'` (`class-ushb-shortcodes.php:61`). A bar in the sidebar, one in the footer and one mid-content are indistinguishable in analytics. Add `placement="footer"` so each location reports separately and gains a `.ushb--footer` class to style against.
**Benefit:** Answers "which bar is actually working", which is the main reason to look at the analytics screen at all.
**Priority:** Medium · **Complexity:** Small · **Milestone:** v1.1.0 · **Status:** Proposed · **Source:** Previous Discussion

Placement already flows cleanly to three consumers — the default network list, a CSS class and the `data-place` attribute that reaches the analytics table — so this is roughly a ten-line change plus a fallback when the named list does not exist.

---

### 2.2 Per-post override meta box
**Description:** A small box on the post editor to force bars on or off for that post, and optionally pick networks, instead of maintaining comma-separated ID lists in settings.
**Benefit:** The current "Always show on these post IDs" field requires looking up numeric IDs, which is the least pleasant part of the settings screen.
**Priority:** Medium · **Complexity:** Medium · **Milestone:** v1.2.0 · **Status:** Proposed · **Source:** New Suggestion

---

### 2.3 Analytics date-range comparison
**Description:** Show the previous period alongside the current one on the analytics screen, with a direction indicator per network.
**Benefit:** A share total is only meaningful against a baseline; "288 Facebook shares" says nothing without "up from 210".
**Priority:** Medium · **Complexity:** Medium · **Milestone:** v1.2.0 · **Status:** Proposed · **Source:** New Suggestion

---

### 2.4 Analytics CSV export
**Description:** Export the click table for a chosen range.
**Benefit:** Lets people reconcile first-party counts against their own analytics, and keeps data portable if they leave.
**Priority:** Low · **Complexity:** Small · **Milestone:** v1.2.0 · **Status:** Proposed · **Source:** New Suggestion

---

### 2.5 Click-table retention policy
**Description:** The `ushb_clicks` table grows one row per post × network × placement × day and is never pruned. A busy site with 6 networks across 3 placements adds ~18 rows per post per day.
**Benefit:** Prevents slow unbounded growth on high-traffic sites. A "keep detail for N months, then roll up" setting would cap it.
**Priority:** Medium · **Complexity:** Medium · **Milestone:** v1.2.0 · **Status:** Proposed · **Source:** Code Review

Not urgent at current install counts, but cheaper to design now than to retrofit over a large table.

---

## Phase 3: Mid-Term Development

### 3.1 Share-count display from the networks
**Description:** Optionally show real counts where a network still exposes them publicly.
**Benefit:** First-party click counts answer "who clicked my button", not "how many people shared this". Some users expect the latter.
**Priority:** Low · **Complexity:** Medium · **Milestone:** v1.3.0 · **Status:** Proposed · **Source:** New Suggestion

Most networks withdrew public count endpoints years ago, so this would cover a short list and must disclose any external request in `readme.txt`. **Weigh carefully against the plugin's "no outbound requests" positioning** — that claim is currently the main differentiator and should not be given up lightly.

---

### 3.2 Template/theme hooks documentation
**Description:** The plugin already exposes `ushb_networks`, `ushb_share_url`, `ushb_bar_html`, `ushb_context`, `ushb_should_display` and others. None are documented outside the code.
**Benefit:** Developer adoption; also reduces support load from people asking for options that a filter already provides.
**Priority:** Medium · **Complexity:** Small · **Milestone:** v1.3.0 · **Status:** Proposed · **Source:** Code Review

---

### 3.3 Block-editor placement controls
**Description:** Let the block pick its own placement label and override the per-placement network list from the sidebar.
**Benefit:** Brings the block to parity with the shortcode once 2.1 lands.
**Priority:** Low · **Complexity:** Medium · **Milestone:** v1.3.0 · **Status:** Proposed · **Source:** New Suggestion

---

### 3.4 Accessibility audit
**Description:** Run the admin screens and front-end bars through an automated audit plus keyboard-only testing.
**Benefit:** Share buttons are interactive controls on every page of a site; an accessibility defect there is site-wide.
**Priority:** Medium · **Complexity:** Small · **Milestone:** v1.3.0 · **Status:** Proposed · **Source:** New Suggestion

The markup was built with labelled buttons, visible focus rings and reduced-motion support, but this has never been formally verified.

---

## Phase 4: Long-Term Development

### 4.1 Pro edition
**Description:** A separate `unstoppable-share-buttons-pro` repo carrying the Base44 licence class, the updater and paid-only features. The free plugin stays complete.
**Benefit:** Revenue without compromising the free listing, which must contain no licence system or updater.
**Priority:** Medium · **Complexity:** Large · **Milestone:** TBD · **Status:** Proposed · **Source:** Previous Discussion

Candidate Pro features: scheduled share-performance email reports, A/B testing of bar placement, per-author analytics, custom networks UI.

---

### 4.2 A/B testing of placements
**Description:** Serve two bar configurations at random and report which converts better.
**Benefit:** Turns the analytics from descriptive to actionable, and is the kind of capability that distinguishes the plugin in a saturated category.
**Priority:** Low · **Complexity:** Large · **Milestone:** TBD · **Status:** Proposed · **Source:** New Suggestion

---

### 4.3 Site Editor / block-theme integration
**Description:** Register the bar as a template part usable in Site Editor templates, not just in post content.
**Benefit:** Block themes are the default now; the "missing link" the WP.org reviewers pointed at when they flagged category saturation.
**Priority:** Medium · **Complexity:** Large · **Milestone:** TBD · **Status:** Proposed · **Source:** New Suggestion

---

### 4.4 Multisite network settings
**Description:** Network-level defaults that individual sites inherit or override.
**Benefit:** Makes the plugin viable for agencies and institutions running many sites.
**Priority:** Low · **Complexity:** Large · **Milestone:** TBD · **Status:** Proposed · **Source:** New Suggestion

---

## Explicitly rejected

Recorded so they are not re-proposed.

| Idea | Why not |
|---|---|
| **Instagram share button** | Instagram publishes no web share endpoint — there is no URL to send anyone to. The native share button already surfaces Instagram in the device share sheet, which is the only real route. |
| **Custom CSS / JS / PHP field** | Prohibited by WP.org since 2026; WordPress has its own CSS editor. Structured settings generate the CSS instead. |
| **Bundled licence system or self-updater in the free plugin** | Automatic rejection on WP.org. Belongs only in the Pro repo. |
| **Review-request or upgrade admin notices** | Guideline 11 (dashboard hijacking). Removed in 1.0.6. |

---

## Verification status

What has and has not been exercised against real software, so the next session does not have to guess.

| Area | Status |
|---|---|
| Core sharing, all four placements | ✅ Verified on WP 7.0.2 and 7.1.3 |
| All six shapes, six fills, palettes | ✅ Verified by rendered measurement |
| Admin screens and all five tabs | ✅ Verified on WP 7.1.3 |
| Partial tab save (the 1.0.3 bug) | ✅ Regression-tested on WP 7.1.3 |
| `uninstall.php`, both opt paths | ✅ Verified on WP 7.1.3 |
| QR encoder, versions 1–15 | ✅ Decoded with an independent reader |
| Plugin Check / PHPCS | ✅ Clean on WP 7.1.3 |
| Elementor widget | ✅ Registers and renders on Elementor 4.3.5; all 8 controls map through |
| WooCommerce integration | ✅ Verified on WooCommerce 11.2.1 — all 5 positions, product image and description enrichment |
| Editor block | ✅ Renders standalone and in a published post; empty-network fallback graceful |
| AJAX click tracking round trip | ✅ Real browser clicks recorded; aggregates rather than duplicating; bad nonce and unknown network both rejected |
| Settings import / export | ✅ Round-tripped; malformed JSON rejected without damaging stored settings |
| Three plugins active together | ✅ No conflicts, no PHP notices, Plugin Check clean |
| Deploy workflow | ❌ Never fired |
