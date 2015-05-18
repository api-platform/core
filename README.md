# DunglasApiBundle
**JSON-LD + Hydra REST API system for Symfony**

This a work in progress under active development.
This bundle relies heavily on the Serializer of Symfony 2.7 and *is not usable in production yet*.

[![JSON-LD enabled](http://json-ld.org/images/json-ld-button-88.png)](http://json-ld.org)
[![Build Status](https://travis-ci.org/dunglas/DunglasApiBundle.svg)](https://travis-ci.org/dunglas/DunglasApiBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a93f5a40-483f-4c46-ba09-3e1033b62552/mini.png)](https://insight.sensiolabs.com/projects/a93f5a40-483f-4c46-ba09-3e1033b62552)
[![Dependency Status](https://www.versioneye.com/user/projects/5552e93306c318a32a0000fa/badge.svg?style=flat)](https://www.versioneye.com/user/projects/5552e93306c318a32a0000fa)
[![HHVM Status](http://hhvm.h4cc.de/badge/dunglas/api-bundle.svg)](http://hhvm.h4cc.de/package/dunglas/api-bundle)

## Features

Here is the fully-featured REST API you'll get in minutes, I promise:

* CRUD support through the API for Doctrine entities: list, `GET`, `POST`, `PUT` and `DELETE`
* Hypermedia implementing [JSON-LD](http://json-ld.org)
* Machine-readable documentation of the API in the [Hydra](http://hydra-cg.com) format, guessed from PHPDoc, Serializer, Validator and Doctrine ORM metadata
* Human-readable Swagger-like documentation including a sandbox automatically generated thanks to the integration with [NelmioApiDoc](https://github.com/nelmio/NelmioApiDocBundle)
* Pagination (compliant with Hydra)
* List filters (compliant with Hydra)
* Validation using the Symfony Validator Component, with groups support
* Errors serialization (compliant with Hydra)
* Custom serialization using the Symfony Serializer Component, with groups support and the possibility to embed relations
* Automatic routes registration
* Automatic entrypoint generation giving access to all resources
* `\DateTime` serialization and deserialization
* [FOSUserBundle](https://github.com/FriendsOfSymfony/FOSUserBundle) integration (user management)
* Easy installation thanks to API Platform

Everything is fully customizable through a powerful event system and strong OOP.
This bundle is documented and tested with Behat (take a look at [the `features/` directory](features/)).

## Official documentation

1. [Getting Started](getting-started.md)
  1. [Installing DunglasApiBundle](getting-started.md#Installing-DunglasApiBundle)
  2. [Configuring the API](getting-started.md#Configuring-the-api)
  3. [Mapping the entities](getting-started.md#Mapping-the-entities)
  4. [Registering the services](getting-started.md#Registering-the-services)
2. [Operations](operations.md)
  1. [Disabling operations](operations.md#Disabling-operations)
  2. [Creating custom operations](operations.md#Creating-custom-operations)
3. [Data providers](data-providers.md)
4. [Filters](filters.md)
  1. [Adding Doctrine ORM filters](filters.md#Adding-Doctrine-ORM-filters)
  2. [Creating custom filters](filters.md#Creating-custom-filters)
5. [Serialization groups and relations](serialization-groups-and-relations.md)
  1. [Using serialization groups](serialization-groups-and-relations.md#Using-serialization-groups)
  2. [Embedding relations](serialization-groups-and-relations.md#Embedding-relations)
    1. [Normalization](serialization-groups-and-relations.md#Normalization)
    2. [Denormalization](serialization-groups-and-relations.md#Denormalization)
6. [Validation](validation.md)
  1. [Using validation groups](validation.md#Using-validation-groups)
7. [The event system](the-event-system.md)
  1. [Retrieving list](the-event-system.md#Retrieving-list)
  2. [Retrieving item](the-event-system.md#Retrieving-item)
  3. [Creating item](the-event-system.md#Creating-item)
  4. [Updating item](the-event-system.md#Updating-item)
  5. [Deleting item](the-event-system.md#Deleting-item)
  6. [Registering an event listener](the-event-system.md#Registering-an-event-listener)
8. [Resources](resources.md)
  1. [Using a custom `Resource` class](resources.md#Using-a-custom-Resource-class)
9. [Controllers](controllers.md)
  1. [Using a custom controller](controllers.md#Using-a-custom-controller)
10. [Using external (JSON-LD) vocabularies](external-vocabularies.md)
11. [Performances](performances.md)
  1. [Enabling the metadata cache](performances.md#Enabling-the-metadata-cache)
12. [AngularJS Integration](angular-integration.md)

## Other resources

* (french) [A la découverte de API Platform (Symfony Paris Live 2015)](http://dunglas.fr/2015/04/mes-slides-du-symfony-live-2015-a-la-decouverte-de-api-platform/)
* (french) [API-first et Linked Data avec Symfony (sfPot Lille 2015)](http://les-tilleuls.coop/slides/dunglas/slides-sfPot-2015-01-15/#/)

## Credits

DunglasApiBundle is part of the API Platform project.
It is developed by [Kévin Dunglas](http://dunglas.fr), [Les-Tilleuls.coop](http://les-tilleuls.coop) and some awesome contributors.
