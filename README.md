# PixelgradeLT Records

Custom WordPress plugin to hold the RECORDS-entity logic for the Pixelgrade LT system (infrastructure-side).

## About



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
