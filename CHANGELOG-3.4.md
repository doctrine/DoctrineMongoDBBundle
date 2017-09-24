CHANGELOG for 3.4.x
===================

This changelog references the relevant changes done in 3.4 minor versions.

To get the diff for a specific change, go to
https://github.com/doctrine/DoctrineMongoDBBundle/commit/XXX
where XXX is the commit hash. To get the diff between two versions, go to
https://github.com/doctrine/DoctrineMongoDBBundle/compare/XXX...YYY
where XXX and YYY are the older and newer versions, respectively.

To generate a changelog summary since the last version, run
`git log --no-merges --oneline XXX...HEAD`

3.4.x-dev
---------

3.4.0 (2017-09-22)
------------------

All issues and pull requests in this release may be found under the [3.4 milestone](https://github.com/doctrine/DoctrineMongoDBBundle/issues?q=milestone%3A3.4.0).

 * [430](https://github.com/doctrine/DoctrineMongoDBBundle/pull/430) excludes embedded documents from proxy generation in `ProxyCacheWarmer`.
 * [425](https://github.com/doctrine/DoctrineMongoDBBundle/pull/425) adds the `odm:schema:shard` command.
 * [416](https://github.com/doctrine/DoctrineMongoDBBundle/pull/416) handles the deprecation of `DefinitionDecorator`.
 * [410](https://github.com/doctrine/DoctrineMongoDBBundle/pull/410) fixes the notation for Twig template names in the data collector.
