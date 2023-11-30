Feature: Entrypoint support
  In order to build an auto-discoverable API
  As a client software developer
  I need to access to an entrypoint listing top-level resources

  Scenario: Retrieve the Entrypoint
    When I add "Accept" header equal to "application/vnd.openapi+json"
    When I send a "GET" request to "/"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.openapi+json; charset=utf-8"
    And the JSON should be sorted

  Scenario: Retrieve the Entrypoint with url format
    When I send a "GET" request to "/index.jsonopenapi"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.openapi+json; charset=utf-8"
    And the JSON should be sorted
