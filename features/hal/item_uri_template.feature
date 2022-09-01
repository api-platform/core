@php8
@v3
Feature: Exposing a collection of objects should use the specified operation to generate the IRI

  Scenario: Get a collection of objects without any itemUriTemplate should generate the IRI from the first Get operation
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/cars"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["_links", "_embedded", "totalItems"],
      "properties": {
        "_links": {
          "type": "object",
          "properties": {
            "self": {
              "type": "object",
              "properties": {"href": {"pattern": "^/cars$"}}
            },
            "item": {
              "type": "array",
              "minItems": 2,
              "maxItems": 2,
              "items": {
                "type": "object",
                "properties": {"href": {"pattern": "^/cars/.+$"}}
              }
            }
          }
        },
        "totalItems": {"type":"number", "minimum": 2, "maximum": 2},
        "_embedded": {
          "type": "object",
          "properties": {
            "item": {
              "type": "array",
              "minItems": 2,
              "maxItems": 2,
              "items": {
                "type": "object",
                "properties": {
                  "_links": {
                    "type": "object",
                    "properties": {
                      "self": {
                        "type": "object",
                        "properties": {"href": {"pattern": "^/cars/.+$"}}
                      }
                    }
                  },
                  "id": {"type": "string"},
                  "owner": {"type": "string"}
                }
              }
            }
          }
        }
      }
    }
    """

  Scenario: Get a collection of objects with an itemUriTemplate should generate the IRI from the correct operation
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/brands/renault/cars"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["_links", "_embedded", "totalItems"],
      "properties": {
        "_links": {
          "type": "object",
          "properties": {
            "self": {
              "type": "object",
              "properties": {"href": {"pattern": "^/brands/renault/cars$"}}
            },
            "item": {
              "type": "array",
              "minItems": 2,
              "maxItems": 2,
              "items": {
                "type": "object",
                "properties": {"href": {"pattern": "^/brands/renault/cars/.+$"}}
              }
            }
          }
        },
        "totalItems": {"type":"number", "minimum": 2, "maximum": 2},
        "_embedded": {
          "type": "object",
          "properties": {
            "item": {
              "type": "array",
              "minItems": 2,
              "maxItems": 2,
              "items": {
                "type": "object",
                "properties": {
                  "_links": {
                    "type": "object",
                    "properties": {
                      "self": {
                        "type": "object",
                        "properties": {"href": {"pattern": "^/brands/renault/cars/.+$"}}
                      }
                    }
                  },
                  "id": {"type": "string"},
                  "owner": {"type": "string"}
                }
              }
            }
          }
        }
      }
    }
    """
