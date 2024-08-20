@sqlite
@customTagCollector
@disableForSymfonyLowest
Feature: Cache invalidation through HTTP Cache tags (custom TagCollector service)
  In order to have a fast API
  As an API software developer
  I need to store API responses in a cache

  @createSchema
  Scenario: Create a dummy resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
    }
    """
    Then the response status code should be 201
    And the header "Cache-Tags" should not exist

  Scenario: TagCollector can identify $object (IRI is overridden with custom logic)
    When I send a "GET" request to "/relation_embedders/1"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/RE/1#anotherRelated,/RE/1#related,/RE/1"

  Scenario: Create some embedded resources
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
      "anotherRelated": {
        "name": "Related"
      }
    }
    """
    Then the response status code should be 201
    And the header "Cache-Tags" should not exist

  Scenario: TagCollector can add cache tags for relations (JSON-LD format)
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/relation_embedders/2"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/related_dummies/1#thirdLevel,/related_dummies/1,/RE/2#anotherRelated,/RE/2#related,/RE/2"
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/RelationEmbedder",
        "@id": "/relation_embedders/2",
        "@type": "RelationEmbedder",
        "krondstadt": "Krondstadt",
        "anotherRelated": {
            "@id": "/related_dummies/1",
            "@type": "https://schema.org/Product",
            "symfony": "symfony",
            "thirdLevel": null
        },
        "related": null
    }
    """

  Scenario: TagCollector can add cache tags for relations (HAL format)
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/relation_embedders/2"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/RE/2,/related_dummies/1,/related_dummies/1#thirdLevel,/RE/2#anotherRelated,/RE/2#related"
    And the JSON should be equal to:
    """
    {
        "_links": {
            "self": {
                "href": "/relation_embedders/2"
            },
            "anotherRelated": {
                "href": "/related_dummies/1"
            }
        },
        "_embedded": {
            "anotherRelated": {
                "_links": {
                    "self": {
                        "href": "/related_dummies/1"
                    }
                },
                "symfony": "symfony"
            }
        },
        "krondstadt": "Krondstadt"
    }
    """

  Scenario: TagCollector can add cache tags for relations (JSONAPI format)
    When I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/relation_embedders/2"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/RE/2,/RE/2#anotherRelated,/RE/2#related"
    And the JSON should be equal to:
    """
    {
        "data": {
            "id": "/relation_embedders/2",
            "type": "RelationEmbedder",
            "attributes": {
                "krondstadt": "Krondstadt"
            },
            "relationships": {
                "anotherRelated": {
                    "data": {
                        "type": "RelatedDummy",
                        "id": "/related_dummies/1"
                    }
                },
                "related": {
                    "data": []
                }
            }
        }
    }
    """

  Scenario: Create resource with extraProperties on ApiProperty
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/extra_properties_on_properties" with body:
    """
    {
    }
    """
    Then the response status code should be 201
    And the header "Cache-Tags" should not exist

  Scenario: TagCollector can read propertyMetadata (tag is overridden with data from extraProperties)
    When I send a "GET" request to "/extra_properties_on_properties/1"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/extra_properties_on_properties/1#overrideRelationTag,/extra_properties_on_properties/1"

  Scenario: Create two Relation2
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation2s" with body:
    """
    {
    }
    """
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation2s" with body:
    """
    {
    }
    """
    Then the response status code should be 201

  Scenario: Create a Relation3 with many to many
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation3s" with body:
    """
    {
      "relation2s": ["/relation2s/1", "/relation2s/2"]
    }
    """
    Then the response status code should be 201

  Scenario: Get a Relation3 (test collection of links; JSON-LD format)
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/relation3s"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/relation3s/1#relation2s,/relation3s/1,/relation3s"
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/Relation3",
        "@id": "/relation3s",
        "@type": "hydra:Collection",
        "hydra:totalItems": 1,
        "hydra:member": [
            {
                "@id": "/relation3s/1",
                "@type": "Relation3",
                "id": 1,
                "relation2s": [
                    "/relation2s/1",
                    "/relation2s/2"
                ]
            }
        ]
    }
    """

  Scenario: Get a Relation3 (test collection of links; HAL format)
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/relation3s"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/relation3s/1,/relation3s/1#relation2s,/relation3s"
    And the JSON should be equal to:
    """
    {
        "_links": {
            "self": {
                "href": "/relation3s"
            },
            "item": [
                {
                    "href": "/relation3s/1"
                }
            ]
        },
        "totalItems": 1,
        "itemsPerPage": 3,
        "_embedded": {
            "item": [
                {
                    "_links": {
                        "self": {
                            "href": "/relation3s/1"
                        },
                        "relation2s": [
                            {
                                "href": "/relation2s/1"
                            },
                            {
                                "href": "/relation2s/2"
                            }
                        ]
                    },
                    "id": 1
                }
            ]
        }
    }
    """

  Scenario: Get a Relation3 (test collection of links; HAL format)
    When I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/relation3s"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/relation3s/1,/relation3s/1#relation2s,/relation3s"
    And the JSON should be equal to:
    """
    {
        "links": {
            "self": "/relation3s"
        },
        "meta": {
            "totalItems": 1,
            "itemsPerPage": 3,
            "currentPage": 1
        },
        "data": [
            {
                "id": "/relation3s/1",
                "type": "Relation3",
                "attributes": {
                    "_id": 1
                },
                "relationships": {
                    "relation2s": {
                        "data": [
                            {
                                "type": "Relation2",
                                "id": "/relation2s/1"
                            },
                            {
                                "type": "Relation2",
                                "id": "/relation2s/2"
                            }
                        ]
                    }
                }
            }
        ]
    }
    """
