Feature: JSON-LD using iri_only parameter
  In order to improve Vulcain support
  As a Vulcain user and as a developer
  I should be able to only get an IRI list when I ask a resource.

  Scenario Outline: Retrieve Dummy's resource context with iri_only
    When I send a "GET" request to "<uri>"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
      """
      {
          "@context": {
              "@vocab": "http://example.com/docs.jsonld#",
              "hydra": "http://www.w3.org/ns/hydra/core#",
              "hydra:member": {
                  "@type": "@id"
              }
          }
      }
      """
    Examples:
      | uri                           |
      | /contexts/IriOnlyDummy        |
      | /contexts/IriOnlyDummy.jsonld |

  Scenario: Retrieve Dummy's resource context with invalid format returns an error
    When I send a "GET" request to "/contexts/IriOnlyDummy.json"
    Then the response status code should be 404

  @createSchema
  Scenario: Retrieve Dummies with iri_only and jsonld_embed_context
    Given there are 3 iriOnlyDummies
    When I send a "GET" request to "/iri_only_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
      """
      {
          "@context": {
              "@vocab": "http://example.com/docs.jsonld#",
              "hydra": "http://www.w3.org/ns/hydra/core#",
              "hydra:member": {
                  "@type": "@id"
              }
          },
          "@id": "/iri_only_dummies",
          "@type": "hydra:Collection",
          "hydra:member": [
              "/iri_only_dummies/1",
              "/iri_only_dummies/2",
              "/iri_only_dummies/3"
          ],
          "hydra:totalItems": 3
      }
      """

  @createSchema
  Scenario: Retrieve Resource with uriTemplate collection Property
    Given there are propertyCollectionIriOnly with relations
    When I send a "GET" request to "/property_collection_iri_onlies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be a superset of:
      """
      {
        "hydra:member": [
          {
            "@id": "/property_collection_iri_onlies/1",
            "@type": "PropertyCollectionIriOnly",
            "propertyCollectionIriOnlyRelation": "/property-collection-relations",
            "iterableIri": "/parent/1/another-collection-operations",
            "toOneRelation": "/parent/1/property-uri-template/one-to-ones/1"
          }
        ]
      }
      """
    When I send a "GET" request to "/property_collection_iri_onlies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be a superset of:
      """
      {
        "@context": "/contexts/PropertyCollectionIriOnly",
        "@id": "/property_collection_iri_onlies/1",
        "@type": "PropertyCollectionIriOnly",
        "propertyCollectionIriOnlyRelation": "/property-collection-relations",
        "iterableIri": "/parent/1/another-collection-operations",
        "toOneRelation": "/parent/1/property-uri-template/one-to-ones/1"
      }
      """
