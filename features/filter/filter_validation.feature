Feature: Validate filters based upon filter description

  @createSchema
  Scenario: Required filter should not throw an error if set
    When I am on "/filter_validators?foo=bar"
    Then the response status code should be 200

    When I am on "/filter_validators?foo="
    Then the response status code should be 200

  @dropSchema
  Scenario: Required filter should throw an error if not set
    When I am on "/filter_validators"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to "query parameter `foo` is required"
