# Changelog

## 1.0.0 beta 4

* Use string values for `Dunglas\ApiBundle\Doctrine\Orm\Filter\DateFilter` null-management constants

## 1.0.0 beta 3

* The Hydra documentation URL is now `/apidoc` (was `/vocab`)
* Exceptions implements `Dunglas\ApiBundle\Exception\ExceptionInterface`
* Prefix automatically generated route names by `api_`
* Automatic detection of the method of the entity class returning the identifier when using Doctrine (previously `getId()` was always used)
* New extension point in `Dunglas\ApiBundle\Doctrine\Orm\DataProvider` allowing to customize Doctrine paginator and performance optimization when using typical queries
* New `Dunglas\ApiBundle\JsonLd\Event\Events::CONTEXT_BUILDER` event allowing to modify the JSON-LD context
* Change HTTP status code from `202` to `200` for `PUT` requests
* Ability to embed the JSON-LD context instead of embedding it

## 1.0.0 beta 2

* Preserve indexes when normalizing and denormalizing associative arrays
* Allow to set default order for property when registering a `Doctrine\Orm\Filter\OrderFilter` instance
