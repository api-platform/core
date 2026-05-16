<?php
// ---
// slug: declare-hydra-operations
// name: Declare Hydra operations
// position: 21
// executable: false
// tags: design, hydra, jsonld
// ---

namespace App\ApiResource {
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\Get;
    use ApiPlatform\Metadata\GetCollection;
    use ApiPlatform\Metadata\HydraOperation;
    use ApiPlatform\Metadata\Post;

    // Issues are publicly readable and may be reported by any authenticated
    // user. Only an administrator may delete an issue — and rather than
    // exposing the DELETE operation globally on the resource (which would
    // leak its existence to every consumer of the Hydra documentation), the
    // operation is declared **per representation** with `#[HydraOperation]`.
    //
    // The `security` expression is evaluated when the issue is serialized; the
    // expression has access to `object` (the current Issue), `user` (the
    // current security token's user) and `request`.
    #[ApiResource(
        operations: [
            new Get(),
            new GetCollection(),
            new Post(),
        ],
    )]
    #[HydraOperation(
        method: 'DELETE',
        title: 'Delete this issue',
        security: "is_granted('ROLE_ADMIN')",
    )]
    class Issue
    {
        public string $id;
        public string $title;
        public string $reporter;
    }
}

// When an admin requests `/issues/42`, the JSON-LD response carries an extra
// `hydra:operation` entry advertising the DELETE capability:
//
// ```json
// {
//   "@context": "/contexts/Issue",
//   "@id": "/issues/42",
//   "@type": "Issue",
//   "title": "Login fails on Firefox",
//   "reporter": "/users/7",
//   "hydra:operation": [
//     {
//       "@type": ["hydra:Operation", "schema:DeleteAction"],
//       "hydra:method": "DELETE",
//       "hydra:title": "Delete this issue",
//       "returns": "owl:Nothing"
//     }
//   ]
// }
// ```
//
// When the same resource is requested by a non-admin, the `hydra:operation`
// property is omitted entirely.
