Feature: JSON API non-resource handling
  In order to use non-resource types
  As a developer
  I should be able to serialize types not mapped to an API resource.

  Background:
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/vnd.api+json"

  Scenario: Get a resource containing a raw object
    When I send a "GET" request to "/contain_non_resources/1?include=nested"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
    And the JSON should be valid according to the JSON API schema
    And the JSON should be a superset of:
    """
    {
      "data": {
        "id": "/contain_non_resources/1",
        "type": "ContainNonResource",
        "attributes": {
          "_id": 1,
          "notAResource": {
            "foo": "f1",
            "bar": "b1"
          }
        },
        "relationships": {
          "nested": {
            "data": {
              "id": "/contain_non_resources/1-nested",
              "type": "ContainNonResource"
            }
          }
        }
      },
      "included": [
        {
          "id": "/contain_non_resources/1-nested",
          "type": "ContainNonResource",
          "attributes": {
            "_id": "1-nested",
            "notAResource": {
              "foo": "f2",
              "bar": "b2"
            }
          }
        }
      ]
    }
    """

  @!mongodb
  @createSchema
  Scenario: Create a resource that has a non-resource relation.
    When I send a "POST" request to "/non_relation_resources" with body:
    """
    {
      "data": {
        "type": "NonRelationResource",
        "attributes": {
          "relation": {
            "foo": "test"
          }
        }
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
    And the JSON should be valid according to the JSON API schema
    And the JSON should be a superset of:
    """
    {
      "data": {
        "id": "/non_relation_resources/1",
        "type": "NonRelationResource",
        "attributes": {
          "_id": 1,
          "relation": {
            "foo": "test"
          }
        }
      }
    }
    """

  @!mongodb
  @createSchema
  Scenario: Create a resource that contains a stdClass object.
    When I send a "POST" request to "/plain_object_dummies" with body:
    """
    {
      "data": {
        "type": "PlainObjectDummy",
        "attributes": {
          "content":"{\"fields\":{\"title\":{\"value\":\"\"},\"images\":[{\"id\":0,\"categoryId\":0,\"uri\":\"/api/pictures\",\"resource\":\"{}\",\"description\":\"\",\"alt\":\"\",\"type\":\"picture\",\"text\":\"\",\"src\":\"\"}],\"alternativeAudio\":{},\"caption\":\"\"},\"showCaption\":false,\"alternativeContent\":false,\"alternativeAudioContent\":false,\"blockLayout\":\"default\"}"
        }
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
    And the JSON should be valid according to the JSON API schema
    And the JSON should be a superset of:
    """
    {
      "data": {
        "id": "/plain_object_dummies/1",
        "type": "PlainObjectDummy",
        "attributes": {
          "_id": 1,
          "data": {
            "fields": [],
            "showCaption": false,
            "alternativeContent": false,
            "alternativeAudioContent": false,
            "blockLayout": "default"
          }
        }
      }
    }
    """
