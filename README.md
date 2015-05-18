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

An extensive documentation is available in the [`Resources/doc/`](Resources/doc/) directory.
The [Getting Started] article will let you build your first API in minutes.

## Other resources

* (french) [A la découverte de API Platform (Symfony Paris Live 2015)](http://dunglas.fr/2015/04/mes-slides-du-symfony-live-2015-a-la-decouverte-de-api-platform/)
* (french) [API-first et Linked Data avec Symfony (sfPot Lille 2015)](http://les-tilleuls.coop/slides/dunglas/slides-sfPot-2015-01-15/#/)

## Credits

DunglasApiBundle is part of the API Platform project.
It is developed by [Kévin Dunglas](http://dunglas.fr), [Les-Tilleuls.coop](http://les-tilleuls.coop) and some awesome contributors.
