Feature: Collections support
  In order to retrieve large collections of resources
  As a client software developer
  I need to retrieve paged collections respecting the Hydra specification

  @createSchema
  Scenario: Retrieve an empty collection
    When I send a "GET" request to "/dummies"
    Then print last JSON response
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 0,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies",
      "hydra:lastPage": "/dummies",
      "hydra:member": []
    }
    """

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
      "hydra:member": [
        {
          "@id": "/dummies/1",
          "@type": "Dummy",
          "name": "Dummy #1",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": []
        },
        {
          "@id": "/dummies/2",
          "@type": "Dummy",
          "name": "Dummy #2",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": []
        },
        {
          "@id": "/dummies/3",
          "@type": "Dummy",
          "name": "Dummy #3",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": []
        }
      ]
    }
    """

  Scenario: Retrieve a page of a collection
    When I send a "GET" request to "/dummies?page=7"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?page=7",
      "@type": "hydra:PagedCollection",
      "hydra:previousPage": "/dummies?page=6",
      "hydra:nextPage": "/dummies?page=8",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies",
      "hydra:lastPage": "/dummies?page=10",
      "hydra:member": [
        {
          "@id": "/dummies/19",
          "@type": "Dummy",
          "name": "Dummy #19",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": []
        },
        {
          "@id": "/dummies/20",
          "@type": "Dummy",
          "name": "Dummy #20",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": []
        },
        {
          "@id": "/dummies/21",
          "@type": "Dummy",
          "name": "Dummy #21",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": []
        }
      ]
    }
    """

  Scenario: Retrieve the last page of a collection
    When I send a "GET" request to "/dummies?page=10"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?page=10",
      "@type": "hydra:PagedCollection",
      "hydra:previousPage": "/dummies?page=9",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies",
      "hydra:lastPage": "/dummies?page=10",
      "hydra:member": [
          {
            "@id": "/dummies/28",
            "@type": "Dummy",
            "name": "Dummy #28",
            "dummy": null,
            "dummyDate": null,
            "relatedDummy": null,
            "relatedDummies": []
          },
          {
            "@id": "/dummies/29",
            "@type": "Dummy",
            "name": "Dummy #29",
            "dummy": null,
            "dummyDate": null,
            "relatedDummy": null,
            "relatedDummies": []
          },
          {
            "@id": "/dummies/30",
            "@type": "Dummy",
            "name": "Dummy #30",
            "dummy": null,
            "dummyDate": null,
            "relatedDummy": null,
            "relatedDummies": []
          }
      ]
    }
    """

  Scenario: Change the number of element by page client side
    When I send a "GET" request to "/dummies?page=2&itemsPerPage=10"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?page=2&itemsPerPage=10",
      "@type": "hydra:PagedCollection",
      "hydra:previousPage": "/dummies?itemsPerPage=10",
      "hydra:nextPage": "/dummies?itemsPerPage=10&page=3",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 10,
      "hydra:firstPage": "/dummies?itemsPerPage=10",
      "hydra:lastPage": "/dummies?itemsPerPage=10&page=3",
      "hydra:member": [
        {
          "@id": "/dummies/11",
          "@type": "Dummy",
          "name": "Dummy #11",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": [
            
          ]
        },
        {
          "@id": "/dummies/12",
          "@type": "Dummy",
          "name": "Dummy #12",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": [
            
          ]
        },
        {
          "@id": "/dummies/13",
          "@type": "Dummy",
          "name": "Dummy #13",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": [
            
          ]
        },
        {
          "@id": "/dummies/14",
          "@type": "Dummy",
          "name": "Dummy #14",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": [
            
          ]
        },
        {
          "@id": "/dummies/15",
          "@type": "Dummy",
          "name": "Dummy #15",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": [
            
          ]
        },
        {
          "@id": "/dummies/16",
          "@type": "Dummy",
          "name": "Dummy #16",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": [
            
          ]
        },
        {
          "@id": "/dummies/17",
          "@type": "Dummy",
          "name": "Dummy #17",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": [
            
          ]
        },
        {
          "@id": "/dummies/18",
          "@type": "Dummy",
          "name": "Dummy #18",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": [
            
          ]
        },
        {
          "@id": "/dummies/19",
          "@type": "Dummy",
          "name": "Dummy #19",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": [
            
          ]
        },
        {
          "@id": "/dummies/20",
          "@type": "Dummy",
          "name": "Dummy #20",
          "dummy": null,
          "dummyDate": null,
          "relatedDummy": null,
          "relatedDummies": [
            
          ]
        }
      ]
    }
    """

  Scenario: Filter with exact match
    When I send a "GET" request to "/dummies?id=8"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?id=8",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?id=8",
      "hydra:lastPage": "/dummies?id=8",
      "hydra:member": [
          {
            "@id": "/dummies/8",
            "@type": "Dummy",
            "name": "Dummy #8",
            "dummy": null,
            "dummyDate": null,
            "relatedDummy": null,
            "relatedDummies": []
          }
      ]
    }
    """

  Scenario: Filter with a raw URL
    When I send a "GET" request to "/dummies?id=%2fdummies%2f8"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?id=%2fdummies%2f8",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?id=%2Fdummies%2F8",
      "hydra:lastPage": "/dummies?id=%2Fdummies%2F8",
      "hydra:member": [
          {
            "@id": "/dummies/8",
            "@type": "Dummy",
            "name": "Dummy #8",
            "dummy": null,
            "dummyDate": null,
            "relatedDummy": null,
            "relatedDummies": []
          }
      ]
    }
    """

  @dropSchema
  Scenario: Filter with non-exact match
    When I send a "GET" request to "/dummies?name=Dummy%20%238"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?name=Dummy%20%238",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?name=Dummy+%238",
      "hydra:lastPage": "/dummies?name=Dummy+%238",
      "hydra:member": [
          {
            "@id": "/dummies/8",
            "@type": "Dummy",
            "name": "Dummy #8",
            "dummy": null,
            "dummyDate": null,
            "relatedDummy": null,
            "relatedDummies": []
          }
      ]
    }
    """
