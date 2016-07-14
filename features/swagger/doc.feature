Feature: Documentation support
  In order to build an auto-discoverable API
  As a client software developer
  I need to know Swagger specifications of objects I send and receive

  Scenario: Retrieve the API vocabulary
    Given I send a "GET" request to "/swagger"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    # Context
    And the JSON node "swagger" should be equal to "2.0"
    # Root properties
    And the JSON node "info.title" should be equal to "My Dummy API"
    And the JSON node "info.description" should be equal to "This is a test API."
    # Supported classes
    And the Swagger class "CircularReference" exist
    And the Swagger class "CustomIdentifierDummy" exist
    And the Swagger class "CustomNormalizedDummy" exist
    And the Swagger class "CustomWritableIdentifierDummy" exist
    And the Swagger class "Dummy" exist
    And the Swagger class "RelatedDummy" exist
    And the Swagger class "RelationEmbedder" exist
    And the Swagger class "ThirdLevel" exist
    And the Swagger class "ParentDummy" not exist
    And the Swagger class "UnknownDummy" not exist
    And the Swagger path "/override/swagger" exist
    # Properties
    And "id" property doesn't exist for the Swagger class "Dummy"
    And "name" property is required for Swagger class "Dummy"
