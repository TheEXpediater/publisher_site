CLAUDE.md — The Enterprise 1979 WordPress Plugin Engineering Rules

0. Authority, Role, and Current Production Context

You are the senior WordPress plugin engineer responsible for maintaining and extending the production site:

Site: https://theenterprise1979.news/

Publisher login: https://theenterprise1979.news/publisher-login/

Native WordPress login: https://theenterprise1979.news/wp-login.php

Existing custom application plugin: client-portal

Current known live release before this task: 3.8.14

SSH alias already configured on the developer PC: enterprise-live

Authorized live SSH command: ssh enterprise-live

Hostinger WordPress root: ~/domains/theenterprise1979.news/public_html

Live plugin path: ~/domains/theenterprise1979.news/public_html/wp-content/plugins/client-portal

The custom plugin remains the application layer. WordPress remains the runtime/content platform and Astra/theme infrastructure remains the surrounding shell where already appropriate.

This task is a feature expansion of the existing client-portal plugin, not permission to create a second competing plugin, a replacement theme, a new framework, or a new CMS.

The project instructions in this file are authoritative. Newer sections in this file supersede older requirements that conflict with them.

1. Engineering Conduct

1.1 Think before coding

Before changing implementation:

Read this CLAUDE.md.

Inspect the repository root.

Locate the active extracted client-portal/ directory.

Locate the latest root-level client-portal.zip or current valid plugin ZIP.

Compare the extracted plugin, ZIP, and current source before editing.

Identify only the files involved in:

Categories admin

Page management

frontend navigation

About Us dropdown

frontend Page rendering

Article Builder patterns that can be reused

image/media handling

plugin bootstrap/enqueues

deployment/versioning

Verify the live SSH connection with a harmless read-only command before relying on it.

Do not deeply scan unrelated code.

1.2 Surgical changes

Do not:

modify WordPress core

rewrite the whole plugin

create a second independent page/category/menu system

create a new theme

add React/Vue or another frontend framework

introduce a custom database table unless direct inspection proves WordPress posts/meta/options cannot satisfy the requirement

refactor unrelated authentication, analytics, article CRUD, user management, or login security

break the working 3.8.14 login limiter

hardcode the current category names into navigation logic

copy The ANGELITE's proprietary code, artwork, or branding assets

Prefer:

WordPress page posts

WordPress taxonomy terms

post meta/options

current plugin helpers

current Article Builder UI patterns

current modal/confirmation patterns

WordPress Media Library / attachment IDs where suitable

existing theme/plugin hooks

accessible semantic markup

small reusable helpers

1.3 Goal-driven execution

Every feature requires a concrete verification condition.

Do not say "implemented" because code exists. Verify the actual data flow, generated markup, syntax, packaged ZIP, and—where authorized—live behavior.

2. Source of Truth and Version Discipline

Before implementation:

Inspect the current repository plugin version.

Inspect the latest root ZIP.

Inspect the live plugin version over SSH.

Determine the true current source of truth.

Do not overwrite newer source with an older ZIP.

Known live version at the time these requirements were written is 3.8.14, but verify rather than assume.

This feature set is large enough to justify a minor feature release if the project is still following semantic-style versioning. If current source is exactly 3.8.14, prefer 3.9.0 unless the repository's established release convention clearly requires another patch release.

Synchronize the final version across:

plugin header

CP_VERSION

changelog / README-PORTAL-LOCK.txt

packaged ZIP

3. Safe Local → Package → Live Workflow

Implementation order:

LOCAL REPOSITORY
    ↓
implement
    ↓
static validation / PHP lint where available
    ↓
rebuild client-portal.zip
    ↓
extract + diff against working plugin
    ↓
SSH read-only live preflight
    ↓
backup live plugin + relevant DB state
    ↓
deploy intended plugin build
    ↓
verify live version/checksums
    ↓
run controlled live verification

Do not use production as the first editing workspace unless a production-only diagnostic requires it.

Before live deployment:

make a timestamped backup of the live plugin

if the task will create/update real WordPress Pages or migration options, use WP-CLI to make a reasonable database backup if WP-CLI is available

never expose DB credentials, salts, passwords, SSH private keys, cookies, or authentication tokens in reports

SSH is already authorized through:

ssh enterprise-live

Expected WordPress root:

~/domains/theenterprise1979.news/public_html

Verify this location every session.

4. New Information Architecture: Categories vs Pages

This task introduces a clear separation:

CATEGORIES
= editorial/article classifications
= WordPress `category` taxonomy
= News, Literary, Features, Archives, etc.

PAGES
= standalone institutional/publication content
= WordPress `page` posts
= Home, About Us, Staff, Join the Publication, future Mailbag, etc.

About Us is a Page, not a Category.

The Categories table must not invent or show an "About Us" category.

Do not create an About Us taxonomy term.

Do not store Pages in the category taxonomy.

Do not create a parallel custom page database when WordPress page posts are appropriate.

5. Categories Admin: New Pages Entry Point

The existing Categories screen remains the main category-management view.

In the action area near the existing controls, provide this clear hierarchy:

[ View Menu ]   [ Pages ]   [ Add Category ]

Pages opens the new Page Manager experience.

When viewing Pages, provide an obvious:

← Back to Categories

Only users with the site's administrator-level page-management capability may access or mutate the Pages feature.

Prefer capability checks such as the existing project admin capability or manage_options/appropriate WordPress capability rather than checking a literal role-name string.

Unauthorized users must:

not see the Pages management control

not be able to invoke save/delete AJAX/actions directly

receive a proper authorization failure if they attempt the endpoint manually

Do not weaken existing category permissions.

6. Page Manager

6.1 Page list

The Page Manager must manage real WordPress Pages.

The table should contain at minimum:

Page
Status
Link / View
Actions

Expected behavior:

Page

Display the real current WordPress post_title.

Status

Portal UX can present:

Active
Inactive

Map those safely to WordPress statuses, preferably:

Active → publish

Inactive → draft

Do not invent another independent active-status database unless required.

Link / View

Show a compact View or Preview action.

The actual URL should be available:

as a clickable link

and/or via tooltip/secondary text on hover/focus

Do not display extremely long URLs as the main table content.

Actions

For normal managed Pages:

Edit
Delete

Prefer WordPress Trash (wp_trash_post) rather than immediate permanent deletion.

Require confirmation before deletion.

6.2 Home Page is first

The first row must be the actual site's Home Page.

Home is a protected system Page.

Do not allow the portal to delete the site Home Page.

If the current homepage is not a normal editable Page, do not force-convert it just to satisfy this table. Show the real homepage entry and provide:

View

only safe Edit functionality that matches the existing homepage architecture

Do not destroy the current article-driven homepage.

6.3 About Us parent Page

About Us must be a real WordPress Page and the fixed navigation parent.

It should be visible in the Page Manager.

Treat it as a protected navigation parent:

editable

viewable

not casually deletable while it is the configured About parent

If deletion is ever allowed, require a safe reassignment/migration first. Do not leave navigation with a broken parent URL.

6.4 Managed child Pages

Pages created through this new Page Manager—except system pages such as Home—should be eligible to appear in the About Us dropdown.

Examples:

Staff

Join the Publication

Mailbag

History

future institutional/publication pages

Store page identity/order by WordPress Page ID, not copied titles.

Renaming a Page must automatically update its menu label.

7. Add Page

Provide:

+ Add Page

The create form must include at minimum:

Page Name / Title

URL Slug

Status: Active / Inactive

Page Builder content

Example concept:

Page Name: Mailbag
Slug: mailbag

Generated URL:
https://theenterprise1979.news/mailbag/

Do not ask the administrator to manually type the entire internal site URL.

Generate it from the site URL + sanitized WordPress slug.

Allow the slug to be edited deliberately, but:

sanitize it with WordPress APIs

prevent invalid/reserved values

handle collisions

show the final permalink preview

External URLs belong in Button/CTA elements, not in the Page's internal permalink field.

8. Page Builder: Flexible but Controlled

The administrator should not need Elementor for About/Staff/Join pages.

Create a plugin-owned Page Builder that visually and behaviorally reuses the existing Article Builder patterns wherever practical.

The experience should feel like a simple Canva-style element editor while remaining a normal WordPress application.

8.1 Required builder behaviors

add elements/blocks

reorder via drag and drop

keyboard-accessible Move Up / Move Down controls

edit element settings

duplicate where useful

remove elements

preview

save confirmation

success/error feedback

local pending state until save succeeds

responsive frontend rendering

Do not require pixel-coordinate freeform canvas positioning.

This should be a structured content builder, not a full graphic-design engine.

8.2 Core element types

Implement the smallest useful set:

Heading

Rich Text / Paragraph

Image

Staff Grid

Button / CTA

Divider / Spacer

If the existing Article Builder already has safe reusable typography or rich-text controls, reuse those rather than implementing a second editor stack.

8.3 Typography

Reuse the existing whitelisted font family/font-size system where suitable.

Do not persist arbitrary raw CSS from users.

Sanitize:

font

size

alignment

URL

image attachment ID

alt text

headings

rich text according to WordPress-safe HTML rules

9. Managed Page Storage and Rendering

Prefer WordPress-native storage.

Recommended architecture:

WordPress Page post
    ├── post_title
    ├── post_name
    ├── post_status
    └── post meta
          └── structured Page Builder block data

Use a plugin marker such as appropriate post meta to distinguish plugin-managed Pages from unrelated WordPress Pages.

Do not hijack every Page on the site.

Render only Pages intentionally managed by this plugin.

Use the site's theme shell/header/footer while allowing the plugin to own the Page's main content.

Possible implementation paths include a scoped the_content filter or another existing plugin rendering hook, but inspect the current architecture first and choose the narrowest reliable WordPress extension point.

Do not break:

Elementor pages elsewhere

normal theme pages

homepage article system

WordPress editor content unrelated to this feature

10. About Us Main Page — Seed from Provided Publication Content

The project includes an authoritative document named:

ABOUT US - THE ENTERPRISE.docx

Use its content as the source for the initial About Us / publication copy.

The document contains:

About the Publication

mission and responsible/unbiased journalism

artistic consciousness / Kapampangan culture and literature

democracy, independence, free speech/expression

Brief History

Membership Requirements and Procedures

Join the Publication CTA/link

Do not silently rewrite factual publication history.

Initial recommended split:

/about-us/

Use:

ABOUT THE PUBLICATION

BRIEF HISTORY

Present these as a polished editorial/institutional page.

Use the site's own dark navy/white visual identity.

Keep content readable with generous spacing and editorial typography.

/join-the-publication/

Move/separate:

MEMBERSHIP REQUIREMENTS AND PROCEDURES

JOIN THE PUBLICATION

into its own managed Page.

Use the provided Google Forms link as a Button/CTA.

Do not duplicate the same Join content on both pages after the split unless the user intentionally wants a short teaser on About Us.

This seed operation must be idempotent:

do not create duplicate About Us/Join pages every deployment

identify existing managed Pages by stable Page IDs/meta/known slugs

update only what is intentionally part of the migration

11. Staff Page — Initial Data and Layout

Create a managed Page:

Staff
/staff/

The layout may take inspiration from:

https://theangelite.net/staff/

but must use The Enterprise's own branding, content, markup, assets, and implementation.

Do not copy The ANGELITE's source code or image assets.

The reference demonstrates a publication Staff page divided into staff groups and portrait grids. Use the concept, not the copyrighted implementation.

11.1 Staff data source

The project includes:

MASTERLIST WITH PORTRAITS OF ENTENG.docx

Use the provided names and positions to seed staff cards.

Current supplied roster:

Editorial Board

Maryiel N. Jimenez — Editor-in-Chief

Cyra Joyce G. Aguilar — Associate Editor - Internal Affairs

Jan Nicole A. Mallari — Associate Editor - External Affairs

Paullete Irys G. De Leon — Managing Editor

Editorial Staff

Alexa G. Soriano — Senior Editor

Angel Bethany T. Timbol — Senior Layout Editor

Sean Naegel V. Beltran — Junior Layout Editor

Merella Jesmine I. Gumabon — Literary Editor

Razel Iommi Q. Tiongson — Head Cartoonist

Boris Sebastian C. Lontabo — Head Photojournalist

Mikaella C. de Leon — Executive Secretary

Samantha C. Ruelo — Events Manager

Margareth Lois R. Moleño — Circulation Manager

Shelyca Franshane M. Simeon — Online Content Manager

Kristine Charsi T. Lusanez — Online Content Manager

Reportorial Staff

Rinka Akisha M. Muldong — Reportorial Staff

Pauleene P. Cabigting — Reportorial Staff

Hydelyn Mae C. De Roxas — Reportorial Staff

Allison Louise T. Dulatre — Reportorial Staff

Eugene P. Lee — Reportorial Staff

Yzabel Euri O. Enriquez — Reportorial Staff

Marizz O. Estrella — Reportorial Staff

Shannon Beatriz P. Dela Vega — Reportorial Staff

Juliana Cloe S. Cosme — Reportorial Staff

Keona Alexis M. Arceo — Reportorial Staff

Ferdina Faye M. Bacani — Reportorial Staff

Do not invent a Faculty Adviser or any missing person not present in the provided source.

If the role grouping above conflicts with an explicit grouping already stored in the project, preserve the project's authoritative grouping and report the discrepancy.

11.2 Portraits

The user does not yet have portraits ready for the implementation.

Every seeded staff member should therefore render with a clean neutral human/person placeholder.

Do not use random stock photos.

Each card stores:

name

position

portrait attachment ID (nullable)

image alt text

ordering

group/section

In the Page Builder Staff Grid editor, the administrator must be able to:

select a staff card

upload/select portrait

replace portrait

remove portrait

edit name

edit position

reorder the person

move between staff sections where appropriate

add a person

remove a person

When no portrait exists, preserve the placeholder.

11.3 Staff grid: maximum six portraits per row

Desktop layout rule:

Maximum 6 staff cards/images per row.

The grid must dynamically adapt to the number of cards in each row/section.

Required behavior:

6 cards → six-column full row

5 cards → enlarge cards appropriately so the row uses the available staff-grid width

4 cards → enlarge cards appropriately so the row uses the available staff-grid width

1–3 cards → do not stretch them to huge widths; keep a reasonable card width and center the group

more than 6 → create additional rows, never more than 6 cards in one row

an incomplete final row must be horizontally centered

The visual goal is a balanced, centered pyramid / inverted-pyramid editorial staff composition, with shorter rows centered relative to fuller rows.

Do not accomplish this by hardcoding person names or exact screen coordinates.

Use responsive CSS Grid/Flexbox and data-driven row composition.

Tablet/mobile must reduce columns gracefully.

Portraits must:

have consistent aspect ratio

use object-fit: cover when a real portrait exists

preserve face/photo quality

have consistent card height

not distort images

The Staff Builder preview should match the frontend layout closely.

12. About Us Navigation Becomes a Page Dropdown

12.1 About Us is fixed top-level Page link

Header top-level navigation retains the existing maximum of 8 top-level items total:

up to 7 direct Categories + ABOUT US

About Us is always the final top-level item.

About Us itself remains a real clickable link to the actual About Us Page.

Use separate accessible dropdown-toggle behavior so opening the submenu does not destroy the About Us link.

Recommended semantic structure:

<li class="...">
    <a href="/about-us/">ABOUT US</a>
    <button aria-expanded="false" aria-controls="...">...</button>
    <ul>...</ul>
</li>

Do not make the parent link hover-only.

12.2 Pages in About Us dropdown

Every active plugin-managed child Page intended for About navigation should automatically appear in the About Us dropdown by its current Page title/order.

Examples:

ABOUT US
├── Staff
├── Join the Publication
├── Mailbag
└── future managed Pages

Home must never appear inside About Us.

Inactive/draft Pages must not appear publicly.

About Us itself should not appear as a duplicate child of itself.

12.3 Existing overflow Categories

The current site previously placed Categories beyond direct category position 7 into the About Us dropdown to preserve the 8-top-level-item rule.

Do not silently delete or lose this existing behavior.

If more than 7 active navigation Categories exist, keep their overflow available inside the same dropdown, but separate them semantically/visually from About Pages.

Preferred model:

ABOUT US ▼
    PAGES
      Staff
      Join the Publication
      ...

    MORE SECTIONS
      Category 8
      Category 9
      ...

The labels PAGES / MORE SECTIONS may be visually subtle and may be omitted if the current design communicates grouping clearly, but Pages and overflow Categories must not become one ambiguous flat data source internally.

The data remains separate:

Page IDs for Pages

term IDs for Categories

Do not create a fake About Us category to make the dropdown easier.

13. Page Ordering

The Page Manager must support ordering of About dropdown Pages.

Use stable WordPress Page IDs.

Possible storage:

WordPress menu_order if it cleanly fits the architecture

or one small plugin option containing ordered managed Page IDs

Do not persist authoritative page titles in the ordering record.

On rename:

order remains

label automatically changes

On delete/trash:

remove stale ID from the About navigation order

On new Page:

append deterministically unless administrator reorders it

Provide drag/drop plus keyboard Move Up/Move Down controls.

14. Page Status and Navigation Rules

Active:

post_status = publish

Inactive:

post_status = draft

Only active Pages appear publicly in the About dropdown.

Page Manager must clearly show the state.

Editing an inactive Page must provide Preview where WordPress permissions allow.

Changing status requires:

nonce

administrator capability

confirmation where appropriate

success/error feedback

15. Page Deletion Rules

Normal managed Page:

use Trash

confirm first

remove from navigation automatically

do not delete media attachments merely because the Page was trashed unless the project has a deliberate media-cleanup policy

Protected Pages:

Home: never delete from this portal

About Us parent: do not allow deletion while configured as the navigation parent

Staff / Join / future child pages may be trashed by an authorized administrator.

If Staff is deleted and recreated, do not duplicate stale menu IDs.

16. Page Builder Media Safety

For image elements and staff portraits:

use WordPress attachment IDs

validate that selected attachments are images

validate MIME/type via WordPress APIs

enforce nonce + capability

use Media Library where practical

enqueue media scripts only on the relevant portal Page Builder screen

do not accept arbitrary filesystem paths from the browser

do not expose server paths

escape alt text

provide Replace and Remove controls

A missing/broken image must never fatal the page.

Use a neutral placeholder for missing staff portraits.

17. Frontend Visual Direction

The public Page templates should look native to The Enterprise site, not like Elementor defaults.

Use:

the existing dark navy identity

white/neutral surfaces

editorial serif/sans hierarchy already present in the project

strong page title

clean section dividers

responsive spacing

accessible contrast

centered staff compositions

restrained motion

The reference site is visual inspiration only.

Reference patterns observed from The ANGELITE include:

About navigation containing multiple institutional pages

a Staff page with sections such as Editorial Board, Editorial Staff, and Reportorial Staff

standalone pages such as Mailbag and Join

simple editorial page titles and content hierarchy

Do not copy its CSS, PHP, images, logos, text, or exact layout measurements.

18. Admin UX: Page Manager and Builder

18.1 Pages table toolbar

Expected:

[ ← Back to Categories ]                         [ + Add Page ]

Within Categories main view:

[ View Menu ]   [ Pages ]   [ Add Category ]

18.2 Edit Page

Edit Page should open the Page Builder with:

title

slug/permalink preview

status

block canvas

preview

Save Changes

Cancel/Back

18.3 Confirmation

Create:

Create Page
Create this page?
[Cancel] [Create Page]

Edit:

Update Page
Save these page changes?
[Cancel] [Save Changes]

Delete:

Delete Page
Move this page to Trash?
[Cancel] [Delete Page]

Do not reuse stale Add-mode text in Edit confirmations.

18.4 Feedback

Success examples:

Page created successfully.

Page updated successfully.

Page moved to Trash.

Page order updated.

Failures must show a real error and must not optimistically claim success.

Prevent double submission.

19. Initial Seed / Migration Requirements

After the Page system is implemented, provide an idempotent migration/seed routine that can create or connect the initial managed Pages only when needed.

Target initial set:

Home — connect to existing site homepage; never duplicate

About Us — real Page

Staff — new managed Page

Join the Publication — new managed Page

Do not create Mailbag unless content is supplied or the administrator adds it.

Seed About Us and Join content from ABOUT US - THE ENTERPRISE.docx.

Seed Staff cards from MASTERLIST WITH PORTRAITS OF ENTENG.docx.

All staff portraits initially use placeholders unless an actual supplied WordPress image/attachment is available.

The seed must be safe to run twice without creating duplicates.

20. Existing Category and Article Behavior Must Remain Intact

Do not break:

category CRUD

category rename syncing

category article associations

article archive/list pages

article builder

article publishing

category frontend renderer

active navigation state

footer navigation

menu font/order editor

login limiter

logout redirect

password reset

user avatars

analytics

A Page is not an Article Category.

A Page should not require an Article to exist before it renders.

21. Header and Footer Navigation

The header remains:

up to 7 direct categories + ABOUT US

About Us is a Page parent/dropdown.

Footer remains separately configurable using the existing footer navigation model unless this implementation directly requires Page links there.

Do not automatically dump every About Page into the footer unless the existing UI or explicit requirement calls for it.

Footer link text must remain white as already fixed.

22. Accessibility

All new admin and frontend controls must support:

keyboard navigation

visible focus

semantic headings

real links

buttons for actions/toggles

aria-expanded for dropdown toggles

labels for inputs

accessible confirmation dialogs

meaningful image alt text

no hover-only essential interaction

touch-friendly drag alternatives

Move Up / Move Down controls for reorderable items

Staff placeholder icon should be decorative if the person's name already labels the card; do not create redundant screen-reader noise.

23. Security

All write operations require:

logged-in user

appropriate administrator capability

nonce verification

sanitization

validation

safe redirects

escaped output

Use:

wp_insert_post() / wp_update_post()

wp_trash_post()

get_permalink()

sanitize_title()

esc_url()

esc_html()

wp_kses_post() where rich HTML is intentionally allowed

attachment APIs for images

Do not allow arbitrary PHP/HTML/JS execution from Page Builder blocks.

No raw <script> blocks.

No arbitrary CSS injection.

No iframe unless explicitly whitelisted for a future requirement.

24. SSH / Hostinger Verification

At the start of implementation, confirm:

ssh enterprise-live

Then harmless read-only checks such as:

pwd
cd ~/domains/theenterprise1979.news/public_html
wp core version
wp plugin get client-portal --field=version
php -v

Use the exact available WP-CLI commands; do not fail the task merely because a specific convenience command is unavailable.

Before modifying production:

verify exact live plugin path

verify current version

backup

avoid printing secrets

After deployment:

verify version

compare checksums for changed files where useful

run PHP lint on changed PHP files on Hostinger if PHP CLI is available

verify the plugin remains active

verify frontend/admin routes respond without fatal errors

Do not leave WP_DEBUG enabled simply for convenience after the task if it was enabled only for diagnostics. Determine the current intended production setting first.

25. Reference Inspection

Use these only as UX/content-architecture references:

https://theangelite.net/staff/

https://theangelite.net/mailbag/

https://theangelite.net/

Observe:

About submenu architecture

publication staff grouping

centered staff portrait presentation

standalone institutional pages

editorial visual hierarchy

Do not clone code or branded assets.

The Enterprise's own supplied documents are authoritative for its factual content.

26. Verification Matrix

26.1 Categories / Pages separation

Verify:

About Us absent from category taxonomy UI
Pages tab visible only to authorized admin
Home listed first
About Us listed as Page
Staff listed as Page
Join the Publication listed as Page

26.2 Add Page

Verify:

Title sanitizes correctly
Slug sanitizes correctly
Generated permalink is correct
Draft does not appear publicly
Published Page appears where configured

26.3 About dropdown

Verify:

Home never appears in About dropdown
About parent link remains clickable
Staff appears when published
Join appears when published
Draft Page disappears
Renamed Page updates label
Trashed Page disappears

If >7 categories:

Categories 1–7 direct
About Us remains 8th top-level item
Category 8+ remains accessible in overflow group
About Pages remain accessible in their own group

26.4 Staff layout

Verify desktop source/render logic for:

1 portrait  → centered, not stretched across entire width
2 portraits → centered, not stretched across entire width
3 portraits → centered, not stretched across entire width
4 portraits → larger balanced row
5 portraits → larger balanced row
6 portraits → max six-column row
7 portraits → 6 + centered 1
10 portraits → 6 + centered/adaptive 4
11 portraits → 6 + adaptive 5
12 portraits → 6 + 6

No row may exceed six cards.

Verify responsive tablet/mobile behavior.

26.5 Staff editing

Verify:

placeholder

upload

replace

remove

rename

position edit

reorder

add

remove person

save

cancel

frontend matches saved state

26.6 About content

Verify:

About the Publication content comes from supplied document

Brief History is preserved

Join content is separated appropriately

Google Forms CTA works

no factual text is silently invented

26.7 Regression

Verify:

publisher login

3-failure lockout remains functional

Categories CRUD

Article CRUD

category frontend pages

existing navigation

footer

user portal

logout

27. Validation

Run available checks.

Local:

node --check <every changed JS file>

If local PHP CLI exists:

php -l <every changed PHP file>

Hostinger:

php -v
php -l <every deployed changed PHP file>

Use WP-CLI for safe inspection/testing where useful.

Check for:

PHP fatal errors

duplicate functions

duplicate hooks

duplicate admin screens

nonce/capability gaps

invalid JSON/meta parsing

stale asset versioning

accidental hardcoded URLs

orphan Page IDs

duplicate seeded Pages

28. Packaging

After implementation:

Delete/recreate client-portal.zip.

Package only the intended plugin.

No .git.

No temporary backups.

No debug logs.

No private keys.

No server config secrets.

Extract ZIP into a clean temp directory.

Run diff -rq against the intended working client-portal/.

Expected: zero source differences.

Then deploy through the established SSH workflow after backup.

29. Definition of Done

The task is not complete until:

About Us is a real Page, not a category

Pages admin exists beside Categories controls

only authorized admin can manage Pages

Home is first and protected

Page table has status/link/actions

Add Page works

Page Builder works

About Us, Staff, and Join are created/connected idempotently

Staff roster is seeded from the provided masterlist

missing portraits show a human placeholder

Staff Grid never exceeds six cards per row

1–3 cards center without excessive stretching

4–5 cards adapt larger

incomplete rows center

About dropdown automatically contains active managed Pages

About parent remains a real link

overflow Categories remain available without becoming Pages

page ordering persists by IDs

page rename updates menu automatically

draft/trash removes Page from public dropdown

article/category systems still work

login limiter still works

PHP/JS validation passes

ZIP matches source

live deployed version matches local

live smoke tests pass

30. Final Report Format

Return:

PAGES + ABOUT + STAFF FEATURE RELEASE COMPLETE

### Architecture
- previous version:
- final version:
- local plugin path:
- live plugin path:
- WordPress Page storage:
- builder storage:
- page-order storage:

### Admin Pages
- Pages entry point:
- capability:
- Home behavior:
- About behavior:
- Add Page:
- status mapping:
- link/view:
- delete/trash:

### Page Builder
- elements:
- reorder:
- media:
- validation:
- confirmation/feedback:

### About Us
- content source:
- About Page:
- Join Page:
- CTA:
- duplicate prevention:

### Staff
- staff source:
- staff count:
- groups:
- placeholders:
- max cards per row:
- 1–3 behavior:
- 4–5 behavior:
- 6 behavior:
- responsive behavior:
- portrait editing:

### Navigation
- About is Page, not Category:
- top-level rule:
- managed Pages dropdown:
- overflow Category behavior:
- active state:
- accessibility:

### Security
- capability:
- nonces:
- sanitization:
- protected Pages:
- raw HTML/JS injection prevented:

### Validation
- local JS:
- local PHP:
- Hostinger PHP:
- WP-CLI:
- regression checks:
- browser/live checks actually performed:

### Packaging / Deployment
- backup:
- ZIP:
- ZIP diff:
- deployed version:
- checksum/source match:

### Remaining Issues
Only list genuinely unresolved or unverified items.

Do not claim a browser behavior was verified unless it was actually observed in a browser or equivalent live-render test.

31. Non-Negotiable Final Constraints

Never:

create an About Us category

create duplicate WordPress Pages on repeated deployment

expose SSH/private keys

modify WordPress core

replace the existing plugin with a new plugin

replace Astra/theme unnecessarily

require Elementor for these managed Pages

hardcode current navigation category names

store Page order by display name

allow Home deletion

break the 3.8.14 login limiter

log passwords

use spoofable proxy headers as account identity

copy The ANGELITE's code/assets

report success without verification

Always prefer:

existing plugin

WordPress Pages

WordPress category taxonomy

stable IDs

attachment IDs

existing Article Builder UI patterns

reusable safe helpers

local-first implementation

versioned ZIP

SSH backup/deploy/verification

accessible responsive rendering