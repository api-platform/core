Feature: Headers addition

  @createSchema
  Scenario: Test Sunset header addition
    Given there is a DummyCar entity with related colors
    When I send a "GET" request to "/dummy_cars"
    Then the response status code should be 200
    And the header "Sunset" should be equal to "Sat, 01 Jan 2050 00:00:00 +0000"
