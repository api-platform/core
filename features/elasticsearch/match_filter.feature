@elasticsearch
Feature: Match filter on collections from Elasticsearch
  In order to get specific results from a large collections of resources from Elasticsearch
  As a client software developer
  I need to search for resources matching the text specified

  Scenario: Match filter on a text property
    When I send a "GET" request to "/tweets?message=Good%20job"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 2,
          "minItems": 2,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/7cdadcda-3fb5-4312-9e32-72acba323cc0$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f91bca21-b5f8-405b-9b08-d5a5dc476a92$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?message=Good%20job$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Match filter on a text property
    When I send a "GET" request to "/tweets?message%5B%5D=Good%20job&message%5B%5D=run"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/6d82a76c-8ba2-4e78-9ab3-6a456e4470c3$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/7cdadcda-3fb5-4312-9e32-72acba323cc0$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/9de3308c-6f82-4a57-a33c-4e3cd5d5a3f6$"
                }
              }
            }
          ]
        },
        "hydra:totalItem": {
          "type": "string",
          "pattern": "^4$"
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?message%5B%5D=Good%20job&message%5B%5D=run&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Match filter on a nested property of text type
    When I send a "GET" request to "/tweets?author.firstName=Caroline"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 2,
          "minItems": 2,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/6d82a76c-8ba2-4e78-9ab3-6a456e4470c3$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f91bca21-b5f8-405b-9b08-d5a5dc476a92$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?author.firstName=Caroline$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Combining match filters on properties of text type and a nested property of text type
    When I send a "GET" request to "/tweets?message%5B%5D=Good%20job&message%5B%5D=run&author.firstName=Caroline"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 2,
          "minItems": 2,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/6d82a76c-8ba2-4e78-9ab3-6a456e4470c3$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f91bca21-b5f8-405b-9b08-d5a5dc476a92$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?message%5B%5D=Good%20job&message%5B%5D=run&author.firstName=Caroline$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
