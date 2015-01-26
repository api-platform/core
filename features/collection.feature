Feature: Collections support
  In order to retrieve large collections of resources
  As a client software developer
  I need to retrieve paged collections respecting the Hydra specification

  @createSchema
  @dropSchema
  Scenario: Retrieve the first page of a collection
    Given there is "30" dummy objects
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies",
      "hydra:lastPage": "/dummies?page=10",
      "member": [
        {
          "@id": "/dummies/1",
          "@type": "Dummy",
          "name": "Dummy #1"
        },
        {
          "@id": "/dummies/2",
          "@type": "Dummy",
          "name": "Dummy #2"
        },
        {
          "@id": "/dummies/3",
          "@type": "Dummy",
          "name": "Dummy #3"
        }
      ]
    }
    """

  @createSchema
  @dropSchema
  Scenario: Retrieve a page of a collection
    Given there is "30" dummy objects
    And I send a "GET" request to "/dummies?page=7"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/dummies?page=7",
      "@type": "hydra:PagedCollection",
      "hydra:previousPage": "/dummies?page=6",
      "hydra:nextPage": "/dummies?page=8",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies",
      "hydra:lastPage": "/dummies?page=10",
      "member": [
        {
          "@id": "/dummies/19",
          "@type": "Dummy",
          "name": "Dummy #19"
        },
        {
          "@id": "/dummies/20",
          "@type": "Dummy",
          "name": "Dummy #20"
        },
        {
          "@id": "/dummies/21",
          "@type": "Dummy",
          "name": "Dummy #21"
        }
      ]
    }
    """

  @createSchema
  @dropSchema
  Scenario: Retrieve the last page of a collection
    Given there is "30" dummy objects
    And I send a "GET" request to "/dummies?page=10"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/dummies?page=10",
      "@type": "hydra:PagedCollection",
      "hydra:previousPage": "/dummies?page=9",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies",
      "hydra:lastPage": "/dummies?page=10",
      "member": [
          {
            "@id": "/dummies/28",
            "@type": "Dummy",
            "name": "Dummy #28"
          },
          {
            "@id": "/dummies/29",
            "@type": "Dummy",
            "name": "Dummy #29"
          },
          {
            "@id": "/dummies/30",
            "@type": "Dummy",
            "name": "Dummy #30"
          }
      ]
    }
    """
