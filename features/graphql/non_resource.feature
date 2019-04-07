# TODO: FIXME: GraphQL support for non-resources is non-existent

Feature: GraphQL non-resource handling
  In order to use non-resource types
  As a developer
  I should be able to serialize types not mapped to an API resource.

  Scenario: Get a resource containing a raw object
    Then it is not supported
#     When I send the following GraphQL request:
#     """
#     {
#       containNonResource(id: "/contain_non_resources/1") {
#         _id
#         id
#         nested {
#           _id
#           id
#           notAResource {
#             foo
#             bar
#           }
#         }
#         notAResource {
#           foo
#           bar
#         }
#       }
#     }
#     """
#     Then the response status code should be 200
#     And the response should be in JSON
#     And the header "Content-Type" should be equal to "application/json"
#     And the JSON should be equal to:
#     """
#     {
#       "data": {
#         "containNonResource": {
#           "_id": 1,
#           "id": "/contain_non_resources/1",
#           "nested": {
#               "_id": "1-nested",
#               "id": "/contain_non_resources/1-nested",
#               "notAResource": {
#                   "foo": "f2",
#                   "bar": "b2"
#               }
#           },
#           "notAResource": {
#               "foo": "f1",
#               "bar": "b1"
#           }
#         }
#       }
#     }
#     """

  @!mongodb
  @createSchema
  Scenario: Create a resource that has a non-resource relation.
    Then it is not supported
#     When I send the following GraphQL request:
#     """
#     mutation {
#       createNonRelationResource(input: {relation: {foo: "test"}}) {
#         nonRelationResource {
#           _id
#           id
#           relation {
#             foo
#           }
#         }
#       }
#     }
#     """
#     Then the response status code should be 200
#     And the response should be in JSON
#     And the header "Content-Type" should be equal to "application/json"
#     And the JSON should be equal to:
#     """
#     {
#       "data": {
#         "nonRelationResource": {
#           "_id": 1,
#           "id": "/non_relation_resources/1",
#           "relation": {
#             "foo": "test"
#           }
#         }
#       }
#     }
#     """
