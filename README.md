# GamerWiki

GamerWiki is wiki software for running your own wiki on your own computer, in the style of the mid-2000s video game wikis: the classic MonoBook look, plain HTML pages, and no OOUI, Lua or JavaScript-heavy editors.

It is MediaWiki 1.19.24 (the last long-term-support release before Lua and OOUI) under a new name. It has been updated to run on modern PHP and has a few later quality-of-life features backported. Wikitext, templates, XML dumps and most 1.19-era extensions work as they always did.

## Quick start

You need PHP 8.1 or newer with the `pdo_sqlite` extension. That's all: no web server and no database server.

```bash
./gamerwiki install "My Game Wiki" YourName
```

```bash
./gamerwiki serve
```

Then open http://127.0.0.1:8080/. The wiki is stored in `data/` (database) and `images/` (uploads).

| Command | What it does |
|---|---|
| `./gamerwiki install "Name" Admin [port]` | Create a new wiki |
| `./gamerwiki serve [port]` | Run it at `http://127.0.0.1:<port>/` (default 8080) |
| `./gamerwiki backup` | Copy the database to `data/backups/` (safe while running) |
| `./gamerwiki update` | Back up, pull the latest GamerWiki, update the database |

Optional programs: ImageMagick for thumbnails (otherwise PHP's GD is used), Ghostscript and poppler-utils for PDF previews.

## Moving an existing wiki in

Export the pages from the old wiki with Special:Export (or `dumpBackup.php`), then import them with Special:Import, or run `php maintenance/importDump.php dump.xml`. Dumps from newer MediaWiki versions (export format 0.11) import fine. Bring files over with `php maintenance/importImages.php /path/to/files`.

## What's different from MediaWiki 1.19

**Looks.** MonoBook is the only skin. It comes with a built-in house style: boxed personal tools, search at the top of the sidebar, crisp pixel-art image scaling, infobox, notice-template, forum and Main Page box styles, and the "article" tab. `MediaWiki:Common.css` and `MediaWiki:Monobook.css` still work for per-wiki changes.

**Backported features.**

- `__METACAT__` (the old `__HIDDENCAT__` still works): meta categories are always shown, on their own row under the content categories.
- `__EXPECTUNUSEDCATEGORY__` keeps an intentionally empty category off Special:UnusedCategories.
- `__EXPECTUNUSEDTEMPLATE__` keeps a template that is meant to go unused (for example one that is only ever substituted) off Special:UnusedTemplates.
- `__DISAMBIG__`, Special:DisambiguationPages, and disambiguation-aware Special:Disambiguations, LonelyPages and ShortPages.
- SVG files are shown as SVG, sharp at any size (`$wgSVGNativeRendering`).

**Built-in extensions.** ParserFunctions (including string functions), Cite, CharInsert, ImageMap, InputBox, Poem, Interwiki, Nuke, Renameuser, ReplaceText, SyntaxHighlight (GeSHi), PdfHandler, DPLforum, Variables, Loops and WikiSEO are always on. See `extensions/README` for versions and licenses.

**Always fresh.** Every page view renders the page from its current wikitext, as if it had just been purged. Template changes, page counts, forum lists and dates are never stale. After each save, GamerWiki also finishes the background job queue, so categories and "What links here" are up to date by the next page load. A busy public wiki can turn caching back on with `$wgEnableParserCache = true; $wgCachePages = true;`.

**Game-wiki defaults.** Uploads are on (png, gif, jpg, svg, pdf, ogv, oga, up to 2 GB). There are Forum and Gallery namespaces, American dates and 200px thumbnails.

To add a namespace, one line in `LocalSettings.php` does it, talk namespace and subpages included:

```php
$wgGamerWikiNamespaces[3002] = 'Fanon';
```

## Security

GamerWiki is meant for local, offline use, and its defaults reflect that. The server only listens on 127.0.0.1 and only answers requests addressed to it, which blocks DNS rebinding. Only MediaWiki's public entry points can run, so nothing in `images/`, `includes/`, `data/` or `LocalSettings.php` is ever served or executed. Pages send a Content-Security-Policy, cookies are `SameSite=Lax` against cross-site request forgery, and uploads are served so that scripts inside them cannot run. XML imports and uploaded SVGs are checked for external-entity (XXE) tricks.

If you put GamerWiki on a public server instead, use a maintained web server setup and treat it like any other old PHP application.

MySQL/MariaDB is not supported, because 1.19's MySQL driver uses PHP functions removed in PHP 7. Use SQLite, the default. PostgreSQL is still offered by the installer but hasn't been tested on PHP 8.

## License and credits

GamerWiki is free software under the GNU General Public License, version 2 or later (see `COPYING`). It is built on MediaWiki, copyright 2001-2012 by the MediaWiki developers (see `CREDITS` and `README`). The bundled extensions keep their own authors and compatible licenses (see `extensions/README`).
