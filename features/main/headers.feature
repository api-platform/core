Feature: Headers addition

  @createSchema
  Scenario: Test Sunset header addition
    Given there is a DummyCar entity with related colors
    When I send a "GET" request to "/dummy_cars"
    Then the response status code should be 200
    And the header "Sunset" should be equal to "Sat, 01 Jan 2050 00:00:00 +0000"

  Scenario: Declare headers from resource
    When I send a "GET" request to "/redirect_to_foobar"
    Then the response status code should be 301
    And the header "Location" should be equal to "/foobar"
    And the header "Hello" should be equal to "World"
