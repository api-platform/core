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

  @createSchema
  Scenario: Retrieve an item with different relations to the same resource
    Given there are 2 multiRelationsDummy objects having each a manyToOneRelation, 2 manyToManyRelations, 3 oneToManyRelations and 4 embeddedRelations
    When I send the following GraphQL request:
    """
    {
      multiRelationsDummy(id: "/multi_relations_dummies/2") {
        id
        name
        manyToOneRelation {
          id
          name
        }
        manyToManyRelations {
          edges{
            node {
             id
              name
            }
          }
        }
        oneToManyRelations {
          edges{
            node {
              id
              name
            }
          }
        }
        nestedCollection {
          name
        }
        nestedPaginatedCollection {
          edges{
            node {
              name
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.multiRelationsDummy.id" should be equal to "/multi_relations_dummies/2"
    And the JSON node "data.multiRelationsDummy.name" should be equal to "Dummy #2"
    And the JSON node "data.multiRelationsDummy.manyToOneRelation.id" should not be null
    And the JSON node "data.multiRelationsDummy.manyToOneRelation.name" should be equal to "RelatedManyToOneDummy #2"
    And the JSON node "data.multiRelationsDummy.manyToManyRelations.edges" should have 2 element
    And the JSON node "data.multiRelationsDummy.manyToManyRelations.edges[1].node.id" should not be null
    And the JSON node "data.multiRelationsDummy.manyToManyRelations.edges[0].node.name" should match "#RelatedManyToManyDummy(1|2)2#"
    And the JSON node "data.multiRelationsDummy.manyToManyRelations.edges[1].node.name" should match "#RelatedManyToManyDummy(1|2)2#"
    And the JSON node "data.multiRelationsDummy.oneToManyRelations.edges" should have 3 element
    And the JSON node "data.multiRelationsDummy.oneToManyRelations.edges[1].node.id" should not be null
    And the JSON node "data.multiRelationsDummy.oneToManyRelations.edges[0].node.name" should match "#RelatedOneToManyDummy(1|3)2#"
    And the JSON node "data.multiRelationsDummy.oneToManyRelations.edges[2].node.name" should match "#RelatedOneToManyDummy(1|3)2#"
    And the JSON node "data.multiRelationsDummy.nestedCollection[0].name" should be equal to "NestedDummy1"
    And the JSON node "data.multiRelationsDummy.nestedCollection[1].name" should be equal to "NestedDummy2"
    And the JSON node "data.multiRelationsDummy.nestedCollection[2].name" should be equal to "NestedDummy3"
    And the JSON node "data.multiRelationsDummy.nestedCollection[3].name" should be equal to "NestedDummy4"
    And the JSON node "data.multiRelationsDummy.nestedPaginatedCollection.edges" should have 4 element
    And the JSON node "data.multiRelationsDummy.nestedPaginatedCollection.edges[0].node.name" should be equal to "NestedPaginatedDummy1"
    And the JSON node "data.multiRelationsDummy.nestedPaginatedCollection.edges[1].node.name" should be equal to "NestedPaginatedDummy2"
    And the JSON node "data.multiRelationsDummy.nestedPaginatedCollection.edges[2].node.name" should be equal to "NestedPaginatedDummy3"
    And the JSON node "data.multiRelationsDummy.nestedPaginatedCollection.edges[3].node.name" should be equal to "NestedPaginatedDummy4"

  @createSchema @!mongodb
  Scenario: Retrieve an item with child relation to the same resource
    Given there are tree dummies
    When I send the following GraphQL request:
    """
    {
      treeDummies {
        edges {
          node {
            id
            children {
              totalCount
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors" should not exist
    And the JSON node "data.treeDummies.edges[0].node.id" should be equal to "/tree_dummies/1"
    And the JSON node "data.treeDummies.edges[0].node.children.totalCount" should be equal to "1"
    And the JSON node "data.treeDummies.edges[1].node.id" should be equal to "/tree_dummies/2"
    And the JSON node "data.treeDummies.edges[1].node.children.totalCount" should be equal to "0"

  @createSchema
  Scenario: Retrieve a Relay Node
    Given there are 2 dummy objects with relatedDummy
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

  @createSchema
  Scenario: Retrieve an item with an iterable field
    Given there are 2 dummy objects with relatedDummy
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

  @createSchema
  Scenario: Retrieve an item with an iterable null field
    Given there are 2 dummy with null JSON objects
    When I send the following GraphQL request:
    """
    {
      withJsonDummy(id: "/with_json_dummies/2") {
        id
        json
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.withJsonDummy.id" should be equal to "/with_json_dummies/2"
    And the JSON node "data.withJsonDummy.json" should be null

  @createSchema
  Scenario: Retrieve an item through a GraphQL query with variables
    Given there are 2 dummy objects with relatedDummy
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
    And the GraphQL debug message should be equal to 'No route matches "/foo/1".'
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
              "message": {"type": "string"},
              "extensions": {
                "type": "object",
                "properties": {
                  "debugMessage": {"type": "string"},
                  "file": {"type": "string"},
                  "line": {"type": "integer"},
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
                }
              },
              "locations": {"type": "array"},
              "path": {"type": "array"}
            },
            "required": [
              "message",
              "extensions",
              "locations",
              "path"
            ]
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
