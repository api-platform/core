Feature: Custom operation
  As a client software developer
  I need to be able to create custom operations

  @createSchema
  Scenario: Custom normalization operation
    When I send a "POST" request to "/custom/denormalization"
    And I add "Content-Type" header equal to "application/ld+json"
    Then the JSON should be equal to:
    """
    {
        "@context": "/contexts/CustomActionDummy",
        "@id": "/custom_action_dummies/1",
        "@type": "CustomActionDummy",
        "id": 1,
        "foo": "custom!"
    }
    """

  @dropSchema
  Scenario: Custom normalization operation
    When I send a "GET" request to "/custom/1/normalization"
    Then the JSON should be equal to:
    """
    {
        "id": 1,
        "foo": "foo"
    }
    """
