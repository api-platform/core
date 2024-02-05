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

  Scenario: Data provider that's return generator has null previous object
    When I add "Accept" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/custom_data_provider_generator"
    Then the response status code should be 200

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
        "owner": "dunglas",
        "adminOnlyProperty": "secret"
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

  Scenario: A user can see a secured owner-only property, or accessible property based on voter, on an object they own
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies/2"
    Then the response status code should be 200
    And the JSON node "ownerOnlyProperty" should exist
    And the JSON node "ownerOnlyProperty" should not be null
    And the JSON node "attributeBasedProperty" should exist
    And the JSON node "attributeBasedProperty" should not be null

  @!mongodb
  Scenario: An admin can create a secured resource with properties depending on themselves
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send a "POST" request to "/secured_dummy_with_properties_depending_on_themselves" with body:
    """
    {
        "canUpdateProperty": false,
        "property": false
    }
    """
    Then the response status code should be 201

  @!mongodb
  Scenario: A user cannot patch a secured property if not granted
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send a "PATCH" request to "/secured_dummy_with_properties_depending_on_themselves/1" with body:
    """
    {
        "canUpdateProperty": true,
        "property": true
    }
    """
    Then the response status code should be 200
    And the JSON node "canUpdateProperty" should be true
    And the JSON node "property" should be false

  Scenario: An admin can't see a secured owner-only property, or non-accessible property based on voter, on objects they don't own
    When I add "Accept" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send a "GET" request to "/secured_dummies"
    Then the response status code should be 200
    And the response should not contain "ownerOnlyProperty"
    And the response should not contain "attributeBasedProperty"

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

  Scenario: An admin retrieves a resource with an admin only viewable property
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send a "GET" request to "/secured_dummies"
    Then the response status code should be 200
    And the response should contain "adminOnlyProperty"

  Scenario: A user retrieves a resource with an admin only viewable property
    When I add "Accept" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies"
    Then the response status code should be 200
    And the response should not contain "adminOnlyProperty"

  Scenario: An admin can create a secured resource with a secured Property
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send a "POST" request to "/secured_dummies" with body:
    """
    {
        "title": "Common Title",
        "description": "Description",
        "owner": "dunglas",
        "adminOnlyProperty": "Is it safe?"
    }
    """
    Then the response status code should be 201
    And the response should contain "adminOnlyProperty"
    And the JSON node "adminOnlyProperty" should be equal to the string "Is it safe?"

  Scenario: A user cannot update a secured property
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "PUT" request to "/secured_dummies/3" with body:
    """
    {
        "adminOnlyProperty": "Yes it is!"
    }
    """
    Then the response status code should be 200
    And the response should not contain "adminOnlyProperty"
    And I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send a "GET" request to "/secured_dummies"
    Then the response status code should be 200
    And the response should contain "adminOnlyProperty"
    And the JSON node "hydra:member[2].adminOnlyProperty" should be equal to the string "Is it safe?"

  Scenario: An user can update owner-only secured or accessible properties on an object they own
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "PUT" request to "/secured_dummies/3" with body:
    """
    {
        "ownerOnlyProperty": "updated",
        "attributeBasedProperty": "updated"
    }
    """
    Then the response status code should be 200
    And the response should contain "ownerOnlyProperty"
    And the JSON node "ownerOnlyProperty" should be equal to the string "updated"
    And the JSON node "attributeBasedProperty" should be equal to the string "updated"

  @link_security
  Scenario: An non existing entity should return Not found
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies/40000/to_from"
    Then the response status code should be 404

  @link_security
  Scenario: An user can get related linked dummies for an secured dummy they own
    Given there are 1 SecuredDummy objects owned by dunglas with related dummies
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies/4/to_from"
    Then the response status code should be 200
    And the response should contain "securedDummy"
    And the JSON node "hydra:member[0].id" should be equal to 1

  @link_security
  Scenario: I define a custom name of the security object
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies/4/with_name"
    Then the response status code should be 200
    And the response should contain "securedDummy"
    And the JSON node "hydra:member[0].id" should be equal to 1

  @link_security
  Scenario: I define a from from link
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/related_linked_dummies/1/from_from"
    Then the response status code should be 200
    And the response should contain "id"
    And the JSON node "hydra:member[0].id" should be equal to 4

  @link_security
  Scenario: I define multiple links with security
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies/4/related/1"
    Then the response status code should be 200
    And the response should contain "id"
    And the JSON node "hydra:member[0].id" should be equal to 1

  @link_security
  Scenario: An user can not get related linked dummies for an secured dummy they do not own
    Given there are 1 SecuredDummy objects owned by someone with related dummies
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies/5/to_from"
    Then the response status code should be 403

  @link_security
  Scenario: I define a custom name of the security object
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies/5/with_name"
    Then the response status code should be 403

  @link_security
  Scenario: I define a from from link
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/related_linked_dummies/2/from_from"
    Then the response status code should be 403

  @link_security
  Scenario: I define multiple links with security
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies/5/related/2"
    Then the response status code should be 403

  Scenario: A user retrieves a resource with an admin only viewable property
    When I add "Accept" header equal to "application/json"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send a "GET" request to "/secured_dummies"
    Then the response status code should be 200
    And the response should contain "ownerOnlyProperty"
    And the response should contain "attributeBasedProperty"
