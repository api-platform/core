Feature: GraphQL DTO input and output
  In order to use the GraphQL API
  As a client software developer
  I need to be able to use DTOs on my resources as Input or Output objects.

  @createSchema
  Scenario: Retrieve an Output with GraphQL
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_dto_input_outputs" with body:
    """
    {
      "foo": "test",
      "bar": 1
    }
    """
    Then the response status code should be 201
    And the JSON should be equal to:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "id": "OutputDto/id",
        "baz": "OutputDto/baz",
        "bat": "OutputDto/bat"
      },
      "@type": "DummyDtoInputOutput",
      "@id": "/dummy_dto_input_outputs/1",
      "id": 1,
      "baz": 1,
      "bat": "test"
    }
    """
    When I send the following GraphQL request:
    """
    {
      dummyDtoInputOutput(id: "/dummy_dto_input_outputs/1") {
        _id, id, baz
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON should be equal to:
    """
    {
      "data": {
        "dummyDtoInputOutput": {
          "_id": 1,
          "id": "/dummy_dto_input_outputs/1",
          "baz": 1
        }
      }
    }
    """

  Scenario: Create an item using custom inputClass & disabled outputClass
    Given there are 2 dummyDtoNoOutput objects
    When I send the following GraphQL request:
    """
    mutation {
      createDummyDtoNoOutput(input: {foo: "A new one", bar: 3, clientMutationId: "myId"}) {
        dummyDtoNoOutput {
          id
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON should be equal to:
    """
    {
      "errors": [
        {
          "message": "Cannot query field \"id\" on type \"DummyDtoNoOutput\".",
          "extensions": {
            "category": "graphql"
          },
          "locations": [
            {
              "line": 4,
              "column": 7
            }
          ]
        }
      ]
    }
    """

  Scenario: Cannot create an item with input fields using disabled inputClass
    When I send the following GraphQL request:
    """
    mutation {
      createDummyDtoNoInput(input: {lorem: "A new one", ipsum: 3, clientMutationId: "myId"}) {
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON should be equal to:
    """
    {
      "errors": [
        {
          "message": "Field \"lorem\" is not defined by type createDummyDtoNoInputInput.",
          "extensions": {
            "category": "graphql"
          },
          "locations": [
            {
              "line": 2,
              "column": 33
            }
          ]
        },
        {
          "message": "Field \"ipsum\" is not defined by type createDummyDtoNoInputInput.",
          "extensions": {
            "category": "graphql"
          },
          "locations": [
            {
              "line": 2,
              "column": 53
            }
          ]
        }
      ]
    }
    """

  Scenario: Create an item with empty input fields using disabled inputClass (no persist done)
    When I send the following GraphQL request:
    """
    mutation {
      createDummyDtoNoInput(input: {clientMutationId: "myId"}) {
        dummyDtoNoInput {
          id
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON should be equal to:
    """
    {
      "data": {
        "createDummyDtoNoInput": {
          "dummyDtoNoInput": null,
          "clientMutationId": "myId"
        }
      }
    }
    """

  Scenario: Use messenger with GraphQL and an input where the handler gives a synchronous result
    When I send the following GraphQL request:
    """
    mutation {
      createMessengerWithInput(input: {var: "test"}) {
        messengerWithInput { id, name }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON should be equal to:
    """
    {
      "data": {
        "createMessengerWithInput": {
          "messengerWithInput": {
            "id": "/messenger_with_inputs/1",
            "name": "test"
          }
        }
      }
    }
    """
