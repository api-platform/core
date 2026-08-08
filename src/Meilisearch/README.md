# API Platform - Meilisearch Support

Integration for [Meilisearch](https://www.meilisearch.com) with the [API Platform](https://api-platform.com) framework.

Modeled on `api-platform/elasticsearch`, with two deliberate deviations:

- **One client, one exception type.** Meilisearch has a single official PHP client (MIT licensed) and a single `ApiException` with a typed `httpStatus` property, so `CollectionProvider`/`ItemProvider` don't need Elasticsearch's `V7Client|Client|OpenSearchClient` union type or its three different 404-exception classes.
- **Flat search parameters, not a nested query body.** Meilisearch's API takes `q`, a single `filter` expression string, `sort: ["field:asc"]`, `facets: [...]`, `limit`/`offset` — there's no bool/must/should tree to build. `RequestParametersCollectionExtensionInterface` and `FilterInterface` operate on that flat array; composing multiple filters is AND-joining expression strings, not merging JSON.

Per-operation config works exactly like Elasticsearch's: attach `stateOptions: new ApiPlatform\Meilisearch\State\Options(index: 'movie')` to an operation and `MeilisearchProviderResourceMetadataCollectionFactory` auto-assigns `CollectionProvider`/`ItemProvider` — no `provider:` needed, no route-name matching.

**Caveat not present in the Elasticsearch integration:** Meilisearch requires attributes to be pre-declared as `filterableAttributes`/`sortableAttributes` on the index, or a query against them 400s. This package does not manage index settings — that's the caller's responsibility (e.g. via whatever indexes the documents in the first place).

> [!CAUTION]
>
> This is a read-only sub split of `api-platform/core`, please
> [report issues](https://github.com/api-platform/core/issues) and
> [send Pull Requests](https://github.com/api-platform/core/pulls)
> in the [core API Platform repository](https://github.com/api-platform/core).
