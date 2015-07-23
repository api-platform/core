Feature: Relations support
  In order to use a hypermedia API
  As a client software developer
  I need to be able to update relations between resources

  @createSchema
  Scenario: Create a third level
    When I send a "POST" request to "/third_levels" with body:
    """
    {"level": 3}
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ThirdLevel",
      "@id": "/third_levels/1",
      "@type": "ThirdLevel",
      "level": 3,
      "test": true
    }
    """

  Scenario: Create a related dummy
    When I send a "POST" request to "/related_dummies" with body:
    """
    {
      "thirdLevel": "/third_levels/1"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelatedDummy",
      "@id": "/related_dummies/1",
      "@type": "https://schema.org/Product",
      "symfony": "symfony",
      "age": null,
      "thirdLevel": "/third_levels/1",
      "unknown": null
    }
    """

  Scenario: Create a dummy with relations
    When I send a "POST" request to "/dummies" with body:
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
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "name": "Dummy with relations",
      "alias": null,
      "dummyDate": null,
      "jsonData": [],
      "dummy": null,
      "relatedDummy": "/related_dummies/1",
      "relatedDummies": [
        "/related_dummies/1"
      ],
      "name_converted": null
    }
    """

  Scenario: Filter on a relation
    When I send a "GET" request to "/dummies?relatedDummy=%2Frelated_dummies%2F1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?relatedDummy=%2Frelated_dummies%2F1",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?relatedDummy=%2Frelated_dummies%2F1",
      "hydra:lastPage": "/dummies?relatedDummy=%2Frelated_dummies%2F1",
      "hydra:member": [
        {
          "@id": "/dummies/1",
          "@type": "Dummy",
          "name": "Dummy with relations",
          "alias": null,
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": "/related_dummies/1",
          "relatedDummies": [
            "/related_dummies/1"
          ],
          "name_converted": null
        }
      ],
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "\/dummies{?id,name,relatedDummies[],order[id],order[name],dummyDate[before],dummyDate[after]}",
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
                  "variable": "relatedDummies[]",
                  "property": "relatedDummies",
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
                  "variable": "dummyDate[before]",
                  "property": "dummyDate",
                  "required": false
              },
              {
                  "@type": "IriTemplateMapping",
                  "variable": "dummyDate[after]",
                  "property": "dummyDate",
                  "required": false
              }
        ]
      }
    }
    """

  Scenario: Filter on a to-many relation
    When I send a "GET" request to "/dummies?relatedDummies[]=%2Frelated_dummies%2F1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?relatedDummies[]=%2Frelated_dummies%2F1",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?relatedDummies%5B%5D=%2Frelated_dummies%2F1",
      "hydra:lastPage": "/dummies?relatedDummies%5B%5D=%2Frelated_dummies%2F1",
      "hydra:member": [
        {
          "@id": "/dummies/1",
          "@type": "Dummy",
          "name": "Dummy with relations",
          "alias": null,
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": "/related_dummies/1",
          "relatedDummies": [
            "/related_dummies/1"
          ],
          "name_converted": null
        }
      ],
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "\/dummies{?id,name,relatedDummies[],order[id],order[name],dummyDate[before],dummyDate[after]}",
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
                  "variable": "relatedDummies[]",
                  "property": "relatedDummies",
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
                  "variable": "dummyDate[before]",
                  "property": "dummyDate",
                  "required": false
              },
              {
                  "@type": "IriTemplateMapping",
                  "variable": "dummyDate[after]",
                  "property": "dummyDate",
                  "required": false
              }
        ]
      }
    }
    """

  Scenario: Embed a relation in the parent object
      When I send a "POST" request to "/relation_embedders" with body:
      """
      {
        "related": "/related_dummies/1"
      }
      """
      Then the response status code should be 201
      And the response should be in JSON
      And the header "Content-Type" should be equal to "application/ld+json"
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
                    "level": 3
                }
        }
      }
      """

  Scenario: Create an existing relation
    When I send a "POST" request to "/relation_embedders" with body:
    """
      {
        "anotherRelated": {
          "symfony": "laravel"
        }
      }
      """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
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

  Scenario: Update an embedded relation
    When I send a "PUT" request to "/relation_embedders/2" with body:
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
    And the header "Content-Type" should be equal to "application/ld+json"
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

  @dropSchema
  Scenario: Update an existing relation
    When I send a "POST" request to "/relation_embedders" with body:
    """
      {
        "anotherRelated": {
          "@id": "/related_dummies/2",
          "@type": "https://schema.org/Product",
          "symfony": "phalcon"
        }
      }
      """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelationEmbedder",
      "@id": "/relation_embedders/3",
      "@type": "RelationEmbedder",
      "krondstadt": "Krondstadt",
      "anotherRelated": {
        "@id": "/related_dummies/2",
        "@type": "https://schema.org/Product",
        "symfony": "phalcon",
        "thirdLevel": null
      },
      "related": null
    }
    """
