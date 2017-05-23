Feature: JSON API order handling
  In order to be able to handle ordering
  As a client software developer
  I need to be able to specify ordering parameters according to JSON API recomendation

  @createSchema
  Scenario: Get collection ordered in ascending or descending order on an integer property and on which order filter has been enabled in whitelist mode
    Given there is "30" dummy objects
    And I add "Accept" header equal to "application/vnd.api+json"
    When I send a "GET" request to "/dummies?order=id"
    Then the response status code should be 200
    And I validate it with jsonapi-validator
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
                "id": {
                  "type": "string",
                  "pattern": "^1$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "id": {
                  "type": "string",
                  "pattern": "^2$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "id": {
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
    And I send a "GET" request to "/dummies?order=-id"
    Then the response status code should be 200
    And I validate it with jsonapi-validator
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
                "id": {
                  "type": "string",
                  "pattern": "^30$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "id": {
                  "type": "string",
                  "pattern": "^29$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "id": {
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

  @dropSchema
  Scenario: Get collection ordered on two properties previously whitelisted
    Given I add "Accept" header equal to "application/vnd.api+json"
    When I send a "GET" request to "/dummies?order=description,-id"
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
                "id": {
                  "type": "string",
                  "pattern": "^30$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "id": {
                  "type": "string",
                  "pattern": "^28$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "id": {
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
