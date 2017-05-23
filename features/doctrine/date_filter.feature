Feature: Date filter on collections
  In order to retrieve large collections of resources filtered by date
  As a client software developer
  I need to retrieve collections filtered by date

  @createSchema
  Scenario: Get collection filtered by date
    Given there is "30" dummy objects with dummyDate
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
            }
          },
          "maxItems": 2
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-28$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
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
            }
          },
          "maxItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
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
            }
          },
          "maxItems": 2
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-28T00%3A00%3A00%2B00%3A00$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
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
            }
          },
          "maxItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05Z&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
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
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/5$"}
                ]
              }
            }
          },
          "maxItems": 1
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bbefore%5D=2015-04-05&dummyDate%5Bafter%5D=2015-04-05$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
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
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/5$"}
                ]
              }
            }
          },
          "maxItems": 1
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyDate%5Bafter%5D=2015-04-05&dummyDate%5Bbefore%5D=2015-04-05$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
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
          }
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
            }
          },
          "maxItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
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
            }
          },
          "maxItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28&relatedDummy_dummyDate%5Bafter%5D=2015-04-28$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
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
            }
          },
          "maxItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy\\.dummyDate%5Bafter%5D=2015-04-28T00%3A00%3A00%2B00%3A00$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
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
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
          "@context": "/contexts/Dummy",
          "@id": "/dummies",
          "@type": "hydra:Collection",
          "hydra:member": [],
          "hydra:totalItems": 0,
          "hydra:view": {
              "@id": "/dummies?relatedDummy.dummyDate%5Bafter%5D=2015-04-28",
              "@type": "hydra:PartialCollectionView"
          },
          "hydra:search": {
              "@type": "hydra:IriTemplate",
              "hydra:template": "/dummies{?id,id[],name,alias,description,relatedDummy.name,relatedDummy.name[],relatedDummies,relatedDummies[],dummy,relatedDummies.name,order[id],order[name],order[relatedDummy.symfony],dummyDate[before],dummyDate[after],relatedDummy.dummyDate[before],relatedDummy.dummyDate[after],dummyFloat[between],dummyFloat[gt],dummyFloat[gte],dummyFloat[lt],dummyFloat[lte],dummyPrice[between],dummyPrice[gt],dummyPrice[gte],dummyPrice[lt],dummyPrice[lte],dummyBoolean,dummyFloat,dummyPrice}",
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
                      "variable": "id[]",
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
                      "variable": "relatedDummy.name[]",
                      "property": "relatedDummy.name",
                      "required": false
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "relatedDummies",
                      "property": "relatedDummies",
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
                      "variable": "relatedDummies.name",
                      "property": "relatedDummies.name",
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
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "dummyFloat[between]",
                      "property": "dummyFloat",
                      "required": false
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "dummyFloat[gt]",
                      "property": "dummyFloat",
                      "required": false
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "dummyFloat[gte]",
                      "property": "dummyFloat",
                      "required": false
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "dummyFloat[lt]",
                      "property": "dummyFloat",
                      "required": false
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "dummyFloat[lte]",
                      "property": "dummyFloat",
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
                      "variable": "dummyBoolean",
                      "property": "dummyBoolean",
                      "required": false
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "dummyFloat",
                      "property": "dummyFloat",
                      "required": false
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "dummyPrice",
                      "property": "dummyPrice",
                      "required": false
                  }
              ]
          }
      }
    """
