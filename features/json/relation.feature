Feature: JSON relations support
  In order to use a hypermedia API
  As a client software developer
  I need to be able to update relations between resources

  @createSchema
  Scenario: Create a third level
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/third_levels" with body:
    """
    {
      "level": 3
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ThirdLevel",
      "@id": "/third_levels/1",
      "@type": "ThirdLevel",
      "fourthLevel": null,
      "badFourthLevel": null,
      "id": 1,
      "level": 3,
      "test": true
    }
    """

  Scenario: Create a new relation
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
      "anotherRelated": {
        "symfony": "laravel"
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelationEmbedder",
      "@id": "/relation_embedders/1",
      "@type": "RelationEmbedder",
      "krondstadt": "Krondstadt",
      "anotherRelated": {
        "@id": "/related_dummies/1",
        "@type": "https://schema.org/Product",
        "symfony": "laravel",
        "thirdLevel": null
      },
      "related": null
    }
    """

  Scenario: Update the relation with a new one
    When I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/relation_embedders/1" with body:
    """
    {
      "anotherRelated": {
        "symfony": "laravel2"
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelationEmbedder",
      "@id": "/relation_embedders/1",
      "@type": "RelationEmbedder",
      "krondstadt": "Krondstadt",
      "anotherRelated": {
        "@id": "/related_dummies/2",
        "@type": "https://schema.org/Product",
        "symfony": "laravel2",
        "thirdLevel": null
      },
      "related": null
    }
    """

  Scenario: Update an embedded relation using an IRI
    When I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/relation_embedders/1" with body:
    """
    {
      "anotherRelated": {
        "id": "/related_dummies/1",
        "symfony": "API Platform"
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelationEmbedder",
      "@id": "/relation_embedders/1",
      "@type": "RelationEmbedder",
      "krondstadt": "Krondstadt",
      "anotherRelated": {
        "@id": "/related_dummies/1",
        "@type": "https://schema.org/Product",
        "symfony": "API Platform",
        "thirdLevel": null
      },
      "related": null
    }
    """

  Scenario: Update an embedded relation
    When I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/relation_embedders/1" with body:
    """
    {
      "anotherRelated": {
        "id": 1,
        "symfony": "API Platform 2"
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelationEmbedder",
      "@id": "/relation_embedders/1",
      "@type": "RelationEmbedder",
      "krondstadt": "Krondstadt",
      "anotherRelated": {
        "@id": "/related_dummies/1",
        "@type": "https://schema.org/Product",
        "symfony": "API Platform 2",
        "thirdLevel": null
      },
      "related": null
    }
    """

  # TODO: to remove in 3.0
  Scenario: Create a related dummy with a relation using plain identifiers
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/related_dummies" with body:
    """
    {
      "thirdLevel": "1"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelatedDummy",
      "@id": "/related_dummies/3",
      "@type": "https://schema.org/Product",
      "id": 3,
      "name": null,
      "symfony": "symfony",
      "dummyDate": null,
      "thirdLevel": {
        "@id": "/third_levels/1",
        "@type": "ThirdLevel",
        "fourthLevel": null
      },
      "relatedToDummyFriend": [],
      "dummyBoolean": null,
      "embeddedDummy": [],
      "age": null
    }
    """

  # TODO: to remove in 3.0
  Scenario: Passing a (valid) plain identifier on a relation
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "relatedDummy": "1",
      "relatedDummies": [
        "1"
      ],
      "name": "Dummy with plain relations"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": null,
      "dummyFloat": null,
      "dummyPrice": null,
      "relatedDummy": "/related_dummies/1",
      "relatedDummies": [
        "/related_dummies/1"
      ],
      "jsonData": [],
      "arrayData": [],
      "name_converted": null,
      "relatedOwnedDummy": null,
      "relatedOwningDummy": null,
      "id": 1,
      "name": "Dummy with plain relations",
      "alias": null,
      "foo": null
    }
    """
