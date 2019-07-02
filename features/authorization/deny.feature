Feature: Authorization checking
  In order to use the API
  As a client software user
  I need to be authorized to access a given resource.

  @createSchema
  Scenario: An anonymous user retrieves a secured resource
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/secured_dummies"
    Then the response status code should be 401

  Scenario: An authenticated user retrieve a secured resource
    When I add "Accept" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies"
    Then the response status code should be 200
    And the response should be in JSON

  Scenario: A standard user cannot create a secured resource
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "POST" request to "/secured_dummies" with body:
    """
    {
        "title": "Title",
        "description": "Description",
        "owner": "foo"
    }
    """
    Then the response status code should be 403

  Scenario: An admin can create a secured resource
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send a "POST" request to "/secured_dummies" with body:
    """
    {
        "title": "Title",
        "description": "Description",
        "owner": "someone"
    }
    """
    Then the response status code should be 201

  Scenario: An admin can create another secured resource
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send a "POST" request to "/secured_dummies" with body:
    """
    {
        "title": "Special Title",
        "description": "Description",
        "owner": "dunglas"
    }
    """
    Then the response status code should be 201

  Scenario: A user cannot retrieve an item they doesn't own
    When I add "Accept" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies/1"
    Then the response status code should be 403
    And the response should be in JSON

  Scenario: A user can retrieve an item they owns
    When I add "Accept" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies/2"
    Then the response status code should be 200

  Scenario: A user can't assign to themself an item they doesn't own
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send a "PUT" request to "/secured_dummies/2" with body:
    """
    {
        "owner": "kitten"
    }
    """
    Then the response status code should be 403

  Scenario: A user can update an item they owns and transfer it
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "PUT" request to "/secured_dummies/2" with body:
    """
    {
        "owner": "vincent"
    }
    """
    Then the response status code should be 200
