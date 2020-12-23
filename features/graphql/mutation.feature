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
              ofType {
                name
                kind
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
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "required": [
        "data"
      ],
      "properties": {
        "data": {
          "type": "object",
          "required": [
            "__type"
          ],
          "properties": {
            "__type": {
              "type": "object",
              "required": [
                "fields"
              ],
              "properties": {
                "fields": {
                  "type": "array",
                  "minItems": 1,
                  "items": {
                    "oneOf": [
                      {
                        "type": "object",
                        "required": [
                          "name",
                          "description",
                          "type",
                          "args"
                        ],
                        "properties": {
                          "name": {
                            "pattern": "^create[A-z0-9]+$"
                          },
                          "description": {
                            "pattern": "^Creates a [A-z0-9]+.$"
                          },
                          "type": {
                            "type": "object",
                            "required": [
                              "name",
                              "kind"
                            ],
                            "properties": {
                              "name": {
                                "pattern": "^create[A-z0-9]+Payload$"
                              },
                              "kind": {
                                "enum": ["OBJECT"]
                              }
                            }
                          },
                          "args": {
                            "type": "array",
                            "minItems": 1,
                            "maxItems": 1,
                            "items": [
                              {
                                "type": "object",
                                "required": [
                                  "name",
                                  "type"
                                ],
                                "properties": {
                                  "name": {
                                    "enum": ["input"]
                                  },
                                  "type": {
                                    "type": "object",
                                    "required": [
                                      "kind",
                                      "ofType"
                                    ],
                                    "properties": {
                                      "kind": {
                                        "enum": ["NON_NULL"]
                                      },
                                      "ofType": {
                                        "type": "object",
                                        "required": [
                                          "name",
                                          "kind"
                                        ],
                                        "properties": {
                                          "name": {
                                            "pattern": "^create[A-z0-9]+Input$"
                                          },
                                          "kind": {
                                            "enum": ["INPUT_OBJECT"]
                                          }
                                        }
                                      }
                                    }
                                  }
                                }
                              }
                            ]
                          }
                        }
                      },
                      {
                        "type": "object",
                        "required": [
                          "name",
                          "description",
                          "type",
                          "args"
                        ],
                        "properties": {
                          "name": {
                            "pattern": "^update[A-z0-9]+$"
                          },
                          "description": {
                            "pattern": "^Updates a [A-z0-9]+.$"
                          },
                          "type": {
                            "type": "object",
                            "required": [
                              "name",
                              "kind"
                            ],
                            "properties": {
                              "name": {
                                "pattern": "^update[A-z0-9]+Payload$"
                              },
                              "kind": {
                                "enum": ["OBJECT"]
                              }
                            }
                          },
                          "args": {
                            "type": "array",
                            "minItems": 1,
                            "maxItems": 1,
                            "items": [
                              {
                                "type": "object",
                                "required": [
                                  "name",
                                  "type"
                                ],
                                "properties": {
                                  "name": {
                                    "enum": ["input"]
                                  },
                                  "type": {
                                    "type": "object",
                                    "required": [
                                      "kind",
                                      "ofType"
                                    ],
                                    "properties": {
                                      "kind": {
                                        "enum": ["NON_NULL"]
                                      },
                                      "ofType": {
                                        "type": "object",
                                        "required": [
                                          "name",
                                          "kind"
                                        ],
                                        "properties": {
                                          "name": {
                                            "pattern": "^update[A-z0-9]+Input$"
                                          },
                                          "kind": {
                                            "enum": ["INPUT_OBJECT"]
                                          }
                                        }
                                      }
                                    }
                                  }
                                }
                              }
                            ]
                          }
                        }
                      },
                      {
                        "type": "object",
                        "required": [
                          "name",
                          "description",
                          "type",
                          "args"
                        ],
                        "properties": {
                          "name": {
                            "pattern": "^delete[A-z0-9]+$"
                          },
                          "description": {
                            "pattern": "^Deletes a [A-z0-9]+.$"
                          },
                          "type": {
                            "type": "object",
                            "required": [
                              "name",
                              "kind"
                            ],
                            "properties": {
                              "name": {
                                "pattern": "^delete[A-z0-9]+Payload$"
                              },
                              "kind": {
                                "enum": ["OBJECT"]
                              }
                            }
                          },
                          "args": {
                            "type": "array",
                            "minItems": 1,
                            "maxItems": 1,
                            "items": [
                              {
                                "type": "object",
                                "required": [
                                  "name",
                                  "type"
                                ],
                                "properties": {
                                  "name": {
                                    "enum": ["input"]
                                  },
                                  "type": {
                                    "type": "object",
                                    "required": [
                                      "kind",
                                      "ofType"
                                    ],
                                    "properties": {
                                      "kind": {
                                        "enum": ["NON_NULL"]
                                      },
                                      "ofType": {
                                        "type": "object",
                                        "required": [
                                          "name",
                                          "kind"
                                        ],
                                        "properties": {
                                          "name": {
                                            "pattern": "^delete[A-z0-9]+Input$"
                                          },
                                          "kind": {
                                            "enum": ["INPUT_OBJECT"]
                                          }
                                        }
                                      }
                                    }
                                  }
                                }
                              }
                            ]
                          }
                        }
                      },
                      {
                        "type": "object",
                        "required": [
                          "name",
                          "description",
                          "type",
                          "args"
                        ],
                        "properties": {
                          "name": {
                            "pattern": "^(?!create|update|delete)[A-z0-9]+$"
                          },
                          "description": {
                            "pattern": "^(?!Create|Update|Delete)[A-z0-9]+s a [A-z0-9]+.$"
                          },
                          "type": {
                            "type": "object",
                            "required": [
                              "name",
                              "kind"
                            ],
                            "properties": {
                              "name": {
                                "pattern": "^(?!create|update|delete)[A-z0-9]+Payload$"
                              },
                              "kind": {
                                "enum": ["OBJECT"]
                              }
                            }
                          },
                          "args": {
                            "type": "array",
                            "minItems": 1,
                            "maxItems": 1,
                            "items": [
                              {
                                "type": "object",
                                "required": [
                                  "name",
                                  "type"
                                ],
                                "properties": {
                                  "name": {
                                    "enum": ["input"]
                                  },
                                  "type": {
                                    "type": "object",
                                    "required": [
                                      "kind",
                                      "ofType"
                                    ],
                                    "properties": {
                                      "kind": {
                                        "enum": ["NON_NULL"]
                                      },
                                      "ofType": {
                                        "type": "object",
                                        "required": [
                                          "name",
                                          "kind"
                                        ],
                                        "properties": {
                                          "name": {
                                            "pattern": "^(?!create|update|delete)[A-z0-9]+Input$"
                                          },
                                          "kind": {
                                            "enum": ["INPUT_OBJECT"]
                                          }
                                        }
                                      }
                                    }
                                  }
                                }
                              }
                            ]
                          }
                        }
                      }
                    ]
                  }
                }
              }
            }
          }
        }
      }
    }
    """

  Scenario: Create an item
    When I send the following GraphQL request:
    """
    mutation {
      createFoo(input: {name: "A new one", bar: "new", clientMutationId: "myId"}) {
        foo {
          id
          _id
          __typename
          name
          bar
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createFoo.foo.id" should be equal to "/foos/1"
    And the JSON node "data.createFoo.foo._id" should be equal to 1
    And the JSON node "data.createFoo.foo.__typename" should be equal to "Foo"
    And the JSON node "data.createFoo.foo.name" should be equal to "A new one"
    And the JSON node "data.createFoo.foo.bar" should be equal to "new"
    And the JSON node "data.createFoo.clientMutationId" should be equal to "myId"

  Scenario: Create an item without a clientMutationId
    When I send the following GraphQL request:
    """
    mutation {
      createFoo(input: {name: "Created without mutation id", bar: "works"}) {
        foo {
          id
          name
          bar
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createFoo.foo.id" should be equal to "/foos/2"
    And the JSON node "data.createFoo.foo.name" should be equal to "Created without mutation id"
    And the JSON node "data.createFoo.foo.bar" should be equal to "works"

  Scenario: Create an item with a subresource
    Given there are 1 dummy objects with relatedDummy
    When I send the following GraphQL request:
    """
    mutation {
      createDummy(input: {name: "A dummy", foo: [], relatedDummy: "/related_dummies/1", name_converted: "Converted" clientMutationId: "myId"}) {
        dummy {
          id
          name
          foo
          relatedDummy {
            name
            __typename
          }
          name_converted
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createDummy.dummy.id" should be equal to "/dummies/2"
    And the JSON node "data.createDummy.dummy.name" should be equal to "A dummy"
    And the JSON node "data.createDummy.dummy.foo" should have 0 elements
    And the JSON node "data.createDummy.dummy.relatedDummy.name" should be equal to "RelatedDummy #1"
    And the JSON node "data.createDummy.dummy.relatedDummy.__typename" should be equal to "RelatedDummy"
    And the JSON node "data.createDummy.dummy.name_converted" should be equal to "Converted"
    And the JSON node "data.createDummy.clientMutationId" should be equal to "myId"

  Scenario: Create an item with an iterable field
    When I send the following GraphQL request:
    """
    mutation {
      createDummy(input: {name: "A dummy", foo: [], jsonData: {bar:{baz:3,qux:[7.6,false,null]}}, arrayData: ["bar", "baz"], clientMutationId: "myId"}) {
        dummy {
          id
          name
          foo
          jsonData
          arrayData
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createDummy.dummy.id" should be equal to "/dummies/3"
    And the JSON node "data.createDummy.dummy.name" should be equal to "A dummy"
    And the JSON node "data.createDummy.dummy.foo" should have 0 elements
    And the JSON node "data.createDummy.dummy.jsonData.bar.baz" should be equal to the number 3
    And the JSON node "data.createDummy.dummy.jsonData.bar.qux[0]" should be equal to the number 7.6
    And the JSON node "data.createDummy.dummy.jsonData.bar.qux[1]" should be false
    And the JSON node "data.createDummy.dummy.jsonData.bar.qux[2]" should be null
    And the JSON node "data.createDummy.dummy.arrayData[1]" should be equal to baz
    And the JSON node "data.createDummy.clientMutationId" should be equal to "myId"

  Scenario: Delete an item through a mutation
    When I send the following GraphQL request:
    """
    mutation {
      deleteFoo(input: {id: "/foos/1", clientMutationId: "anotherId"}) {
        foo {
          id
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.deleteFoo.foo.id" should be equal to "/foos/1"
    And the JSON node "data.deleteFoo.clientMutationId" should be equal to "anotherId"

  Scenario: Trigger an error trying to delete item of different resource
    When I send the following GraphQL request:
    """
    mutation {
      deleteFoo(input: {id: "/dummies/1", clientMutationId: "myId"}) {
        foo {
          id
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to 'Item "/dummies/1" did not match expected type "Foo".'

  @!mongodb
  Scenario: Delete an item with composite identifiers through a mutation
    Given there are Composite identifier objects
    When I send the following GraphQL request:
    """
    mutation {
      deleteCompositeRelation(input: {id: "/composite_relations/compositeItem=1;compositeLabel=1", clientMutationId: "myId"}) {
        compositeRelation {
          id
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.deleteCompositeRelation.compositeRelation.id" should be equal to "/composite_relations/compositeItem=1;compositeLabel=1"
    And the JSON node "data.deleteCompositeRelation.clientMutationId" should be equal to "myId"

  @createSchema
  Scenario: Modify an item through a mutation
    Given there are 1 dummy objects having each 2 relatedDummies
    When I send the following GraphQL request:
    """
    mutation {
      updateDummy(input: {id: "/dummies/1", description: "Modified description.", dummyDate: "2018-06-05T00:00:00+00:00", clientMutationId: "myId"}) {
        dummy {
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
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateDummy.dummy.id" should be equal to "/dummies/1"
    And the JSON node "data.updateDummy.dummy.name" should be equal to "Dummy #1"
    And the JSON node "data.updateDummy.dummy.description" should be equal to "Modified description."
    And the JSON node "data.updateDummy.dummy.dummyDate" should be equal to "2018-06-05"
    And the JSON node "data.updateDummy.dummy.relatedDummies.edges[0].node.name" should be equal to "RelatedDummy11"
    And the JSON node "data.updateDummy.clientMutationId" should be equal to "myId"

  @!mongodb
  Scenario: Modify an item with composite identifiers through a mutation
    Given there are Composite identifier objects
    When I send the following GraphQL request:
    """
    mutation {
      updateCompositeRelation(input: {id: "/composite_relations/compositeItem=1;compositeLabel=2", value: "Modified value.", clientMutationId: "myId"}) {
        compositeRelation {
          id
          value
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateCompositeRelation.compositeRelation.id" should be equal to "/composite_relations/compositeItem=1;compositeLabel=2"
    And the JSON node "data.updateCompositeRelation.compositeRelation.value" should be equal to "Modified value."
    And the JSON node "data.updateCompositeRelation.clientMutationId" should be equal to "myId"

  Scenario: Create an item with a custom UUID
    When I send the following GraphQL request:
    """
    mutation {
      createWritableId(input: {_id: "c6b722fe-0331-48c4-a214-f81f9f1ca082", name: "Foo", clientMutationId: "m"}) {
        writableId {
          id
          _id
          name
        }
        clientMutationId
      }
    }
    """
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createWritableId.writableId.id" should be equal to "/writable_ids/c6b722fe-0331-48c4-a214-f81f9f1ca082"
    And the JSON node "data.createWritableId.writableId._id" should be equal to "c6b722fe-0331-48c4-a214-f81f9f1ca082"
    And the JSON node "data.createWritableId.writableId.name" should be equal to "Foo"
    And the JSON node "data.createWritableId.clientMutationId" should be equal to "m"

  @!mongodb
  Scenario: Update an item with a custom UUID
    When I send the following GraphQL request:
    """
    mutation {
      updateWritableId(input: {id: "/writable_ids/c6b722fe-0331-48c4-a214-f81f9f1ca082", _id: "f8a708b2-310f-416c-9aef-b1b5719dfa47", name: "Foo", clientMutationId: "m"}) {
        writableId {
          id
          _id
          name
        }
        clientMutationId
      }
    }
    """
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateWritableId.writableId.id" should be equal to "/writable_ids/f8a708b2-310f-416c-9aef-b1b5719dfa47"
    And the JSON node "data.updateWritableId.writableId._id" should be equal to "f8a708b2-310f-416c-9aef-b1b5719dfa47"
    And the JSON node "data.updateWritableId.writableId.name" should be equal to "Foo"
    And the JSON node "data.updateWritableId.clientMutationId" should be equal to "m"

  Scenario: Use serialization groups
    Given there are 1 dummy group objects
    When I send the following GraphQL request:
    """
    mutation {
      createDummyGroup(input: {bar: "Bar", baz: "Baz", clientMutationId: "myId"}) {
        dummyGroup {
          id
          bar
          __typename
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createDummyGroup.dummyGroup.id" should be equal to "/dummy_groups/2"
    And the JSON node "data.createDummyGroup.dummyGroup.bar" should be equal to "Bar"
    And the JSON node "data.createDummyGroup.dummyGroup.__typename" should be equal to "createDummyGroupPayloadData"
    And the JSON node "data.createDummyGroup.clientMutationId" should be equal to "myId"

  Scenario: Trigger a validation error
    When I send the following GraphQL request:
    """
    mutation {
      createDummy(input: {name: "", foo: [], clientMutationId: "myId"}) {
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].extensions.status" should be equal to "422"
    And the JSON node "errors[0].message" should be equal to "name: This value should not be blank."
    And the JSON node "errors[0].extensions.violations" should exist
    And the JSON node "errors[0].extensions.violations[0].path" should be equal to "name"
    And the JSON node "errors[0].extensions.violations[0].message" should be equal to "This value should not be blank."

  Scenario: Execute a custom mutation
    Given there are 1 dummyCustomMutation objects
    When I send the following GraphQL request:
    """
    mutation {
      sumDummyCustomMutation(input: {id: "/dummy_custom_mutations/1", operandB: 5}) {
        dummyCustomMutation {
          id
          result
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.sumDummyCustomMutation.dummyCustomMutation.result" should be equal to "8"

  Scenario: Execute a not persisted custom mutation (resolver returns null)
    When I send the following GraphQL request:
    """
    mutation {
      sumNotPersistedDummyCustomMutation(input: {id: "/dummy_custom_mutations/1", operandB: 5}) {
        dummyCustomMutation {
          id
          result
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.sumNotPersistedDummyCustomMutation.dummyCustomMutation" should be null

  Scenario: Execute a not persisted custom mutation (write set to false) with custom result
    When I send the following GraphQL request:
    """
    mutation {
      sumNoWriteCustomResultDummyCustomMutation(input: {id: "/dummy_custom_mutations/1", operandB: 5}) {
        dummyCustomMutation {
          id
          result
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.sumNoWriteCustomResultDummyCustomMutation.dummyCustomMutation.result" should be equal to "1234"

  Scenario: Execute a custom mutation with read, deserialize, validate and serialize set to false
    When I send the following GraphQL request:
    """
    mutation {
      sumOnlyPersistDummyCustomMutation(input: {id: "/dummy_custom_mutations/1", operandB: 5}) {
        dummyCustomMutation {
          id
          result
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.sumOnlyPersistDummyCustomMutation.dummyCustomMutation" should be null

  Scenario: Execute a custom mutation with custom arguments
    When I send the following GraphQL request:
    """
    mutation {
      testCustomArgumentsDummyCustomMutation(input: {operandC: 18, clientMutationId: "myId"}) {
        dummyCustomMutation {
          result
        }
        clientMutationId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.testCustomArgumentsDummyCustomMutation.dummyCustomMutation.result" should be equal to "18"
    And the JSON node "data.testCustomArgumentsDummyCustomMutation.clientMutationId" should be equal to "myId"

  Scenario: Uploading a file with a custom mutation
    Given I have the following file for a GraphQL request:
      | name | file     |
      | file | test.gif |
    And I have the following GraphQL multipart request map:
    """
    {
      "file": ["variables.file"]
    }
    """
    When I send the following GraphQL multipart request operations:
    """
      {
        "query": "mutation($file: Upload!) { uploadMediaObject(input: {file: $file}) { mediaObject { id contentUrl } } }",
        "variables": {
          "file": null
        }
      }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "data.uploadMediaObject.mediaObject.contentUrl" should be equal to "test.gif"

  Scenario: Uploading multiple files with a custom mutation
    Given I have the following files for a GraphQL request:
      | name | file     |
      | 0    | test.gif |
      | 1    | test.gif |
      | 2    | test.gif |
    And I have the following GraphQL multipart request map:
    """
    {
      "0": ["variables.files.0"],
      "1": ["variables.files.1"],
      "2": ["variables.files.2"]
    }
    """
    When I send the following GraphQL multipart request operations:
    """
      {
        "query": "mutation($files: [Upload!]!) { uploadMultipleMediaObject(input: {files: $files}) { mediaObject { id contentUrl } } }",
        "variables": {
          "files": [
            null,
            null,
            null
          ]
        }
      }
    """
    Then the response status code should be 200
    And the JSON node "data.uploadMultipleMediaObject.mediaObject.contentUrl" should be equal to "test.gif"
