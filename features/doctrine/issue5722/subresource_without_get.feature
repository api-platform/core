Feature: Get a subresource from inverse side that has no item operation

  @!mongodb
  @createSchema
  Scenario: Get a subresource from inverse side that has no item operation
    Given there are logs on an event
    When I send a "GET" request to "/events/03af3507-271e-4cca-8eee-6244fb06e95b/logs"
    Then the response status code should be 200
