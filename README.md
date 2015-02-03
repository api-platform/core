# DunglasJsonLdApiBundle
**JSON-LD + Hydra REST API generator for Symfony**

This a work in progress under active development.
This bundle *is not usable in production yet*.

[![Build Status](https://travis-ci.org/dunglas/DunglasJsonLdApiBundle.svg)](https://travis-ci.org/dunglas/DunglasJsonLdApiBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a93f5a40-483f-4c46-ba09-3e1033b62552/mini.png)](https://insight.sensiolabs.com/projects/a93f5a40-483f-4c46-ba09-3e1033b62552)

## Features

* Create a fully featured hypermedia REST API in minutes with CRUD support and pagination
* Automatic Doctrine ORM support
* Validation groups
* Serialization groups
* Automatic routes registration
* [JSON-LD](http://json-ld.org) serialization (hypermedia) including external contexts (for performance)
* Automatic [Hydra](http://hydra-cg.com) documentation guessed from PHPDoc, Serializer, Validator and Doctrine ORM mappings
* Automatic entrypoint generation giving access to all resources
* [FOSUserBundle](https://github.com/FriendsOfSymfony/FOSUserBundle) bridge
* Fully customizable trough a powerful event system and strong OOP
* Tested with Behat

## Resources

* [API-first et Linked Data avec Symfony](http://les-tilleuls.coop/slides/dunglas/slides-sfPot-2015-01-15/#/) (in french)

## TODO

* [ ] Filters (hydra:search)
* [ ] Externals IRIs support
* [ ] Spec classes with PHPSpec
* [ ] Documentation
