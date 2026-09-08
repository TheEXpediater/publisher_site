Enterprise1979 Publisher Portal 3.8.14

WordPress owner account
enterpriseenteng@gmail.com

Access rules
1. enterpriseenteng@gmail.com is the only account allowed to see the native WordPress admin interface.
2. Every other logged-in account stays inside the custom Publisher Portal, including other administrator accounts.
3. Portal-only accounts are redirected away from direct WordPress admin URLs.
4. Portal-only accounts see a full-screen custom shell with no WordPress sidebar, toolbar, footer, notices, Screen Options, or Help panel.
5. Portal-only accounts are redirected to the Publisher Portal after login.
6. The front-end WordPress toolbar is hidden for portal-only accounts.
7. The owner account keeps normal WordPress access for plugins, themes, updates, settings, and maintenance.

Installation
1. Back up the current plugin and database.
2. In Plugins, upload this ZIP and replace the existing client-portal folder when prompted.
3. Activate the plugin if WordPress deactivates it during replacement.
4. Sign out and test with a portal account in a private browser window.
5. Sign in as enterpriseenteng@gmail.com and confirm the normal WordPress admin remains visible.

Changing the WordPress owner account
Add this before the WordPress stop-editing line in wp-config.php:

define('CP_WORDPRESS_ACCESS_EMAIL', 'owner@example.com');

The constant overrides the built-in default email.


Version 3.6.2 landing-page card refinement
1. Removes the repeated category badge inside landing-page section cards.
2. Keeps the outer card size, border, padding, grid, and section spacing unchanged.
3. Enlarges card images with a stable 5:4 frame and proportional cropping.
4. Rebalances headline, metadata, excerpt, and Read More spacing.
5. Applies these changes only to category cards on the site landing page.
6. Existing shortcodes remain compatible and do not need replacement.

Version 3.6.3 landing-page refinement:
- Keeps featured story copy and Read More within the featured image height on desktop/tablet.
- Clamps long featured and card text with an ellipsis.
- Equalizes landing-page section card widths and heights.
- Keeps landing images cropped proportionally with object-fit: cover.
- No shortcode changes are required.

Version 3.6.4 featured-image refinement:
- Enlarges the landing-page featured image on wide desktop screens.
- Uses one consistent maximum frame of 380 by 285 pixels with a 4:3 ratio.
- Crops mixed source-image sizes proportionally through object-fit: cover.
- Keeps the featured text and Read More aligned within the same image height.
- Leaves landing cards, archives, article pages, portal screens, and shortcodes unchanged.


Version 3.6.5 landing-page excerpt refinement:
- Lets featured and category-card excerpts use the available copy height before showing an ellipsis.
- Keeps Read More inside the existing image-height boundary.
- Uses longer source excerpts only on the landing page.
- Leaves card sizes, image proportions, archives, article pages, portal screens, and shortcode syntax unchanged.


Version 3.6.6 landing-page responsive refinement:
- Stacks the featured image and article copy cleanly on narrow screens.
- Moves About Us below the featured story on tablet-sized screens.
- Removes fixed landing-card heights on smaller screens and preserves proportional 5:4 images.
- Keeps card titles, metadata, excerpts, and Read More within responsive card bounds.
- Replaces WordPress bracketed excerpt endings with plain three dots on landing-page featured and card excerpts.
- Leaves archive pages, single articles, portal screens, and shortcode syntax unchanged.

Version 3.7.0 article-builder refinement:
- Removed Table from the standalone Content Block menu. Existing table blocks remain supported for backward compatibility.
- Added a Google Docs-style table grid picker to the Paragraph toolbar.
- Replaced the Format dropdown with Font and Font Size controls.
- Added a clean white-page editing state with block controls shown on hover or focus.
- Added safe frontend support for paragraph fonts, font sizes, and inline tables.


Version 3.8.0 administration refinement:
- Adds a desktop collapse control for the custom portal sidebar. The compact state shows navigation icons and stays saved in the browser.
- Keeps the existing mobile slide-out navigation behavior.
- Replaces the Author Publishing switch with a clean, accessible toggle and expands the Preferences panel across the available content row.
- Adds an administrator-only Activity Logs tab under Settings.
- Records successful logins, logouts, article changes, category changes, user changes, settings changes, and homepage-feature changes.
- Adds date-range and action filters, pagination, and a details modal with the affected item and recorded changes.
- Creates the activity-log database table automatically during activation or the first request after an update.


Version 3.8.1 category and administration refinement:
- Keeps category archive article copy within the same height as its proportional 4:3 image on desktop.
- Clamps long category titles and excerpts while anchoring Read More inside the image-height boundary.
- Preserves natural stacked content flow on tablets and phones.
- Reduces the sidebar collapse control visibility until the sidebar or control is hovered or focused.
- Shows 10 activity-log records per page with the existing portal-style pagination.
- Does not require shortcode changes.


Version 3.8.2 published image ratio standardization:
- Renders published article, card, featured, category, related-story, other-story, and search images in an exact 1:1 square frame.
- Uses proportional center cropping with object-fit: cover.
- Applies the square ratio to the single-article hero and article-body image blocks.
- Leaves original Media Library files unchanged.
- Leaves portal administration, article data, queries, text, and non-image functions unchanged.


Version 3.8.3 single-article image sizing refinement:
- Reduces only the single-article featured/hero image to a maximum 700 x 700 px square on desktop.
- Keeps the single-article hero responsive and square on tablets and phones.
- Changes Related Stories thumbnails to the same compact square size used by standard article cards: 78 x 78 px, and 70 x 70 px on narrow phones.
- Leaves homepage, archive, category, search, other-story, article-body, portal administration, publishing logic, queries, and Media Library files unchanged.


Version 3.8.4 single-article Related Stories refinement:
- Moves Related Stories closer to the single-article hero by matching the main grid column to the 700 px hero width.
- Keeps the existing square Related Stories thumbnails unchanged.
- Limits Related Stories headlines to two lines with an ellipsis so text stays within the thumbnail row height.
- Keeps Related Stories copy constrained to the thumbnail height on desktop and phones.
- Leaves homepage cards, category archives, search results, other stories, article-body images, publishing logic, queries, and Media Library files unchanged.


Version 3.8.5 single-article alignment refinement:
- Shifts only the single-article breadcrumb, title/meta header, and article/Related Stories grid slightly to the right on desktop while retaining a clearly left-aligned editorial layout.
- Forces Related Stories thumbnails and their category/title copy to align from the top edge of each row, including rows with short titles.
- Preserves the existing two-line Related Stories title ellipsis and square thumbnail sizing.
- Leaves homepage cards, archives, search, other stories, article-body images, publishing logic, queries, and Media Library files unchanged.


3.8.6
- Added a portal-branded Forgot Password form on the existing publisher login route.
- Password reset requests use WordPress core retrieve_password() and its secure reset tokens.
- Added non-enumerating recovery confirmation and preserved native WordPress reset validation.
- No publishing, article, category, search, media, or front-end story layout logic changed.


3.8.7
- Updates Facebook links in the About Us page and homepage About Us content to https://www.facebook.com/theenterprisehau.
- Adds Full Screen / Exit Full Screen controls to Heading and Paragraph content blocks only.
- Adds an Article Details settings button with a modal editor for the title and excerpt.
- Keeps the WordPress post title and excerpt plain for compatibility while storing safe rich formatting separately for the single-article title and deck.
- Rebuilds Heading blocks as focused rich-text editors with a dedicated H1-H6 level control and formatting toolbar.
- Leaves image sizing, cards, categories, search, publishing permissions, and Media Library behavior unchanged.

3.8.7 Author Article Library refinement
- Author accounts can browse the complete published Article Library instead of seeing only their own posts.
- Draft and private stories remain hidden from Author accounts.
- The Article Library Actions column shows only View for Author accounts.
- Editor and Administrator Article Library behavior remains unchanged.
- Article creation and all unrelated portal features remain unchanged.

3.8.8 Publisher Portal navigation, logout, login-security, and footer refinement
- Replaces the primary navigation's separate "More" dropdown with an About Us dropdown: categories beyond the first 8 now live under About Us itself (About Us stays the fixed final item and still links straight to the About Us page).
- Adds real server-rendered active-navigation state (aria-current + a white underline) for the current category and About Us, replacing the previous permanently gold-colored About Us link. Determined from WordPress's own query context (the category's own frontend Page, the taxonomy archive as a defensive fallback, or a single article's primary category), never guessed client-side or by list position.
- Connects the site footer menu to the same category source, saved order, and typography as the header (includes/nav-menu.php), via the same pre_wp_nav_menu override already used for the header - so a category created, renamed, reordered, or deleted in the Category Manager never needs separate footer maintenance. Previously the footer's menu location had no menu assigned and rendered nothing tied to the plugin's categories.
- Adds a "View Menu" action to the Category Manager: a live preview of the real header/footer navigation, with an Edit Menu mode to drag-and-drop (or use accessible Move Up/Down controls) the category order and choose a shared navigation Font Family/Font Size from the same whitelist already used by the Article Builder. Saved order and typography persist via term-ID-based options so renaming a category never loses its position.
- Fixes the Publisher Portal logout landing on wp-login.php?loggedout=true instead of /publisher-login/: the logout link's requested redirect is now explicitly re-asserted via a scoped logout_redirect filter, independent of whatever was previously causing it to be dropped. Native WordPress/wp-login.php logout behavior elsewhere is unaffected.
- Adds a live, server-timestamp-driven lockout countdown to the Publisher Login screen (mm:ss) and disables the Sign In button for the duration - the existing server-side progressive rate limiting (includes/login-rate-limit.php) already rejected every attempt during a lockout, including a correct password; this only makes the already-enforced cooldown visible and accurate across refreshes.
- No changes to article creation/editing, Article Builder, categories CRUD, Media Library behavior, analytics, activity logs, or role/capability restrictions.

3.8.9 corrective hardening pass
- Adds a missing Remove Photo control to the User Manager's profile-photo picker (previously only Choose/Change existed, with no way to clear a photo back to the default WordPress avatar). Backed by a new cp_remove_user_profile_image() that clears the stored attachment and user meta.
- Wraps the profile-photo upload path (includes/users.php: cp_handle_profile_image_upload(), and its caller in cp_handle_user_save()) in try/catch(\Throwable), so any unexpected PHP error during upload/attachment/metadata handling now returns a normal Publisher Portal warning notice instead of a possible white-screen critical error. Direct source review found no reproducible fatal in this version's upload implementation (it already required the correct wp-admin/includes files and checked every WP API call for WP_Error/failure); this closes the gap between "no defect found by static review" and "cannot fatal," which static review alone cannot guarantee.
- Makes the footer navigation's Astra "footer_menu" placeholder menu assignment reversible: deactivating the plugin now unassigns that location if it still points at the plugin's own auto-managed placeholder menu, so a deactivated plugin doesn't leave an empty menu location wired into the live theme footer.
- No other behavioral changes; this pass is corrective verification of 3.8.8, not a rewrite.

3.8.10 login lockout duration correction
- Fixes the 3rd-failed-login lockout duration: it was 1 minute, which let a correct password through again well inside the required window. The 3rd consecutive failure now locks for 5 minutes.
- Escalation for repeated abuse past the first lock: 6 failures -> 30 minutes, 9 -> 3 hours, 12+ -> 24 hours (was 5 min / 30 min / 3 hours).
- No other change: identifier/IP keying, the separate IP-wide guard, generic non-enumerating error messages, transient-based persistence, nonce handling, and native/owner WordPress authentication are all unchanged.

3.8.11 live bug-fix pass
- Fixes "About Us" wrapping to a second row in the Category Manager's View Menu / Edit Menu preview: the header preview's flex row inherited the live header's flex-wrap: wrap, which is correct at full site width but wrapped in the modal's narrower preview area. Scoped entirely to the admin preview (assets/css/category-menu-editor.css) - forces one row and compacts item padding there only; the live frontend header (assets/css/frontend-navigation.css) is untouched.
- Adds a confirmation step before the menu editor's Save actually persists ("Save menu changes?"), reusing the portal's existing shared confirm dialog (assets/js/app.js gained a small additive cpConfirmAction() API alongside its existing declarative confirm triggers - nothing existing was changed). Cancel leaves Edit Menu mode with unsaved edits intact and sends no request.
- Adds a visible in-modal success/error notice after Save: a green "Menu updated successfully" (using the server's actual response, not assumed client state) or a red "Unable to save menu changes" - and the Save button is disabled for the duration of the request to prevent double submission.
- Login rate limiter: re-traced and source-simulated the full failure #1/#2/#3/locked-attempt/expiration/successful-login sequence against the current code; the 3rd-failure lock (and the same-response countdown) is already correct as of 3.8.10's duration fix. Added targeted debug-log breadcrumbs (includes/login-rate-limit.php, active only when WP_DEBUG is enabled) at the lock check, the failure-count bump, and a same-request lock-write readback verification, so if this remains invisible on a live install after deployment, the actual cause (e.g. a non-persistent object cache silently dropping the transient write) is diagnosable from the PHP error log instead of unobservable. No logic changed in this file beyond the added logging.

3.8.12 category + navigation UX pass
- Fixes a category rename (e.g. WordPress's original "Uncategorized" category renamed to something else) still showing the old name on its frontend category page: the mapped Page's own post_title was set once when the Page was first provisioned and never kept in sync on a later rename. cp_handle_category_save() now syncs the Page title (only the title - never the URL/slug) whenever a rename is detected. The category's term ID, its Page mapping, and every article already assigned to it were never affected by this bug (a rename never changes a term's ID or its post associations) - only the WordPress Page's own displayed title was stale.
- Corrects the header navigation's top-level item count: it was showing 8 categories plus About Us (9 total). The limit is now 7 direct categories + About Us = 8 top-level items total, via the single shared CP_NAV_MAX_VISIBLE_CATEGORIES constant (now 7, was 8) that every renderer (public header, admin preview, menu editor) already read from. Category #8 onward now correctly falls inside the About Us dropdown. The footer is unaffected - it was never subject to this cap and continues to show every active category in a flat row.
- Redesigns Edit Menu into separate Header and Footer tabs, each with its own live preview, Font Family/Font Size, and drag-or-arrow-button reordering - switching tabs preserves the other tab's unsaved edits, and one Save Changes action persists both. Header and footer now have independent saved order/typography (includes/nav-menu.php: new per-surface options), while both still resolve every item through the same "category" taxonomy term IDs - no category data is duplicated. Existing installs are migrated automatically: until a surface's own settings are explicitly saved, it falls back to reading the pre-existing shared order/typography, so nothing resets on upgrade.
- Centers both navigation previews in the Category Manager's View Menu / Edit Menu modal (scoped to the admin preview only; the live public header's own left-aligned-categories/right-pushed-About-Us layout is unchanged).
- Fixes the public footer navigation text rendering gray instead of white - scoped to the plugin's own footer nav (#cp-footer-navigation) so no other footer link on the site is affected; hover/focus/current states remain distinguishable.
- About Us dropdown active state: when a category inside the overflow dropdown (position 8+) is the current page, the toggle now shows a small indicator dot that a child section is active, without ever marking the About Us link itself as aria-current - that remains true only on the actual About Us page.
- Investigated the reported Category Manager "Edit shows Add Category" issue: traced templates/categories.php and assets/js/categories.js end to end (trigger click -> setEditMode() -> title/button text/field population) and found the existing implementation already sets Edit-mode text and populates existing name/slug/description/ID correctly, with no code path found that falls back to Add-mode text while editing. Left unchanged - no defect found to fix.

3.8.13 category confirmation dialog + login limiter diagnostics
- Fixes the category Add/Edit confirmation dialog showing a mismatched Add title and Add button label alongside a correctly Edit-worded message. Root cause: assets/js/categories.js's refreshConfirmMessage() only ever updated the dialog's message attribute (data-cp-confirm) based on the current mode; the title and button-label attributes (data-cp-confirm-title, data-cp-confirm-label) were left exactly as PHP rendered them on the initial page load - which is Add-mode text, since the page normally loads with no category being edited - and were never updated again when switching into Edit mode. All three attributes are now kept in sync with the actual add/edit state together.
- Login rate limiter: re-traced and re-simulated the full failure #1/#2/#3/locked-attempt/correct-password-while-locked/expiration/successful-login sequence once more; found no logic defect (identifier-key derivation is identical across the lock check, the failure increment, and the post-login clear; the 3rd failure produces and returns a lock exactly 300 seconds in the future within the same response). Added explicit lifecycle debug-log breadcrumbs across every branch of cp_process_custom_login() (WP_DEBUG-gated, includes/custom-login.php) - including the nonce-check and empty-credentials early-exit paths - so a live trace can show definitively whether a submission is even reaching the rate limiter at all (e.g. a page cache serving a stale/expired nonce to every visitor would look identical to "the limiter doesn't work" from the outside while never actually running it, and would now be visible as a distinct "REJECTED at nonce check" log line instead).
- No third-party security plugin added; the custom limiter is unchanged in its actual lockout logic.

3.8.14 login rate limiter architecture fix (proven via live SSH diagnostics)
- Fixes the actual proven root cause of the login limiter never visibly locking out on the live site: the failure counter was keyed by normalized-login + REMOTE_ADDR together, and REMOTE_ADDR was observed to change between consecutive requests from the same real visitor on the live Hostinger runtime - silently resetting the counter to 1 before it ever reached 3.
- Splits the limiter into two fully independent tracks: an ACCOUNT limiter keyed only by the canonical account being attempted (never the client address, so it now counts correctly regardless of address instability), and the existing SOURCE/IP guard kept as a separate defense-in-depth limiter. A login is blocked if either is active; the more restrictive expiration is shown.
- Canonicalizes the account key: whether the admin types their username or their email, both now resolve (via get_user_by(), used only internally to derive the limiter key - never exposed in any response) to the same WordPress user ID, so alternating between username and email no longer resets the count. An identifier that matches no account still gets its own stable key, so the limiter works the same way against guessed/fake usernames.
- Account keys are now HMAC-SHA256'd with WordPress's own auth salt (wp_salt()) rather than a plain hash, so a key can't be precomputed from a guessed email address without server-side secrets.
- Renamed the internal key-generation functions for clarity (cp_login_rate_limit_identifier_key() -> cp_login_rate_limit_account_key(), cp_login_rate_limit_ip_key() -> cp_login_rate_limit_source_key()); the public functions custom-login.php calls (cp_login_rate_limit_is_locked(), cp_login_rate_limit_record_failure(), cp_login_rate_limit_clear()) kept the same names and behavior contract.
- Removed the verbose per-request lifecycle diagnostics added in 3.8.13/3.8.13's follow-ups now that the live root cause is proven and fixed; retained only production-appropriate logging (an account lock being created, and a request being blocked by an active lock), gated behind WP_DEBUG as before.
- Verified live on the production Hostinger environment via SSH (deployed, then tested with 3 deliberately wrong-password submissions against the real owner account, cleared afterward): account fail count went 1 -> 2 -> 3 across requests made from this session's own network path (independent of the live site's original REMOTE_ADDR instability), the 3rd failure produced a lock exactly 300 seconds out (data-cp-lockout-until returned in that same response), and a 4th request during the lock was confirmed blocked before wp_signon() (fails count stayed at 3, the existing lock timestamp was preserved unchanged) via both the HTTP response and direct wp-cli transient inspection.
