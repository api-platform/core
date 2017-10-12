Feature: GraphQL query support

  @createSchema
  @dropSchema
  Scenario: Retrieve an item through a GraphQL query with variables
    Given there is 2 dummy objects with relatedDummy
    When I have the following GraphQL request:
    """
    query DummyWithId($itemId: Int = 1) {
      dummyItem: dummy(id: $itemId) {
        name
        relatedDummy {
          name
        }
      }
    }
    """
    When I send the GraphQL request with variables:
    """
    {
      "itemId": 2
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummyItem.name" should be equal to "Dummy #2"
    And the JSON node "data.dummyItem.relatedDummy.name" should be equal to "RelatedDummy #2"

  @createSchema
  @dropSchema
  Scenario: Run a specific operation through a GraphQL query
    Given there is 2 dummy objects
    When I have the following GraphQL request:
    """
    query DummyWithId1 {
      dummyItem: dummy(id: 1) {
        name
      }
    }
    query DummyWithId2 {
      dummyItem: dummy(id: 2) {
        name
      }
    }
    """
    When I send the GraphQL request with operation "DummyWithId2"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummyItem.name" should be equal to "Dummy #2"
    When I send the GraphQL request with operation "DummyWithId1"
    Then the JSON node "data.dummyItem.name" should be equal to "Dummy #1"

  @createSchema
  @dropSchema
  Scenario: Retrieve an nonexistent item through a GraphQL query
    Given there is 1 dummy objects
    When I send the following GraphQL request:
    """
    {
      dummy(id: 2) {
        name
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummy" should be null

  @createSchema
  @dropSchema
  Scenario: Retrieve a collection through a GraphQL query
    Given there is 4 dummy objects with relatedDummy and its thirdLevel
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
          name
          relatedDummy {
            name
            thirdLevel {
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
    And the JSON node "data.dummies.edges[2].node.relatedDummy.thirdLevel.level" should be equal to "3"

  @createSchema
  @dropSchema
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
    And the JSON node "data.dummies.pageInfo.endCursor" should be null
    And the JSON node "data.dummies.pageInfo.hasNextPage" should be false

  @createSchema
  @dropSchema
  Scenario: Retrieve a collection with a nested collection through a GraphQL query
    Given there is 4 dummy objects having each 3 relatedDummies
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
