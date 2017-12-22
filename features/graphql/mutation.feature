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
    And the JSON node "data.__type.fields[0].description" should match '/^Deletes a [A-z]+\.$/'
    And the JSON node "data.__type.fields[0].type.name" should match "/^delete[A-z]+Payload$/"
    And the JSON node "data.__type.fields[0].type.kind" should be equal to "OBJECT"
    And the JSON node "data.__type.fields[0].args[0].name" should be equal to "input"
    And the JSON node "data.__type.fields[0].args[0].type.name" should match "/^delete[A-z]+Input$/"
    And the JSON node "data.__type.fields[0].args[0].type.kind" should be equal to "INPUT_OBJECT"

  Scenario: Create an item
    When I send the following GraphQL request:
    """
    mutation {
      createFoo(input: {name: "A new one", bar: "new", clientMutationId: "myId"}) {
        id
        name
        bar
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createFoo.id" should be equal to "/foos/1"
    And the JSON node "data.createFoo.name" should be equal to "A new one"
    And the JSON node "data.createFoo.bar" should be equal to "new"
    And the JSON node "data.createFoo.clientMutationId" should be equal to "myId"

  @dropSchema
  Scenario: Delete an item through a mutation
    When I send the following GraphQL request:
    """
    mutation {
      deleteFoo(input: {id: "/foos/1", clientMutationId: "anotherId"}) {
        id
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.deleteFoo.id" should be equal to "/foos/1"
    And the JSON node "data.deleteFoo.clientMutationId" should be equal to "anotherId"

  @createSchema
  @dropSchema
  Scenario: Delete an item with composite identifiers through a mutation
    Given there are Composite identifier objects
    When I send the following GraphQL request:
    """
    mutation {
      deleteCompositeRelation(input: {id: "/composite_relations/compositeItem=1;compositeLabel=1", clientMutationId: "myId"}) {
        id
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.deleteCompositeRelation.id" should be equal to "/composite_relations/compositeItem=1;compositeLabel=1"
    And the JSON node "data.deleteCompositeRelation.clientMutationId" should be equal to "myId"

  @createSchema
  @dropSchema
  Scenario: Modify an item through a mutation
    Given there are 1 foo objects with fake names
    When I send the following GraphQL request:
    """
    mutation {
      updateFoo(input: {id: "/foos/1", bar: "Modified description.", clientMutationId: "myId"}) {
        id
        name
        bar
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateFoo.id" should be equal to "/foos/1"
    And the JSON node "data.updateFoo.name" should be equal to "Hawsepipe"
    And the JSON node "data.updateFoo.bar" should be equal to "Modified description."
    And the JSON node "data.updateFoo.clientMutationId" should be equal to "myId"

  @createSchema
  @dropSchema
  Scenario: Modify an item with composite identifiers through a mutation
    Given there are Composite identifier objects
    When I send the following GraphQL request:
    """
    mutation {
      updateCompositeRelation(input: {id: "/composite_relations/compositeItem=1;compositeLabel=2", value: "Modified value.", clientMutationId: "myId"}) {
        id
        value
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateCompositeRelation.id" should be equal to "/composite_relations/compositeItem=1;compositeLabel=2"
    And the JSON node "data.updateCompositeRelation.value" should be equal to "Modified value."
    And the JSON node "data.updateCompositeRelation.clientMutationId" should be equal to "myId"
