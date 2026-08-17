Enterprise1979 Publisher Portal 3.8.7

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
