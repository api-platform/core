# Performances

## Enabling the metadata cache

Computing metadata used by the bundle is a costly operation. Fortunately, metadata can be computed once then cached. The
bundle provides a built-in cache service using [APCu](https://github.com/krakjoe/apcu).
To enable it in the prod environment (requires APCu to be installed), add the following lines to `app/config/config_prod.yml`:

```yaml
dunglas_api:
    cache: api.mapping.cache.apc
```

DunglasApiBundle leverages [Doctrine Cache](https://github.com/doctrine/cache) to abstract the cache backend. If
you want to use a custom cache backend such as Redis, Memcache or MongoDB, register a Doctrine Cache provider as a service
and set the `cache` config key to the id of the custom service you created.

A built-in cache warmer will be automatically executed every time you clear or warmup the cache if a cache service is configured.

Next chapter: [AngularJS Integration](angular-integration.md)
Previous chapter: [Using external (JSON-LD) vocabularies](external-vocabularies.md)
