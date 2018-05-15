Feature: FOSUser integration
  In order to use FOSUserBundle
  As an API software developer
  I need to be able manage users

  @createSchema
  Scenario: Create a user
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/users" with body:
    """
    {
      "fullname": "Dummy User",
      "username": "dummy.user",
      "email": "dummy.user@example.com",
      "plainPassword": "azerty"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/User",
      "@id": "/users/1",
      "@type": "User",
      "email": "dummy.user@example.com",
      "fullname": "Dummy User",
      "username": "dummy.user"
    }
    """
    And the password "azerty" for user 1 should be hashed

  Scenario: Delete a user
    When I send a "DELETE" request to "/users/1"
    Then the response status code should be 204
