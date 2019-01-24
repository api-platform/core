Feature: Documentation support
  In order to build an auto-discoverable API
  As a client software developer
  I need to know Swagger specifications of objects I send and receive

  @createSchema
  Scenario: Retrieve the Swagger/OpenAPI documentation
    Given I send a "GET" request to "/docs.json"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    # OverrideDocumentationNormalizer
    And the JSON node "definitions.RamseyUuidDummy.properties.id.description" should be equal to "The dummy id"
    And the JSON node "definitions.RelatedDummy-barcelona" should exist
    And the JSON node "definitions.RelatedDummybarcelona" should not exist
    # Context
    And the JSON node "swagger" should be equal to "2.0"
    # Root properties
    And the JSON node "info.title" should be equal to "My Dummy API"
    And the JSON node "info.description" should contain "This is a test API."
    And the JSON node "info.description" should contain "Made with love"
    # Supported classes
    And the Swagger class "AbstractDummy" exists
    And the Swagger class "CircularReference" exists
    And the Swagger class "CircularReference-circular" exists
    And the Swagger class "CompositeItem" exists
    And the Swagger class "CompositeLabel" exists
    And the Swagger class "ConcreteDummy" exists
    And the Swagger class "CustomIdentifierDummy" exists
    And the Swagger class "CustomNormalizedDummy-input" exists
    And the Swagger class "CustomNormalizedDummy-output" exists
    And the Swagger class "CustomWritableIdentifierDummy" exists
    And the Swagger class "Dummy" exists
    And the Swagger class "RelatedDummy" exists
    And the Swagger class "DummyTableInheritance" exists
    And the Swagger class "DummyTableInheritanceChild" exists
    And the Swagger class "OverriddenOperationDummy-overridden_operation_dummy_get" exists
    And the Swagger class "OverriddenOperationDummy-overridden_operation_dummy_put" exists
    And the Swagger class "OverriddenOperationDummy-overridden_operation_dummy_read" exists
    And the Swagger class "OverriddenOperationDummy-overridden_operation_dummy_write" exists
    And the Swagger class "RelatedDummy" exists
    And the Swagger class "NoCollectionDummy" exists
    And the Swagger class "RelatedToDummyFriend" exists
    And the Swagger class "RelatedToDummyFriend-fakemanytomany" exists
    And the Swagger class "DummyFriend" exists
    And the Swagger class "RelationEmbedder-barcelona" exists
    And the Swagger class "RelationEmbedder-chicago" exists
    And the Swagger class "User-user_user-read" exists
    And the Swagger class "User-user_user-write" exists
    And the Swagger class "UuidIdentifierDummy" exists
    And the Swagger class "ThirdLevel" exists
    And the Swagger class "ParentDummy" doesn't exist
    And the Swagger class "UnknownDummy" doesn't exist
    And the Swagger path "/relation_embedders/{id}/custom" exists
    And the Swagger path "/override/swagger" exists
    And the Swagger path "/api/custom-call/{id}" exists
    And the JSON node "paths./api/custom-call/{id}.get" should exist
    And the JSON node "paths./api/custom-call/{id}.put" should exist
    # Properties
    And "id" property exists for the Swagger class "Dummy"
    And "name" property is required for Swagger class "Dummy"
    # Filters
    And the JSON node "paths./dummies.get.parameters[0].name" should be equal to "dummyBoolean"
    And the JSON node "paths./dummies.get.parameters[0].in" should be equal to "query"
    And the JSON node "paths./dummies.get.parameters[0].required" should be false
    And the JSON node "paths./dummies.get.parameters[0].type" should be equal to "boolean"

    # Subcollection - check filter on subResource
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[0].name" should be equal to "id"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[0].in" should be equal to "path"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[0].required" should be true
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[0].type" should be equal to "string"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[1].name" should be equal to "name"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[1].in" should be equal to "query"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[1].required" should be false
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[1].type" should be equal to "string"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[2].name" should be equal to "description"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[2].in" should be equal to "query"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[2].required" should be false
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[2].type" should be equal to "string"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters" should have 3 element

    # Subcollection - check schema
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.responses.200.schema.items.$ref" should be equal to "#/definitions/RelatedToDummyFriend-fakemanytomany"

    # Deprecations
    And the JSON node "paths./dummies.get.deprecated" should not exist
    And the JSON node "paths./deprecated_resources.get.deprecated" should be true
    And the JSON node "paths./deprecated_resources.post.deprecated" should be true
    And the JSON node "paths./deprecated_resources/{id}.get.deprecated" should be true
    And the JSON node "paths./deprecated_resources/{id}.delete.deprecated" should be true
    And the JSON node "paths./deprecated_resources/{id}.put.deprecated" should be true
    And the JSON node "paths./deprecated_resources/{id}.patch.deprecated" should be true

  Scenario: Swagger UI is enabled for docs endpoint
    Given I add "Accept" header equal to "text/html"
    And I send a "GET" request to "/docs"
    Then the response status code should be 200
    And I should see text matching "My Dummy API"
    And I should see text matching "swagger"
    And I should see text matching "2.0"

  Scenario: Swagger UI is enabled for an arbitrary endpoint
    Given I add "Accept" header equal to "text/html"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And I should see text matching "My Dummy API"
    And I should see text matching "swagger"
    And I should see text matching "2.0"

  Scenario: Retrieve the Swagger/OpenAPI documentation with API Gateway compatibility
    Given I send a "GET" request to "/docs.json?api_gateway=true"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the JSON node "basePath" should be equal to "/"
    And the JSON node "definitions.RamseyUuidDummy.properties.id.description" should be equal to "The dummy id"
    And the JSON node "definitions.RelatedDummy-barcelona" should not exist
    And the JSON node "definitions.RelatedDummybarcelona" should exist
