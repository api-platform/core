@absoluteUrl
Feature: Check the Entrypoint with Absolute URLs

  Scenario: Retrieve the API Entrypoint
    And I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "GET" request to "/"
    And the JSON nodes should be equal to:
    | @context  | http://example.com/contexts/Entrypoint  |
    | @id       | http://example.com/                     |
    | @type     | Entrypoint                              |
