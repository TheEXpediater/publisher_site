# CLAUDE.md — The Enterprise 1979 WordPress Plugin Development Rules

## 0. Primary Objective

You are acting as a **senior WordPress plugin engineer** responsible for maintaining an existing production-style WordPress site:

- Site: `https://theenterprise1979.news/`
- Publisher login: `https://theenterprise1979.news/publisher-login/`
- WordPress core login endpoint: `https://theenterprise1979.news/wp-login.php`
- The plugin is the **application layer** for the site's content and admin functionality.
- WordPress/theme should remain the surrounding shell where possible: global upper area, footer, WordPress runtime, and required theme infrastructure.
- Do not replace the existing architecture with a new framework.

Your job is to make **small, targeted, production-safe changes** to the existing implementation.

---

# 1. REQUIRED WORKING STYLE — KARPATHY GUIDELINES

Use the Karpathy-style rules from:

`https://github.com/multica-ai/andrej-karpathy-skills`

If the Claude Code plugin is already installed, use it. If it is not installed and Claude Code permits plugin commands, the recommended source is:

```text
/plugin marketplace add forrestchang/andrej-karpathy-skills
/plugin install andrej-karpathy-skills@karpathy-skills
```

The project instructions below are the authoritative task-specific rules.

### Think Before Coding

- Do not guess the architecture.
- Identify where the plugin currently handles:
  - publisher login
  - forgot-password/reset flow
  - categories
  - article/content rendering
  - admin category management
  - frontend navigation/header
  - WordPress/theme integration
- Surface uncertainty internally before making structural changes.
- Prefer the smallest existing extension point over creating a new subsystem.

### Simplicity First

- Implement only what this task requires.
- Do not introduce a new framework, router, template system, CSS framework, database layer, or abstraction unless the existing project truly requires it.
- Do not duplicate functionality already present in the plugin or WordPress.
- Reuse existing category queries, rendering helpers, assets, hooks, and data structures.

### Surgical Changes

- Do not refactor unrelated files.
- Do not reformat the repository.
- Do not rename existing classes/functions/files unless required.
- Do not modify WordPress core files.
- Do not delete existing functionality just because a cleaner implementation is possible.
- If a change creates an unused import/function/variable, clean up only that orphan.
- Preserve the project's existing conventions.

### Goal-Driven Execution

Every change must have a concrete verification condition.

Example:

```text
1. Locate the active reset flow → verify the actual redirect path.
2. Trace category storage → verify the frontend can query the same source.
3. Implement custom navigation → verify category additions automatically appear.
4. Implement reset redirect → verify success returns to /publisher-login/.
5. Run PHP/static checks → verify no syntax errors or broken references.
```

Do not declare success merely because code was written.

---

# 2. FIRST ACTION — SCAN THE PROJECT STRUCTURE

This is an implementation task, but you MUST perform an initial **targeted structural scan** before editing.

Do not burn tokens by deeply reading every file.

Start with:

```text
1. Read the root CLAUDE.md / project instructions if one already exists.
2. List the repository root and major directories.
3. Locate the active custom plugin.
4. Locate theme/template files involved in the site's outer shell.
5. Locate the newest ZIP file in the repository root.
6. Locate the plugin's login, password-reset, category, navigation/header, and frontend rendering code.
7. Determine which files are actually loaded at runtime.
```

Use filename search / grep / ripgrep first.

Prioritize:

```text
*.php
*.css
*.js
*.json
*.zip
functions.php
header.php
footer.php
style.css
plugin bootstrap/main files
admin files
includes/
templates/
views/
assets/
```

Do NOT spend time reading unrelated documentation, vendor code, WordPress core, node_modules, cache folders, uploads, or generated files unless they are directly relevant.

---

# 3. LATEST ZIP IS THE CURRENT SOURCE OF TRUTH

The repository root contains a **latest plugin ZIP** which may be newer than the currently extracted plugin code.

Before changing implementation:

```text
1. Find all root-level ZIP files.
2. Determine which one is the latest/current plugin package using filename, timestamp, and contents.
3. Inspect the ZIP structure.
4. Compare its plugin contents against the currently extracted plugin.
5. Treat the newest valid plugin ZIP as the preferred implementation source.
```

The task is NOT to blindly overwrite the repository.

Instead:

```text
- Extract the ZIP into a temporary working directory.
- Inspect its plugin structure.
- Compare files before replacing anything.
- Merge/copy the latest implementation into the active project location.
- Preserve any repository-only configuration or development files.
- Do not overwrite unrelated site files.
```

After the merge, verify that the project is actually using the updated plugin code.

If multiple ZIPs exist and there is genuine ambiguity about which one is latest, inspect their timestamps and contents rather than guessing.

---

# 4. IMPORTANT ARCHITECTURE RULE

The custom plugin owns the site's **content/application behavior**.

WordPress/theme should remain the surrounding shell only where that is currently the architecture.

Current intended separation:

```text
WordPress runtime/theme shell
    ├── global/upper site area where still required
    └── footer where still required

Custom plugin
    ├── publisher/admin functionality
    ├── category management
    ├── article/content output
    ├── frontend application views
    ├── custom publisher login/reset behavior
    └── replacement frontend navigation
```

Do NOT move the entire site into a custom theme.

Do NOT rewrite the plugin to become a theme.

Do NOT replace WordPress authentication internals unnecessarily.

---

# 5. PASSWORD RESET / FORGOT PASSWORD — REQUIRED FIX

## Current problem

The custom publisher login page is:

```text
https://theenterprise1979.news/publisher-login/
```

The current forgot-password process eventually returns the user to:

```text
https://theenterprise1979.news/wp-login.php
```

That is incorrect for this site's custom publisher experience.

## Required behavior

The complete flow must remain:

```text
Publisher Login
    ↓
Forgot Password
    ↓
WordPress password reset email
    ↓
Reset password
    ↓
Successful reset
    ↓
https://theenterprise1979.news/publisher-login/
```

The WordPress core endpoint may still be used internally for the secure reset token workflow. Do NOT modify WordPress core.

The important requirement is that the user-facing post-reset destination returns to:

```text
/publisher-login/
```

## Implementation rules

First trace the current implementation.

Check:

```text
- custom login page/form
- lost password handler
- reset key generation
- reset URL
- reset-password handler
- password reset success handling
- login_redirect / lostpassword_redirect / related WordPress filters
- plugin redirects
- JavaScript redirects
- query parameters used by the plugin
```

Use the correct WordPress hooks/extension points where applicable.

Prefer an explicit, narrow redirect for this custom publisher flow rather than globally breaking normal WordPress login/reset behavior.

The implementation must not:

- disable secure password reset tokens
- expose reset keys
- bypass WordPress password validation
- change unrelated administrator login behavior
- redirect normal WordPress users unexpectedly
- modify `wp-login.php`

## Verification

Test all relevant paths.

At minimum verify:

```text
A. Publisher forget-password starts correctly.
B. Reset email is generated.
C. Reset link remains valid.
D. New password can be saved.
E. Successful reset redirects to /publisher-login/.
F. Direct access to normal WordPress login still behaves normally unless intentionally scoped.
G. Invalid/expired reset links are still handled by WordPress.
```

---

# 6. CUSTOM FRONTEND NAVIGATION

## Goal

Create a new frontend navigation/header component similar in visual structure to the provided reference image.

The reference style is approximately:

```text
---------------------------------------------------------
                    SITE LOGO / BRAND
---------------------------------------------------------
NEWS   LITERARY   FEATURES   ARCHIVES   OPINION   SPORTS
                         ...                         ABOUT US
---------------------------------------------------------
```

Visual direction:

- very dark navy / near-black navy background
- thin light divider/border
- compact editorial/newspaper appearance
- uppercase navigation labels
- centered branding area above the menu
- clean horizontal desktop layout
- responsive behavior on smaller screens
- no dependency on the WordPress default navigation menu

Do not reproduce copyrighted artwork or branding assets from another site. Reuse this project's own logo/assets where available.

---

# 7. CATEGORY-DRIVEN NAVIGATION — CORE REQUIREMENT

The frontend menu must be generated from the **same category data/source used by the plugin's admin category management**.

This is critical.

Do NOT create a separate hardcoded category list.

First identify:

```text
- category database/storage
- category model/class
- category CRUD/admin screen
- category IDs/slugs
- category ordering logic
- category URL generation
```

Then reuse that existing source.

## Required behavior

Whenever an administrator creates a new category in the existing plugin admin:

```text
Category created in Admin
        ↓
Existing category data source
        ↓
Frontend navigation automatically includes the category
        ↓
Clicking category opens its real category page
```

No manual menu editing should be necessary.

---

# 8. MENU ORDER AND LIMITS

The navigation rules are:

```text
- Show a maximum of 8 dynamic categories directly in the main navigation.
- If there are more than 8 categories, the remaining categories must be placed in a dropdown.
- "About Us" is always the final item on the right side.
- About Us must not be displaced by dynamic category additions.
```

Preferred layout:

```text
[CAT 1] [CAT 2] [CAT 3] [CAT 4] [CAT 5] [CAT 6] [CAT 7] [CAT 8] [MORE ▼] [ABOUT US]
```

When fewer than 8 categories exist:

```text
[CAT 1] [CAT 2] [CAT 3] ... [ABOUT US]
```

When more than 8 exist:

```text
[CAT 1] ... [CAT 8] [MORE ▼] [ABOUT US]
                           └── remaining categories
```

Use an appropriate dropdown label such as:

```text
MORE
```

unless the existing project already has an established label.

"About Us" is a fixed final navigation item linked to the project's existing About Us page.

Do not create an "About Us" category unless the project already does so.

---

# 9. CATEGORY ORDER

Do not invent a new ordering system if the plugin already has one.

Use, in order of preference:

```text
1. Existing admin-defined category/menu order.
2. Existing plugin query ordering.
3. Existing WordPress taxonomy term ordering, if that is the current source.
4. Only as a last resort, a deterministic fallback such as name ASC.
```

Document the actual source/order discovered in the implementation notes or code comments only where useful.

---

# 10. CATEGORY LINKS

Each category navigation item must use the project's actual category URL mechanism.

Do not manually concatenate URLs if the plugin already has a route/helper.

The resulting menu item must open the same category page currently used elsewhere in the application.

Verify:

```text
Category name
    ↓
Correct slug/ID
    ↓
Correct frontend URL
    ↓
Correct category page
    ↓
Correct content filtering
```

---

# 11. REPLACING THE CURRENT WORDPRESS MENU

The new navigation should replace the existing WordPress/theme navigation that currently appears in the site's menu position.

First determine whether the current menu is generated by:

```text
wp_nav_menu()
theme header.php
custom walker
theme hook
plugin hook
widget
block/navigation system
```

Preferred approach:

```text
- remove/disable only the relevant existing navigation output
- insert the plugin-owned navigation through an existing hook/extension point
- preserve the rest of the WordPress shell
```

Do not delete the entire theme header if only the menu needs replacement.

Do not duplicate both navigation systems.

After implementation, verify that only one visible primary navigation exists.

---

# 12. RESPONSIVE NAVIGATION

The desktop reference is the main target, but the new navigation must degrade cleanly on smaller widths.

At minimum:

```text
Desktop:
horizontal categories + About Us on right

Tablet:
allow compact spacing or controlled wrap/dropdown

Mobile:
use a simple accessible menu/dropdown rather than overflowing horizontally
```

Do not install a frontend framework just for responsive navigation.

Use the project's existing CSS approach.

---

# 13. ACCESSIBILITY AND WORDPRESS SAFETY

The navigation must use normal semantic HTML:

```text
<nav>
<ul>
<li>
<a>
<button> (where appropriate for dropdown controls)
```

Ensure:

- keyboard accessibility
- visible focus states
- dropdown can be used without hover-only dependency
- links remain real links
- no unsafe HTML output from category names
- escape dynamic labels/attributes correctly
- use WordPress escaping functions where appropriate
- use nonces/capability checks for admin actions if touched

Dynamic category labels must be escaped before output.

---

# 14. PERFORMANCE / TOKEN-EFFICIENCY RULES

Do not repeatedly rescan the whole repository.

After the initial architecture scan:

```text
1. Identify the small set of relevant files.
2. Read only those files deeply.
3. Make surgical changes.
4. Re-check only the affected dependency paths.
5. Run focused verification.
```

Avoid repeatedly printing large files.

Avoid opening vendor, cache, generated, or unrelated code.

Do not paste massive logs back into the working context when a concise summary is enough.

---

# 15. IMPLEMENTATION PLAN FOR THIS TASK

Follow this sequence:

## Phase 1 — Understand

```text
- Read project instructions.
- Inspect repository structure.
- Locate latest ZIP.
- Inspect plugin architecture.
- Identify reset flow.
- Identify category source.
- Identify current navigation source.
```

Verify before coding:

```text
You can name the exact files/classes/functions responsible for:
- password reset
- categories
- current navigation
- plugin bootstrap
```

## Phase 2 — Synchronize Latest ZIP

```text
- extract latest plugin ZIP to temporary location
- compare with active plugin
- merge current implementation
- verify plugin bootstrap still loads
```

## Phase 3 — Fix Password Reset

Implement the narrowest correct redirect so successful publisher password resets return to:

```text
https://theenterprise1979.news/publisher-login/
```

Verify reset flow.

## Phase 4 — Implement Dynamic Navigation

Create the plugin-owned navigation.

Requirements:

```text
- category-driven
- maximum 8 visible dynamic categories
- overflow categories under More dropdown
- About Us always last
- correct category URLs
- no hardcoded category names except fixed About Us
- visually consistent with the provided reference
- responsive
- accessible
```

## Phase 5 — Replace Existing Menu

Disable/remove only the existing primary WordPress menu output.

Keep the rest of the WordPress shell intact.

## Phase 6 — Verify

Run appropriate checks:

```text
- PHP syntax/lint checks
- existing project tests if available
- WordPress/plugin-specific checks if available
- search for duplicate navigation output
- search for remaining incorrect reset redirects
- inspect affected templates/hooks
```

If a local WordPress installation/browser testing tool is available, use it.

---

# 16. SUCCESS CRITERIA

The task is complete only when all of these are true:

### Password reset

```text
[ ] Publisher forgot-password flow works.
[ ] Reset email still uses WordPress-secure reset mechanics.
[ ] New password saves successfully.
[ ] Successful password reset returns to /publisher-login/.
[ ] No WordPress core files were modified.
[ ] Normal WordPress authentication is not unintentionally broken.
```

### Navigation

```text
[ ] Existing WordPress primary menu is no longer the site's visible primary navigation.
[ ] New plugin-owned navigation is visible.
[ ] Categories come from the existing admin category source.
[ ] Adding a category in admin automatically makes it available in navigation.
[ ] Maximum 8 categories are shown directly.
[ ] Additional categories appear inside More dropdown.
[ ] About Us is always the final right-side item.
[ ] Category links point to the real category pages.
[ ] Dynamic labels are safely escaped.
[ ] Navigation is responsive and keyboard-accessible.
```

### Project integrity

```text
[ ] Latest ZIP implementation was inspected and synchronized.
[ ] No unrelated refactoring was performed.
[ ] No WordPress core modifications were made.
[ ] No duplicate navigation exists.
[ ] PHP syntax is valid.
[ ] Relevant tests/checks pass.
```

---

# 17. REPORTING FORMAT AFTER IMPLEMENTATION

At the end, provide a concise implementation report:

```text
IMPLEMENTED
- Files changed:
- Latest ZIP synchronized:
- Password reset redirect:
- Navigation source:
- Category ordering:
- Category overflow behavior:
- About Us behavior:
- Existing menu replacement point:
- Verification performed:
- Remaining issues (if any):
```

Do not claim a browser/UI behavior was verified unless it was actually tested.

If something cannot be verified locally, state exactly what was checked and what remains unverified.

---

# 18. NON-NEGOTIABLE CONSTRAINTS

Never:

```text
- modify WordPress core
- rewrite the entire project
- replace the plugin with a new architecture
- hardcode the current category names
- create a second independent category system
- create a second navigation system alongside the old one
- break existing publisher/admin routes
- weaken password reset security
- silently change unrelated behavior
- perform broad cleanup unrelated to this task
```

Always prefer:

```text
existing plugin data
existing plugin helpers
existing WordPress hooks
existing theme/plugin integration points
existing CSS/assets
small verified changes
```
