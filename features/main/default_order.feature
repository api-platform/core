Feature: Default order
  In order to get a list in a specific order,
  As a client software developer,
  I need to be able to specify default order.

  @createSchema
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
          "@id": "/foos/3",
          "@type": "Foo",
          "id": 3,
          "name": "Ephesian",
          "bar": "Dolor"
        },
        {
          "@id": "/foos/2",
          "@type": "Foo",
          "id": 2,
          "name": "Sthenelus",
          "bar": "Ipsum"
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

  Scenario: Override custom order by association
    Given there are 5 fooDummy objects with fake names
    When I send a "GET" request to "/foo_dummies?itemsPerPage=10"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/FooDummy",
      "@id": "/foo_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/foo_dummies/5",
          "@type": "FooDummy",
          "id": 5,
          "name": "Balbo",
          "dummy": "/dummies/5"
        },
        {
          "@id": "/foo_dummies/3",
          "@type": "FooDummy",
          "id": 3,
          "name": "Sthenelus",
          "dummy": "/dummies/3"
        },
        {
          "@id": "/foo_dummies/2",
          "@type": "FooDummy",
          "id": 2,
          "name": "Ephesian",
          "dummy": "/dummies/2"
        },
        {
          "@id": "/foo_dummies/1",
          "@type": "FooDummy",
          "id": 1,
          "name": "Hawsepipe",
          "dummy": "/dummies/1"
        },
        {
          "@id": "/foo_dummies/4",
          "@type": "FooDummy",
          "id": 4,
          "name": "Separativeness",
          "dummy": "/dummies/4"
        }
      ],
      "hydra:totalItems": 5,
      "hydra:view": {
        "@id": "/foo_dummies?itemsPerPage=10",
        "@type": "hydra:PartialCollectionView"
      }
    }
    """
