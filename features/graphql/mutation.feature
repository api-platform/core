Feature: GraphQL mutation support

  @createSchema
  Scenario: Introspect types
    When I send the following GraphQL request:
    """
    {
      __type(name: "Mutation") {
        fields {
          name
          description
          type {
            name
            kind
          }
          args {
            name
            type {
              name
              kind
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.__type.fields[0].name" should contain "delete"
    And the JSON node "data.__type.fields[0].description" should contain "Deletes "
    And the JSON node "data.__type.fields[0].type.name" should contain "DeleteMutation"
    And the JSON node "data.__type.fields[0].type.kind" should be equal to "OBJECT"
    And the JSON node "data.__type.fields[0].args[0].name" should be equal to "input"
    And the JSON node "data.__type.fields[0].args[0].type.name" should contain "InputDeleteMutation"
    And the JSON node "data.__type.fields[0].args[0].type.kind" should be equal to "INPUT_OBJECT"
    And print last JSON response

  Scenario: Delete an item through a mutation
    Given there is 1 dummy objects
    When I send the following GraphQL request:
    """
    mutation {
      deleteDummy(input: {id: 1}) {
        id
      }
    }
    """
    Then print last response
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.deleteDummy.id" should be equal to 1

  @dropSchema
  Scenario: Delete an item with composite identifiers through a mutation
    Given there are Composite identifier objects
    When I send the following GraphQL request:
    """
    mutation {
      deleteCompositeRelation(input: {compositeItem: {id: 1}, compositeLabel: {id: 1}}) {
        compositeItem {
          id
        },
        compositeLabel {
          id
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.deleteCompositeRelation.compositeItem.id" should be equal to 1
    And the JSON node "data.deleteCompositeRelation.compositeLabel.id" should be equal to 1
