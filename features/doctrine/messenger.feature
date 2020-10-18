Feature: Combine messenger with doctrine
  In order to persist and send a resource
  As a client software developer
  I need to configure the messenger ApiResource attribute properly

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  @createSchema
  Scenario: Using messenger="persist" should combine doctrine and messenger
    When I send a "POST" request to "/messenger_with_persists" with body:
    """
    {
      "name": "test"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/MessengerWithPersist",
      "@id": "/messenger_with_persists/1",
      "@type": "MessengerWithPersist",
      "id": 1,
      "name": "test"
    }
    """

  Scenario: Using messenger={"persist", "input"} should combine doctrine, messenger and input DTO
    When I send a "POST" request to "/messenger_with_arrays" with body:
    """
    {
      "var": "test"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/MessengerWithArray",
      "@id": "/messenger_with_arrays/1",
      "@type": "MessengerWithArray",
      "id": 1,
      "name": "test"
    }
    """
