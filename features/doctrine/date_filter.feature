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
            }
          },
          "minItems": 2,
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
          "minItems": 3,
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
          "minItems": 2,
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
          "minItems": 3,
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
          "minItems": 1,
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
          "minItems": 1,
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
            }
          },
          "minItems": 3,
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
          "minItems": 3,
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
          "minItems": 3,
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

  @createSchema
  Scenario: Get collection filtered by association date
    Given there are 2 dummy objects with dummyDate and relatedDummy
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
        "hydra:template": "/dummies{?dummyBoolean,relatedDummy.embeddedDummy.dummyBoolean,dummyDate[before],dummyDate[strictly_before],dummyDate[after],dummyDate[strictly_after],relatedDummy.dummyDate[before],relatedDummy.dummyDate[strictly_before],relatedDummy.dummyDate[after],relatedDummy.dummyDate[strictly_after],description[exists],relatedDummy.name[exists],dummyBoolean[exists],relatedDummy[exists],dummyFloat,dummyFloat[],dummyPrice,dummyPrice[],order[id],order[name],order[description],order[relatedDummy.name],order[relatedDummy.symfony],order[dummyDate],dummyFloat[between],dummyFloat[gt],dummyFloat[gte],dummyFloat[lt],dummyFloat[lte],dummyPrice[between],dummyPrice[gt],dummyPrice[gte],dummyPrice[lt],dummyPrice[lte],id,id[],name,alias,description,relatedDummy.name,relatedDummy.name[],relatedDummies,relatedDummies[],dummy,relatedDummies.name,relatedDummy.thirdLevel.level,relatedDummy.thirdLevel.level[],relatedDummy.thirdLevel.fourthLevel.level,relatedDummy.thirdLevel.fourthLevel.level[],relatedDummy.thirdLevel.badFourthLevel.level,relatedDummy.thirdLevel.badFourthLevel.level[],relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level,relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level[],properties[]}",
        "hydra:variableRepresentation": "BasicRepresentation",
        "hydra:mapping": [
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyBoolean",
            "property": "dummyBoolean",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.embeddedDummy.dummyBoolean",
            "property": "relatedDummy.embeddedDummy.dummyBoolean",
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
            "variable": "dummyDate[strictly_before]",
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
            "variable": "dummyDate[strictly_after]",
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
            "variable": "relatedDummy.dummyDate[strictly_before]",
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
            "variable": "relatedDummy.dummyDate[strictly_after]",
            "property": "relatedDummy.dummyDate",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "description[exists]",
            "property": "description",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.name[exists]",
            "property": "relatedDummy.name",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyBoolean[exists]",
            "property": "dummyBoolean",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy[exists]",
            "property": "relatedDummy",
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
            "variable": "dummyFloat[]",
            "property": "dummyFloat",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyPrice",
            "property": "dummyPrice",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyPrice[]",
            "property": "dummyPrice",
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
            "variable": "order[description]",
            "property": "description",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "order[relatedDummy.name]",
            "property": "relatedDummy.name",
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
            "variable": "order[dummyDate]",
            "property": "dummyDate",
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
            "variable": "relatedDummy.thirdLevel.level",
            "property": "relatedDummy.thirdLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.level[]",
            "property": "relatedDummy.thirdLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.fourthLevel.level",
            "property": "relatedDummy.thirdLevel.fourthLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.fourthLevel.level[]",
            "property": "relatedDummy.thirdLevel.fourthLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.badFourthLevel.level",
            "property": "relatedDummy.thirdLevel.badFourthLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.badFourthLevel.level[]",
            "property": "relatedDummy.thirdLevel.badFourthLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level",
            "property": "relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level[]",
            "property": "relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level",
            "required": false
          },
          {
              "@type": "IriTemplateMapping",
              "variable": "properties[]",
              "property": null,
              "required": false
          }
        ]
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
    Given there are 2 embedded dummy objects with dummyDate and embeddedDummy
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
            }
          },
          "hydra:search": {
              "@type": "hydra:IriTemplate",
              "hydra:template": "/dummies{?dummyBoolean,dummyDate[before],dummyDate[after],relatedDummy.dummyDate[before],relatedDummy.dummyDate[strictly_before],relatedDummy.dummyDate[after],relatedDummy.dummyDate[strictly_after],description[exists],relatedDummy.name[exists],dummyBoolean[exists],relatedDummy[exists],dummyFloat,dummyPrice,order[id],order[name],order[relatedDummy.symfony],dummyFloat[between],dummyFloat[gt],dummyFloat[gte],dummyFloat[lt],dummyFloat[lte],dummyPrice[between],dummyPrice[gt],dummyPrice[gte],dummyPrice[lt],dummyPrice[lte],id,id[],name,alias,description,relatedDummy.name,relatedDummy.name[],relatedDummies,relatedDummies[],dummy,relatedDummies.name}",
              "hydra:variableRepresentation": "BasicRepresentation",
              "hydra:mapping": [
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "dummyBoolean",
                      "property": "dummyBoolean",
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
                      "variable": "dummyDate[strictly_before]",
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
                      "variable": "dummyDate[strictly_after]",
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
                      "variable": "relatedDummy.dummyDate[strictly_before]",
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
                      "variable": "relatedDummy.dummyDate[strictly_after]",
                      "property": "relatedDummy.dummyDate",
                      "required": false
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "description[exists]",
                      "property": "description",
                      "required": false
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "relatedDummy.name[exists]",
                      "property": "relatedDummy.name",
                      "required": false
                  },
                  {
                    "@type": "IriTemplateMapping",
                    "variable": "relatedDummy[exists]",
                    "property": "relatedDummy",
                    "required": false
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "dummyBoolean[exists]",
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
                      "variable": "properties[]",
                      "property": null,
                      "required": false
                  }
              ]
          },
          "hydra:view": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/embedded_dummies\\?embeddedDummy\\.dummyDate%5Bafter%5D=2015-04-28$"},
              "@type": {"pattern": "^hydra:PartialCollectionView$"}
            }
          }
        }
      }
    }
    """
