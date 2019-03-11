Feature: Numeric filter on collections
  In order to retrieve ordered large collections of resources
  As a client software developer
  I need to retrieve collections with numerical value

  @createSchema
  Scenario: Get collection by dummyPrice=9.99
    Given there are 10 dummy objects with dummyPrice
    When I send a "GET" request to "/dummies?dummyPrice=9.99"
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
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/5$"},
                  {"pattern": "^/dummies/9$"}
                ]
              }
            }
          },
          "minItems": 3,
          "maxItems": 3,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "number", "minimum": 3, "maximum": 3},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyPrice=9.99"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  @createSchema
  Scenario: Get collection by multiple dummyPrice
    Given there are 10 dummy objects with dummyPrice
    When I send a "GET" request to "/dummies?dummyPrice[]=9.99&dummyPrice[]=12.99"
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
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/5$"}
                ]
              }
            }
          },
          "minItems": 3,
          "maxItems": 3,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "number", "minimum": 6, "maximum": 6},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyPrice%5B%5D=9.99&dummyPrice%5B%5D=12.99"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection by non-numeric dummyPrice=marty
    Given there are 10 dummy objects with dummyPrice
    When I send a "GET" request to "/dummies?dummyPrice=marty"
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
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          },
          "minItems": 3,
          "maxItems": 3,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "number", "minimum": 20, "maximum": 20},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyPrice=marty"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
