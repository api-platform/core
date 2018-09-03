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
    And the JSON node "data.__type.fields[0].description" should match '/^Deletes a [A-z0-9]+\.$/'
    And the JSON node "data.__type.fields[0].type.name" should match "/^delete[A-z0-9]+Payload$/"
    And the JSON node "data.__type.fields[0].type.kind" should be equal to "OBJECT"
    And the JSON node "data.__type.fields[0].args[0].name" should be equal to "input"
    And the JSON node "data.__type.fields[0].args[0].type.name" should match "/^delete[A-z0-9]+Input$/"
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
      createDummy(input: {_id: 2, name: "A dummy", foo: [], jsonData: {bar:{baz:3,qux:[7.6,false,null]}}, arrayData: ["bar", "baz"], clientMutationId: "myId"}) {
        id
        name
        foo
        jsonData
        arrayData
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
    And the JSON node "data.createDummy.arrayData[1]" should be equal to baz
    And the JSON node "data.createDummy.clientMutationId" should be equal to "myId"

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
  Scenario: Modify an item through a mutation
    Given there are 1 dummy objects having each 2 relatedDummies
    When I send the following GraphQL request:
    """
    mutation {
      updateDummy(input: {id: "/dummies/1", description: "Modified description.", dummyDate: "2018-06-05", clientMutationId: "myId"}) {
        id
        name
        description
        dummyDate
        relatedDummies {
          edges {
            node {
              name
            }
          }
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateDummy.id" should be equal to "/dummies/1"
    And the JSON node "data.updateDummy.name" should be equal to "Dummy #1"
    And the JSON node "data.updateDummy.description" should be equal to "Modified description."
    And the JSON node "data.updateDummy.dummyDate" should be equal to "2018-06-05T00:00:00+00:00"
    And the JSON node "data.updateDummy.relatedDummies.edges[0].node.name" should be equal to "RelatedDummy11"
    And the JSON node "data.updateDummy.clientMutationId" should be equal to "myId"

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

  Scenario: Create an item with a custom UUID
    When I send the following GraphQL request:
    """
    mutation {
      createWritableId(input: {_id: "c6b722fe-0331-48c4-a214-f81f9f1ca082", name: "Foo", clientMutationId: "m"}) {
        id
        _id
        name
        clientMutationId
      }
    }
    """
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createWritableId.id" should be equal to "/writable_ids/c6b722fe-0331-48c4-a214-f81f9f1ca082"
    And the JSON node "data.createWritableId._id" should be equal to "c6b722fe-0331-48c4-a214-f81f9f1ca082"
    And the JSON node "data.createWritableId.name" should be equal to "Foo"
    And the JSON node "data.createWritableId.clientMutationId" should be equal to "m"

  Scenario: Update an item with a custom UUID
    When I send the following GraphQL request:
    """
    mutation {
      updateWritableId(input: {id: "/writable_ids/c6b722fe-0331-48c4-a214-f81f9f1ca082", _id: "f8a708b2-310f-416c-9aef-b1b5719dfa47", name: "Foo", clientMutationId: "m"}) {
        id
        _id
        name
        clientMutationId
      }
    }
    """
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateWritableId.id" should be equal to "/writable_ids/f8a708b2-310f-416c-9aef-b1b5719dfa47"
    And the JSON node "data.updateWritableId._id" should be equal to "f8a708b2-310f-416c-9aef-b1b5719dfa47"
    And the JSON node "data.updateWritableId.name" should be equal to "Foo"
    And the JSON node "data.updateWritableId.clientMutationId" should be equal to "m"

  Scenario: Use serialization groups
    Given there are 1 dummy group objects
    When I send the following GraphQL request:
    """
    mutation {
      createDummyGroup(input: {bar: "Bar", baz: "Baz", clientMutationId: "myId"}) {
        id
        bar
        baz
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createDummyGroup.id" should be equal to "/dummy_groups/2"
    And the JSON node "data.createDummyGroup.bar" should be equal to "Bar"
    And the JSON node "data.createDummyGroup.baz" should be null
    And the JSON node "data.createDummyGroup.clientMutationId" should be equal to "myId"

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
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to "name: This value should not be blank."
