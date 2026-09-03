## pdoTools3

[![Tests](https://github.com/modx-pro/pdoTools3/actions/workflows/phpunit.yml/badge.svg)](https://github.com/modx-pro/pdoTools3/actions/workflows/phpunit.yml)
[![PHP](https://img.shields.io/badge/php-8.2%20%7C%208.3%20%7C%208.4%20%7C%208.5-777BB4)](https://github.com/modx-pro/pdoTools3/actions/workflows/phpunit.yml)
[![Coverage](https://img.shields.io/codecov/c/github/modx-pro/pdoTools3)](https://codecov.io/gh/modx-pro/pdoTools3)

pdoTools3 is a set of everyday [MODX Revolution](https://modx.com/) 3 snippets plus a small library that keeps them fast. Queries are built with xPDO and run through PDO. Elements can live in the database or in files. [MiniShop3](https://github.com/modx-pro/MiniShop3) use it.

Requires **MODX 3** and **PHP 8.2+**. For MODX 2 use [pdoTools](https://github.com/modx-pro/pdoTools).

Package signature is `pdotools3` (transport `pdotools3-1.0.0-pl.transport.zip`). Component paths, snippets (`pdoResources`, …), and settings (`pdotools_*`) stay under `pdotools`. Sites on older `pdoTools` 3.x install this package over the same files, then remove the old package record in Package Manager.

**Documentation:** [docs.modx.pro/components/pdotools](https://docs.modx.pro/components/pdotools/)

### Tests

From `core/components/pdotools`:

```bash
composer install
composer test
```

Integration tests need a live MODX 3 tree (CI installs 3.2.4-pl and the transport with `PKG_AUTO_INSTALL=true`):

```bash
export MODX_TEST_BASE=/path/to/modx/
composer test:integration
```

Coverage (pcov or xdebug): `composer test:coverage`. HTML report lands in `coverage/html`.

### Advantages

Every pdoTools snippet shares the same core:

- Database work goes through PDO. xPDO objects are created only when needed.
- Simple placeholders in chunks are preprocessed so the MODX parser only handles complex tags.
- TV parameters are sorted, prepared, processed, and output correctly.
- Chunk code can sit inline in the snippet call, in a normal chunk, or in a static file (`@FILE`, `@INLINE`, `@TEMPLATE`).
- Fast placeholders wrap a value in tags only when it is not empty (covers many `isempty`-style cases).
- A timed work log for debugging bottlenecks. Snippet logs go into a matching placeholder (`pdoResourcesLog`, `pdoPageLog`, …).

Queries can target any tables, with joins and conditions. Pagination stays compatible with getPage via [pdoPage](https://docs.modx.pro/components/pdotools/snippets/pdopage). The package includes the [Fenom](https://docs.modx.pro/components/pdotools/parser) template engine.

Compared with stock MODX helpers:

- `pdoTools::getChunk()` processes placeholders faster than `modX::getChunk()`.
- `pdoTools::runSnippet()` is faster and more powerful than `modX::runSnippet()`.

These snippets usually run faster when you select more fields in a single query.

### Composition

Classes live under `ModxPro\PdoTools\` (PSR-4). The service container still accepts the aliases `pdoTools` and `pdoFetch`.

#### Snippets

| Snippet | Role |
| --- | --- |
| [pdoResources](https://docs.modx.pro/components/pdotools/snippets/pdoresources) | Resource lists (parameter-compatible replacement for getResources) |
| [pdoMenu](https://docs.modx.pro/components/pdotools/snippets/pdomenu) | Menus (Wayfinder replacement) |
| [pdoPage](https://docs.modx.pro/components/pdotools/snippets/pdopage) | Pagination (getPage replacement) |
| [pdoCrumbs](https://docs.modx.pro/components/pdotools/snippets/pdocrumbs) | Breadcrumbs (BreadCrumb replacement) |
| [pdoUsers](https://docs.modx.pro/components/pdotools/snippets/pdousers) | Users, with role and group filters |
| [pdoSitemap](https://docs.modx.pro/components/pdotools/snippets/pdositemap) | Sitemap generation (GoogleSiteMap replacement) |
| [pdoNeighbors](https://docs.modx.pro/components/pdotools/snippets/pdoneighbors) | Links to neighboring resources |
| [pdoField](https://docs.modx.pro/components/pdotools/snippets/pdofield) | Any resource field (getResourceField / UltimateParent replacement) |
| [pdoTitle](https://docs.modx.pro/components/pdotools/snippets/pdotitle) | Page title helper |
| [pdoArchive](https://docs.modx.pro/components/pdotools/snippets/pdoarchive) | Archive listings by date |

#### Classes

| Class | Docs |
| --- | --- |
| [pdoTools](https://docs.modx.pro/components/pdotools/classes/pdotools) | `ModxPro\PdoTools\CoreTools` — core helpers (`getChunk`, `runSnippet`, caching, …) |
| [pdoFetch](https://docs.modx.pro/components/pdotools/classes/pdofetch) | `ModxPro\PdoTools\Fetch` — query builder and PDO fetch layer |
| [pdoParser](https://docs.modx.pro/components/pdotools/classes/pdoparser) | `ModxPro\PdoTools\Parsing\Parser` — parser integration (Fenom / FastField tags) |

### More documentation

- [General properties](https://docs.modx.pro/components/pdotools/general-properties) shared by the snippets
- [File elements](https://docs.modx.pro/components/pdotools/file-elements)
- [Parser](https://docs.modx.pro/components/pdotools/parser)

### Links

- Docs: https://docs.modx.pro/components/pdotools/
- Issues: https://github.com/modx-pro/pdoTools3/issues
- MODX 2 branch: https://github.com/modx-pro/pdoTools
