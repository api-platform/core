Feature: Retrieve data with Composite identifiers
  In order to retrieve relations with composite identifiers
  As a client software developer
  I need to retrieve all collections 

  @createSchema
  @dropSchema
  Scenario: Get collection with composite identifiers
    Given there are Composite identifier objects
    When I send a "GET" request to "/composite_items"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
         "@context": "/contexts/CompositeItem",
         "@id": "/composite_items",
         "@type": "hydra:Collection",
         "hydra:member": [
             {
                 "@id": "/composite_items/1",
                 "@type": "CompositeItem",
                 "field1": "foobar",
                 "compositeValues": [
                     "/composite_relations/1-1",
                     "/composite_relations/1-2",
                     "/composite_relations/1-3",
                     "/composite_relations/1-4"
                 ]
             }
         ],
         "hydra:totalItems": 1
    }
    """

  @createSchema
  @dropSchema
  Scenario: Get collection with composite identifiers
    Given there are Composite identifier objects
    When I send a "GET" request to "/composite_relations"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
        "@context": "\/contexts\/CompositeRelation",
        "@id": "\/composite_relations",
        "@type": "hydra:Collection",
        "hydra:member": [
            {
                "@id": "\/composite_relations\/1-1",
                "@type": "CompositeRelation",
                "id": "1-1",
                "value": "somefoobardummy"
            },
            {
                "@id": "\/composite_relations\/1-2",
                "@type": "CompositeRelation",
                "id": "1-2",
                "value": "somefoobardummy"
            },
            {
                "@id": "\/composite_relations\/1-3",
                "@type": "CompositeRelation",
                "id": "1-3",
                "value": "somefoobardummy"
            }
        ],
        "hydra:totalItems": 4,
        "hydra:view": {
            "@id": "\/composite_relations?page=1",
            "@type": "hydra:PartialCollectionView",
            "hydra:first": "\/composite_relations?page=1",
            "hydra:last": "\/composite_relations?page=2",
            "hydra:next": "\/composite_relations?page=2"
        }
    }
    """
