Feature: Collections support
  In order to retrieve large collections of resources
  As a client software developer
  I need to retrieve paged collections respecting the Hydra specification

  @createSchema
  Scenario: Retrieve an empty collection
    When I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 0},
        "hydra:itemsPerPage": {"type":"number", "maximum": 3},
        "hydra:firstPage": {"pattern": "^/dummies$"},
        "hydra:lastPage": {"pattern": "^/dummies$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 0
        },
        "hydra:search": {}
      },
      "additionalProperties": false
    }
    """

  Scenario: Retrieve the first page of a collection
    Given there is "30" dummy objects
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 30},
        "hydra:firstPage": {"pattern": "^/dummies$"},
        "hydra:lastPage": {"pattern": "^/dummies\\?page=10$"},
        "hydra:nextPage": {"pattern": "^/dummies\\?page=2$"},
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
          "maxItems": 3
        }
      }
    }
    """

  Scenario: Retrieve a page of a collection
    When I send a "GET" request to "/dummies?page=7"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?page=7$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 30},
        "hydra:firstPage": {"pattern": "^/dummies$"},
        "hydra:lastPage": {"pattern": "^/dummies\\?page=10$"},
        "hydra:nextPage": {"pattern": "^/dummies\\?page=8$"},
        "hydra:previousPage": {"pattern": "^/dummies\\?page=6$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/19$"},
                  {"pattern": "^/dummies/20$"},
                  {"pattern": "^/dummies/21$"}
                ]
              }
            }
          },
          "maxItems": 3
        }
      }
    }
    """

  Scenario: Retrieve the last page of a collection
    When I send a "GET" request to "/dummies?page=10"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?page=10$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 30},
        "hydra:itemsPerPage": {"type":"number", "maximum": 3},
        "hydra:firstPage": {"pattern": "^/dummies$"},
        "hydra:lastPage": {"pattern": "^/dummies\\?page=10$"},
        "hydra:previousPage": {"pattern": "^/dummies\\?page=9$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/28$"},
                  {"pattern": "^/dummies/29$"},
                  {"pattern": "^/dummies/30$"}
                ]
              }
            }
          },
          "maxItems": 3
        },
        "hydra:search": {}
      },
      "additionalProperties": false
    }
    """

  Scenario: Retrieve a non-existing page of the collection
    When I send a "GET" request to "/dummies?page=11"
    Then the response status code should be 404

  Scenario: Change the number of element by page client side
    When I send a "GET" request to "/dummies?page=2&itemsPerPage=10"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?page=2\\&itemsPerPage=10$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 30},
        "hydra:itemsPerPage": {"type":"number", "maximum": 10},
        "hydra:firstPage": {"pattern": "^/dummies\\?itemsPerPage=10$"},
        "hydra:lastPage": {"pattern": "^/dummies\\?itemsPerPage=10\\&page=3$"},
        "hydra:previousPage": {"pattern": "^/dummies\\?itemsPerPage=10$"},
        "hydra:nextPage": {"pattern": "^/dummies\\?itemsPerPage=10\\&page=3$"},
        "hydra:member": {
          "type": "array",
          "minItems": 10,
          "maxItems": 10
        },
        "hydra:search": {}
      },
      "additionalProperties": false
    }
    """

  Scenario: Filter with exact match
    When I send a "GET" request to "/dummies?id=8"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?id=8$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 1},
        "hydra:itemsPerPage": {"type":"number", "maximum": 3},
        "hydra:firstPage": {"pattern": "^/dummies\\?id=8"},
        "hydra:lastPage": {"pattern": "^/dummies\\?id=8"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/dummies/8$"}
            }
          },
          "maxItems": 1
        },
        "hydra:search": {}
      },
      "additionalProperties": false
    }
    """

  Scenario: Filter with a raw URL
    When I send a "GET" request to "/dummies?id=%2fdummies%2f8"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?id=%2fdummies%2f8$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 1},
        "hydra:itemsPerPage": {"type":"number", "maximum": 3},
        "hydra:firstPage": {"pattern": "^/dummies\\?id=%2Fdummies%2F8$"},
        "hydra:lastPage": {"pattern": "^/dummies\\?id=%2Fdummies%2F8$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/dummies/8$"}
            }
          },
          "maxItems": 1
        },
        "hydra:search": {}
      },
      "additionalProperties": false
    }
    """

  @dropSchema
  Scenario: Filter with non-exact match
    When I send a "GET" request to "/dummies?name=Dummy%20%238"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?name=Dummy%20%238$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 1},
        "hydra:itemsPerPage": {"type":"number", "maximum": 3},
        "hydra:firstPage": {"pattern": "^/dummies\\?name=Dummy\\+%238$"},
        "hydra:lastPage": {"pattern": "^/dummies\\?name=Dummy\\+%238$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/dummies/8$"}
            }
          },
          "maxItems": 1
        },
        "hydra:search": {}
      },
      "additionalProperties": false
    }
    """
