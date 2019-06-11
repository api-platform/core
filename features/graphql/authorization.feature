Feature: Authorization checking
  In order to use the GraphQL API
  As a client software user
  I need to be authorized to access a given resource.

  @createSchema
  Scenario: An anonymous user tries to retrieve a secured item
    Given there are 1 SecuredDummy objects
    When I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/1") {
        title
        description
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to "Access Denied."

  Scenario: An anonymous user tries to retrieve a secured collection
    Given there are 1 SecuredDummy objects
    When I send the following GraphQL request:
    """
    {
      securedDummies {
        edges {
          node {
            title
            description
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to "Access Denied."

  Scenario: An anonymous user tries to create a resource they are not allowed to
    When I send the following GraphQL request:
    """
    mutation {
      createSecuredDummy(input: {owner: "me", title: "Hi", description: "Desc", clientMutationId: "auth"}) {
        securedDummy {
          title
          owner
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to "Only admins can create a secured dummy."

  @createSchema
  Scenario: An admin can create a secured resource
    When I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send the following GraphQL request:
    """
    mutation {
      createSecuredDummy(input: {owner: "someone", title: "Hi", description: "Desc"}) {
        securedDummy {
          id
          title
          owner
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createSecuredDummy.securedDummy.owner" should be equal to "someone"

  Scenario: An admin can create another secured resource
    When I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send the following GraphQL request:
    """
    mutation {
      createSecuredDummy(input: {owner: "dunglas", title: "Hi", description: "Desc"}) {
        securedDummy {
          id
          title
          owner
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createSecuredDummy.securedDummy.owner" should be equal to "dunglas"

  Scenario: A user cannot retrieve an item they doesn't own
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/1") {
        owner
        title
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to "Access Denied."

  Scenario: A user can retrieve an item they owns
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/2") {
        owner
        title
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.owner" should be equal to the string "dunglas"

  Scenario: A user can't assign to themself an item they doesn't own
    When I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send the following GraphQL request:
    """
    mutation {
      updateSecuredDummy(input: {id: "/secured_dummies/1", owner: "kitten"}) {
        securedDummy {
          id
          title
          owner
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to "Access Denied."

  Scenario: A user can update an item they owns and transfer it
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    mutation {
      updateSecuredDummy(input: {id: "/secured_dummies/2", owner: "vincent"}) {
        securedDummy {
          id
          title
          owner
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateSecuredDummy.securedDummy.owner" should be equal to the string "vincent"
