@mongodb
Feature: Embed many without target document deserializable
  In order to create and update resources
  As a developer
  I need to be able to deserialize data into objects with embed many that omit target document directive

  @createSchema
  Scenario: Post a resource with embedded data
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_with_embed_many_omitting_target_documents" with body:
    """
    {
      "embeddedDummies": [
        {
          "dummyName": "foo",
          "dummyBoolean": true,
          "dummyDate": "2020-01-01",
          "dummyFloat": 0.1,
          "dummyPrice": 10
        },
        {
          "dummyName": "bar",
          "dummyBoolean": false,
          "dummyDate": "2021-01-01",
          "dummyFloat": 0.2,
          "dummyPrice": 20
        }
      ]
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be a superset of:
    """
    {
      "@context": "/contexts/DummyWithEmbedManyOmittingTargetDocument",
      "@id": "/dummy_with_embed_many_omitting_target_documents/1",
      "@type": "DummyWithEmbedManyOmittingTargetDocument",
      "id": 1,
      "embeddedDummies": [
        {
          "@type": "EmbeddableDummy",
          "dummyName": "foo",
          "dummyBoolean": true,
          "dummyDate": "2020-01-01T00:00:00+00:00",
          "dummyFloat": 0.1,
          "dummyPrice": 10
        },
        {
          "@type": "EmbeddableDummy",
          "dummyName": "bar",
          "dummyBoolean": false,
          "dummyDate": "2021-01-01T00:00:00+00:00",
          "dummyFloat": 0.2,
          "dummyPrice": 20
        }
      ]
    }
    """
