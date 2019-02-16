Feature: Documentation support
  In order to build an auto-discoverable API
  As a client software developer
  I need to know OpenAPI specifications of objects I send and receive

  @createSchema
  Scenario: Retrieve the OpenAPI documentation
    Given I send a "GET" request to "/docs.json?spec_version=3"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    # Context
    And the JSON node "openapi" should be equal to "3.0.2"
    # Root properties
    And the JSON node "info.title" should be equal to "My Dummy API"
    And the JSON node "info.description" should contain "This is a test API."
    And the JSON node "info.description" should contain "Made with love"
    # Supported classes
    And the OpenAPI class "AbstractDummy" exists
    And the OpenAPI class "CircularReference" exists
    And the OpenAPI class "CircularReference-circular" exists
    And the OpenAPI class "CompositeItem" exists
    And the OpenAPI class "CompositeLabel" exists
    And the OpenAPI class "ConcreteDummy" exists
    And the OpenAPI class "CustomIdentifierDummy" exists
    And the OpenAPI class "CustomNormalizedDummy-input" exists
    And the OpenAPI class "CustomNormalizedDummy-output" exists
    And the OpenAPI class "CustomWritableIdentifierDummy" exists
    And the OpenAPI class "Dummy" exists
    And the OpenAPI class "RelatedDummy" exists
    And the OpenAPI class "DummyTableInheritance" exists
    And the OpenAPI class "DummyTableInheritanceChild" exists
    And the OpenAPI class "OverriddenOperationDummy-overridden_operation_dummy_get" exists
    And the OpenAPI class "OverriddenOperationDummy-overridden_operation_dummy_put" exists
    And the OpenAPI class "OverriddenOperationDummy-overridden_operation_dummy_read" exists
    And the OpenAPI class "OverriddenOperationDummy-overridden_operation_dummy_write" exists
    And the OpenAPI class "RelatedDummy" exists
    And the OpenAPI class "NoCollectionDummy" exists
    And the OpenAPI class "RelatedToDummyFriend" exists
    And the OpenAPI class "RelatedToDummyFriend-fakemanytomany" exists
    And the OpenAPI class "DummyFriend" exists
    And the OpenAPI class "RelationEmbedder-barcelona" exists
    And the OpenAPI class "RelationEmbedder-chicago" exists
    And the OpenAPI class "User-user_user-read" exists
    And the OpenAPI class "User-user_user-write" exists
    And the OpenAPI class "UuidIdentifierDummy" exists
    And the OpenAPI class "ThirdLevel" exists
    And the OpenAPI class "ParentDummy" doesn't exist
    And the OpenAPI class "UnknownDummy" doesn't exist
    And the OpenAPI path "/relation_embedders/{id}/custom" exists
    And the OpenAPI path "/override/swagger" exists
    And the OpenAPI path "/api/custom-call/{id}" exists
    And the JSON node "paths./api/custom-call/{id}.get" should exist
    And the JSON node "paths./api/custom-call/{id}.put" should exist
    # Properties
    And "id" property exists for the OpenAPI class "Dummy"
    And "name" property is required for OpenAPI class "Dummy"
    # Filters
    And the JSON node "paths./dummies.get.parameters[0].name" should be equal to "dummyBoolean"
    And the JSON node "paths./dummies.get.parameters[0].in" should be equal to "query"
    And the JSON node "paths./dummies.get.parameters[0].required" should be false
    And the JSON node "paths./dummies.get.parameters[0].schema.type" should be equal to "boolean"

    # Subcollection - check filter on subResource
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[0].name" should be equal to "id"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[0].in" should be equal to "path"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[0].required" should be true
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[0].schema.type" should be equal to "string"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[1].name" should be equal to "name"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[1].in" should be equal to "query"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[1].required" should be false
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[1].schema.type" should be equal to "string"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[2].name" should be equal to "description"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[2].in" should be equal to "query"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[2].required" should be false
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[2].schema.type" should be equal to "string"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters" should have 3 element

    # Subcollection - check schema
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.responses.200.content.application/ld+json.schema.items.$ref" should be equal to "#/components/schemas/RelatedToDummyFriend-fakemanytomany"

    # Deprecations
    And the JSON node "paths./dummies.get.deprecated" should not exist
    And the JSON node "paths./deprecated_resources.get.deprecated" should be true
    And the JSON node "paths./deprecated_resources.post.deprecated" should be true
    And the JSON node "paths./deprecated_resources/{id}.get.deprecated" should be true
    And the JSON node "paths./deprecated_resources/{id}.delete.deprecated" should be true
    And the JSON node "paths./deprecated_resources/{id}.put.deprecated" should be true
    And the JSON node "paths./deprecated_resources/{id}.patch.deprecated" should be true

  Scenario: OpenAPI UI is enabled for docs endpoint
    Given I add "Accept" header equal to "text/html"
    And I send a "GET" request to "/docs?spec_version=3"
    Then the response status code should be 200
    And I should see text matching "My Dummy API"
    And I should see text matching "openapi"
    And I should see text matching "3.0.2"

  Scenario: OpenAPI UI is enabled for an arbitrary endpoint
    Given I add "Accept" header equal to "text/html"
    And I send a "GET" request to "/dummies?spec_version=3"
    Then the response status code should be 200
    And I should see text matching "openapi"
    And I should see text matching "3.0.2"
