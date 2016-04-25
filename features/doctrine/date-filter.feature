@dateFilter
Feature: Order filter on collections
  In order to retrieve ordered large collections of resources
  As a client software developer
  I need to retrieve collections ordered properties

  @createSchema
  Scenario: Get collection filtered by date
    Given there is "30" dummy objects with dummyDate
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyDate\\[after\\]=2015-04-28$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 2},
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
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?dummyDate[before]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyDate\\[before\\]=2015-04-05$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 5},
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

  Scenario: Search for entities within a range
    # The order should not influence the search
    When I send a "GET" request to "/dummies?dummyDate[before]=2015-04-05&dummyDate[after]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyDate\\[before\\]=2015-04-05\\&dummyDate\\[after\\]=2015-04-05$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 1},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/5$"}
                ]
              }
            }
          },
          "maxItems": 1
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-05&dummyDate[before]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyDate\\[after\\]=2015-04-05\\&dummyDate\\[before\\]=2015-04-05$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 1},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/5$"}
                ]
              }
            }
          },
          "maxItems": 1
        }
      }
    }
    """

  Scenario: Search for entities within an impossible range
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-06&dummyDate[before]=2015-04-04"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyDate\\[after\\]=2015-04-06\\&dummyDate\\[before\\]=2015-04-04$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 0},
        "hydra:member": {
          "type": "array",
          "maxItems": 0
        }
      }
    }
    """

  @dropSchema
  Scenario: Get collection filtered by association date
    Given there is "30" dummy objects with dummyDate and relatedDummy
    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate\\[after\\]=2015-04-28$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 3},
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
            }
          },
          "maxItems": 3
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-28&relatedDummy_dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate\\[after\\]=2015-04-28\\&relatedDummy_dummyDate\\[after\\]=2015-04-28$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 3},
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
            }
          },
          "maxItems": 3
        }
      }
    }
    """

  @createSchema
  @dropSchema
  Scenario: Search for entities within a range
    Given there is "2" dummy objects with dummyDate
    When I send a "GET" request to "/dummies?dummyDate[after]="
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON node "hydra:totalItems" should be equal to "2"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyDate\\[after\\]=$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"}
                ]
              }
            }
          }
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?dummyDate[before]="
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON node "hydra:totalItems" should be equal to "2"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?dummyDate\\[before\\]=$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"}
                ]
              }
            }
          }
        }
      }
    }
    """

  @dropSchema
  @createSchema
  Scenario: Get collection filtered by association date
    Given there is "2" dummy objects with dummyDate and relatedDummy
    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/Dummy",
        "@id": "/dummies?relatedDummy.dummyDate[after]=2015-04-28",
        "@type": "hydra:PagedCollection",
        "hydra:totalItems": 0,
        "hydra:itemsPerPage": 3,
        "hydra:firstPage": "/dummies?relatedDummy.dummyDate%5Bafter%5D=2015-04-28",
        "hydra:lastPage": "/dummies?relatedDummy.dummyDate%5Bafter%5D=2015-04-28",
        "hydra:member": [],
        "hydra:search": {
            "@type": "hydra:IriTemplate",
            "hydra:template": "/dummies{?id,name,alias,description,relatedDummy.name,relatedDummies[],dummy,order[id],order[name],order[relatedDummy.symfony],dummyPrice[between],dummyPrice[gt],dummyPrice[gte],dummyPrice[lt],dummyPrice[lte],dummyDate[before],dummyDate[after],relatedDummy.dummyDate[before],relatedDummy.dummyDate[after]}",
            "hydra:variableRepresentation": "BasicRepresentation",
            "hydra:mapping": [
                {
                    "@type": "IriTemplateMapping",
                    "variable": "id",
                    "property": "id",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "name",
                    "property": "name",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "alias",
                    "property": "alias",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "description",
                    "property": "description",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "relatedDummy.name",
                    "property": "relatedDummy.name",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "relatedDummies[]",
                    "property": "relatedDummies",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "dummy",
                    "property": "dummy",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "order[id]",
                    "property": "id",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "order[name]",
                    "property": "name",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "order[relatedDummy.symfony]",
                    "property": "relatedDummy.symfony",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "dummyPrice[between]",
                    "property": "dummyPrice",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "dummyPrice[gt]",
                    "property": "dummyPrice",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "dummyPrice[gte]",
                    "property": "dummyPrice",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "dummyPrice[lt]",
                    "property": "dummyPrice",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "dummyPrice[lte]",
                    "property": "dummyPrice",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "dummyDate[before]",
                    "property": "dummyDate",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "dummyDate[after]",
                    "property": "dummyDate",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "relatedDummy.dummyDate[before]",
                    "property": "relatedDummy.dummyDate",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "relatedDummy.dummyDate[after]",
                    "property": "relatedDummy.dummyDate",
                    "required": false
                }
            ]
        }
    }
    """
