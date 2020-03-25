Feature: JSON-LD using IriOnly parameter
  In order to improve Vulcain support
  As a vulcain user and as a developer
  I should be able to only get an IRI list when I ask a resource to do so.

  Scenario: Retrieve Dummy's resource context with IriOnly
    When I send a "GET" request to "/contexts/IriOnlyDummy"
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

  @createSchema
  Scenario: Retrieve Dummies with an embedded IriOnly context
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
