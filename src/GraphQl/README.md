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
Re-registration reuses the subscription ID. If its initial payload differs from
the stored fingerprint, subscribers sharing that topic may now hold different
values. The store writes a null hash with a new version, allowing the next event
through regardless of which value it contains. Older acknowledgements preserve
this invalidation; a publication prepared against the new version can restore
normal suppression. An identical initial payload leaves a matching fingerprint
unchanged. Collections still have no fingerprints.

Fingerprint writes during registration must succeed for enrollment to succeed.
After publication, fingerprint write failures are logged and the stale fingerprint
is discarded on a best-effort basis so delivery continues to other subscribers. Delete cleanup
failures are also logged without dropping the delete recipients. Cache failures
can leave stale entries if invalidation also fails; restore cache health and clear
subscription state when necessary.

Concurrent identical publications can still occur; this is change suppression,
not an exactly-once delivery or ordering guarantee.

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
