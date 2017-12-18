Feature: GraphQL collection support

  @createSchema
  @dropSchema
  Scenario: Retrieve a collection and an item through a GraphQL query
    Given there is 3 dummy objects with dummyDate
    And there is 2 dummy group objects
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
    And the JSON node "data.dummies.edges[1].node.dummyDate" should be equal to "2015-04-02T00:00:00+00:00"
    And the JSON node "data.dummyGroup.foo" should be equal to "Foo #2"

  @createSchema
  @dropSchema
  Scenario: Retrieve a specific number of items in a collection through a GraphQL query
    Given there is 4 dummy objects
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
  @dropSchema
  Scenario: Retrieve a specific number of items in a nested collection through a GraphQL query
    Given there is 2 dummy objects having each 5 relatedDummies
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
  @dropSchema
  Scenario: Paginate through collections through a GraphQL query
    Given there is 4 dummy objects having each 4 relatedDummies
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
    And the JSON node "data.dummies.pageInfo.endCursor" should be equal to "Mw=="
    And the JSON node "data.dummies.pageInfo.hasNextPage" should be true
    And the JSON node "data.dummies.edges[1].node.name" should be equal to "Dummy #2"
    And the JSON node "data.dummies.edges[1].cursor" should be equal to "MQ=="
    And the JSON node "data.dummies.edges[1].node.relatedDummies.pageInfo.endCursor" should be equal to "Mw=="
    And the JSON node "data.dummies.edges[1].node.relatedDummies.pageInfo.hasNextPage" should be true
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
    And the JSON node "data.dummies.edges[0].node.name" should be equal to "Dummy #4"
    And the JSON node "data.dummies.edges[0].cursor" should be equal to "Mw=="
    And the JSON node "data.dummies.edges[0].node.relatedDummies.pageInfo.hasNextPage" should be false
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
  @dropSchema
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

  @createSchema
  @dropSchema
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
