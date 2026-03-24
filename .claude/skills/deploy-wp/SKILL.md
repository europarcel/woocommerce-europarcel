---
name: deploy-wp
description: >
  Deploy the EuroParcel plugin to WordPress.org SVN repository. Handles version
  bumping, trunk sync, tagging, and SVN commit. Use when user says "deploy",
  "push to wordpress", "publish plugin", "svn deploy", or "release".
allowed-tools: Read, Grep, Glob, Bash, Edit, Write
user-invocable: true
argument-hint: "[version]"
---

# Deploy EuroParcel Plugin to WordPress.org

Deploy the current plugin code to WordPress.org SVN repository.

## Prerequisites

- SVN must be installed (`brew install svn` if missing)
- WordPress.org SVN credentials:
  - Username: `europarcelcom`
  - Password: stored in environment or prompted at commit time (SVN application password)

## Steps

### 1. Determine Version

If `$ARGUMENTS` contains a version number, use that. Otherwise, read the current version from `europarcel-com.php` (the `EUROPARCELCOM_WC_VERSION` constant) — that should already be bumped before deploying.

Confirm with the user what version is being deployed.

### 2. Verify Version Consistency

Check that the version number is consistent across ALL these files:
- `europarcel-com.php` (both the header `Version:` and `EUROPARCELCOM_WC_VERSION` constant)
- `README.txt` (`Stable tag:`)
- All files in `includes/` (docblock `@since` tags)
- All files in `assets/js/` and `assets/css/`
- `languages/europarcel-com-ro_RO.po` (`Project-Id-Version`)
- `uninstall.php`

If versions are inconsistent, alert the user and stop.

### 3. Verify Changelog

Check that `README.txt` has a changelog entry and upgrade notice for the version being deployed. If missing, alert the user and stop.

### 4. Regenerate Translation Binary

```bash
msgfmt -o languages/europarcel-com-ro_RO.mo languages/europarcel-com-ro_RO.po
```

### 5. SVN Checkout

```bash
cd /tmp
rm -rf europarcel-svn
svn checkout https://plugins.svn.wordpress.org/europarcel-com/ europarcel-svn --depth immediates
cd europarcel-svn
svn update trunk --set-depth infinity
```

### 6. Sync Files to Trunk

Use rsync to copy plugin files, excluding dev artifacts:

```bash
rsync -av --delete \
  --exclude='.svn' \
  --exclude='.git' \
  --exclude='.gitignore' \
  --exclude='.DS_Store' \
  --exclude='CLAUDE.md' \
  --exclude='.claude' \
  --exclude='*.sh' \
  --exclude='*.log' \
  --exclude='node_modules' \
  --exclude='europarcel-svn' \
  /Users/radumetes/Documents/Developement/wordpress/wp-content/plugins/europarcel-com/ \
  /tmp/europarcel-svn/trunk/
```

### 7. Verify SVN Status

Run `svn status trunk/` and review the changes. Check for any unexpected files. If there are new files (marked with `?`), add them with `svn add`. If there are deleted files (marked with `!`), remove them with `svn delete`.

### 8. Create SVN Tag

```bash
cd /tmp/europarcel-svn
svn cp trunk tags/<VERSION>
```

### 9. Commit

Ask the user for their SVN password if not provided, then commit:

```bash
cd /tmp/europarcel-svn
svn commit -m "Version <VERSION>: <changelog summary>" \
  --username europarcelcom \
  --password <SVN_PASSWORD> \
  --non-interactive
```

### 10. Cleanup

```bash
rm -rf /tmp/europarcel-svn
```

### 11. Confirm

Tell the user the deployment is complete with the revision number. The update should appear on https://wordpress.org/plugins/europarcel-com/ within a few minutes.
