Feature: JSON-LD DTO input and output
  In order to use a hypermedia API
  As a client software developer
  I need to be able to use DTOs on my resources as Input or Output objects.

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  @createSchema
  Scenario: Create a resource with a custom Input
    When I send a "POST" request to "/dummy_dto_customs" with body:
    """
    {
      "foo": "test",
      "bar": 1
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyDtoCustom",
      "@id": "/dummy_dto_customs/1",
      "@type": "DummyDtoCustom",
      "lorem": "test",
      "ipsum": "1",
      "id": 1
    }
    """

  @createSchema
  Scenario: Get an item with a custom output
    Given there is a DummyDtoCustom
    When I send a "GET" request to "/dummy_dto_custom_output/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be a superset of:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "foo": "CustomOutputDto/foo",
        "bar": "CustomOutputDto/bar"
      },
      "@type": "CustomOutputDto",
      "foo": "test",
      "bar": 1
    }
    """

  @createSchema
  Scenario: Get a collection with a custom output
    Given there are 2 DummyDtoCustom
    When I send a "GET" request to "/dummy_dto_custom_output"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be a superset of:
    """
    {
      "@context": "/contexts/DummyDtoCustom",
      "@id": "/dummy_dto_custom_output",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@type": "CustomOutputDto",
          "foo": "test",
          "bar": 1
        },
        {
          "@type": "CustomOutputDto",
          "foo": "test",
          "bar": 2
        }
      ],
      "hydra:totalItems": 2
    }
    """

  @createSchema
  Scenario: Create a DummyDtoCustom object without output
    When I send a "POST" request to "/dummy_dto_custom_post_without_output" with body:
    """
    {
      "lorem": "test",
      "ipsum": "1"
    }
    """
    Then the response status code should be 204
    And the response should be empty

  @createSchema
  Scenario: Create and update a DummyInputOutput
    When I send a "POST" request to "/dummy_dto_input_outputs" with body:
    """
    {
      "foo": "test",
      "bar": 1
    }
    """
    Then the response status code should be 201
    And the JSON should be a superset of:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "id": "OutputDto/id",
        "baz": "OutputDto/baz",
        "bat": "OutputDto/bat",
        "relatedDummies": "OutputDto/relatedDummies"
      },
      "@type": "OutputDto",
      "id": 1,
      "baz": 1,
      "bat": "test",
      "relatedDummies": []
    }
    """
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummy_dto_input_outputs/1" with body:
    """
    {
      "foo": "test",
      "bar": 2
    }
    """
    Then the response status code should be 200
    And the JSON should be a superset of:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "id": "OutputDto/id",
        "baz": "OutputDto/baz",
        "bat": "OutputDto/bat",
        "relatedDummies": "OutputDto/relatedDummies"
      },
      "@type": "OutputDto",
      "id": 1,
      "baz": 2,
      "bat": "test",
      "relatedDummies": []
    }
    """

  @!mongodb
  @createSchema
  Scenario: Use DTO with relations on User
    When I send a "POST" request to "/users" with body:
    """
    {
      "username": "soyuka",
      "plainPassword": "a real password",
      "email": "soyuka@example.com"
    }
    """
    Then the response status code should be 201
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/users/recover/1" with body:
    """
    {
      "user": "/users/1"
    }
    """
    Then the response status code should be 200
    And the JSON should be a superset of:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "dummy": "RecoverPasswordOutput/dummy"
      },
      "@type": "RecoverPasswordOutput",
      "dummy": "/dummies/1"
    }
    """

  @createSchema
  @controller
  Scenario: Create a resource with no input
    When I send a "POST" request to "/dummy_dto_no_inputs"
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be a superset of:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "id": "OutputDto/id",
        "baz": "OutputDto/baz",
        "bat": "OutputDto/bat",
        "relatedDummies": "OutputDto/relatedDummies"
      },
      "@type": "OutputDto",
      "id": 1,
      "baz": 1,
      "bat": "test",
      "relatedDummies": []
    }
    """

  @controller
  Scenario: Update a resource with no input
    When I send a "POST" request to "/dummy_dto_no_inputs/1/double_bat"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be a superset of:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "id": "OutputDto/id",
        "baz": "OutputDto/baz",
        "bat": "OutputDto/bat",
        "relatedDummies": "OutputDto/relatedDummies"
      },
      "@type": "OutputDto",
      "id": 1,
      "baz": 1,
      "bat": "testtest",
      "relatedDummies": []
    }
    """

  @!mongodb
  Scenario: Use messenger with an input where the handler gives a synchronous result
    When I send a "POST" request to "/messenger_with_inputs" with body:
    """
    {
      "var": "test"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/MessengerWithInput",
      "@id": "/messenger_with_inputs/1",
      "@type": "MessengerWithInput",
      "id": 1,
      "name": "test"
    }
    """

  @!mongodb
  Scenario: Use messenger with an input where the handler gives a synchronous Response result
    When I send a "POST" request to "/messenger_with_responses" with body:
    """
    {
      "var": "test"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON should be equal to:
    """
    {
      "data": 123
    }
    """

  @createSchema
  Scenario: Initialize input data with a DataTransformerInitializer
    Given there is an InitializeInput object with id 1
    When I send a "PUT" request to "/initialize_inputs/1" with body:
    """
    {
      "name": "La peste"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/InitializeInput",
      "@id": "/initialize_inputs/1",
      "@type": "InitializeInput",
      "id": 1,
      "manager": "Orwell",
      "name": "La peste"
    }
    """

  Scenario: Create a resource with a custom Input
    When I send a "POST" request to "/dummy_dto_customs" with body:
    """
    {
      "foo": "test",
      "bar": "test"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the JSON node "detail" should be equal to "The input data is misformatted."

  @!mongodb
  Scenario: Reset password through an input DTO without DataTransformer
    When I send a "POST" request to "/user-reset-password" with body:
    """
    {
      "email": "user@example.com"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "email" should be equal to "user@example.com"

  @!mongodb
  Scenario: Reset password with an invalid payload through an input DTO without DataTransformer
    And I send a "POST" request to "/user-reset-password" with body:
    """
    {
      "email": "this is not an email"
    }
    """
    Then the response status code should be 422
    And the response should be in JSON

  @v3
  Scenario: Get a collection with a custom output and without item operations, from a resource without identifier
    When I send a "GET" request to "/dummy_collection_dtos"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "hydra:member", "hydra:totalItems"],
      "properties": {
        "@context": {"pattern": "^/contexts/DummyCollectionDto$"},
        "@id": {"pattern": "^/dummy_collection_dtos$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "minItems": 2,
          "maxItems": 2,
          "uniqueItems": true,
          "items": {
            "type": "object",
            "additionalProperties": false,
            "required": ["@id", "@type", "foo", "bar"],
            "properties": {
              "@id": {"pattern": "^/.well-known/genid/.+$"},
              "@type": {"pattern": "^DummyCollectionDtoOutput$"},
              "foo": {"type": "string"},
              "bar": {"type": "integer"}
            }
          }
        },
        "hydra:totalItems": {"type": "integer", "minimum": 2, "maximum": 2}
      }
    }
    """

  @v3
  # Cannot generate proper IRI because DTO does not support resource yet
  # todo Change member IRI to `/dummy_foos/bar` once DTO support resource
  Scenario: Get a collection with a custom output and itemUriTemplate, from a resource without identifier
    When I send a "GET" request to "/dummy_foo_collection_dtos"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "hydra:member", "hydra:totalItems"],
      "properties": {
        "@context": {"pattern": "^/contexts/DummyFooCollectionDto$"},
        "@id": {"pattern": "^/dummy_foo_collection_dtos$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "minItems": 2,
          "maxItems": 2,
          "uniqueItems": true,
          "items": {
            "type": "object",
            "additionalProperties": false,
            "required": ["@id", "@type", "foo", "bar"],
            "properties": {
              "@id": {"pattern": "^/.well-known/genid/.+$"},
              "@type": {"pattern": "^DummyCollectionDtoOutput$"},
              "foo": {"type": "string"},
              "bar": {"type": "integer"}
            }
          }
        },
        "hydra:totalItems": {"type": "integer", "minimum": 2, "maximum": 2}
      }
    }
    """

  @v3
  # Cannot generate proper IRI because DTO does not support output yet
  # todo Change member IRI to `/dummy_id_collection_dtos/.+` once DTO support @ApiProperty
  Scenario: Get a collection with a custom output and without item operations, from a resource with an identifier
    When I send a "GET" request to "/dummy_id_collection_dtos"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "hydra:member", "hydra:totalItems"],
      "properties": {
        "@context": {"pattern": "^/contexts/DummyIdCollectionDto$"},
        "@id": {"pattern": "^/dummy_id_collection_dtos$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "minItems": 2,
          "maxItems": 2,
          "uniqueItems": true,
          "items": {
            "type": "object",
            "additionalProperties": false,
            "required": ["@id", "@type", "id", "foo", "bar"],
            "properties": {
              "@id": {"pattern": "^/.well-known/genid/.+$"},
              "@type": {"pattern": "^DummyIdCollectionDtoOutput$"},
              "id": {"type": "integer"},
              "foo": {"type": "string"},
              "bar": {"type": "integer"}
            }
          }
        },
        "hydra:totalItems": {"type": "integer", "minimum": 2, "maximum": 2}
      }
    }
    """
