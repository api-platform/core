Feature: Create-Retrieve-Update-Delete
  In order to use an hypermedia API
  As a client software developer
  I need to be able to retrieve, create, update and delete JSON-LD encoded resources.

  @createSchema
  @dropSchema
  Scenario: Create a resource
    When I send a "POST" request to "/users" with body:
    """
    {
      "fullname": "Dummy User",
      "email": "dummy.user@example.com",
      "plainPassword": "azerty"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
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
