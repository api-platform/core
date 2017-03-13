Feature: Search filter on collections
  In order to get specific result from a large collections of resources
  As a client software developer
  I need to search for collections properties

  @createSchema
  @dropSchema
  Scenario: Test ManyToMany with filter on join table
    Given there is a RelatedDummy with 4 friends
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/related_dummies?relatedToDummyFriend.dummyFriend=/dummy_friends/4"
    Then the response status code should be 200
    And the JSON node "_embedded.item" should have 1 element
    And the JSON node "_embedded.item[0]._links.relatedToDummyFriend" should have 4 elements
    And the JSON node "_embedded.item[0]._embedded.relatedToDummyFriend" should have 4 elements

  @createSchema
  Scenario: Test #944
    Given there is a DummyCar entity with related colors
    When I send a "GET" request to "/dummy_cars?colors.prop=red"
    Then the response status code should be 200
    And the JSON should be equal to:
		"""
    {
        "@context": "/contexts/DummyCar",
        "@id": "/dummy_cars",
        "@type": "hydra:Collection",
        "hydra:member": [
            {
                "@id": "/dummy_cars/1",
                "@type": "DummyCar",
                "colors": [
                    {
                        "@id": "/dummy_car_colors/1",
                        "@type": "DummyCarColor",
                        "prop": "red"
                    },
                    {
                        "@id": "/dummy_car_colors/2",
                        "@type": "DummyCarColor",
                        "prop": "blue"
                    }
                ]
            }
        ],
        "hydra:totalItems": 1,
        "hydra:view": {
            "@id": "/dummy_cars?colors.prop=red",
            "@type": "hydra:PartialCollectionView"
        },
        "hydra:search": {
            "@type": "hydra:IriTemplate",
            "hydra:template": "/dummy_cars{?colors.prop}",
            "hydra:variableRepresentation": "BasicRepresentation",
            "hydra:mapping": [
                {
                    "@type": "IriTemplateMapping",
                    "variable": "colors.prop",
                    "property": "colors.prop",
                    "required": false
                }
            ]
        }
    }
    """

  Scenario: Search collection by name (partial)
    Given there is "30" dummy objects
    When I send a "GET" request to "/dummies?name=my"
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
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?name=my"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Search collection by name (partial case insensitive)
    When I send a "GET" request to "/dummies?dummy=somedummytest1"
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
              "dummy": {
                "pattern": "^SomeDummyTest\\d{1,2}$"
              }
            }
          }
        }
      }
    }
    """

  Scenario: Search collection by alias (start)
    When I send a "GET" request to "/dummies?alias=Ali"
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
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?alias=Ali"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Search collection by description (word_start)
    When I send a "GET" request to "/dummies?description=smart"
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
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?description=smart"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  @dropSchema
  Scenario: Search for entities within an impossible range
    When I send a "GET" request to "/dummies?name=MuYm"
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
            "@id": {"pattern": "^/dummies\\?name=MuYm$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  @createSchema
  @dropSchema
  Scenario: Search related collection by name
    Given there is 3 dummy objects having each 3 relatedDummies
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies?relatedDummies.name=RelatedDummy1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "_embedded.item" should have 3 elements
    And the JSON node "_embedded.item[0]._links.relatedDummies" should have 3 elements
    And the JSON node "_embedded.item[1]._links.relatedDummies" should have 3 elements
    And the JSON node "_embedded.item[2]._links.relatedDummies" should have 3 elements

