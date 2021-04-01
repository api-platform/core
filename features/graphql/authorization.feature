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
    And the JSON node "errors[0].extensions.status" should be equal to 403
    And the JSON node "errors[0].extensions.category" should be equal to user
    And the JSON node "errors[0].message" should be equal to "Access Denied."
    And the JSON node "data.securedDummy" should be null

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
    And the JSON node "errors[0].extensions.status" should be equal to 403
    And the JSON node "errors[0].extensions.category" should be equal to user
    And the JSON node "errors[0].message" should be equal to "Access Denied."
    And the JSON node "data.securedDummies" should be null

  Scenario: An admin can retrieve a secured collection
    When I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send the following GraphQL request:
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
    And the JSON node "data.securedDummies" should exist
    And the JSON node "data.securedDummies" should not be null

  Scenario: An anonymous user cannot retrieve a secured collection
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
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
    And the JSON node "data.securedDummies" should be null
    And the JSON node "errors[0].extensions.status" should be equal to 403
    And the JSON node "errors[0].extensions.category" should be equal to user
    And the JSON node "errors[0].message" should be equal to "Access Denied."
    And the JSON node "data.securedDummies" should be null

  Scenario: An anonymous user tries to create a resource they are not allowed to
    When I send the following GraphQL request:
    """
    mutation {
      createSecuredDummy(input: {owner: "me", title: "Hi", description: "Desc", adminOnlyProperty: "secret", clientMutationId: "auth"}) {
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
    And the JSON node "errors[0].extensions.status" should be equal to 403
    And the JSON node "errors[0].extensions.category" should be equal to user
    And the JSON node "errors[0].message" should be equal to "Only admins can create a secured dummy."
    And the JSON node "data.createSecuredDummy" should be null

  @createSchema
  Scenario: An admin can access a secured collection relation
    Given there are 1 SecuredDummy objects owned by admin with related dummies
    When I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/1") {
        relatedDummies {
          edges {
            node {
              id
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.relatedDummies" should have 1 element

  Scenario: An admin can access a secured relation
    When I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/1") {
        relatedDummy {
          id
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.relatedDummy" should exist
    And the JSON node "data.securedDummy.relatedDummy" should not be null

  @createSchema
  Scenario: A user can't access a secured collection relation
    Given there are 1 SecuredDummy objects owned by dunglas with related dummies
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/1") {
        relatedDummies {
          edges {
            node {
              id
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.relatedDummies" should be null

  Scenario: A user can't access a secured relation
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/1") {
        relatedDummy {
          id
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.relatedDummy" should be null

  Scenario: A user can't access a secured relation resource directly
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      relatedSecuredDummy(id: "/related_secured_dummies/1") {
        id
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].extensions.status" should be equal to 403
    And the JSON node "errors[0].extensions.category" should be equal to user
    And the JSON node "errors[0].message" should be equal to "Access Denied."
    And the JSON node "data.relatedSecuredDummy" should be null

  Scenario: A user can't access a secured relation resource collection directly
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      relatedSecuredDummies {
        edges {
          node {
            id
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].extensions.status" should be equal to 403
    And the JSON node "errors[0].extensions.category" should be equal to user
    And the JSON node "errors[0].message" should be equal to "Access Denied."
    And the JSON node "data.relatedSecuredDummies" should be null

  Scenario: A user can access a secured collection relation
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/1") {
        relatedSecuredDummies {
          edges {
            node {
              id
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.relatedSecuredDummies" should have 1 element

  Scenario: A user can access a secured relation
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/1") {
        relatedSecuredDummy {
          id
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.relatedSecuredDummy" should exist
    And the JSON node "data.securedDummy.relatedSecuredDummy" should not be null

  Scenario: A user can access a non-secured collection relation
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/1") {
        publicRelatedSecuredDummies {
          edges {
            node {
              id
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.publicRelatedSecuredDummies" should have 1 element

  Scenario: A user can access a non-secured relation
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    When I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/1") {
        publicRelatedSecuredDummy {
          id
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.publicRelatedSecuredDummy" should exist
    And the JSON node "data.securedDummy.publicRelatedSecuredDummy" should not be null

  @createSchema
  Scenario: An admin can create a secured resource
    When I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send the following GraphQL request:
    """
    mutation {
      createSecuredDummy(input: {owner: "someone", title: "Hi", description: "Desc", adminOnlyProperty: "secret"}) {
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
      createSecuredDummy(input: {owner: "dunglas", title: "Hi", description: "Desc", adminOnlyProperty: "secret"}) {
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

  Scenario: An admin can create a secured resource with an owner-only property if they will be the owner
    When I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send the following GraphQL request:
    """
    mutation {
      createSecuredDummy(input: {owner: "admin", title: "Hi", description: "Desc", adminOnlyProperty: "secret", ownerOnlyProperty: "it works"}) {
        securedDummy {
          ownerOnlyProperty
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createSecuredDummy.securedDummy.ownerOnlyProperty" should be equal to the string "it works"
    And I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send the following GraphQL request:
    """
    {
      securedDummies {
        edges {
          node {
            ownerOnlyProperty
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "data.securedDummies.edges[2].node.ownerOnlyProperty" should be equal to "it works"

  Scenario: An admin can't create a secured resource with an owner-only property if they won't be the owner
    When I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send the following GraphQL request:
    """
    mutation {
      createSecuredDummy(input: {owner: "dunglas", title: "Hi", description: "Desc", adminOnlyProperty: "secret", ownerOnlyProperty: "should not be set"}) {
        securedDummy {
          ownerOnlyProperty
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.createSecuredDummy.securedDummy.ownerOnlyProperty" should exist
    And the JSON node "data.createSecuredDummy.securedDummy.ownerOnlyProperty" should be null
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/4") {
        ownerOnlyProperty
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "data.securedDummy.ownerOnlyProperty" should be equal to the string ""

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
    And the JSON node "errors[0].extensions.status" should be equal to 403
    And the JSON node "errors[0].extensions.category" should be equal to user
    And the JSON node "errors[0].message" should be equal to "Access Denied."
    And the JSON node "data.securedDummy" should be null

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

  Scenario: An admin can see a secured admin-only property on an object they don't own
    When I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/2") {
        owner
        title
        adminOnlyProperty
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.adminOnlyProperty" should exist
    And the JSON node "data.securedDummy.adminOnlyProperty" should not be null

  Scenario: A user can't see a secured admin-only property on an object they own
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/2") {
        owner
        title
        adminOnlyProperty
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.adminOnlyProperty" should be null

  Scenario: A user can see a secured owner-only property on an object they own
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/2") {
        ownerOnlyProperty
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.ownerOnlyProperty" should exist
    And the JSON node "data.securedDummy.ownerOnlyProperty" should not be null

  Scenario: A user can update a secured owner-only property on an object they own
    When I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    mutation {
      updateSecuredDummy(input: {id: "/secured_dummies/2", ownerOnlyProperty: "updated"}) {
        securedDummy {
          ownerOnlyProperty
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateSecuredDummy.securedDummy.ownerOnlyProperty" should be equal to the string "updated"
    And I add "Authorization" header equal to "Basic ZHVuZ2xhczprZXZpbg=="
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/2") {
        ownerOnlyProperty
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "data.securedDummy.ownerOnlyProperty" should be equal to the string "updated"

  Scenario: An admin can't see a secured owner-only property on an object they don't own
    When I add "Authorization" header equal to "Basic YWRtaW46a2l0dGVu"
    And I send the following GraphQL request:
    """
    {
      securedDummy(id: "/secured_dummies/2") {
        ownerOnlyProperty
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.securedDummy.ownerOnlyProperty" should be null

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
    And the JSON node "errors[0].extensions.status" should be equal to 403
    And the JSON node "errors[0].extensions.category" should be equal to user
    And the JSON node "errors[0].message" should be equal to "Access Denied."
    And the JSON node "data.updateSecuredDummy" should be null

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
