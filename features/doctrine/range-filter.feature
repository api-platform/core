@rangeFilter
Feature: Range filter on collections
  In order to filter results from large collections of resources
  As a client software developer
  I need to filter collections by range

  @createSchema
  Scenario: Get collection filtered by range (between)
    Given there is "30" dummy objects with dummyPrice
    When I send a "GET" request to "/dummies?dummyPrice[between]=12.99..15.99"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyPrice\\[between\\]=12.99..15.99$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 15},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"},
                  {"pattern": "^/dummies/6$"},
                  {"pattern": "^/dummies/7$"},
                  {"pattern": "^/dummies/10$"},
                  {"pattern": "^/dummies/11$"},
                  {"pattern": "^/dummies/14$"},
                  {"pattern": "^/dummies/15$"},
                  {"pattern": "^/dummies/18$"},
                  {"pattern": "^/dummies/19$"},
                  {"pattern": "^/dummies/22$"},
                  {"pattern": "^/dummies/23$"},
                  {"pattern": "^/dummies/26$"},
                  {"pattern": "^/dummies/27$"},
                  {"pattern": "^/dummies/30$"}
                ]
              }
            }
          },
          "maxItems": 15
        }
      }
    }
    """

  Scenario: Filter for entities by range (less than)
    When I send a "GET" request to "/dummies?dummyPrice[lt]=12.99"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyPrice\\[lt\\]=12.99$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 8},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/5$"},
                  {"pattern": "^/dummies/9$"},
                  {"pattern": "^/dummies/13$"},
                  {"pattern": "^/dummies/17$"},
                  {"pattern": "^/dummies/21$"},
                  {"pattern": "^/dummies/25$"},
                  {"pattern": "^/dummies/29$"}
                ]
              }
            }
          },
          "maxItems": 8
        }
      }
    }
    """

  Scenario: Filter for entities by range (less than or equal)
    When I send a "GET" request to "/dummies?dummyPrice[lte]=12.99"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyPrice\\[lte\\]=12.99$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 16},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/5$"},
                  {"pattern": "^/dummies/6$"},
                  {"pattern": "^/dummies/9$"},
                  {"pattern": "^/dummies/10$"},
                  {"pattern": "^/dummies/13$"},
                  {"pattern": "^/dummies/14$"},
                  {"pattern": "^/dummies/17$"},
                  {"pattern": "^/dummies/18$"},
                  {"pattern": "^/dummies/21$"},
                  {"pattern": "^/dummies/22$"},
                  {"pattern": "^/dummies/25$"},
                  {"pattern": "^/dummies/26$"},
                  {"pattern": "^/dummies/29$"},
                  {"pattern": "^/dummies/30$"}
                ]
              }
            }
          },
          "maxItems": 16
        }
      }
    }
    """

  Scenario: Filter for entities by range (greater than)
    When I send a "GET" request to "/dummies?dummyPrice[gt]=15.99"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyPrice\\[gt\\]=15.99$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 7},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/4$"},
                  {"pattern": "^/dummies/8$"},
                  {"pattern": "^/dummies/12$"},
                  {"pattern": "^/dummies/15$"},
                  {"pattern": "^/dummies/20$"},
                  {"pattern": "^/dummies/24$"},
                  {"pattern": "^/dummies/28$"}
                ]
              }
            }
          },
          "maxItems": 7
        }
      }
    }
    """

  Scenario: Filter for entities by range (greater than or equal)
    When I send a "GET" request to "/dummies?dummyPrice[gte]=15.99"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyPrice\\[gte\\]=15.99$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 14},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/3$"},
                  {"pattern": "^/dummies/4$"},
                  {"pattern": "^/dummies/7$"},
                  {"pattern": "^/dummies/8$"},
                  {"pattern": "^/dummies/11$"},
                  {"pattern": "^/dummies/12$"},
                  {"pattern": "^/dummies/14$"},
                  {"pattern": "^/dummies/15$"},
                  {"pattern": "^/dummies/19$"},
                  {"pattern": "^/dummies/20$"},
                  {"pattern": "^/dummies/23$"},
                  {"pattern": "^/dummies/24$"},
                  {"pattern": "^/dummies/27$"},
                  {"pattern": "^/dummies/28$"}
                ]
              }
            }
          },
          "maxItems": 14
        }
      }
    }
    """

  Scenario: Filter for entities by range (greater than and less than)
    When I send a "GET" request to "/dummies?dummyPrice[gt]=12.99&dummyPrice[lt]=19.99"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyPrice\\[gt\\]=12.99\\&dummyPrice\\[lt\\]=19.99$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 7},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/3$"},
                  {"pattern": "^/dummies/7$"},
                  {"pattern": "^/dummies/11$"},
                  {"pattern": "^/dummies/15$"},
                  {"pattern": "^/dummies/19$"},
                  {"pattern": "^/dummies/23$"},
                  {"pattern": "^/dummies/27$"}
                ]
              }
            }
          },
          "maxItems": 7
        }
      }
    }
    """

  @dropSchema
  Scenario: Filter for entities within an impossible range
    When I send a "GET" request to "/dummies?dummyPrice[gt]=19.99"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyPrice\\[gt\\]=19.99$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 0
        }
      }
    }
    """
