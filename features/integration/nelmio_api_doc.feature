Feature: NelmioApiDoc integration
  In order to use NelmioApiDocBundle
  As an API software developer
  I need to see the generated documentation

  Scenario: Create a user
    When I send a "GET" request to "/nelmioapidoc"
    Then the response status code should be 200
    And I should see text matching "AbstractDummy"
    And I should see text matching "Dummy"
    And I should see text matching "User"
    And I should see text matching "Retrieves the collection of Dummy resources."
    And I should see text matching "Creates a Dummy resource."
    And I should see text matching "Deletes the Dummy resource."
    And I should see text matching "Replaces the Dummy resource."
