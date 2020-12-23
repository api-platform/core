Feature: GraphQL query support
  @createSchema
  Scenario: Execute a basic GraphQL query
    Given there are 2 dummy objects with relatedDummy
    When I send the following GraphQL request:
    """
    {
      dummy(id: "/dummies/1") {
        id
        name
        name_converted
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummy.id" should be equal to "/dummies/1"
    And the JSON node "data.dummy.name" should be equal to "Dummy #1"
    And the JSON node "data.dummy.name_converted" should be equal to "Converted 1"

  Scenario: Retrieve a Relay Node
    When I send the following GraphQL request:
    """
    {
      node(id: "/dummies/1") {
        id
        ... on Dummy {
          name
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.node.id" should be equal to "/dummies/1"
    And the JSON node "data.node.name" should be equal to "Dummy #1"

  Scenario: Retrieve an item with an iterable field
    Given there are 2 dummy objects with JSON and array data
    When I send the following GraphQL request:
    """
    {
      dummy(id: "/dummies/3") {
        id
        name
        jsonData
        arrayData
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummy.id" should be equal to "/dummies/3"
    And the JSON node "data.dummy.name" should be equal to "Dummy #1"
    And the JSON node "data.dummy.jsonData.foo" should have 2 elements
    And the JSON node "data.dummy.jsonData.bar" should be equal to 5
    And the JSON node "data.dummy.arrayData[2]" should be equal to baz

  Scenario: Retrieve an item through a GraphQL query with variables
    When I have the following GraphQL request:
    """
    query DummyWithId($itemId: ID = "/dummies/1") {
      dummyItem: dummy(id: $itemId) {
        id
        name
        relatedDummy {
          id
          name
        }
      }
    }
    """
    And I send the GraphQL request with variables:
    """
    {
      "itemId": "/dummies/2"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummyItem.id" should be equal to "/dummies/2"
    And the JSON node "data.dummyItem.name" should be equal to "Dummy #2"
    And the JSON node "data.dummyItem.relatedDummy.id" should be equal to "/related_dummies/2"
    And the JSON node "data.dummyItem.relatedDummy.name" should be equal to "RelatedDummy #2"

  Scenario: Run a specific operation through a GraphQL query
    When I have the following GraphQL request:
    """
    query DummyWithId1 {
      dummyItem: dummy(id: "/dummies/1") {
        name
      }
    }
    query DummyWithId2 {
      dummyItem: dummy(id: "/dummies/2") {
        id
        name
      }
    }
    """
    And I send the GraphQL request with operationName "DummyWithId2"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummyItem.id" should be equal to "/dummies/2"
    And the JSON node "data.dummyItem.name" should be equal to "Dummy #2"
    And I send the GraphQL request with operationName "DummyWithId1"
    And the JSON node "data.dummyItem.name" should be equal to "Dummy #1"

  Scenario: Use serialization groups
    Given there are 1 dummy group objects
    When I send the following GraphQL request:
    """
    {
      dummyGroup(id: "/dummy_groups/1") {
        foo
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummyGroup.foo" should be equal to "Foo #1"

  Scenario: Query a serialized name
    Given there is a DummyCar entity with related colors
    When I send the following GraphQL request:
    """
    {
      dummyCar(id: "/dummy_cars/1") {
        carBrand
      }
    }
    """
    Then the JSON node "data.dummyCar.carBrand" should be equal to "DummyBrand"

  Scenario: Fetch only the internal id
    When I send the following GraphQL request:
    """
    {
      dummy(id: "/dummies/1") {
        _id
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummy._id" should be equal to "1"

  Scenario: Retrieve an nonexistent item through a GraphQL query
    When I send the following GraphQL request:
    """
    {
      dummy(id: "/dummies/5") {
        name
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummy" should be null

  Scenario: Retrieve an nonexistent IRI through a GraphQL query
    When I send the following GraphQL request:
    """
    {
      foo(id: "/foo/1") {
        name
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].debugMessage" should be equal to 'No route matches "/foo/1".'
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "errors": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "debugMessage": {"type": "string"},
              "message": {"type": "string"},
              "extensions": {"type": "object"},
              "locations": {"type": "array"},
              "path": {"type": "array"},
              "trace": {
                "type": "array",
                "items": {
                  "type": "object",
                  "properties": {
                    "file": {"type": "string"},
                    "line": {"type": "integer"},
                    "call": {"type": ["string", "null"]},
                    "function": {"type": ["string", "null"]}
                  },
                  "additionalProperties": false
                },
                "minItems": 1
              }
            },
            "required": [
              "debugMessage",
              "message",
              "extensions",
              "locations",
              "path",
              "trace"
            ],
            "additionalProperties": false
          },
          "minItems": 1,
          "maxItems": 1
        }
      }
    }
    """

  Scenario: Use outputClass instead of resource class through a GraphQL query
    Given there are 2 dummyDtoNoInput objects
    When I send the following GraphQL request:
    """
    {
      dummyDtoNoInputs {
        edges {
          node {
            baz
            bat
          }
        }
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
        "dummyDtoNoInputs": {
          "edges": [
            {
              "node": {
                "baz": 0.33,
                "bat": "DummyDtoNoInput foo #1"
              }
            },
            {
              "node": {
                "baz": 0.67,
                "bat": "DummyDtoNoInput foo #2"
              }
            }
          ]
        }
      }
    }
    """

  @createSchema
  Scenario: Disable outputClass leads to an empty response through a GraphQL query
    Given there are 2 dummyDtoNoOutput objects
    When I send the following GraphQL request:
    """
    {
      dummyDtoNoInputs {
        edges {
          node {
            baz
            bat
          }
        }
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
        "dummyDtoNoInputs": {
          "edges": []
        }
      }
    }
    """

  Scenario: Custom not retrieved item query
    When I send the following GraphQL request:
    """
    {
      testNotRetrievedItemDummyCustomQuery {
        message
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
        "testNotRetrievedItemDummyCustomQuery": {
          "message": "Success (not retrieved)!"
        }
      }
    }
    """

  Scenario: Custom item query with read and serialize set to false
    When I send the following GraphQL request:
    """
    {
      testNoReadAndSerializeItemDummyCustomQuery(id: "/not_used") {
        message
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
        "testNoReadAndSerializeItemDummyCustomQuery": null
      }
    }
    """

  Scenario: Custom item query
    Given there are 2 dummyCustomQuery objects
    When I send the following GraphQL request:
    """
    {
      testItemDummyCustomQuery(id: "/dummy_custom_queries/1") {
        message
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
        "testItemDummyCustomQuery": {
          "message": "Success!"
        }
      }
    }
    """

  Scenario: Custom item query with custom arguments
    Given there are 2 dummyCustomQuery objects
    When I send the following GraphQL request:
    """
    {
      testItemCustomArgumentsDummyCustomQuery(
        id: "/dummy_custom_queries/1",
        customArgumentBool: true,
        customArgumentInt: 3,
        customArgumentString: "A string",
        customArgumentFloat: 2.6,
        customArgumentIntArray: [4],
        customArgumentCustomType: "2019-05-24T00:00:00+00:00"
      ) {
        message
        customArgs
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
        "testItemCustomArgumentsDummyCustomQuery": {
          "message": "Success!",
          "customArgs": {
            "id": "/dummy_custom_queries/1",
            "customArgumentBool": true,
            "customArgumentInt": 3,
            "customArgumentString": "A string",
            "customArgumentFloat": 2.6,
            "customArgumentIntArray": [4],
            "customArgumentCustomType": "2019-05-24T00:00:00+00:00"
          }
        }
      }
    }
    """

  @createSchema
  Scenario: Retrieve an item with different serialization groups for item_query and collection_query
    Given there are 1 dummy with different GraphQL serialization groups objects
    When I send the following GraphQL request:
    """
    {
      dummyDifferentGraphQlSerializationGroup(id: "/dummy_different_graph_ql_serialization_groups/1") {
        name
        title
      }
    }
    """
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummyDifferentGraphQlSerializationGroup.name" should be equal to "Name #1"
    And the JSON node "data.dummyDifferentGraphQlSerializationGroup.title" should be equal to "Title #1"
