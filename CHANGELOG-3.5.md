CHANGELOG for 3.5.x
===================

This changelog references the relevant changes done in 3.5 minor versions.

To get the diff for a specific change, go to
https://github.com/doctrine/DoctrineMongoDBBundle/commit/XXX
where XXX is the commit hash. To get the diff between two versions, go to
https://github.com/doctrine/DoctrineMongoDBBundle/compare/XXX...YYY
where XXX and YYY are the older and newer versions, respectively.

To generate a changelog summary since the last version, run
`git log --no-merges --oneline XXX...HEAD`

3.5.4 (2019-09-24)
------------------

All issues and pull requests in this release may be found under the [3.5.4 milestone](https://github.com/doctrine/DoctrineMongoDBBundle/issues?q=milestone%3A3.5.4).

 * [580](https://github.com/doctrine/DoctrineMongoDBBundle/pull/514) fixes issues in the travis-ci build
 * [578](https://github.com/doctrine/DoctrineMongoDBBundle/pull/513) removes hardcoded exit calls in favour of exceptions
 * [534](https://github.com/doctrine/DoctrineMongoDBBundle/pull/534) fixes the link to the PHP 7 usage instructions

3.5.3 (2018-12-22)
------------------

All issues and pull requests in this release may be found under the [3.5.3 milestone](https://github.com/doctrine/DoctrineMongoDBBundle/issues?q=milestone%3A3.5.3).

 * [514](https://github.com/doctrine/DoctrineMongoDBBundle/pull/514) fixes a wrong call to `clearstatcache`
 * [513](https://github.com/doctrine/DoctrineMongoDBBundle/pull/513) fixes command deprecation notices when using Symfony 4.2

3.5.2 (2018-12-07)
------------------

All issues and pull requests in this release may be found under the [3.5.2 milestone](https://github.com/doctrine/DoctrineMongoDBBundle/issues?q=milestone%3A3.5.2).

 * [506](https://github.com/doctrine/DoctrineMongoDBBundle/pull/506) fixes command deprecation notices when using Symfony 4.2

3.5.1 (2018-12-03)
------------------

All issues and pull requests in this release may be found under the [3.5.1 milestone](https://github.com/doctrine/DoctrineMongoDBBundle/issues?q=milestone%3A3.5.1).

 * [501](https://github.com/doctrine/DoctrineMongoDBBundle/pull/501) fixes deprecation notices when using Symfony 4.2.
 * [505](https://github.com/doctrine/DoctrineMongoDBBundle/pull/505) adds a better error message when trying to instantiate a service repository for an unmapped class.

3.5.0 (2018-09-24)
------------------

All issues and pull requests in this release may be found under the [3.5.0 milestone](https://github.com/doctrine/DoctrineMongoDBBundle/issues?q=milestone%3A3.5.0).

 * [441](https://github.com/doctrine/DoctrineMongoDBBundle/pull/441) adds service aliases to autowire the document manager and manager registry services.
 * [448](https://github.com/doctrine/DoctrineMongoDBBundle/pull/448) updates the documentation to document autowiring features.
 * [469](https://github.com/doctrine/DoctrineMongoDBBundle/pull/469) deprecates the `default_repository_class` configuration option in favor of `default_document_repository_class`.
 * [470](https://github.com/doctrine/DoctrineMongoDBBundle/pull/470) clarifies the documentation to better explain using multiple databases in one project.
 * [471](https://github.com/doctrine/DoctrineMongoDBBundle/pull/471) fixes a wrong connection service name.
 * [473](https://github.com/doctrine/DoctrineMongoDBBundle/pull/473) adds support for service repositories as added to DoctrineBundle.
