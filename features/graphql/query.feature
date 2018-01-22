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
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummy.id" should be equal to "/dummies/1"
    And the JSON node "data.dummy.name" should be equal to "Dummy #1"

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
    Given there are 2 dummy objects with JSON data
    When I send the following GraphQL request:
    """
    {
      dummy(id: "/dummies/3") {
        id
        name
        jsonData
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
    And I send the GraphQL request with operation "DummyWithId2"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummyItem.id" should be equal to "/dummies/2"
    And the JSON node "data.dummyItem.name" should be equal to "Dummy #2"
    And I send the GraphQL request with operation "DummyWithId1"
    And the JSON node "data.dummyItem.name" should be equal to "Dummy #1"

  @dropSchema
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
