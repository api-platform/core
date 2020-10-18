Feature: IRI should contain network path
  In order to add detail to IRIs
  Include the network path

  @createSchema
  Scenario: I should be able to GET a collection of objects with network paths
    Given there are 1 networkPathDummy objects with a related networkPathRelationDummy
    And I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/network_path_dummies"
    And the JSON should be equal to:
    """
    {
      "links": {
        "self": "//example.com/network_path_dummies"
      },
      "meta": {
        "totalItems": 1,
        "itemsPerPage": 3,
        "currentPage": 1
      },
      "data": [
        {
          "id": "//example.com/network_path_dummies/1",
          "type": "NetworkPathDummy",
          "attributes": {
            "_id": 1
          },
          "relationships": {
            "networkPathRelationDummy": {
              "data": {
                "type": "NetworkPathRelationDummy",
                "id": "//example.com/network_path_relation_dummies/1"
              }
            }
          }
        }
      ]
    }
    """

  Scenario: I should be able to POST an object using a network path
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/network_path_relation_dummies" with body:
    """
    {
      "network_path_dummies": "//example.com/network_path_dummies/1"
    }
    """
    Then the response status code should be 201
    And the JSON should be equal to:
    """
    {
      "data": {
        "id": "//example.com/network_path_relation_dummies/2",
        "type": "NetworkPathRelationDummy",
        "attributes": {
          "_id": 2
        }
      }
    }
    """

  Scenario: I should be able to GET an Item with network paths
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/network_path_dummies/1"
    And the JSON should be equal to:
    """
    {
      "data": {
        "id": "//example.com/network_path_dummies/1",
        "type": "NetworkPathDummy",
        "attributes": {
          "_id": 1
        },
        "relationships": {
          "networkPathRelationDummy": {
            "data": {
              "type": "NetworkPathRelationDummy",
              "id": "//example.com/network_path_relation_dummies/1"
            }
          }
        }
      }
    }
    """

  Scenario: I should be able to GET subresources with network paths
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/network_path_relation_dummies/1/network_path_dummies"
    And the JSON should be equal to:
    """
    {
      "links": {
        "self": "//example.com/network_path_relation_dummies/1/network_path_dummies"
      },
      "meta": {
        "totalItems": 1,
        "itemsPerPage": 3,
        "currentPage": 1
      },
      "data": [
        {
          "id": "//example.com/network_path_dummies/1",
          "type": "NetworkPathDummy",
          "attributes": {
            "_id": 1
          },
          "relationships": {
            "networkPathRelationDummy": {
                "data": {
                    "type": "NetworkPathRelationDummy",
                    "id": "//example.com/network_path_relation_dummies/1"
                }
            }
          }
        }
      ]
    }
    """
