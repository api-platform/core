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

  Scenario: Create an item with a subresource
    Given there are 1 dummy objects with relatedDummy
    When I send the following GraphQL request:
    """
    mutation {
      createDummy(input: {_id: 1, name: "A dummy", foo: [], relatedDummy: "/related_dummies/1", clientMutationId: "myId"}) {
        id
        name
        foo
        relatedDummy {
          name
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createDummy.id" should be equal to "/dummies/2"
    And the JSON node "data.createDummy.name" should be equal to "A dummy"
    And the JSON node "data.createDummy.foo" should have 0 elements
    And the JSON node "data.createDummy.relatedDummy.name" should be equal to "RelatedDummy #1"
    And the JSON node "data.createDummy.clientMutationId" should be equal to "myId"

  Scenario: Create an item with an iterable field
    When I send the following GraphQL request:
    """
    mutation {
      createDummy(input: {_id: 2, name: "A dummy", foo: [], jsonData: {bar:{baz:3,qux:[7.6,false,null]}}, clientMutationId: "myId"}) {
        id
        name
        foo
        jsonData
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createDummy.id" should be equal to "/dummies/3"
    And the JSON node "data.createDummy.name" should be equal to "A dummy"
    And the JSON node "data.createDummy.foo" should have 0 elements
    And the JSON node "data.createDummy.jsonData.bar.baz" should be equal to the number 3
    And the JSON node "data.createDummy.jsonData.bar.qux[0]" should be equal to the number 7.6
    And the JSON node "data.createDummy.jsonData.bar.qux[1]" should be false
    And the JSON node "data.createDummy.jsonData.bar.qux[2]" should be null
    And the JSON node "data.createDummy.clientMutationId" should be equal to "myId"

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

  @dropSchema
  Scenario: Trigger a validation error
    When I send the following GraphQL request:
    """
    mutation {
      createDummy(input: {_id: 12, name: "", foo: [], clientMutationId: "myId"}) {
        clientMutationId
      }
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to "name: This value should not be blank."
