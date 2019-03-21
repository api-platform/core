Feature: JSON API DTO input and output
  In order to use a hypermedia API
  As a client software developer
  I need to be able to use DTOs on my resources as Input or Output objects.

  Background:
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/vnd.api+json"

  @createSchema
  Scenario: Get an item with a custom output
    Given there is a DummyDtoCustom
    When I send a "GET" request to "/dummy_dto_custom_output/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
    And the JSON should be valid according to the JSON API schema
    And the JSON should be a superset of:
    """
    {
      "data": {
        "id": "/dummy_dto_customs/1",
        "type": "DummyDtoCustom",
        "attributes": {
          "foo": "test",
          "bar": 1
        }
      }
    }
    """

  @createSchema
  Scenario: Get a collection with a custom output
    Given there are 2 DummyDtoCustom
    When I send a "GET" request to "/dummy_dto_custom_output"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
    And the JSON should be valid according to the JSON API schema
    And the JSON should be a superset of:
    """
    {
      "data": [
        {
          "id": "/dummy_dto_customs/1",
          "type": "DummyDtoCustom",
          "attributes": {
            "foo": "test",
            "bar": 1
          }
        },
        {
          "id": "/dummy_dto_customs/2",
          "type": "DummyDtoCustom",
          "attributes": {
            "foo": "test",
            "bar": 2
          }
        }
      ]
    }
    """
