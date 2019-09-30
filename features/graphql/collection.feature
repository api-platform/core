Feature: GraphQL collection support
  @createSchema
  Scenario: Retrieve a collection through a GraphQL query
    Given there are 4 dummy objects with relatedDummy and its thirdLevel
    When I send the following GraphQL request:
    """
    {
      dummies {
        ...dummyFields
      }
    }
    fragment dummyFields on DummyConnection {
      edges {
        node {
          id
          name
          relatedDummy {
            name
            thirdLevel {
              id
              level
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges[2].node.name" should be equal to "Dummy #3"
    And the JSON node "data.dummies.edges[2].node.relatedDummy.name" should be equal to "RelatedDummy #3"
    And the JSON node "data.dummies.edges[2].node.relatedDummy.thirdLevel.level" should be equal to 3

  @createSchema
  Scenario: Retrieve an nonexistent collection through a GraphQL query
    When I send the following GraphQL request:
    """
    {
      dummies {
        edges {
          node {
            name
          }
        }
        pageInfo {
          startCursor
          endCursor
          hasNextPage
          hasPreviousPage
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges" should have 0 element
    And the JSON node "data.dummies.pageInfo.endCursor" should be null
    And the JSON node "data.dummies.pageInfo.startCursor" should be null
    And the JSON node "data.dummies.pageInfo.hasNextPage" should be false
    And the JSON node "data.dummies.pageInfo.hasPreviousPage" should be false

  @createSchema
  Scenario: Retrieve a collection with a nested collection through a GraphQL query
    Given there are 4 dummy objects having each 3 relatedDummies
    When I send the following GraphQL request:
    """
    {
      dummies {
        edges {
          node {
            name
            relatedDummies {
              edges {
                node {
                  name
                }
              }
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges[2].node.name" should be equal to "Dummy #3"
    And the JSON node "data.dummies.edges[2].node.relatedDummies.edges[1].node.name" should be equal to "RelatedDummy23"

  @createSchema
  Scenario: Retrieve a collection and an item through a GraphQL query
    Given there are 3 dummy objects with dummyDate
    And there are 2 dummy group objects
    When I send the following GraphQL request:
    """
    {
      dummies {
        edges {
          node {
            name
            dummyDate
          }
        }
      }
      dummyGroup(id: "/dummy_groups/2") {
        foo
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges[1].node.name" should be equal to "Dummy #2"
    And the JSON node "data.dummies.edges[1].node.dummyDate" should be equal to "2015-04-02"
    And the JSON node "data.dummyGroup.foo" should be equal to "Foo #2"

  @createSchema
  Scenario: Retrieve a specific number of items in a collection through a GraphQL query
    Given there are 4 dummy objects
    When I send the following GraphQL request:
    """
    {
      dummies(first: 2) {
        edges {
          node {
            name
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges" should have 2 elements

  @createSchema
  Scenario: Retrieve a specific number of items in a nested collection through a GraphQL query
    Given there are 2 dummy objects having each 5 relatedDummies
    When I send the following GraphQL request:
    """
    {
      dummies(first: 1) {
        edges {
          node {
            name
            relatedDummies(first: 2) {
              edges {
                node {
                  name
                }
              }
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges" should have 1 element
    And the JSON node "data.dummies.edges[0].node.relatedDummies.edges" should have 2 elements

  @createSchema
  Scenario: Paginate through collections through a GraphQL query
    Given there are 4 dummy objects having each 4 relatedDummies
    When I send the following GraphQL request:
    """
    {
      dummies(first: 2) {
        edges {
          node {
            name
            relatedDummies(first: 2) {
              edges {
                node {
                  name
                }
                cursor
              }
              totalCount
              pageInfo {
                endCursor
                hasNextPage
              }
            }
          }
          cursor
        }
        totalCount
        pageInfo {
          endCursor
          hasNextPage
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.pageInfo.endCursor" should be equal to "MQ=="
    And the JSON node "data.dummies.pageInfo.hasNextPage" should be true
    And the JSON node "data.dummies.totalCount" should be equal to 4
    And the JSON node "data.dummies.edges[1].node.name" should be equal to "Dummy #2"
    And the JSON node "data.dummies.edges[1].cursor" should be equal to "MQ=="
    And the JSON node "data.dummies.edges[1].node.relatedDummies.pageInfo.endCursor" should be equal to "MQ=="
    And the JSON node "data.dummies.edges[1].node.relatedDummies.pageInfo.hasNextPage" should be true
    And the JSON node "data.dummies.edges[1].node.relatedDummies.totalCount" should be equal to 4
    And the JSON node "data.dummies.edges[1].node.relatedDummies.edges[0].node.name" should be equal to "RelatedDummy12"
    And the JSON node "data.dummies.edges[1].node.relatedDummies.edges[0].cursor" should be equal to "MA=="
    When I send the following GraphQL request:
    """
    {
      dummies(first: 2, after: "MQ==") {
        edges {
          node {
            name
            relatedDummies(first: 2, after: "MA==") {
              edges {
                node {
                  name
                }
                cursor
              }
              pageInfo {
                endCursor
                hasNextPage
              }
            }
          }
          cursor
        }
        pageInfo {
          endCursor
          hasNextPage
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges[0].node.name" should be equal to "Dummy #3"
    And the JSON node "data.dummies.edges[0].cursor" should be equal to "Mg=="
    And the JSON node "data.dummies.edges[1].node.relatedDummies.edges[0].node.name" should be equal to "RelatedDummy24"
    And the JSON node "data.dummies.edges[1].node.relatedDummies.edges[0].cursor" should be equal to "MQ=="
    When I send the following GraphQL request:
    """
    {
      dummies(first: 2, after: "Mg==") {
        edges {
          node {
            name
            relatedDummies(first: 3, after: "MQ==") {
              edges {
                node {
                  name
                }
                cursor
              }
              pageInfo {
                endCursor
                hasNextPage
              }
            }
          }
          cursor
        }
        pageInfo {
          endCursor
          hasNextPage
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges" should have 1 element
    And the JSON node "data.dummies.pageInfo.hasNextPage" should be false
    And the JSON node "data.dummies.pageInfo.endCursor" should be equal to "Mw=="
    And the JSON node "data.dummies.edges[0].node.name" should be equal to "Dummy #4"
    And the JSON node "data.dummies.edges[0].cursor" should be equal to "Mw=="
    And the JSON node "data.dummies.edges[0].node.relatedDummies.pageInfo.hasNextPage" should be false
    And the JSON node "data.dummies.edges[0].node.relatedDummies.pageInfo.endCursor" should be equal to "Mw=="
    And the JSON node "data.dummies.edges[0].node.relatedDummies.edges" should have 2 elements
    And the JSON node "data.dummies.edges[0].node.relatedDummies.edges[1].node.name" should be equal to "RelatedDummy44"
    And the JSON node "data.dummies.edges[0].node.relatedDummies.edges[1].cursor" should be equal to "Mw=="
    When I send the following GraphQL request:
    """
    {
      dummies(first: 2, after: "Mw==") {
        edges {
          node {
            name
            relatedDummies(first: 1, after: "MQ==") {
              edges {
                node {
                  name
                }
                cursor
              }
              pageInfo {
                endCursor
                hasNextPage
              }
            }
          }
          cursor
        }
        pageInfo {
          endCursor
          hasNextPage
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges" should have 0 element

  @createSchema
  Scenario: Paginate backwards through collections through a GraphQL query
    Given there are 4 dummy objects having each 4 relatedDummies
    When I send the following GraphQL request:
    """
    {
      dummies(last: 2) {
        edges {
          node {
            name
            relatedDummies(last: 2) {
              edges {
                node {
                  name
                }
                cursor
              }
              totalCount
              pageInfo {
                startCursor
                hasPreviousPage
              }
            }
          }
          cursor
        }
        totalCount
        pageInfo {
          startCursor
          hasPreviousPage
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.pageInfo.startCursor" should be equal to "Mg=="
    And the JSON node "data.dummies.pageInfo.hasPreviousPage" should be true
    And the JSON node "data.dummies.totalCount" should be equal to 4
    And the JSON node "data.dummies.edges[1].node.name" should be equal to "Dummy #4"
    And the JSON node "data.dummies.edges[1].cursor" should be equal to "Mw=="
    And the JSON node "data.dummies.edges[1].node.relatedDummies.pageInfo.startCursor" should be equal to "Mg=="
    And the JSON node "data.dummies.edges[1].node.relatedDummies.pageInfo.hasPreviousPage" should be true
    And the JSON node "data.dummies.edges[1].node.relatedDummies.totalCount" should be equal to 4
    And the JSON node "data.dummies.edges[1].node.relatedDummies.edges[0].node.name" should be equal to "RelatedDummy34"
    And the JSON node "data.dummies.edges[1].node.relatedDummies.edges[0].cursor" should be equal to "Mg=="
    When I send the following GraphQL request:
    """
    {
      dummies(last: 2, before: "Mw==") {
        edges {
          node {
            name
            relatedDummies(last: 2, before: "Mg==") {
              edges {
                node {
                  name
                }
                cursor
              }
              pageInfo {
                startCursor
                hasPreviousPage
              }
            }
          }
          cursor
        }
        pageInfo {
          startCursor
          hasPreviousPage
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges[0].node.name" should be equal to "Dummy #2"
    And the JSON node "data.dummies.edges[0].cursor" should be equal to "MQ=="
    And the JSON node "data.dummies.edges[1].node.relatedDummies.edges[0].node.name" should be equal to "RelatedDummy13"
    And the JSON node "data.dummies.edges[1].node.relatedDummies.edges[0].cursor" should be equal to "MA=="
    When I send the following GraphQL request:
    """
    {
      dummies(last: 2, before: "MQ==") {
        edges {
          node {
            name
            relatedDummies(last: 3, before: "Mg==") {
              edges {
                node {
                  name
                }
                cursor
              }
              pageInfo {
                startCursor
                hasPreviousPage
              }
            }
          }
          cursor
        }
        pageInfo {
          startCursor
          hasPreviousPage
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges" should have 1 element
    And the JSON node "data.dummies.pageInfo.hasPreviousPage" should be false
    And the JSON node "data.dummies.pageInfo.startCursor" should be equal to "MA=="
    And the JSON node "data.dummies.edges[0].node.name" should be equal to "Dummy #1"
    And the JSON node "data.dummies.edges[0].cursor" should be equal to "MA=="
    And the JSON node "data.dummies.edges[0].node.relatedDummies.pageInfo.hasPreviousPage" should be false
    And the JSON node "data.dummies.edges[0].node.relatedDummies.pageInfo.startCursor" should be equal to "MA=="
    And the JSON node "data.dummies.edges[0].node.relatedDummies.edges" should have 2 elements
    And the JSON node "data.dummies.edges[0].node.relatedDummies.edges[1].node.name" should be equal to "RelatedDummy21"
    And the JSON node "data.dummies.edges[0].node.relatedDummies.edges[1].cursor" should be equal to "MQ=="
    When I send the following GraphQL request:
    """
    {
      dummies(last: 2, before: "MA==") {
        edges {
          node {
            name
            relatedDummies(last: 1, before: "MQ==") {
              edges {
                node {
                  name
                }
                cursor
              }
              pageInfo {
                startCursor
                hasPreviousPage
              }
            }
          }
          cursor
        }
        pageInfo {
          startCursor
          hasPreviousPage
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges" should have 0 element

  @createSchema
  Scenario: Retrieve a collection with pagination disabled
    Given there are 4 foo objects with fake names
    When I send the following GraphQL request:
    """
    {
      foos {
        id
        name
        bar
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.foos[3].id" should be equal to "/foos/4"
    And the JSON node "data.foos[3].name" should be equal to "Separativeness"
    And the JSON node "data.foos[3].bar" should be equal to "Sit"

  Scenario: Custom collection query
    Given there are 2 dummyCustomQuery objects
    When I send the following GraphQL request:
    """
    {
      testCollectionDummyCustomQueries {
        edges {
          node {
            message
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
        "testCollectionDummyCustomQueries": {
          "edges": [
            {
              "node": {"message": "Success!"}
            },
            {
              "node": {"message": "Success!"}
            }
          ]
        }
      }
    }
    """

  @createSchema
  Scenario: Custom collection query with read and serialize set to false
    Given there are 2 dummyCustomQuery objects
    When I send the following GraphQL request:
    """
    {
      testCollectionNoReadAndSerializeDummyCustomQueries {
        edges {
          node {
            message
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
        "testCollectionNoReadAndSerializeDummyCustomQueries": {
          "edges": []
        }
      }
    }
    """

  @createSchema
  Scenario: Custom collection query with custom arguments
    Given there are 2 dummyCustomQuery objects
    When I send the following GraphQL request:
    """
    {
      testCollectionCustomArgumentsDummyCustomQueries(customArgumentString: "A string") {
        edges {
          node {
            message
            customArgs
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
        "testCollectionCustomArgumentsDummyCustomQueries": {
          "edges": [
            {
              "node": {"message": "Success!", "customArgs": {"customArgumentString": "A string"}}
            },
            {
              "node": {"message": "Success!", "customArgs": {"customArgumentString": "A string"}}
            }
          ]
        }
      }
    }
    """

  @!mongodb
  @createSchema
  Scenario: Retrieve an item with composite primitive identifiers through a GraphQL query
    Given there are composite primitive identifiers objects
    When I send the following GraphQL request:
    """
    {
      compositePrimitiveItem(id: "/composite_primitive_items/name=Bar;year=2017") {
        description
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.compositePrimitiveItem.description" should be equal to "This is bar."

  @!mongodb
  @createSchema
  Scenario: Retrieve an item with composite identifiers through a GraphQL query
    Given there are Composite identifier objects
    When I send the following GraphQL request:
    """
    {
      compositeRelation(id: "/composite_relations/compositeItem=1;compositeLabel=1") {
        value
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.compositeRelation.value" should be equal to "somefoobardummy"

  @createSchema
  Scenario: Retrieve a collection using name converter
    Given there are 4 dummy objects
    When I send the following GraphQL request:
    """
    {
      dummies {
        edges {
          node {
            name_converted
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummies.edges[1].node.name_converted" should be equal to "Converted 2"

  @createSchema
  Scenario: Retrieve a collection with different serialization groups for item_query and collection_query
    Given there are 3 dummy with different GraphQL serialization groups objects
    When I send the following GraphQL request:
    """
    {
      dummyDifferentGraphQlSerializationGroups {
        edges {
          node {
            name
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "data.dummyDifferentGraphQlSerializationGroups.edges[0].node.name" should exist
    And the JSON node "data.dummyDifferentGraphQlSerializationGroups.edges[1].node.name" should exist
    And the JSON node "data.dummyDifferentGraphQlSerializationGroups.edges[2].node.name" should exist
    And the JSON node "data.dummyDifferentGraphQlSerializationGroups.edges[0].node.title" should not exist
    And the JSON node "data.dummyDifferentGraphQlSerializationGroups.edges[1].node.title" should not exist
    And the JSON node "data.dummyDifferentGraphQlSerializationGroups.edges[2].node.title" should not exist
