Feature: JSON-LD contexts generation
  In order to have an hypermedia, Linked Data enabled API
  As a client software developer
  I need to access to a JSON-LD context describing data types

  Scenario: Retrieve Entrypoint context
    When I send a "GET" request to "/contexts/Dummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
        "@context": {
            "@vocab": "http://example.com/apidoc#",
            "hydra": "http://www.w3.org/ns/hydra/core#",
            "name": "http://schema.org/name",
            "alias": "https://schema.org/alternateName",
            "description": "https://schema.org/description",
            "foo": "#Dummy/foo",
            "dummyDate": "#Dummy/dummyDate",
            "dummyPrice": "#Dummy/dummyPrice",
            "jsonData": "#Dummy/jsonData",
            "relatedDummy": {
                "@id": "#Dummy/relatedDummy",
                "@type": "@id"
            },
            "dummy": "#Dummy/dummy",
            "relatedDummies": {
                "@id": "#Dummy/relatedDummies",
                "@type": "@id"
            },
            "name_converted": "#Dummy/name_converted"
        }
    }
    """

  Scenario: Retrieve Dummy context
    When I send a "GET" request to "/contexts/Dummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
        "@context": {
            "@vocab": "http://example.com/apidoc#",
            "hydra": "http://www.w3.org/ns/hydra/core#",
            "name": "http://schema.org/name",
            "alias": "https://schema.org/alternateName",
            "description": "https://schema.org/description",
            "foo": "#Dummy/foo",
            "dummyDate": "#Dummy/dummyDate",
            "dummyPrice": "#Dummy/dummyPrice",
            "jsonData": "#Dummy/jsonData",
            "relatedDummy": {
                "@id": "#Dummy/relatedDummy",
                "@type": "@id"
            },
            "dummy": "#Dummy/dummy",
            "relatedDummies": {
                "@id": "#Dummy/relatedDummies",
                "@type": "@id"
            },
            "name_converted": "#Dummy/name_converted"
        }
    }
    """

    Scenario: Retrieve context of an object with an embed relation
      When I send a "GET" request to "/contexts/RelationEmbedder"
      Then the response status code should be 200
      And the response should be in JSON
      And the header "Content-Type" should be equal to "application/ld+json"
      And the JSON should be equal to:
      """
      {
        "@context": {
          "@vocab": "http://example.com/apidoc#",
          "hydra": "http://www.w3.org/ns/hydra/core#",
          "related": "#RelationEmbedder/related",
          "paris": "#RelationEmbedder/paris",
          "krondstadt": "#RelationEmbedder/krondstadt",
          "anotherRelated": "#RelationEmbedder/anotherRelated"
        }
      }
      """
