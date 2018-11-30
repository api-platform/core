Feature: Relations support
  In order to use a hypermedia API
  As a client software developer
  I need to be able to update relations between resources

  @createSchema
  Scenario: Create a third level
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/third_levels" with body:
    """
    {"level": 3}
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
      "id": 1,
      "level": 3,
      "test": true
    }
    """

  Scenario: Create a dummy friend
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_friends" with body:
    """
    {"name": "Zoidberg"}
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyFriend",
      "@id": "/dummy_friends/1",
      "@type": "DummyFriend",
      "id": 1,
      "name": "Zoidberg"
    }
    """

  Scenario: Create a related dummy
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/related_dummies" with body:
    """
    {"thirdLevel": "/third_levels/1"}
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelatedDummy",
      "@id": "/related_dummies/1",
      "@type": "https://schema.org/Product",
      "id": 1,
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

  Scenario: Create a friend relationship
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/related_to_dummy_friends" with body:
    """
    {
      "name": "Friends relation",
      "dummyFriend": "/dummy_friends/1",
      "relatedDummy": "/related_dummies/1"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelatedToDummyFriend",
      "@id": "/related_to_dummy_friends/dummyFriend=1;relatedDummy=1",
      "@type": "RelatedToDummyFriend",
      "name": "Friends relation",
      "description": null,
      "dummyFriend": {
        "@id": "/dummy_friends/1",
        "@type": "DummyFriend",
        "name": "Zoidberg"
      }
    }
    """

  Scenario: Get the relationship
    When I send a "GET" request to "/related_to_dummy_friends/dummyFriend=1;relatedDummy=1"
    And the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelatedToDummyFriend",
      "@id": "/related_to_dummy_friends/dummyFriend=1;relatedDummy=1",
      "@type": "RelatedToDummyFriend",
      "name": "Friends relation",
      "description": null,
      "dummyFriend": {
        "@id": "/dummy_friends/1",
        "@type": "DummyFriend",
        "name": "Zoidberg"
      }
    }
    """

  Scenario: Create a dummy with relations
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Dummy with relations",
      "relatedDummy": "http://example.com/related_dummies/1",
      "relatedDummies": [
        "/related_dummies/1"
      ],
      "name_converted": null
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
      "name": "Dummy with relations",
      "alias": null,
      "foo": null
    }
    """

  Scenario: Filter on a relation
    When I send a "GET" request to "/dummies?relatedDummy=%2Frelated_dummies%2F1"
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
        "hydra:totalItems": {"type":"number", "maximum": 1},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/dummies/1$"}
            }
          },
          "maxItems": 1
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummy=%2Frelated_dummies%2F1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Filter on a to-many relation
    When I send a "GET" request to "/dummies?relatedDummies[]=%2Frelated_dummies%2F1"
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
        "hydra:totalItems": {"type":"number", "maximum": 1},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/dummies/1$"}
            }
          },
          "maxItems": 1
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?relatedDummies%5B%5D=%2Frelated_dummies%2F1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Embed a relation in the parent object
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation_embedders" with body:
      """
      {
        "related": "/related_dummies/1"
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
        "anotherRelated": null,
        "related": {
          "@id": "/related_dummies/1",
          "@type": "https://schema.org/Product",
          "symfony": "symfony",
          "thirdLevel": {
            "@id": "/third_levels/1",
            "@type": "ThirdLevel",
            "level": 3,
            "fourthLevel": null
          }
        }
      }
      """

  Scenario: Create an existing relation
    When I add "Content-Type" header equal to "application/ld+json"
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
      "@id": "/relation_embedders/2",
      "@type": "RelationEmbedder",
      "krondstadt": "Krondstadt",
      "anotherRelated": {
        "@id": "/related_dummies/2",
        "@type": "https://schema.org/Product",
        "symfony": "laravel",
        "thirdLevel": null
      },
      "related": null
    }
    """

  Scenario: Update the relation with a new one
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/relation_embedders/2" with body:
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
      "@id": "/relation_embedders/2",
      "@type": "RelationEmbedder",
      "krondstadt": "Krondstadt",
      "anotherRelated": {
        "@id": "/related_dummies/3",
        "@type": "https://schema.org/Product",
        "symfony": "laravel2",
        "thirdLevel": null
      },
      "related": null
    }
    """

  Scenario: Post a wrong relation
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
      "anotherRelated": {
        "@id": "/related_dummies/123",
        "@type": "https://schema.org/Product",
        "symfony": "phalcon"
      }
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  Scenario: Post a relation with a not existing IRI
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
      "related": "/related_dummies/123"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  Scenario: Create a new relation (json)
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
      "@id": "/relation_embedders/3",
      "@type": "RelationEmbedder",
      "krondstadt": "Krondstadt",
      "anotherRelated": {
        "@id": "/related_dummies/4",
        "@type": "https://schema.org/Product",
        "symfony": "laravel",
        "thirdLevel": null
      },
      "related": null
    }
    """

  Scenario: Update the relation with a new one (json)
    When I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/relation_embedders/3" with body:
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
      "@id": "/relation_embedders/3",
      "@type": "RelationEmbedder",
      "krondstadt": "Krondstadt",
      "anotherRelated": {
        "@id": "/related_dummies/5",
        "@type": "https://schema.org/Product",
        "symfony": "laravel2",
        "thirdLevel": null
      },
      "related": null
    }
    """

  Scenario: Update an embedded relation
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/relation_embedders/2" with body:
    """
    {
      "anotherRelated": {
        "@id": "/related_dummies/2",
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
      "@id": "/relation_embedders/2",
      "@type": "RelationEmbedder",
      "krondstadt": "Krondstadt",
      "anotherRelated": {
        "@id": "/related_dummies/2",
        "@type": "https://schema.org/Product",
        "symfony": "API Platform",
        "thirdLevel": null
      },
      "related": null
    }
    """

  Scenario: Create a related dummy with a relation (json)
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/related_dummies" with body:
    """
    {"thirdLevel": "1"}
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelatedDummy",
      "@id": "/related_dummies/6",
      "@type": "https://schema.org/Product",
      "id": 6,
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

  Scenario: Issue #1222
    Given there are people having pets
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/people"
    And the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Person",
      "@id": "/people",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/people/1",
          "@type": "Person",
          "name": "foo",
          "pets": [
            {
              "pet": {
                "@id": "/pets/1",
                "@type": "Pet",
                "name": "bar"
              }
            }
          ]
        }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: Passing a (valid) plain identifier on a relation
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "relatedDummy": "1",
      "relatedDummies": ["1"],
      "name": "Dummy with plain relations"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context":"/contexts/Dummy",
      "@id":"/dummies/2",
      "@type":"Dummy",
      "description":null,
      "dummy":null,
      "dummyBoolean":null,
      "dummyDate":null,
      "dummyFloat":null,
      "dummyPrice":null,
      "relatedDummy":"/related_dummies/1",
      "relatedDummies":["/related_dummies/1"],
      "jsonData":[],
      "arrayData":[],
      "name_converted":null,
      "relatedOwnedDummy": null,
      "relatedOwningDummy": null,
      "id":2,
      "name":"Dummy with plain relations",
      "alias":null,
      "foo":null
    }
    """

  Scenario: Passing an invalid IRI to a relation
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
      "related": "certainly not an iri and not a plain identifier"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:description" should contain "Invalid value provided (invalid IRI?)."

  Scenario: Passing an invalid type to a relation
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
      "related": 8
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:description" should contain "Invalid value provided (invalid IRI?)."
