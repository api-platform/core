Feature: IRI should contain Absolute URL
  In order to add detail to IRIs
  Include the absolute url

  @createSchema
  Scenario: I should be able to GET a collection of Objects with Absolute Urls
    Given there are 1 absoluteUrlDummy objects with a related absoluteUrlRelationDummy
    And I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/absolute_url_dummies"
    And the JSON should be equal to:
    """
    {
      "links": {
        "self": "http://example.com/absolute_url_dummies"
      },
      "meta": {
        "totalItems": 1,
        "itemsPerPage": 3,
        "currentPage": 1
      },
      "data": [
        {
          "id": "http://example.com/absolute_url_dummies/1",
          "type": "AbsoluteUrlDummy",
          "attributes": {
            "_id": 1
          },
          "relationships": {
            "absoluteUrlRelationDummy": {
              "data": {
                "type": "AbsoluteUrlRelationDummy",
                "id": "http://example.com/absolute_url_relation_dummies/1"
              }
            }
          }
        }
      ]
    }
    """

  Scenario: I should be able to POST an object using an Absolute Url
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/absolute_url_relation_dummies" with body:
    """
    {
      "absolute_url_dummies": "http://example.com/absolute_url_dummies/1"
    }
    """
    Then the response status code should be 201
    And the JSON should be equal to:
    """
    {
      "data": {
        "id": "http://example.com/absolute_url_relation_dummies/2",
        "type": "AbsoluteUrlRelationDummy",
        "attributes": {
          "_id": 2
        }
      }
    }
    """

  Scenario: I should be able to GET an Item with Absolute Urls
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/absolute_url_dummies/1"
    And the JSON should be equal to:
    """
    {
      "data": {
        "id": "http://example.com/absolute_url_dummies/1",
        "type": "AbsoluteUrlDummy",
        "attributes": {
          "_id": 1
        },
        "relationships": {
          "absoluteUrlRelationDummy": {
            "data": {
              "type": "AbsoluteUrlRelationDummy",
              "id": "http://example.com/absolute_url_relation_dummies/1"
            }
          }
        }
      }
    }
    """

  Scenario: I should be able to GET subresources with Absolute Urls
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/absolute_url_relation_dummies/1/absolute_url_dummies"
    And the JSON should be equal to:
    """
    {
      "links": {
        "self": "http://example.com/absolute_url_relation_dummies/1/absolute_url_dummies"
      },
      "meta": {
        "totalItems": 1,
        "itemsPerPage": 3,
        "currentPage": 1
      },
      "data": [
        {
          "id": "http://example.com/absolute_url_dummies/1",
          "type": "AbsoluteUrlDummy",
          "attributes": {
            "_id": 1
          },
          "relationships": {
            "absoluteUrlRelationDummy": {
                "data": {
                    "type": "AbsoluteUrlRelationDummy",
                    "id": "http://example.com/absolute_url_relation_dummies/1"
                }
            }
          }
        }
      ]
    }
    """
