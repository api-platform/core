Feature: Authorization checking
  In order to use the API
  I need to be authorized to access a given resource.

  @!mongodb
  @createSchema
  Scenario: An anonymous user retrieves a secured resource
    When I add "Accept" header equal to "application/ld+json"
    When I am on "/secured_dummy_with_filters?required=&required-allow-empty=&arrayRequired[foo]="
    Then the response status code should be 401

