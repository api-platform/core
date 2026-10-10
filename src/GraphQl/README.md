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

The subscription registry groups operations in a shared bucket for each resource,
item IRI (for item subscriptions), and private partition. Item and collection
registrations use separate buckets. Each bucket maps GraphQL short name/operation name pairs to
lists of subscription IDs and selected fields. Operation identity remains part of each
subscription ID, so sharing a bucket does not merge registrations across operations.
Private field names and values determine the partition; their configured order does
not create separate buckets.

Individual-item payloads are represented by SHA-256 fingerprints in separate cache
entries; collection subscriptions do not retain payloads or fingerprints.

Symfony wires two cache pools:

- `api_platform.graphql.cache.subscription`: the registration registry;
- `api_platform.graphql.cache.subscription_fingerprint`: fingerprints, inheriting
  the registry pool's adapter configuration by default.

`SubscriptionStore` receives these pools and a Symfony `LockFactory` through
injection. The `api_platform.graphql.subscription.lock_factory` alias points to
Symfony's `lock.factory`, configured through `framework.lock`. It can instead
reference a named lock factory when subscriptions need their own lock backend.
API Platform does not construct or select a lock store. The factory is a required
dependency: enable `framework.lock` or supply the subscription lock factory alias.
A missing factory fails container compilation, including for query-only GraphQL
applications using the default service wiring. There is no unlocked fallback.

When sharing subscription caches between servers, configure a shared lock backend
(such as Redis) accessible to every registration and publication worker. Both cache
pools must also use shared, coherent storage. Cache pools expose no portable way
to derive their connection for the Lock component; configure the cache and lock
backends together. Local locks are suitable only when all writers share the same
local storage and lock scope.

Lock acquisition uses Symfony's blocking `acquire(true)` with a **5-second
lease** on expiring backends. Symfony handles waiting and retries. There is no
separate acquisition deadline: repeated contention can keep a waiter blocked
longer than five seconds. Registration and bucket-removal acquisition failures
propagate. The store does not add lease-expiry checks or exceptions; operations
that outlast the lease may overlap another writer. Configure backend
connection/read timeouts well below the lease duration.
For Redis, also configure the lock store's initial TTL to 5 seconds: Symfony
applies the requested lease after its initial acquisition. Non-expiring stores
retain their backend-specific release behavior.

Registration and deletion lock their registry bucket to serialize concurrent mutations
while the lease is held.
Item fingerprints are plain SHA-256 hashes, read in bulk and replaced after
successful publication without additional locks: the last successful fingerprint
write wins. Normalization, hashing, and Mercure/Messenger calls occur outside locks.
Re-registration reuses the subscription ID. If its initial payload differs from
the stored fingerprint, the store writes null so the next event is eligible for
existing and newly enrolled clients. An identical initial payload preserves the
fingerprint. Collections have no fingerprints.

Fingerprint writes during registration must succeed for enrollment to succeed.
After publication, fingerprint write failures are logged and the stale fingerprint
is discarded on a best-effort basis so delivery continues to other subscribers.
Delete cleanup errors are logged without dropping recipients. A late acknowledgement
can replace re-registration invalidation or recreate an unused fingerprint after
deletion, but cannot recreate a registration. Neither pool is assigned an expiry;
entries remain until explicit deletion, clearing, or backend-configured removal.

Concurrent identical publications can still occur; this is change suppression,
not an exactly-once delivery or ordering guarantee. Durable delivery is outside
this feature's scope because it requires a generic delivery design for API Platform.

The Doctrine publisher passes the applicable operations and their objects/delete
snapshots for one changed resource to `SubscriptionManagerInterface::getUpdates()`.
The manager groups them by registry key and loads each shared bucket once per call,
then yields each prepared update together with its operation. This is one registry
read for three item subscription operations sharing an item and private partition;
collection registrations and different partitions have separate lookups. Item
fingerprints are fetched in bulk per bucket and acknowledged individually.
Processing an item bucket for deletion removes all its registrations and fingerprints,
including those for operations whose delivery is currently disabled; collection buckets remain.
After a successful flush, the listener processes deletions before creates and updates.
Delete delivery errors are retained while the remaining delete notifications are attempted,
allowing the store to retire every affected item bucket as it is read. Delete updates
are acknowledged after every attempt, including failed delivery. The first delivery
error is rethrown after the deletion pass, and the listener resets its buffers. Create and
update delivery errors propagate immediately. Collection registrations remain available.
Synchronous deletion delivery has no automatic replay; messages already accepted by
Messenger can still be retried using their queued topic and payload.
The publisher uses each operation's own Mercure options, publishes the update, then
explicitly calls `acknowledge()` for that update.
The manager handles normalization; the store owns fingerprint comparison and
acknowledgement. Preparing or iterating updates does not advance their fingerprints.
A fingerprint advances only after successful hub acceptance for synchronous delivery
or Messenger dispatch acceptance for asynchronous delivery. Worker retries remain
Messenger's responsibility; acceptance does not imply client delivery.

Custom subscription managers must implement the complete `SubscriptionManagerInterface`:
registration receives the subscription operation, `getUpdates()` accepts a list of
object/operation pairs for one changed resource and yields operation/update pairs,
and `acknowledge()` records successful publication or completion of a delete attempt. The previous
`getPushPayloads()` contract is removed.

The cache layout is internal and has no migration path. When upgrading, stop old
registration and publication workers, clear subscription state, and re-establish
subscriptions: subscription IDs and Mercure topics change with this layout.
Old and new workers must not share this registry. Fingerprint cache
eviction can cause an additional publication without losing registrations.
