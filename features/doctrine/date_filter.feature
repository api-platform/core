Feature: Date filter on collections
  In order to retrieve large collections of resources filtered by date
  As a client software developer
  I need to retrieve collections filtered by date

  @createSchema
  Scenario: Get collection filtered by date
    Given there are 30 dummy objects with dummyDate
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-28"
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
            },
            "required": ["@id"]
          },
          "minItems": 2,
          "maxItems": 2
        },
        "hydra:totalItems": {"type":"number", "minimum": 2, "maximum": 2},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-28$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?dummyDate[before]=2015-04-05"
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
            },
            "required": ["@id"]
          },
          "minItems": 3,
          "maxItems": 3
        },
        "hydra:totalItems": {"type":"number", "minimum": 5, "maximum": 5},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"]
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-28T00:00:00%2B00:00"
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
            },
            "required": ["@id"]
          },
          "minItems": 2,
          "maxItems": 2
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-28T00%3A00%3A00%2B00%3A00$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?dummyDate[before]=2015-04-05Z"
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
            },
            "required": ["@id"]
          },
          "minItems": 3,
          "maxItems": 3
        },
        "hydra:totalItems": {"type":"number", "minimum": 5, "maximum": 5},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05Z&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"]
        }
      }
    }
    """

  Scenario: Search for entities within a range
    # The order should not influence the search
    When I send a "GET" request to "/dummies?dummyDate[before]=2015-04-05&dummyDate[after]=2015-04-05"
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
              "@id": {"pattern": "^/dummies/5$"}
            },
            "required": ["@id"]
          },
          "minItems": 1,
          "maxItems": 1
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05&dummyDate%5Bafter%5D=2015-04-05$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-05&dummyDate[before]=2015-04-05"
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
              "@id": {"pattern": "^/dummies/5$"}
            },
            "required": ["@id"]
          },
          "minItems": 1,
          "maxItems": 1
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-05&dummyDate%5Bbefore%5D=2015-04-05$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        }
      }
    }
    """

  Scenario: Search for entities within an impossible range
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-06&dummyDate[before]=2015-04-04"
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
          "maxItems": 0
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-06&dummyDate%5Bbefore%5D=2015-04-04$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        }
      }
    }
    """

  Scenario: Get collection filtered by association date
    Given there are 30 dummy objects with dummyDate and relatedDummy
    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-28"
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
                  {"pattern": "^/dummies/58$"},
                  {"pattern": "^/dummies/59$"},
                  {"pattern": "^/dummies/60$"}
                ]
              }
            },
            "required": ["@id"]
          },
          "minItems": 3,
          "maxItems": 3
        },
        "hydra:totalItems": {"type":"number", "minimum": 3, "maximum": 3},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-28&relatedDummy_dummyDate[after]=2015-04-28"
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
                  {"pattern": "^/dummies/58$"},
                  {"pattern": "^/dummies/59$"},
                  {"pattern": "^/dummies/60$"}
                ]
              }
            },
            "required": ["@id"]
          },
          "minItems": 3,
          "maxItems": 3
        },
        "hydra:totalItems": {"type":"number", "minimum": 3, "maximum": 3},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28&relatedDummy_dummyDate%5Bafter%5D=2015-04-28$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-28T00:00:00%2B00:00"
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
                  {"pattern": "^/dummies/58$"},
                  {"pattern": "^/dummies/59$"},
                  {"pattern": "^/dummies/60$"}
                ]
              }
            },
            "required": ["@id"]
          },
          "minItems": 3,
          "maxItems": 3
        },
        "hydra:totalItems": {"type":"number", "minimum": 3, "maximum": 3},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28T00%3A00%3A00%2B00%3A00$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        }
      }
    }
    """

  @createSchema
  Scenario: Get collection filtered by association date
    Given there are 2 dummy objects with dummyDate and relatedDummy
    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-28"
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
          "maxItems": 0
        },
        "hydra:totalItems": {"type":"number", "maximum": 0},
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        }
      }
    }
    """

  @createSchema
  Scenario: Get collection filtered by date that is not a datetime
    Given there are 30 dummydate objects with dummyDate
    When I send a "GET" request to "/dummy_dates?dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the JSON node "hydra:totalItems" should be equal to 3
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  @createSchema
  Scenario: Get collection filtered by date that is not a datetime including null after
    Given there are 3 dummydate objects with nullable dateIncludeNullAfter
    When I send a "GET" request to "/dummy_dates?dateIncludeNullAfter[after]=2015-04-02"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 2
    And the JSON node "hydra:member[0].dateIncludeNullAfter" should be equal to "2015-04-02T00:00:00+00:00"
    And the JSON node "hydra:member[1].dateIncludeNullAfter" should be null
    When I send a "GET" request to "/dummy_dates?dateIncludeNullAfter[before]=2015-04-02"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 2
    And the JSON node "hydra:member[0].dateIncludeNullAfter" should be equal to "2015-04-01T00:00:00+00:00"
    And the JSON node "hydra:member[1].dateIncludeNullAfter" should be equal to "2015-04-02T00:00:00+00:00"

  @createSchema
  Scenario: Get collection filtered by date that is not a datetime including null before
    Given there are 3 dummydate objects with nullable dateIncludeNullBefore
    When I send a "GET" request to "/dummy_dates?dateIncludeNullBefore[before]=2015-04-01"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 2
    And the JSON node "hydra:member[0].dateIncludeNullBefore" should be equal to "2015-04-01T00:00:00+00:00"
    And the JSON node "hydra:member[1].dateIncludeNullBefore" should be null
    When I send a "GET" request to "/dummy_dates?dateIncludeNullBefore[after]=2015-04-01"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 2
    And the JSON node "hydra:member[0].dateIncludeNullBefore" should be equal to "2015-04-01T00:00:00+00:00"
    And the JSON node "hydra:member[1].dateIncludeNullBefore" should be equal to "2015-04-02T00:00:00+00:00"

  @createSchema
  Scenario: Get collection filtered by date that is not a datetime including null before and after
    Given there are 3 dummydate objects with nullable dateIncludeNullBeforeAndAfter
    When I send a "GET" request to "/dummy_dates?dateIncludeNullBeforeAndAfter[before]=2015-04-01"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 2
    And the JSON node "hydra:member[0].dateIncludeNullBeforeAndAfter" should be equal to "2015-04-01T00:00:00+00:00"
    And the JSON node "hydra:member[1].dateIncludeNullBeforeAndAfter" should be null
    When I send a "GET" request to "/dummy_dates?dateIncludeNullBeforeAndAfter[after]=2015-04-02"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:totalItems" should be equal to 2
    And the JSON node "hydra:member[0].dateIncludeNullBeforeAndAfter" should be equal to "2015-04-02T00:00:00+00:00"
    And the JSON node "hydra:member[1].dateIncludeNullBeforeAndAfter" should be null

  @!mongodb
  @createSchema
  Scenario: Get collection filtered by date that is an immutable date variant
    Given there are 30 dummyimmutabledate objects with dummyDate
    When I send a "GET" request to "/dummy_immutable_dates?dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the JSON node "hydra:totalItems" should be equal to 3
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  @createSchema
  Scenario: Get collection filtered by embedded date
    Given there are 29 embedded dummy objects with dummyDate and embeddedDummy
    When I send a "GET" request to "/embedded_dummies?embeddedDummy.dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/EmbeddedDummy$"},
        "@id": {"pattern": "^/embedded_dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/embedded_dummies/28$"},
                  {"pattern": "^/embedded_dummies/29$"}
                ]
              }
            },
            "required": ["@id"]
          },
          "minItems": 2,
          "maxItems": 2,
          "uniqueItems": true
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/embedded_dummies\\?embeddedDummy\\.dummyDate%5Bafter%5D=2015-04-28$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        }
      }
    }
    """

  @createSchema
  Scenario: Get collection filtered using a name converter
    Given there are 30 convertedDate objects
    When I send a "GET" request to "/converted_dates?name_converted[strictly_after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/ConvertedDate"},
        "@id": {"pattern": "^/converted_dates"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/converted_dates/(29|30)$"},
              "@type":  {"pattern": "^ConvertedDate"},
              "name_converted": {"type": "string"},
              "id": {"type": "integer", "minimum":29, "maximum": 30}
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
            "@id": {"pattern": "^/converted_dates\\?name_converted%5Bstrictly_after%5D=2015\\-04\\-28$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          },
          "required": ["@id", "@type"],
          "additionalProperties": false
        },
        "hydra:search": {
          "type": "object",
          "properties": {
            "@type": {"pattern": "^hydra:IriTemplate$"},
            "hydra:template": {"pattern": "^/converted_dates\\{\\?.*name_converted\\[before\\],name_converted\\[strictly_before\\],name_converted\\[after\\],name_converted\\[strictly_after\\].*\\}$"},
            "hydra:variableRepresentation": {"pattern": "^BasicRepresentation$"},
            "hydra:mapping": {
              "type": "array",
              "items": {
                "type": "object",
                "properties": {
                  "@type": {"pattern": "^IriTemplateMapping$"},
                  "variable": {"pattern": "^name_converted(\\[(strictly_)?(before|after)\\])$"},
                  "property": {"pattern": "^name_converted$"},
                  "required": {"type": "boolean"}
                },
                "required": ["@type", "variable", "property", "required"],
                "additionalProperties": false
              },
              "minItems": 4,
              "maxItems": 4,
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
