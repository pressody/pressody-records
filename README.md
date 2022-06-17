# Pressody Records

Custom WordPress plugin to hold the RECORDS-entity logic for the Pressody system (infrastructure-side).

## About

**Pressody Records** is part of the **Pressody** (PD for short) infrastructure.
It is the part that **manages the deployable code to WordPress sites.** All the code that we account for (i.e. take responsibility for) should be managed and exposed through PD Records.

At a fundamental level, the WordPress instance that has this plugin active (let's call it **the Pressody Records instance)** is **a private Composer repository.** This means that in the same way [Packagist.org](https://packagist.org) or [WPackagist.org](https://wpackagist.org) provide a public Composer repository, we provide to our users private packages (of code) that Composer can install and update. Even if we deliver public packages from the public repositories, we will ingest them and expose them as Pressody packages for redundancy and control.

Needless to say that **we have built Pressody with Composer at its core** — Composer being the most used PHP package manager. Every entity and data-flow is structured to take advantage of the wonderful logic that Composer delivers when it comes to managing code.

Pressody Records focuses on **two entities: PD Packages and PD Parts.**

### PD Packages

An PD Package is **the most fundamental, low-level entity** in the entire Pressody ecosystem. It is basically a regular Composer package managed by us and made available to be included in PD Parts.

**PD Packages are to remain internal to PD Records,** not to be used directly by external entities. This way we remain free to manage PD Packages without wondering about unexpected, external side effects.

**PD Packages can depend/require only other PD Packages.**

### PD Parts

An PD Part is the upper hierarchical level above PD Packages. This is the **only entity that PD Records exposes externally** to be used by other Pressody entities (like Pressody Retailer).

**PD Parts group PD Packages** into meaningful functional entities. While a PD Packages may not be able to be used as a standalone piece of functionality, **a PD Part should deliver actual functionality** while allowing for further composition into bigger PD Parts.

While the bulk of the code delivered by a PD Part will be provided by the required PD Packages (i.e. actual WordPress plugins, themes, etc.), **the code specific to a PD Part should handle the integration** of those PD Packages. 

**An PD Part must provide its own code** in the form of a dedicated PD Part Plugin. Even if there is no integration to be made, an "empty" (skeleton) plugin must be attached to each PD Part release since PD Parts are not Composer `metapackages`. An easy way to generate such a PD Part Plugin will be provided.

### PD Solutions

All the (public) PD Parts managed by Pressody Records are available to be included in **PD Solutions** by the [Pressody Retailer](https://github.com/pressody/pressody-retailer) instance. Only by being part of a PD Solution that a PD Part can become part of an actual site.

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
