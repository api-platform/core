Feature: Subresource support
  In order to use a hypermedia API
  As a client software developer
  I need to be able to retrieve embedded resources only as Subresources

  @createSchema
  Scenario: Get subresource one to one relation
    Given there is an answer "42" to the question "What's the answer to the Ultimate Question of Life, the Universe and Everything?"
    When I send a "GET" request to "/questions/1/answer"
    And the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Answer",
      "@id": "/answers/1",
      "@type": "Answer",
      "id": 1,
      "content": "42",
      "question": "/questions/1",
      "relatedQuestions": [
        "/questions/1"
      ]
    }
    """

  Scenario: Get subresource one to one relation
    When I send a "GET" request to "/questions/1/answer/related_questions"
    And the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Question",
      "@id": "/questions/1/answer/related_questions",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/questions/1",
          "@type": "Question",
          "content": "What's the answer to the Ultimate Question of Life, the Universe and Everything?",
          "id": 1,
          "answer": "/answers/1"
        }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: Create a fourth level
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/fourth_levels" with body:
    """
    {"level": 4}
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/FourthLevel",
      "@id": "/fourth_levels/1",
      "@type": "FourthLevel",
      "id": 1,
      "level": 4
    }
    """

  Scenario: Create a third level
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/third_levels" with body:
    """
    {"level": 3, "fourthLevel": "/fourth_levels/1"}
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
      "fourthLevel": "/fourth_levels/1",
      "id": 1,
      "level": 3,
      "test": true
    }
    """

  Scenario: Create a named related dummy
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/related_dummies" with body:
    """
    {"name": "Hello", "thirdLevel": "/third_levels/1"}
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  Scenario: Create an unnamed related dummy
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/related_dummies" with body:
    """
    {"thirdLevel": "/third_levels/1"}
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  Scenario: Create a dummy with relations
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Dummy with relations",
      "relatedDummy": "http://example.com/related_dummies/1",
      "relatedDummies": [
        "/related_dummies/1",
        "/related_dummies/2"
      ],
      "name_converted": null
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  Scenario: Get the subresource relation collection
    When I send a "GET" request to "/dummies/1/related_dummies"
    And the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelatedDummy",
      "@id": "/dummies/1/related_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/related_dummies/1",
          "@type": "https://schema.org/Product",
          "id": 1,
          "name": "Hello",
          "symfony": "symfony",
          "dummyDate": null,
          "thirdLevel": {
            "@id": "/third_levels/1",
            "@type": "ThirdLevel",
            "fourthLevel": "/fourth_levels/1"
          },
          "relatedToDummyFriend": [],
          "dummyBoolean": null,
          "embeddedDummy": [],
          "age": null
        },
        {
          "@id": "/related_dummies/2",
          "@type": "https://schema.org/Product",
          "id": 2,
          "name": null,
          "symfony": "symfony",
          "dummyDate": null,
          "thirdLevel": {
            "@id": "/third_levels/1",
            "@type": "ThirdLevel",
            "fourthLevel": "/fourth_levels/1"
          },
          "relatedToDummyFriend": [],
          "dummyBoolean": null,
          "embeddedDummy": [],
          "age": null
        }
      ],
      "hydra:totalItems": 2,
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "/dummies/1/related_dummies{?relatedToDummyFriend.dummyFriend,relatedToDummyFriend.dummyFriend[],name}",
        "hydra:variableRepresentation": "BasicRepresentation",
        "hydra:mapping": [
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedToDummyFriend.dummyFriend",
            "property": "relatedToDummyFriend.dummyFriend",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedToDummyFriend.dummyFriend[]",
            "property": "relatedToDummyFriend.dummyFriend",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "name",
            "property": "name",
            "required": false
          }
        ]
      }
    }
    """

  Scenario: Get filtered embedded relation collection
    When I send a "GET" request to "/dummies/1/related_dummies?name=Hello"
    And the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelatedDummy",
      "@id": "/dummies/1/related_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/related_dummies/1",
          "@type": "https://schema.org/Product",
          "id": 1,
          "name": "Hello",
          "symfony": "symfony",
          "dummyDate": null,
          "thirdLevel": {
            "@id": "/third_levels/1",
            "@type": "ThirdLevel",
            "fourthLevel": "/fourth_levels/1"
          },
          "relatedToDummyFriend": [],
          "dummyBoolean": null,
          "embeddedDummy": [],
          "age": null
        }
      ],
      "hydra:totalItems": 1,
      "hydra:view": {
        "@id": "/dummies/1/related_dummies?name=Hello",
        "@type": "hydra:PartialCollectionView"
      },
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "/dummies/1/related_dummies{?relatedToDummyFriend.dummyFriend,relatedToDummyFriend.dummyFriend[],name}",
        "hydra:variableRepresentation": "BasicRepresentation",
        "hydra:mapping": [
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedToDummyFriend.dummyFriend",
            "property": "relatedToDummyFriend.dummyFriend",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedToDummyFriend.dummyFriend[]",
            "property": "relatedToDummyFriend.dummyFriend",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "name",
            "property": "name",
            "required": false
          }
        ]
      }
    }
    """

  Scenario: Get the embedded relation collection at the third level
    When I send a "GET" request to "/dummies/1/related_dummies/1/third_level"
    And the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ThirdLevel",
      "@id": "/third_levels/1",
      "@type": "ThirdLevel",
      "fourthLevel": "/fourth_levels/1",
      "id": 1,
      "level": 3,
      "test": true
    }
    """

  Scenario: Get the embedded relation collection at the fourth level
    When I send a "GET" request to "/dummies/1/related_dummies/1/third_level/fourth_level"
    And the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/FourthLevel",
      "@id": "/fourth_levels/1",
      "@type": "FourthLevel",
      "id": 1,
      "level": 4
    }
    """

  Scenario: Get offers subresource from aggregate offers subresource
    Given I have a product with offers
    When I send a "GET" request to "/dummy_products/2/offers/1/offers"
    And the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyOffer",
      "@id": "/dummy_products/2/offers/1/offers",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/dummy_offers/1",
          "@type": "DummyOffer",
          "id": 1,
          "value": 2,
          "aggregate": "/dummy_aggregate_offers/1"
        }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: Get offers subresource from aggregate offers subresource
    When I send a "GET" request to "/dummy_aggregate_offers/1/offers"
    And the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyOffer",
      "@id": "/dummy_aggregate_offers/1/offers",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/dummy_offers/1",
          "@type": "DummyOffer",
          "id": 1,
          "value": 2,
          "aggregate": "/dummy_aggregate_offers/1"
        }
      ],
      "hydra:totalItems": 1
    }
    """

  @dropSchema
  Scenario: test
    When I send a "GET" request to "/dummy_products/2"
    And the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyProduct",
      "@id": "/dummy_products/2",
      "@type": "DummyProduct",
      "offers": [
        "/dummy_aggregate_offers/1"
      ],
      "id": 2,
      "name": "Dummy product",
      "relatedProducts": [
        "/dummy_products/1"
      ],
      "parent": null
    }
    """
