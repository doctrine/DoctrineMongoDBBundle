CHANGELOG for 2.1.x
===================

This changelog references the relevant changes (bug and security fixes) done
in 2.1 minor versions.

2.1.0
-----

### Events

 * The format for naming event manager services is now
   `doctrine.odm.mongodb.%s_connection.event_manager` and uses the connection
   name. The event manager shared by the default document manager is still
   aliased as `doctrine.odm.mongodb.event_manager`.
 * Listeners and subscriber services must be tagged with 
   `doctrine.odm.mongodb.event_listener` and
   `doctrine.odm.mongodb.event_subscriber`, respectively. By default, listeners
   and subscribers will be registered with event managers for all connections,
   unless the `connection` attribute is specified.
 * Added `lazy` attribute to tags to request lazy loading of listener services
   by the event manager.
 * Added `priority` attribute to tags to control listener execution order.
