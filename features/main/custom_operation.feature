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

  Scenario: Custom normalization operation
    When I send a "GET" request to "/custom/1/normalization"
    Then the JSON should be equal to:
    """
    {
        "id": 1,
        "foo": "foo"
    }
    """

  Scenario: Custom normalization operation with shorthand configuration
    When I send a "POST" request to "/short_custom/denormalization"
    And I add "Content-Type" header equal to "application/ld+json"
    Then the JSON should be equal to:
    """
    {
        "@context": "/contexts/CustomActionDummy",
        "@id": "/custom_action_dummies/2",
        "@type": "CustomActionDummy",
        "id": 2,
        "foo": "short declaration"
    }
    """

  Scenario: Custom normalization operation with shorthand configuration
    When I send a "GET" request to "/short_custom/2/normalization"
    Then the JSON should be equal to:
    """
    {
        "id": 2,
        "foo": "short"
    }
    """

  Scenario: Custom collection name without specific route
    When I send a "GET" request to "/custom_action_collection_dummies"
    Then the response status code should be 200
    Then the JSON node "hydra:member" should have 2 elements

  Scenario: Custom operation name without specific route
    When I send a "GET" request to "/custom_action_collection_dummies/1"
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
