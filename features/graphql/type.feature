Feature: GraphQL type support
  @createSchema
  Scenario: Use a custom type for a field
    Given there are 2 dummy objects with dummyDate
    When I send the following GraphQL request:
    """
    {
      dummy(id: "/dummies/1") {
        dummyDate
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummy.dummyDate" should be equal to "2015-04-01"

  Scenario: Use a custom type for an input field
    When I send the following GraphQL request:
    """
    mutation {
      updateDummy(input: {id: "/dummies/1", dummyDate: "2019-05-24T00:00:00+00:00"}) {
        dummy {
          dummyDate
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateDummy.dummy.dummyDate" should be equal to "2019-05-24"

  Scenario: Use a custom type for a query variable
    When I have the following GraphQL request:
    """
    mutation UpdateDummyDate($itemId: ID!, $itemDate: DateTime!) {
      updateDummy(input: {id: $itemId, dummyDate: $itemDate}) {
        dummy {
          dummyDate
        }
      }
    }
    """
    And I send the GraphQL request with variables:
    """
    {
      "itemId": "/dummies/1",
      "itemDate": "2017-11-14T00:00:00+00:00"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateDummy.dummy.dummyDate" should be equal to "2017-11-14"

  Scenario: Use a custom type for a query variable and use a bad value
    When I have the following GraphQL request:
    """
    mutation UpdateDummyDate($itemId: ID!, $itemDate: DateTime!) {
      updateDummy(input: {id: $itemId, dummyDate: $itemDate}) {
        dummy {
          dummyDate
        }
      }
    }
    """
    And I send the GraphQL request with variables:
    """
    {
      "itemId": "/dummies/1",
      "itemDate": "bad date"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to 'Variable "$itemDate" got invalid value "bad date"; Expected type DateTime; DateTime cannot represent non date value: "bad date"'
