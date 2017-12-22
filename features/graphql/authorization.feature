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
    Then the response status code should be 400
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
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to "Access Denied."

  @dropSchema
  Scenario: An anonymous user tries to create a resource he is not allowed to
    When I send the following GraphQL request:
    """
    mutation {
      createSecuredDummy(input: {owner: "me", title: "Hi", description: "Desc", clientMutationId: "auth"}) {
        title
        owner
      }
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "errors[0].message" should be equal to "Access Denied."
