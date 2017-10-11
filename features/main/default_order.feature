Feature: Default order
  In order to get a list in a specific order,
  As a client software developer,
  I need to be able to specify default order.

  @createSchema @dropSchema
  Scenario: Override custom order
    Given there are 5 foo objects with fake names
    When I send a "GET" request to "/foos?itemsPerPage=10"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Foo",
      "@id": "/foos",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/foos/5",
          "@type": "Foo",
          "id": 5,
          "name": "Balbo",
          "bar": "Amet"
        },
        {
          "@id": "/foos/2",
          "@type": "Foo",
          "id": 2,
          "name": "Sthenelus",
          "bar": "Dolor"
        },
        {
          "@id": "/foos/3",
          "@type": "Foo",
          "id": 3,
          "name": "Ephesian",
          "bar": "Dolor"
        },
        {
          "@id": "/foos/1",
          "@type": "Foo",
          "id": 1,
          "name": "Hawsepipe",
          "bar": "Lorem"
        },
        {
          "@id": "/foos/4",
          "@type": "Foo",
          "id": 4,
          "name": "Separativeness",
          "bar": "Sit"
        }
      ],
      "hydra:totalItems": 5,
      "hydra:view": {
        "@id": "/foos?itemsPerPage=10",
        "@type": "hydra:PartialCollectionView"
      }
    }
    """
