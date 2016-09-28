# CONTRIBUTING

Contributions are very welcome, and are accepted via pull requests. Please review these guidelines before submitting any pull requests.

## Guidelines

* Please follow the [PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) and [PHP-FIG Naming Conventions](https://github.com/php-fig/fig-standards/blob/master/bylaws/002-psr-naming-conventions.md) Style-CI runs on every PR so you can fetch the necessary style changes from there.
* Ensure that the current tests pass, and add more tests if you have added logic.

## Running Tests

You will need an install of [Composer](https://getcomposer.org) before continuing. Yii2 also unfortunately needs you to install `fxp/composer-asset-plugin`.

```bash
composer global require "fxp/composer-asset-plugin:^1.2.0"
```

Install the dependencies:

```bash
composer install --prefer-source
```
**Note: please use the `prefer-source` flag cause some tests depend on Yii2's test cases which won't get installed without it.** 

You need to also configure the `phpunit.xml` with your Algolia keys for running the integration tests:
```xml
<php>
    <env name="ALGOLIA_ID" value="id" />
    <env name="ALGOLIA_KEY" value="secret" />
</php>

```

Then run phpunit:

```bash
php vendor/phpunit/phpunit/phpunit
```

If the test suites pass on your local machine you should be good to go.

When you make a pull request, the tests will automatically be run again by [Travis CI](https://travis-ci.org/) on multiple php versions.
