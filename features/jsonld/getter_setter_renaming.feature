Feature: Resource should contain one field for each property
  In order to use API resource
  As a developer
  I need to have one field exposed for each property (which take getter/setter name)

  @createSchema
  Scenario: I should be able to POST a new entity
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    When I send a "POST" request to "/entity_with_renamed_getter_and_setters" with body:
    """
    {
      "firstnameOnly": "Sarah"
    }
    """
    Then the response status code should be 201
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/EntityWithRenamedGetterAndSetter",
      "@id": "/entity_with_renamed_getter_and_setters",
      "@type": "EntityWithRenamedGetterAndSetter",
      "firstnameOnly": "Sarah"
    }
    """
