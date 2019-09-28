@absoluteUrl
Feature: Check the Entrypoint with Absolute URLs

  Scenario: Retrieve the API Entrypoint
    And I add "Accept" header equal to "application/hal+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "GET" request to "/"
    And the JSON nodes should be equal to:
    | _links.self.href | http://example.com/ |
