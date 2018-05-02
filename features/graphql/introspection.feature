Feature: GraphQL introspection support

  @createSchema
  Scenario: Execute an empty GraphQL query
    When I send a "GET" request to "/graphql"
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to "GraphQL query is not valid"

  Scenario: Introspect the GraphQL schema
    When I send the following GraphQL request:
    """
    {
      __schema {
        types {
          name
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.__schema.types" should exist

  Scenario: Introspect types
    When I send the following GraphQL request:
    """
    {
      type1: __type(name: "DummyProduct") {
        description,
        fields {
          name
          type {
            name
            kind
            ofType {
              name
              kind
            }
          }
        }
      }
      type2: __type(name: "DummyAggregateOfferConnection") {
        description,
        fields {
          name
          type {
            name
            kind
            ofType {
              name
              kind
            }
          }
        }
      }
      type3: __type(name: "DummyAggregateOfferEdge") {
        description,
        fields {
          name
          type {
            name
            kind
            ofType {
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
    And the JSON node "data.type1.description" should be equal to "Dummy Product."
    And the JSON node "data.type1.fields[1].type.name" should be equal to "DummyAggregateOfferConnection"
    And the JSON node "data.type2.fields[0].name" should be equal to "edges"
    And the JSON node "data.type2.fields[0].type.ofType.name" should be equal to "DummyAggregateOfferEdge"
    And the JSON node "data.type3.fields[0].name" should be equal to "node"
    And the JSON node "data.type3.fields[1].name" should be equal to "cursor"
    And the JSON node "data.type3.fields[0].type.name" should be equal to "DummyAggregateOffer"

  Scenario: Retrieve the Relay's node interface
    When I send the following GraphQL request:
    """
    {
      __type(name: "Node") {
        name
        kind
        fields {
          name
          type {
            kind
            ofType {
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
    And the JSON should be deep equal to:
    """
    {
      "data": {
        "__type": {
          "name": "Node",
          "kind": "INTERFACE",
          "fields": [
            {
              "name": "id",
              "type": {
                "kind": "NON_NULL",
                "ofType": {
                  "name": "ID",
                  "kind": "SCALAR"
                }
              }
            }
          ]
        }
      }
    }
    """

  Scenario: Retrieve the Relay's node field
    When I send the following GraphQL request:
    """
    {
      __schema {
        queryType {
          fields {
            name
            type {
              name
              kind
            }
            args {
              name
              type {
                kind
                ofType {
                  name
                  kind
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
    And the JSON node "data.__schema.queryType.fields[0].name" should be equal to "node"
    And the JSON node "data.__schema.queryType.fields[0].type.name" should be equal to "Node"
    And the JSON node "data.__schema.queryType.fields[0].type.kind" should be equal to "INTERFACE"
    And the JSON node "data.__schema.queryType.fields[0].args[0].name" should be equal to "id"
    And the JSON node "data.__schema.queryType.fields[0].args[0].type.kind" should be equal to "NON_NULL"
    And the JSON node "data.__schema.queryType.fields[0].args[0].type.ofType.name" should be equal to "ID"
    And the JSON node "data.__schema.queryType.fields[0].args[0].type.ofType.kind" should be equal to "SCALAR"

  Scenario: GraphiQL can print the documentation
    When I post the following GraphQL request:
    """
    {"query":"\n  query IntrospectionQuery {\n    __schema {\n      queryType { name }\n      mutationType { name }\n      subscriptionType { name }\n      types {\n        ...FullType\n      }\n      directives {\n        name\n        description\n        locations\n        args {\n          ...InputValue\n        }\n      }\n    }\n  }\n\n  fragment FullType on __Type {\n    kind\n    name\n    description\n    fields(includeDeprecated: true) {\n      name\n      description\n      args {\n        ...InputValue\n      }\n      type {\n        ...TypeRef\n      }\n      isDeprecated\n      deprecationReason\n    }\n    inputFields {\n      ...InputValue\n    }\n    interfaces {\n      ...TypeRef\n    }\n    enumValues(includeDeprecated: true) {\n      name\n      description\n      isDeprecated\n      deprecationReason\n    }\n    possibleTypes {\n      ...TypeRef\n    }\n  }\n\n  fragment InputValue on __InputValue {\n    name\n    description\n    type { ...TypeRef }\n    defaultValue\n  }\n\n  fragment TypeRef on __Type {\n    kind\n    name\n    ofType {\n      kind\n      name\n      ofType {\n        kind\n        name\n        ofType {\n          kind\n          name\n          ofType {\n            kind\n            name\n            ofType {\n              kind\n              name\n              ofType {\n                kind\n                name\n                ofType {\n                  kind\n                  name\n                }\n              }\n            }\n          }\n        }\n      }\n    }\n  }\n"}
    """
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.__schema.queryType.name" should be equal to "Query"
    And the JSON node "data.__schema.mutationType.name" should be equal to "Mutation"
    And the JSON node "data.__schema.types[0].kind" should be equal to "OBJECT"

  Scenario: Introspect an Iterable type field
    When I send the following GraphQL request:
    """
    {
      __type(name: "Dummy") {
        description,
        fields {
          name
          type {
            name
            kind
            ofType {
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
    And the JSON node "data.__type.fields[9].name" should be equal to "jsonData"
    And the JSON node "data.__type.fields[9].type.name" should be equal to "Iterable"

  Scenario: Retrieve an item through a GraphQL query
    Given there are 4 dummy objects with relatedDummy
    When I send the following GraphQL request:
    """
    {
      dummyItem: dummy(id: "/dummies/3") {
        name
        relatedDummy {
          id
          name
          __typename
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.dummyItem.name" should be equal to "Dummy #3"
    And the JSON node "data.dummyItem.relatedDummy.name" should be equal to "RelatedDummy #3"
    And the JSON node "data.dummyItem.relatedDummy.__typename" should be equal to "RelatedDummy"
