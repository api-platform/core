# FOSUser Bundle integration

This bundle is shipped with a bridge for the [FOSUserBundle](https://github.com/FriendsOfSymfony/FOSUserBundle). If the FOSUserBundle is enabled, this bridges registers to the persist, update and delete events to pass user objects to the UserManager, before redispatching the event. 
