Feature: Collections with absolute IRIs support
  In order to retrieve large collections of resources
  As a client software developer
  I need to retrieve paged collections respecting the Hydra specification and with absolute iris

  @createSchema
  Scenario: Retrieve third page of collection with absolute iris
    Given there are 30 absoluteUrlDummy objects with a related absoluteUrlRelationDummy
    When I send a "GET" request to "/absolute_url_dummies?page=3"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:view" should be equal to:
    """
    {
      "@id": "http://example.com/absolute_url_dummies?page=3",
      "@type": "hydra:PartialCollectionView",
      "hydra:first": "http://example.com/absolute_url_dummies?page=1",
      "hydra:last": "http://example.com/absolute_url_dummies?page=10",
      "hydra:previous": "http://example.com/absolute_url_dummies?page=2",
      "hydra:next": "http://example.com/absolute_url_dummies?page=4"
    }
    """
