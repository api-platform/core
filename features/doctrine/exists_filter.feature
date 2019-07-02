Feature: Exists filter on collections
  In order to retrieve large collections of resources
  As a client software developer
  I need to retrieve collections that exists

  @createSchema
  Scenario: Get collection where exists does not exist
    Given there are 15 dummy objects with dummyBoolean true
    When I send a "GET" request to "/dummies?exists[dummyBoolean]=0"
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
        "hydra:totalItems": {"type":"number", "maximum": 0},
        "hydra:member": {
          "type": "array",
          "maxItems": 0
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?exists%5BdummyBoolean%5D=0$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection where exists does exist
    When I send a "GET" request to "/dummies?exists[dummyBoolean]=1"
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
        "hydra:totalItems": {"type":"number", "minimum": 3},
        "hydra:member": {
          "type": "array",
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?exists%5BdummyBoolean%5D=1&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  @createSchema
  Scenario: Get collection filtered using a name converter
    Given there are 4 convertedString objects
    When I send a "GET" request to "/converted_strings?exists[name_converted]=true"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/ConvertedString"},
        "@id": {"pattern": "^/converted_strings"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/converted_strings/(1|3)$"},
              "@type":  {"pattern": "^ConvertedString"},
              "name_converted": {"pattern": "^name#(1|3)$"},
              "id": {"type": "integer", "minimum":1, "maximum": 3}
            },
            "required": ["@id", "@type", "name_converted", "id"],
            "additionalProperties": false
          },
          "minItems": 2,
          "maxItems": 2,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "integer", "minimum": 2, "maximum": 2},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/converted_strings\\?exists%5Bname_converted%5D=true"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        },
        "hydra:search": {
          "type": "object",
          "properties": {
            "@type": {"pattern": "^hydra:IriTemplate$"},
            "hydra:template": {"pattern": "^/converted_strings\\{\\?exists\\[name_converted\\]\\}$"},
            "hydra:variableRepresentation": {"pattern": "^BasicRepresentation$"},
            "hydra:mapping": {
              "type": "array",
              "items": {
                "type": "object",
                "properties": {
                  "@type": {"pattern": "^IriTemplateMapping$"},
                  "variable": {"pattern": "^exists\\[name_converted\\]$"},
                  "property": {"pattern": "^name_converted$"},
                  "required": {"type": "boolean"}
                },
                "required": ["@type", "variable", "property", "required"],
                "additionalProperties": false
              },
              "minItems": 1,
              "maxItems": 1,
              "uniqueItems": true
            }
          },
          "additionalProperties": false,
          "required": ["@type", "hydra:template", "hydra:variableRepresentation", "hydra:mapping"]
        },
        "additionalProperties": false,
        "required": ["@context", "@id", "@type", "hydra:member", "hydra:totalItems", "hydra:view", "hydra:search"]
      }
    }
    """
