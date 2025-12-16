UPGRADE from 5.5 to 5.6
=======================

Remove namespace aliases
------------------------

The namespace alias shortcut feature was deprecated in Doctrine Persistence 2.3.0
and removed in 3.0.0. This is already not supported by Doctrine MongoDB Bundle 5.x

 * [BC break] Remove the method `Doctrine\Bundle\MongoDBBundle\ManagerRegistry::getAliasNamespace()`
 * Remove parameter `$aliasMap` in `Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\RegisterMappingsPass`
 * The `Doctrine\ODM\MongoDB\Configuration` class is not populated with namespace aliases anymore;
   the method `Configuration::getDocumentNamespaces()` always return an empty array.
