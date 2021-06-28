# PixelgradeLT Records

Custom WordPress plugin to hold the RECORDS-entity logic for the Pixelgrade LT system (infrastructure-side).

## About

PixelgradeLT Records is part of the PixelgradeLT infrastructure.
It is the part that **manages the deployable code to WordPress sites.** All the code that we account for (i.e. take responsibility for) should be managed and exposed through LT Records.

At a fundamental level, the WordPress instance that has this plugin active (let's call it **the PixelgradeLT Records instance)** is **a private Composer repository.** This means that in the same way [Packagist.org](https://packagist.org) or [WPackagist.org](https://wpackagist.org) provide a public Composer repository, we provide to our users private packages (of code) that Composer can install and update. Even if we deliver public packages from the public repositories, we will ingest them and expose them as PixelgradeLT packages for redundancy and control.

Needless to say that **we have built PixelgradeLT with Composer at its core** — Composer being the most used PHP package manager. Every entity and data-flow is structured to take advantage of the wonderful logic that Composer delivers when it comes to managing code.

PixelgradeLT Records focuses on **two entities:** LT Packages and LT Parts.

### LT Packages

An LT Package is **the most fundamental, low-level entity** in the entire PixelgradeLT ecosystem. It is basically a regular Composer package managed by us and made available to be included in LT Parts.

**LT Packages are to remain internal to LT Records,** not to be used directly by external entities. This way we remain free to manage LT Packages without wondering about unexpected, external side effects.

**LT Packages can depend/require only other LT Packages.**

### LT Parts

An LT Part is the upper hierarchical level above LT Packages. This is the only entity that LT Records exposes externally to be used by other PixelgradeLT entities (like PixelgradeLT Retailer).

**LT Parts group LT Packages** into meaningful functional entities. While an LT Packages may not be able to be used as a standalone piece of functionality, **an LT Part should deliver actual functionality** while allowing for further composition into bigger LT Parts.

While the bulk of the code delivered by an LT Part will be provided by the required LT Packages (i.e. actual WordPress plugins, themes, etc.), **the code specific to an LT Part should handle the integration** of those LT Packages. 

**An LT Part must provide its own code** in the form of a dedicated LT Part Plugin. Even if there is no integration to be made, an "empty" (skeleton) plugin must be attached to each LT Part release since LT Parts are not Composer `metapackages`. An easy way to generate such a LT Part Plugin will be provided.

### LT Solutions

All the (public) LT Parts managed by PixelgradeLT Records are available to be included in LT Solutions by the [PixelgradeLT Retailer](https://github.com/pixelgradelt/pixelgradelt-retailer) instance. Only by being part of an LT Solution an LT Part can become part of an actual site.

## Running Tests

To run the PHPUnit tests, in the root directory of the plugin, run something like:

```
./vendor/bin/phpunit --testsuite=Unit --colors=always
```
or
```
composer run tests
```

Bear in mind that there are **simple unit tests** (hence the `--testsuite=Unit` parameter) that are very fast to run, and there are **integration tests** (`--testsuite=Integration`) that need to load the entire WordPress codebase, recreate the db, etc. Choose which ones you want to run depending on what you are after.

You can run either the unit tests or the integration tests with the following commands:

```
composer run tests-unit
```
or
```
composer run tests-integration
```

**Important:** Before you can run the tests, you need to create a `.env` file in `tests/phpunit/` with the necessary data. You can copy the already existing `.env.example` file. Further instructions are in the `.env.example` file.

## Credits

This WordPress plugin took the wonderful work from [SatisPress](https://github.com/cedaro/satispress) and adapted/expanded it to our specific needs.
