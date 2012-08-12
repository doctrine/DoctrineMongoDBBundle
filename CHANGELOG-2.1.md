CHANGELOG for 2.1.x
===================

This changelog references the relevant changes (bug and security fixes) done
in 2.1 minor versions.

2.1.0
-----

### Services

 * The ManagerRegistry's service ID has changed from `doctrine.odm.mongodb` to
   `doctrine_mongodb`.
 * The prefix for all service ID's has changed from `doctrine.odm.mongodb` to
   `doctrine_mongodb.odm`.

### Events

 * The format for naming event manager services is now
   `doctrine_mongodb.odm.%s_connection.event_manager` and uses the connection
   name. The event manager shared by the default document manager is still
   aliased as `doctrine_mongodb.odm.event_manager`.
 * Listeners and subscriber services must be tagged with 
   `doctrine_mongodb.odm.event_listener` and
   `doctrine_mongodb.odm.event_subscriber`, respectively. By default, listeners
   and subscribers will be registered with event managers for all connections,
   unless the `connection` attribute is specified.
 * Added `lazy` attribute to tags to request lazy loading of listener services
   by the event manager.
 * Added `priority` attribute to tags to control listener execution order.
