Messenger Integration
=====================

When `symfony/messenger` package is installed, the bundle automatically
registers a `Messenger`_ event subscriber that clears all document managers
after handling messages, which helps to isolate each handler and guard
against reading out-of-date document data.

.. _`Messenger`: https://symfony.com/doc/current/components/messenger.html
