Feature: JSON-LD contexts generation
  In order to have an hypermedia, Linked Data enabled API
  As a client software developer
  I need to access to a JSON-LD context describing data types

  Scenario: Retrieve Entrypoint context
    When I send a "GET" request to "/contexts/Entrypoint"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "@context.@vocab" should be equal to "http://example.com/docs.jsonld#"
    And the JSON node "@context.hydra" should be equal to "http://www.w3.org/ns/hydra/core#"
    And the JSON node "@context.dummy.@id" should be equal to "Entrypoint/dummy"
    And the JSON node "@context.dummy.@type" should be equal to "@id"

  Scenario: Retrieve Dummy context
    When I send a "GET" request to "/contexts/Dummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be a superset of:
    """
    {
        "@context": {
            "@vocab": "http://example.com/docs.jsonld#",
            "hydra": "http://www.w3.org/ns/hydra/core#",
            "description": "https://schema.org/description",
            "dummy": "Dummy/dummy",
            "dummyBoolean": "Dummy/dummyBoolean",
            "dummyDate": "https://schema.org/DateTime",
            "dummyFloat": "Dummy/dummyFloat",
            "dummyPrice": "Dummy/dummyPrice",
            "relatedDummy": {
                "@id": "Dummy/relatedDummy",
                "@type": "@id"
            },
            "relatedDummies": {
                "@id": "Dummy/relatedDummies",
                "@type": "@id"
            },
            "jsonData": "Dummy/jsonData",
            "arrayData": "Dummy/arrayData",
            "nameConverted": "Dummy/nameConverted",
            "name": "https://schema.org/name",
            "alias": "https://schema.org/alternateName",
            "foo": "Dummy/foo"
        }
    }
    """

  Scenario: Retrieve context of an object with an embed relation
    When I send a "GET" request to "/contexts/RelationEmbedder"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
      """
      {
          "@context": {
              "@vocab": "http://example.com/docs.jsonld#",
              "hydra": "http://www.w3.org/ns/hydra/core#",
              "paris": "RelationEmbedder/paris",
              "krondstadt": "RelationEmbedder/krondstadt",
              "anotherRelated": "RelationEmbedder/anotherRelated",
              "related": "RelationEmbedder/related"
          }
      }
      """

  Scenario: Retrieve Dummy with extended jsonld context
    When I send a "GET" request to "/contexts/JsonldContextDummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
      """
      {
          "@context": {
              "@vocab": "http://example.com/docs.jsonld#",
              "hydra": "http://www.w3.org/ns/hydra/core#",
              "person": {
                  "@id": "https://example.com/id",
                  "@type": "@id",
                  "foo": "bar"
              }
          }
      }
      """

  @createSchema
  Scenario: The JSON-LD context of a translatable resource contains the language
    When I add "Accept-Language" header equal to "en"
    And I send a "GET" request to "/dummy_translatables"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "@context.@language" should be equal to "en"
    When I add "Accept-Language" header equal to "fr"
    And I send a "GET" request to "/dummy_translatables"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "@context.@language" should be equal to "fr"

  Scenario: The JSON-LD context of a translatable resource with all its translations uses the language map
    When I send a "GET" request to "/dummy_translatables?allTranslations=true"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "@context.name.@container" should be equal to "@language"
    And the JSON node "@context.description.@container" should be equal to "@language"
