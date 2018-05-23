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

3.4.4 (2018-05-23)
------------------

All issues and pull requests in this release may be found under the [3.4.4 milestone](https://github.com/doctrine/DoctrineMongoDBBundle/issues?q=milestone%3A3.4.4).

 * [464](https://github.com/doctrine/DoctrineMongoDBBundle/pull/464) adds a missing service for the `doctrine:mongodb:generate:repositories` command

3.4.3 (2018-04-19)
------------------

All issues and pull requests in this release may be found under the [3.4.3 milestone](https://github.com/doctrine/DoctrineMongoDBBundle/issues?q=milestone%3A3.4.3).

 * [458](https://github.com/doctrine/DoctrineMongoDBBundle/pull/458) makes form type guesser ignore unmapped fields instead of throwing an exception

3.4.2 (2018-03-22)
------------------

All issues and pull requests in this release may be found under the [3.4.2 milestone](https://github.com/doctrine/DoctrineMongoDBBundle/issues?q=milestone%3A3.4.2).

 * [455](https://github.com/doctrine/DoctrineMongoDBBundle/pull/455) fixes tests by using memcached instead of memcache (Symfony 4 doctrine-bridge compatibility)
 * [454](https://github.com/doctrine/DoctrineMongoDBBundle/pull/454) fixes wrongly named MONGODB-X509 auth mechanism

3.4.1 (2017-11-18)
------------------

All issues and pull requests in this release may be found under the [3.4.1 milestone](https://github.com/doctrine/DoctrineMongoDBBundle/issues?q=milestone%3A3.4.1).

 * [438](https://github.com/doctrine/DoctrineMongoDBBundle/pull/438) removes deprecated ODM functionality in tests
 * [437](https://github.com/doctrine/DoctrineMongoDBBundle/pull/437) declares commands as services to make them discoverable with Symfony 4
 * [433](https://github.com/doctrine/DoctrineMongoDBBundle/pull/433) fixes the container configuration to make services public for Symfony 4

3.4.0 (2017-09-22)
------------------

All issues and pull requests in this release may be found under the [3.4 milestone](https://github.com/doctrine/DoctrineMongoDBBundle/issues?q=milestone%3A3.4.0).

 * [430](https://github.com/doctrine/DoctrineMongoDBBundle/pull/430) excludes embedded documents from proxy generation in `ProxyCacheWarmer`.
 * [425](https://github.com/doctrine/DoctrineMongoDBBundle/pull/425) adds the `odm:schema:shard` command.
 * [416](https://github.com/doctrine/DoctrineMongoDBBundle/pull/416) handles the deprecation of `DefinitionDecorator`.
 * [410](https://github.com/doctrine/DoctrineMongoDBBundle/pull/410) fixes the notation for Twig template names in the data collector.
