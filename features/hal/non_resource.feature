Feature: HAL non-resource handling
  In order to use non-resource types
  As a developer
  I should be able to serialize types not mapped to an API resource.

  Background:
    Given I add "Accept" header equal to "application/hal+json"

  Scenario: Get a resource containing a raw object
    When I send a "GET" request to "/contain_non_resources/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "/contain_non_resources/1"
        },
        "nested": {
          "href": "/contain_non_resources/1-nested"
        }
      },
      "_embedded": {
        "nested": {
          "_links": {
            "self": {
              "href": "/contain_non_resources/1-nested"
            }
          },
          "id": "1-nested",
          "notAResource": {
            "foo": "f2",
            "bar": "b2"
          }
        }
      },
      "id": 1,
      "notAResource": {
        "foo": "f1",
        "bar": "b1"
      }
    }
    """
