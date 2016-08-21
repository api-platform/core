Feature: Documentation support
  In order to build an auto-discoverable API
  As a client software developer
  I need to know Swagger specifications of objects I send and receive

  Scenario: Retrieve the API vocabulary
    Given I send a "GET" request to "/apidoc.json"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    # Context
    And the JSON node "swagger" should be equal to "2.0"
    # Root properties
    And the JSON node "info.title" should be equal to "My Dummy API"
    And the JSON node "info.description" should be equal to "This is a test API."
    # Supported classes
    And the Swagger class "CircularReference" exists
    And the Swagger class "CustomIdentifierDummy" exists
    And the Swagger class "CustomNormalizedDummy" exists
    And the Swagger class "CustomWritableIdentifierDummy" exists
    And the Swagger class "Dummy" exists
    And the Swagger class "RelatedDummy" exists
    And the Swagger class "RelationEmbedder" exists
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
