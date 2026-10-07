# API Platform - GraphQL

The [GraphQL](https://graphql.org/) component of the [API Platform](https://api-platform.com) framework.

[Documentation](https://api-platform.com/docs/core/graphql/)

> [!CAUTION]
>
> This is a read-only sub split of `api-platform/core`, please
> [report issues](https://github.com/api-platform/core/issues) and
> [send Pull Requests](https://github.com/api-platform/core/pulls)
> in the [core API Platform repository](https://github.com/api-platform/core).

## Subscription state and publication

The subscription registry stores IDs and selected fields. Individual-item payloads
are represented by SHA-256 fingerprints in separate cache entries; collection
subscriptions do not retain payloads or fingerprints. IDs and Mercure topics are
unchanged.

Symfony wires two cache pools:

- `api_platform.graphql.cache.subscription`: the registration registry;
- `api_platform.graphql.cache.subscription_fingerprint`: fingerprints, inheriting
  the registry pool's adapter configuration by default.

`SubscriptionStore` receives these pools and a Symfony `LockFactory` through
injection. The `api_platform.graphql.subscription.lock_factory` alias points to
Symfony's `lock.factory`, configured through `framework.lock`. It can instead
reference a named lock factory when subscriptions need their own lock backend.
API Platform does not construct or select a lock store. Query-only applications
can leave locking disabled; registering subscriptions requires a configured
factory and fails explicitly if it is missing. There is no unlocked fallback.

When sharing subscription caches between servers, configure a shared lock backend
(such as Redis) accessible to every registration and publication worker. Both cache
pools must also use shared, coherent storage. Cache pools expose no portable way
to derive their connection for the Lock component; configure the cache and lock
backends together. Local locks are suitable only when all writers share the same
local storage and lock scope.

Registration and deletion lock their registry bucket. Successful item publications
update their fingerprint under a separate per-subscription lock. Normalization,
hashing, and Mercure/Messenger calls occur outside those locks. Versioned
comparison detects fingerprints changed in the meantime. Conflicting completions
invalidate the fingerprint so the next event is not incorrectly suppressed.
Fingerprint write failures are logged and the stale fingerprint is discarded on
a best-effort basis so delivery continues to other subscribers. Delete cleanup
failures are also logged without dropping the delete recipients. Cache failures
can leave stale entries if invalidation also fails; restore cache health and clear
subscription state when necessary.

Concurrent identical publications can still occur; this is change suppression,
not an exactly-once delivery or ordering guarantee.

The Doctrine publisher iterates `SubscriptionManagerInterface::getUpdates()`,
publishes each prepared update, then explicitly calls `acknowledge()` for that update.
The manager handles normalization; the store owns fingerprint comparison and
acknowledgement. Preparing or iterating updates does not advance their fingerprints.
A fingerprint advances only after successful hub acceptance for synchronous delivery
or Messenger dispatch acceptance for asynchronous delivery. Worker retries remain
Messenger's responsibility; acceptance does not imply client delivery.

Custom subscription managers must implement the complete `SubscriptionManagerInterface`:
registration receives the subscription operation, `getUpdates()` prepares updates
for that operation, and `acknowledge()` records successful publication. The previous
`getPushPayloads()` contract is removed.

The cache layout is internal and has no migration path. When upgrading, stop old
registration and publication workers, clear subscription state, and re-establish
subscriptions. Old and new workers must not share this registry. Fingerprint cache
eviction can cause an additional publication without losing registrations.
