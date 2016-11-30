CHANGELOG for 3.2.x
===================

This changelog references the relevant changes done in 3.2 minor versions.

To get the diff for a specific change, go to
https://github.com/doctrine/DoctrineMongoDBBundle/commit/XXX
where XXX is the commit hash. To get the diff between two versions, go to
https://github.com/doctrine/DoctrineMongoDBBundle/compare/XXX...YYY
where XXX and YYY are the older and newer versions, respectively.

To generate a changelog summary since the last version, run
`git log --no-merges --oneline XXX...HEAD`

3.2.x-dev
---------

3.2.0 (2016-06-30)
----------------------

This release introduces compatibility with Custom Collections introduced in
ODM 1.1. The minimum PHP version for this release is 5.6, same as for ODM.
If you need support for older ODM version (1.0) or old PHP versions (< 5.6)
please use the 3.1 release. The following Pull Requests were merged for this release:

 * [#345](https://github.com/doctrine/DoctrineMongoDBBundle/pull/345) adds support for custom Collections
 * [#369](https://github.com/doctrine/DoctrineMongoDBBundle/pull/369) fixes exit code of failed commands
 * [#371](https://github.com/doctrine/DoctrineMongoDBBundle/pull/371) exposes configuration for `PersistentCollectionFactory`
