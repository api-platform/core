Feature: Multiple filters on collections
  In order to retrieve large collections of filtered resources
  As a client software developer
  I need to retrieve collections filtered by multiple parameters

  @createSchema
  @dropSchema
  Scenario: Get collection filtered by multiple parameters
    Given there is "30" dummy objects with dummyDate and dummyBoolean true
    And there is "20" dummy objects with dummyDate and dummyBoolean false
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-28&dummyBoolean=1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/28$"},
                  {"pattern": "^/dummies/29$"}
                ]
              }
            }
          },
          "maxItems": 2
        },
        "hydra:view": {
          "type": "object",
          "properties": {
          "@id": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-28&dummyBoolean=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

