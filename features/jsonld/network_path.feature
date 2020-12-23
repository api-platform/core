Feature: IRI should contain network path
  In order to add detail to IRIs
  Include the network path

  @createSchema
  Scenario: I should be able to GET a collection of objects with network paths
    Given there are 1 networkPathDummy objects with a related networkPathRelationDummy
    And I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "GET" request to "/network_path_dummies"
    And the JSON should be equal to:
    """
    {
      "@context": "//example.com/contexts/NetworkPathDummy",
      "@id": "//example.com/network_path_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "//example.com/network_path_dummies/1",
          "@type": "NetworkPathDummy",
          "networkPathRelationDummy": "//example.com/network_path_relation_dummies/1",
          "id": 1
        }
      ],
      "hydra:totalItems": 1
    }

    """

  Scenario: I should be able to POST an object using a network path
    Given I add "Accept" header equal to "application/ld+json"
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
      "@context": "//example.com/contexts/NetworkPathRelationDummy",
      "@id": "//example.com/network_path_relation_dummies/2",
      "@type": "NetworkPathRelationDummy",
      "networkPathDummies": [],
      "id": 2
    }
    """

  Scenario: I should be able to GET an Item with network paths
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "GET" request to "/network_path_dummies/1"
    And the JSON should be equal to:
    """
    {
      "@context": "//example.com/contexts/NetworkPathDummy",
      "@id": "//example.com/network_path_dummies/1",
      "@type": "NetworkPathDummy",
      "networkPathRelationDummy": "//example.com/network_path_relation_dummies/1",
      "id": 1
    }
    """

  Scenario: I should be able to GET subresources with network paths
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "GET" request to "/network_path_relation_dummies/1/network_path_dummies"
    And the JSON should be equal to:
    """
    {
      "@context": "//example.com/contexts/NetworkPathDummy",
      "@id": "//example.com/network_path_relation_dummies/1/network_path_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "//example.com/network_path_dummies/1",
          "@type": "NetworkPathDummy",
          "networkPathRelationDummy": "//example.com/network_path_relation_dummies/1",
          "id": 1
        }
      ],
      "hydra:totalItems": 1
    }
    """
