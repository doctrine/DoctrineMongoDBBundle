CHANGELOG for 3.0.x
===================

This changelog references the relevant changes done in 3.0 minor versions.

To get the diff for a specific change, go to
https://github.com/doctrine/DoctrineMongoDBBundle/commit/XXX
where XXX is the commit hash. To get the diff between two versions, go to
https://github.com/doctrine/DoctrineMongoDBBundle/compare/XXX...YYY
where XXX and YYY are the older and newer versions, respectively.

To generate a changelog summary since the last version, run
`git log --no-merges --oneline XXX...HEAD`

3.0.x-dev
---------

3.0.2 (2015-12-26)
------------------

* [#286](https://github.com/doctrine/DoctrineMongoDBBundle/pull/286) fixed duplicate calls to "limit" in queries displayed in the profiler
* [#289](https://github.com/doctrine/DoctrineMongoDBBundle/pull/289) fixed an issue where the annotation mapping driver was not correctly initialized
* [#330](https://github.com/doctrine/DoctrineMongoDBBundle/pull/330) adds validation to ensure the replicaSet parameter is a string

3.0.1 (2015-08-31)
------------------

* [42565f1](https://github.com/doctrine/DoctrineMongoDBBundle/commit/42565f1511d0cae687319e0479b9628aa963589c) fixed security vulnerability [CVE-2015-5723](http://www.cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2015-5723)

3.0.0 (2015-05-22)
------------------

* [#264](https://github.com/doctrine/DoctrineMongoDBBundle/pull/264) add support for bundle namespace alias
* [#260](https://github.com/doctrine/DoctrineMongoDBBundle/pull/260) Support filter parameters in configuration
* [#259](https://github.com/doctrine/DoctrineMongoDBBundle/pull/259) Configuration - added "authMechanism" option
* [#258](https://github.com/doctrine/DoctrineMongoDBBundle/pull/258) bundle autoloading moved to PSR4
* [#249](https://github.com/doctrine/DoctrineMongoDBBundle/pull/249) Unset username and password options when null
* [#245](https://github.com/doctrine/DoctrineMongoDBBundle/pull/245) Query logging should be done at the debug level not info

3.0.0-BETA6 (2014-05-19)
------------------------

 * [#197](https://github.com/doctrine/DoctrineMongoDBBundle/pull/197): Expose ResolveTargetDocumentListener in configuration
 * [#206](https://github.com/doctrine/DoctrineMongoDBBundle/pull/206): Fixes "event_listener" tag for "resolve_target_documents" configuration

3.0.0-BETA5 (2014-04-03)
------------------------

 * [#180](https://github.com/doctrine/DoctrineMongoDBBundle/pull/180): Fixed build error 'WARNING: undefined label: event_dispatcher'
 * [#182](https://github.com/doctrine/DoctrineMongoDBBundle/pull/182): Update composer.json for Symfony 2.3
 * [#183](https://github.com/doctrine/DoctrineMongoDBBundle/pull/183): Documentation for the abstraction layer
 * [#185](https://github.com/doctrine/DoctrineMongoDBBundle/pull/185): Add compiler pass for bundles to register mappings
 * [#192](https://github.com/doctrine/DoctrineMongoDBBundle/pull/192): Add more info about command in pretty data collector
 * [#194](https://github.com/doctrine/DoctrineMongoDBBundle/pull/194): Updated registration form documentation
 * [#195](https://github.com/doctrine/DoctrineMongoDBBundle/pull/195): Documentation tip on using the parameters
 * [#200](https://github.com/doctrine/DoctrineMongoDBBundle/pull/200): Document db connection option
 * [#207](https://github.com/doctrine/DoctrineMongoDBBundle/pull/207): Composer should only update current dependency
 * [#210](https://github.com/doctrine/DoctrineMongoDBBundle/pull/210): Fixed typo
 * [#211](https://github.com/doctrine/DoctrineMongoDBBundle/pull/211): FormBuilderInterface documentation update
 * [#215](https://github.com/doctrine/DoctrineMongoDBBundle/pull/215): Support PHP driver options in Configuration
 * [#219](https://github.com/doctrine/DoctrineMongoDBBundle/pull/219): Update configuration for 1.4.x driver options
 * [#223](https://github.com/doctrine/DoctrineMongoDBBundle/pull/223): Restore addDefaultsIfNotSet() for "default_commit_options"
 * [#233](https://github.com/doctrine/DoctrineMongoDBBundle/pull/233): Added a parameter allowing the default fixtures path to be configured
