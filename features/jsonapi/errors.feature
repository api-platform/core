Feature: JSON API error handling
  In order to be able to handle error client side
  As a client software developer
  I need to retrieve an JSON API serialization of errors

  Background:
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/vnd.api+json"

  @createSchema
  Scenario: Get a validation error on an attribute
    When I send a "POST" request to "/dummies" with body:
    """
    {
      "data": {
        "type": "dummy",
        "attributes": {}
      }
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be equal to:
    """
    {
      "errors": [
        {
          "detail": "This value should not be blank.",
          "source": {
            "pointer": "data\/attributes\/name"
          }
        }
      ]
    }
    """

  Scenario: Get a validation error on an relationship
    Given there is a RelatedDummy
    And there is a DummyFriend
    When I send a "POST" request to "/related_to_dummy_friends" with body:
    """
    {
      "data": {
        "type": "RelatedToDummyFriend",
        "attributes": {
          "name": "Related to dummy friend"
        }
      }
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be equal to:
    """
    {
      "errors": [
        {
          "detail": "This value should not be null.",
          "source": {
            "pointer": "data\/relationships\/dummyFriend"
          }
        },
        {
          "detail": "This value should not be null.",
          "source": {
            "pointer": "data\/relationships\/relatedDummy"
          }
        }
      ]
    }
    """
