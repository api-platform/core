@elasticsearch
Feature: Range filter on collections from Elasticsearch
  In order to filter resources by a numeric or date range from Elasticsearch
  As a client software developer
  I need to query for resources matching range comparison operators

  Scenario: Range filter using the gt operator
    When I send a "GET" request to "/products?price%5Bgt%5D=20"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Product$"},
        "@id": {"pattern": "^/products$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "minItems": 3,
          "maxItems": 3,
          "items": {
            "type": "object",
            "properties": {
              "price": {"type": "integer", "minimum": 21}
            },
            "required": ["price"]
          }
        }
      }
    }
    """

  Scenario: Range filter combining gte and lte (bounded range)
    When I send a "GET" request to "/products?price%5Bgte%5D=10&price%5Blte%5D=30"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Product$"},
        "@id": {"pattern": "^/products$"},
        "hydra:member": {
          "type": "array",
          "minItems": 3,
          "maxItems": 3,
          "items": {
            "type": "object",
            "properties": {
              "price": {"type": "integer", "minimum": 10, "maximum": 30}
            },
            "required": ["price"]
          }
        }
      }
    }
    """

  Scenario: Range filter using the lt operator
    When I send a "GET" request to "/products?price%5Blt%5D=20"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "hydra:member": {
          "type": "array",
          "minItems": 1,
          "maxItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "price": {"type": "integer", "maximum": 19}
            },
            "required": ["price"]
          }
        }
      }
    }
    """

  Scenario: Range filter on a date property using gte
    When I send a "GET" request to "/products?releaseDate%5Bgte%5D=2023-01-01"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "hydra:member": {
          "type": "array",
          "minItems": 2,
          "maxItems": 2
        }
      }
    }
    """

  Scenario: Range filter ignores unknown operators
    When I send a "GET" request to "/products?price%5Bgte%5D=30&price%5Bunknown%5D=99"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "hydra:member": {
          "type": "array",
          "minItems": 3,
          "maxItems": 3,
          "items": {
            "type": "object",
            "properties": {
              "price": {"type": "integer", "minimum": 30}
            },
            "required": ["price"]
          }
        }
      }
    }
    """
