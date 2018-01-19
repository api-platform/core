Feature: Collections filtering
  In order to retrieve subsets of collections
  As an API consumer
  I need to be able to set filters

  @createSchema
  @dropSchema
  Scenario: Retrieve a collection filtered using the boolean filter
    Given there is 1 dummy object with dummyBoolean true
    And there is 1 dummy object with dummyBoolean false
    When I send the following GraphQL request:
    """
    {
      dummies(dummyBoolean: false) {
        edges {
          node {
            id
            dummyBoolean
          }
        }
      }
    }
    """
    Then the JSON node "data.dummies.edges" should have 1 element
    And the JSON node "data.dummies.edges[0].node.dummyBoolean" should be false

  @createSchema
  @dropSchema
  Scenario: Retrieve a collection filtered using the date filter
    Given there are 3 dummy objects with dummyDate
    When I send the following GraphQL request:
    """
    {
      dummies(dummyDate: {after: "2015-04-02"}) {
        edges {
          node {
            id
            dummyDate
          }
        }
      }
    }
    """
    Then the JSON node "data.dummies.edges" should have 1 element
    And the JSON node "data.dummies.edges[0].node.dummyDate" should be equal to "2015-04-02T00:00:00+00:00"

  @createSchema
  @dropSchema
  Scenario: Retrieve a collection filtered using the search filter
    Given there are 10 dummy objects
    When I send the following GraphQL request:
    """
    {
      dummies(name: "#2") {
        edges {
          node {
            id
            name
          }
        }
      }
    }
    """
    Then the JSON node "data.dummies.edges" should have 1 element
    And the JSON node "data.dummies.edges[0].node.id" should be equal to "/dummies/2"

  @createSchema
  @dropSchema
  Scenario: Retrieve a collection filtered using the search filter
    Given there are 3 dummy objects having each 3 relatedDummies
    When I send the following GraphQL request:
    """
    {
      dummies {
        edges {
          node {
            id
            relatedDummies(name: "RelatedDummy13") {
              edges {
                node {
                  id
                  name
                }
              }
            }
          }
        }
      }
    }
    """
    And the JSON node "data.dummies.edges[0].node.relatedDummies.edges" should have 0 elements
    And the JSON node "data.dummies.edges[1].node.relatedDummies.edges" should have 0 elements
    And the JSON node "data.dummies.edges[2].node.relatedDummies.edges" should have 1 element
    And the JSON node "data.dummies.edges[2].node.relatedDummies.edges[0].node.name" should be equal to "RelatedDummy13"
