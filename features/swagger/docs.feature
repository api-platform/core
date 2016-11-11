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
    # Context
    And the JSON node "swagger" should be equal to "2.0"
    # Root properties
    And the JSON node "info.title" should be equal to "My Dummy API"
    And the JSON node "info.description" should be equal to "This is a test API."
    # Supported classes
    And the Swagger class "AbstractDummy" exists
    And the Swagger class "CircularReference" exists
    And the Swagger class "CircularReference_a0dd2858dcb0d966f739c1ac906afa2e" exists
    And the Swagger class "CompositeItem" exists
    And the Swagger class "CompositeLabel" exists
    And the Swagger class "ConcreteDummy" exists
    And the Swagger class "CustomIdentifierDummy" exists
    And the Swagger class "CustomNormalizedDummy_601856395b57c6b15175297eb6c9890e" exists
    And the Swagger class "CustomNormalizedDummy_db9cba1a967111a02380774784c47722" exists
    And the Swagger class "CustomWritableIdentifierDummy" exists
    And the Swagger class "Dummy" exists
    And the Swagger class "RelatedDummy" exists
    And the Swagger class "DummyTableInheritance" exists
    And the Swagger class "DummyTableInheritanceChild" exists
    And the Swagger class "OverriddenOperationDummy_441e1f98db3d0250bcb18dca087687c3" exists
    And the Swagger class "OverriddenOperationDummy_45f46ed6dc6f412229a8c12cd5583586" exists
    And the Swagger class "OverriddenOperationDummy_868796b9924a520acbb96f8b75dade9f" exists
    And the Swagger class "OverriddenOperationDummy_ff74003f36aebfe31c696fae1f701ae4" exists
    And the Swagger class "RelatedDummy" exists
    And the Swagger class "NoCollectionDummy" exists
    And the Swagger class "RelatedToDummyFriend" exists
    And the Swagger class "RelatedToDummyFriend_ad38b7a2760884e744c577a92e02b8c4" exists
    And the Swagger class "DummyFriend" exists
    And the Swagger class "RelationEmbedder_ced9cba177bf3134e609fccf878df9a7" exists
    And the Swagger class "RelationEmbedder_f02fd88a2291463447338402aee9a220" exists
    And the Swagger class "User_4320517091b72c69e9f0c72aac0141e8" exists
    And the Swagger class "User_7ce91261c0e731d95bb24b83b1f637b2" exists
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
    And the JSON node "paths./dummies.get.parameters[0].name" should be equal to "id"
    And the JSON node "paths./dummies.get.parameters[0].in" should be equal to "query"
    And the JSON node "paths./dummies.get.parameters[0].required" should be false
    And the JSON node "paths./dummies.get.parameters[0].type" should be equal to "integer"

  Scenario: Swagger UI is enabled for docs endpoint
    Given I add "Accept" header equal to "text/html"
    And I send a "GET" request to "/docs"
    Then the response status code should be 200
    And I should see text matching "My Dummy API"

  @dropSchema
  Scenario: Swagger UI is enabled for an arbitrary endpoint
    Given I add "Accept" header equal to "text/html"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And I should see text matching "My Dummy API"
