Feature: JSON API order handling
  In order to be able to handle ordering
  As a client software developer
  I need to be able to specify ordering parameters according to JSON API recommendation

  Background:
    Given I add "Content-Type" header equal to "application/vnd.api+json"
    And I add "Accept" header equal to "application/vnd.api+json"

  @createSchema
  Scenario: Get collection ordered in ascending order on an integer property and on which order filter has been enabled in whitelist mode
    Given there are 30 dummy objects
    When I send a "GET" request to "/dummies?sort=id"
    Then the response status code should be 200
    And the JSON should be valid according to the JSON API schema
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "data": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "_id": {
                  "type": "string",
                  "pattern": "^1$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "_id": {
                  "type": "string",
                  "pattern": "^2$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "_id": {
                  "type": "string",
                  "pattern": "^3$"
                }
              }
            }
          ]
        }
      }
    }
    """

  Scenario: Get collection ordered in descending order on an integer property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?sort=-id"
    Then the response status code should be 200
    And the JSON should be valid according to the JSON API schema
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "data": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "_id": {
                  "type": "string",
                  "pattern": "^30$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "_id": {
                  "type": "string",
                  "pattern": "^29$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "_id": {
                  "type": "string",
                  "pattern": "^28$"
                }
              }
            }
          ]
        }
      }
    }
    """

  Scenario: Get collection ordered on two properties previously whitelisted
    When I send a "GET" request to "/dummies?sort=description,-id"
    Then the JSON should be valid according to the JSON API schema
    Then the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "data": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "_id": {
                  "type": "string",
                  "pattern": "^30$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "_id": {
                  "type": "string",
                  "pattern": "^28$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "_id": {
                  "type": "string",
                  "pattern": "^26$"
                }
              }
            }
          ]
        }
      }
    }
    """
