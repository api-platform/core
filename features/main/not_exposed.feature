@php8
@v3
Feature: Expose only a collection of objects

  Background:
    Given I add "Accept" header equal to "application/ld+json"

  # A NotExposed operation with "routeName: api_genid" is automatically added to this resource.
  Scenario: Get a collection of objects without identifiers from a single resource with a single collection
    When I send a "GET" request to "/chairs"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "hydra:member", "hydra:totalItems"],
      "properties": {
        "@context": {"pattern": "^/contexts/Chair$"},
        "@id": {"pattern": "^/chairs$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "additionalProperties": false,
            "required": ["@id", "@type", "id", "owner"],
            "properties": {
              "@id": {"pattern": "^/.well-known/genid/.+$"},
              "@type":  {"pattern": "^Chair$"},
              "id": {"type": "string"},
              "owner": {"type": "string"}
            }
          },
          "minItems": 2,
          "maxItems": 2,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "integer", "minimum": 2, "maximum": 2}
      }
    }
    """

  # A NotExposed operation with a valid path (e.g.: "/tables/{id}") is automatically added to this resource.
  Scenario: Get a collection of objects with identifiers from a single resource with a single collection
    When I send a "GET" request to "/tables"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "hydra:member", "hydra:totalItems"],
      "properties": {
        "@context": {"pattern": "^/contexts/Table$"},
        "@id": {"pattern": "^/tables$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "additionalProperties": false,
            "required": ["@id", "@type", "id", "owner"],
            "properties": {
              "@id": {"pattern": "^/tables/.+$"},
              "@type":  {"pattern": "^Table$"},
              "id": {"type": "string"},
              "owner": {"type": "string"}
            }
          },
          "minItems": 2,
          "maxItems": 2,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "integer", "minimum": 2, "maximum": 2}
      }
    }
    """

  # A NotExposed operation with a valid path (e.g.: "/forks/{id}") is automatically added to the last resource.
  # This operation does not inherit from the resource uriTemplate as it's not intended to.
  Scenario Outline: Get a collection of objects with identifiers from a multiple resources class with multiple collections
    When I send a "GET" request to "<uri>"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "hydra:member", "hydra:totalItems"],
      "properties": {
        "@context": {"pattern": "^/contexts/Fork$"},
        "@id": {"pattern": "^<uri>"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "additionalProperties": false,
            "required": ["@id", "@type", "id", "owner"],
            "properties": {
              "@id": {"pattern": "^/forks/.+$"},
              "@type":  {"pattern": "^Fork$"},
              "id": {"type": "string"},
              "owner": {"type": "string"}
            }
          },
          "minItems": 2,
          "maxItems": 2,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "integer", "minimum": 2, "maximum": 2}
      }
    }
    """
    Examples:
      | uri          |
      | /forks       |
      | /fourchettes |


  # A NotExposed operation is not automatically added.
  Scenario Outline: Get a collection of objects with identifiers from a multiple resources class with multiple collections and an item operation
    When I send a "GET" request to "<uri>"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "hydra:member", "hydra:totalItems"],
      "properties": {
        "@context": {"pattern": "^/contexts/Spoon$"},
        "@id": {"pattern": "^<uri>"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "additionalProperties": false,
            "required": ["@id", "@type", "id", "owner"],
            "properties": {
              "@id": {"pattern": "^/cuillers/.+$"},
              "@type":  {"pattern": "^Spoon$"},
              "id": {"type": "string"},
              "owner": {"type": "string"}
            }
          },
          "minItems": 2,
          "maxItems": 2,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "integer", "minimum": 2, "maximum": 2}
      }
    }
    """
    Examples:
      | uri       |
      | /spoons   |
      | /cuillers |

  Scenario Outline: Get a not exposed route returns a 404 with an explanation
    When I send a "GET" request to "<uri>"
    Then the response status code should be 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the JSON node "hydra:description" should be equal to "<hydra:description>"
    Examples:
      | uri                      | hydra:description                                                                                                          |
      | /.well-known/genid/12345 | This route is not exposed on purpose. It generates an IRI for a collection resource without identifier nor item operation. |
      | /tables/12345            | This route does not aim to be called.                                                                                      |
      | /forks/12345             | This route does not aim to be called.                                                                                      |

  Scenario: Get a single item still works
    When I send a "GET" request to "/cuillers/12345"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/Spoon",
        "@id": "/cuillers/12345",
        "@type": "Spoon",
        "id": "12345",
        "owner": "Vincent"
    }
    """
