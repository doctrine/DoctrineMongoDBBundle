CHANGELOG for 3.1.x
===================

This changelog references the relevant changes done in 3.1 minor versions.

To get the diff for a specific change, go to
https://github.com/doctrine/DoctrineMongoDBBundle/commit/XXX
where XXX is the commit hash. To get the diff between two versions, go to
https://github.com/doctrine/DoctrineMongoDBBundle/compare/XXX...YYY
where XXX and YYY are the older and newer versions, respectively.

To generate a changelog summary since the last version, run
`git log --no-merges --oneline XXX...HEAD`

3.1.x-dev
---------

3.1.0 (2016-04-10)
----------------------

This release introduces compatibility with Symfony 3.0. The minimum PHP version
for this release is 5.5. If you need support for older Symfony versions (< 2.7),
old PHP versions (< 5.5) or old MongoDB driver versions (< 1.5) please use the
3.0 release. The following Pull Requests were merged for this release:

 * [#352](https://github.com/doctrine/DoctrineMongoDBBundle/pull/352) adds support for Symfony 2.8+ toolbar
 * [#342](https://github.com/doctrine/DoctrineMongoDBBundle/pull/342) introduces backward compatibility layer for Symfony 2.7
 * [#331](https://github.com/doctrine/DoctrineMongoDBBundle/pull/331) enforces certain configuration values to be integers
 * [#328](https://github.com/doctrine/DoctrineMongoDBBundle/pull/328) introduces compatibility to Symfony 3.0 and drops support for Symfony < 2.8 as well as old PHP and MongoDB driver versions
 * [#316](https://github.com/doctrine/DoctrineMongoDBBundle/pull/316) adds support for the SCRAM-SHA-1 auth mechanism introduced with MongoDB 3.0
 * [#308](https://github.com/doctrine/DoctrineMongoDBBundle/pull/308) improves PSR-4 compliance
 * [#302](https://github.com/doctrine/DoctrineMongoDBBundle/pull/302) allows to load individual fixture files in addition to fixture folders
 * [#297](https://github.com/doctrine/DoctrineMongoDBBundle/pull/297) adds support for the `repository_factory` and `default_repository_class` configuration options
 * [#277](https://github.com/doctrine/DoctrineMongoDBBundle/pull/297) allows to load fixtures for individual bundles
 * [#274](https://github.com/doctrine/DoctrineMongoDBBundle/pull/274) adds a confirmation dialog to the `fixtures:load` command to confirm database deletion when the `purge` option is set
 * [#269](https://github.com/doctrine/DoctrineMongoDBBundle/pull/269) allows to pass a document manager instance to the document form type
 * [#267](https://github.com/doctrine/DoctrineMongoDBBundle/pull/267) allows using the `auto_mapping` option when using multiple document managers
 * [#235](https://github.com/doctrine/DoctrineMongoDBBundle/pull/235) changes the logger class to directly implement the PSR-3 logger interface
