Feature: Issue 5926
  In order to reproduce the issue at https://github.com/api-platform/core/issues/5926
  As a client software developer
  I need to be able to use every operation on a resource with non-resources embed objects

  @!mongodb
  Scenario: Create and retrieve a WriteResource
    When I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/test_issue5926s/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
