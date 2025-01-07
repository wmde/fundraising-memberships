# Fundraising Memberships

[![Build Status](https://travis-ci.org/wmde/fundraising-memberships.svg?branch=master)](https://travis-ci.org/wmde/fundraising-memberships)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/wmde/fundraising-memberships/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/wmde/fundraising-memberships/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/wmde/fundraising-memberships/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/wmde/fundraising-memberships/?branch=master)

Bounded Context for the Wikimedia Deutschland fundraising membership (sub-)domain. Used by the
[user facing donation application](https://github.com/wmde/FundraisingFrontend) and the
"Fundraising Operations Center" (which is not public software).

## Installation

To use the Fundraising Memberships library in your project, simply add a dependency on wmde/fundraising-memberships
to your project's `composer.json` file. Here is a minimal example of a `composer.json`
file that just defines a dependency on Fundraising Memberships 1.x:

```json
{
    "require": {
        "wmde/fundraising-memberships": "~1.0"
    }
}
```

## Development

For development you need to have Docker and the Docker `compose` plugin installed. You don't need a local PHP runtime environment.

    sudo apt-get install docker docker-compose-plugin

### Running Composer

To pull in the project dependencies via Composer, run:

    make install-php

To update the dependencies, run 

    make update-php

### Running the CI checks

To run all CI checks, which includes PHPUnit tests, PHPCS style checks and static analysis with PHPStan, run:

    make
    
### Running the tests

To run just the PHPUnit tests run

    make test

To run only a subset of PHPUnit tests or otherwise pass flags to PHPUnit, run

    docker compose run --rm app ./vendor/bin/phpunit --filter SomeClassNameOrFilter
    
## Architecture

This Bounded Context follows the architecture rules outlined in [Clean Architecture + Bounded Contexts](https://www.entropywins.wtf/blog/2018/08/14/clean-architecture-bounded-contexts/).

With the exception that the persistance is still (mostly) in [FundraisingStore](https://github.com/wmde/FundraisingStore/), and thus not private to this Bounded Context.

![Architecture diagram](https://user-images.githubusercontent.com/146040/44942179-6bd68080-adac-11e8-9506-179a9470113b.png)
