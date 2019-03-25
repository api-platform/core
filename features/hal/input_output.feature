Feature: HAL DTO input and output
  In order to use a hypermedia API
  As a client software developer
  I need to be able to use DTOs on my resources as Input or Output objects.

  Background:
    Given I add "Accept" header equal to "application/hal+json"

  @createSchema
  Scenario: Get an item with a custom output
    Given there is a DummyDtoCustom
    When I send a "GET" request to "/dummy_dto_custom_output/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be a superset of:
    """
    {
      "_links": {
        "self": {
          "href": "/dummy_dto_customs/1"
        }
      },
      "foo": "test",
      "bar": 1
    }
    """

  @createSchema
  Scenario: Get a collection with a custom output
    Given there are 2 DummyDtoCustom
    When I send a "GET" request to "/dummy_dto_custom_output"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be a superset of:
    """
    {
      "_embedded": {
        "item": [
          {
            "_links": {
              "self": {
                "href": "/dummy_dto_customs/1"
              }
            },
            "foo": "test",
            "bar": 1
          },
          {
            "_links": {
              "self": {
                "href": "/dummy_dto_customs/2"
              }
            },
            "foo": "test",
            "bar": 2
          }
        ]
      }
    }
    """
