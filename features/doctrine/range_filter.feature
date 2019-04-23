Feature: Range filter on collections
  In order to filter results from large collections of resources
  As a client software developer
  I need to filter collections by range

  @createSchema
  Scenario: Get collection filtered by range (between)
    Given there are 30 dummy objects with dummyPrice
    When I send a "GET" request to "/dummies?dummyPrice[between]=12.99..15.99"
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
          "minItems": 3,
          "maxItems": 3,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "number", "minimum": 15, "maximum": 15},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyPrice%5Bbetween%5D=12.99..15.99"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Filter by range (between) with invalid format
    When I send a "GET" request to "/dummies?dummyPrice[between]=9.99..12.99..15.99"
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
                "pattern": "^/dummies/([1-9]|[12][0-9]|30)$"
              }
            }
          },
          "minItems": 3,
          "maxItems": 3,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "number", "minimum": 30, "maximum": 30},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyPrice%5Bbetween%5D=9.99..12.99..15.99"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Filter for entities by range (less than)
    When I send a "GET" request to "/dummies?dummyPrice[lt]=12.99"
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
          "minItems": 3,
          "maxItems": 3,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "number", "minimum": 8, "maximum": 8},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyPrice%5Blt%5D=12.99"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Filter for entities by range (less than or equal)
    When I send a "GET" request to "/dummies?dummyPrice[lte]=12.99"
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
          "minItems": 3,
          "maxItems": 3,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "number", "minimum": 16, "maximum": 16},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyPrice%5Blte%5D=12.99"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Filter for entities by range (greater than)
    When I send a "GET" request to "/dummies?dummyPrice[gt]=15.99"
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
          "minItems": 3,
          "maxItems": 3,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "number", "minimum": 7, "maximum": 7},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyPrice%5Bgt%5D=15.99"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Filter for entities by range (greater than or equal)
    When I send a "GET" request to "/dummies?dummyPrice[gte]=15.99"
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
          "minItems": 3,
          "maxItems": 3,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "number", "minimum": 14, "maximum": 14},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyPrice%5Bgte%5D=15.99"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Filter for entities by range (greater than and less than)
    When I send a "GET" request to "/dummies?dummyPrice[gt]=12.99&dummyPrice[lt]=19.99"
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
          "minItems": 3,
          "maxItems": 3,
          "uniqueItems": true
        },
        "hydra:totalItems": {"type": "number", "minimum": 7, "maximum": 7},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyPrice%5Bgt%5D=12.99&dummyPrice%5Blt%5D=19.99"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Filter for entities within an impossible range
    When I send a "GET" request to "/dummies?dummyPrice[gt]=19.99"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 0
        },
        "hydra:totalItems": {"type": "number", "maximum": 0},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyPrice%5Bgt%5D=19.99$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  @createSchema
  Scenario: Get collection filtered using a name converter
    Given there are 5 convertedInteger objects
    When I send a "GET" request to "/converted_integers?name_converted[lte]=2"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/ConvertedInteger$"},
        "@id": {"pattern": "^/converted_integers$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/converted_integers/(1|2)$"},
              "@type":  {"pattern": "^ConvertedInteger$"},
              "name_converted": {"type": "integer"},
              "id": {"type": "integer", "minimum":1, "maximum": 2}
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
            "@id": {"pattern": "^/converted_integers\\?name_converted%5Blte%5D=2$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        },
        "hydra:search": {
          "type": "object",
          "properties": {
            "@type": {"pattern": "^hydra:IriTemplate$"},
            "hydra:template": {"pattern": "^/converted_integers\\{\\?.*name_converted\\[between\\],name_converted\\[gt\\],name_converted\\[gte\\],name_converted\\[lt\\],name_converted\\[lte\\].*\\}$"},
            "hydra:variableRepresentation": {"pattern": "^BasicRepresentation$"},
            "hydra:mapping": {
              "type": "array",
              "items": {
                "type": "object",
                "properties": {
                  "@type": {"pattern": "^IriTemplateMapping$"},
                  "variable": {
                    "oneOf": [
                      {"pattern": "^name_converted(\\[(between|gt|gte|lt|lte)?\\])?$"},
                      {"pattern": "^order\\[name_converted\\]$"}
                    ]
                  },
                  "property": {"pattern": "^name_converted$"},
                  "required": {"type": "boolean"}
                },
                "required": ["@type", "variable", "property", "required"],
                "additionalProperties": false
              },
              "minItems": 8,
              "maxItems": 8,
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
