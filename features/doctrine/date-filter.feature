Feature: Date filter on collections
  In order to retrieve large collections of resources filtered by date
  As a client software developer
  I need to retrieve collections filtered by date

  @createSchema
  Scenario: Get collection filtered by date
    Given there is "30" dummy objects with dummyDate
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?dummyDate[after]=2015-04-28",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 2,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?dummyDate%5Bafter%5D=2015-04-28",
      "hydra:lastPage": "/dummies?dummyDate%5Bafter%5D=2015-04-28",
      "hydra:member": [
        {
          "@id": "/dummies/28",
          "@type": "Dummy",
          "name": "Dummy #28",
          "alias": "Alias #2",
          "description": "Not so smart dummy.",
          "dummyDate": "2015-04-28T00:00:00+00:00",
          "jsonData": [],
          "relatedDummy": null,
          "dummy": null,
          "relatedDummies": [],
          "name_converted": null
        },
        {
          "@id": "/dummies/29",
          "@type": "Dummy",
          "name": "Dummy #29",
          "alias": "Alias #1",
          "description": "Smart dummy.",
          "dummyDate": "2015-04-29T00:00:00+00:00",
          "jsonData": [],
          "relatedDummy": null,
          "dummy": null,
          "relatedDummies": [],
          "name_converted": null
        }
      ]
    }
    """

    When I send a "GET" request to "/dummies?dummyDate[before]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?dummyDate[before]=2015-04-05",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?dummyDate%5Bbefore%5D=2015-04-05&page=2",
      "hydra:totalItems": 5,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?dummyDate%5Bbefore%5D=2015-04-05",
      "hydra:lastPage": "/dummies?dummyDate%5Bbefore%5D=2015-04-05&page=2",
      "hydra:member": [
          {
              "@id": "/dummies/1",
              "@type": "Dummy",
              "name": "Dummy #1",
              "alias": "Alias #29",
              "description": "Smart dummy.",
              "dummyDate": "2015-04-01T00:00:00+00:00",
              "jsonData": [],
              "relatedDummy": null,
              "dummy": null,
              "relatedDummies": [],
              "name_converted": null
          },
          {
              "@id": "/dummies/2",
              "@type": "Dummy",
              "name": "Dummy #2",
              "alias": "Alias #28",
              "description": "Not so smart dummy.",
              "dummyDate": "2015-04-02T00:00:00+00:00",
              "jsonData": [],
              "relatedDummy": null,
              "dummy": null,
              "relatedDummies": [],
              "name_converted": null
          },
          {
              "@id": "/dummies/3",
              "@type": "Dummy",
              "name": "Dummy #3",
              "alias": "Alias #27",
              "description": "Smart dummy.",
              "dummyDate": "2015-04-03T00:00:00+00:00",
              "jsonData": [],
              "relatedDummy": null,
              "dummy": null,
              "relatedDummies": [],
              "name_converted": null
          }
      ]
    }
    """

  Scenario: Search for entities within a range
    # The order should not influence the search
    When I send a "GET" request to "/dummies?dummyDate[before]=2015-04-05&dummyDate[after]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?dummyDate[before]=2015-04-05&dummyDate[after]=2015-04-05",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?dummyDate%5Bbefore%5D=2015-04-05&dummyDate%5Bafter%5D=2015-04-05",
      "hydra:lastPage": "/dummies?dummyDate%5Bbefore%5D=2015-04-05&dummyDate%5Bafter%5D=2015-04-05",
      "hydra:member": [
          {
              "@id": "/dummies/5",
              "@type": "Dummy",
              "name": "Dummy #5",
              "alias": "Alias #25",
              "description": "Smart dummy.",
              "dummyDate": "2015-04-05T00:00:00+00:00",
              "jsonData": [],
              "relatedDummy": null,
              "dummy": null,
              "relatedDummies": [],
              "name_converted": null
          }
      ]
    }
    """

    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-05&dummyDate[before]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?dummyDate[after]=2015-04-05&dummyDate[before]=2015-04-05",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?dummyDate%5Bafter%5D=2015-04-05&dummyDate%5Bbefore%5D=2015-04-05",
      "hydra:lastPage": "/dummies?dummyDate%5Bafter%5D=2015-04-05&dummyDate%5Bbefore%5D=2015-04-05",
      "hydra:member": [
          {
              "@id": "/dummies/5",
              "@type": "Dummy",
              "name": "Dummy #5",
              "alias": "Alias #25",
              "description": "Smart dummy.",
              "dummyDate": "2015-04-05T00:00:00+00:00",
              "jsonData": [],
              "relatedDummy": null,
              "dummy": null,
              "relatedDummies": [],
              "name_converted": null
          }
      ]
    }
    """

  Scenario: Search for entities within an impossible range
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-06&dummyDate[before]=2015-04-04"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?dummyDate[after]=2015-04-06&dummyDate[before]=2015-04-04",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 0,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?dummyDate%5Bafter%5D=2015-04-06&dummyDate%5Bbefore%5D=2015-04-04",
      "hydra:lastPage": "/dummies?dummyDate%5Bafter%5D=2015-04-06&dummyDate%5Bbefore%5D=2015-04-04",
      "hydra:member": []
    }
    """

  @dropSchema
  Scenario: Get collection filtered by association date
    Given there is "30" dummy objects with dummyDate and relatedDummy
    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?relatedDummy.dummyDate[after]=2015-04-28",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 3,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?relatedDummy_dummyDate%5Bafter%5D=2015-04-28",
      "hydra:lastPage": "/dummies?relatedDummy_dummyDate%5Bafter%5D=2015-04-28",
      "hydra:member": [
          {
              "@id": "/dummies/58",
              "@type": "Dummy",
              "name": "Dummy #28",
              "alias": "Alias #2",
              "description": null,
              "dummyDate": "2015-04-28T00:00:00+00:00",
              "jsonData": [],
              "relatedDummy": "/related_dummies/28",
              "dummy": null,
              "relatedDummies": [],
              "name_converted": null
          },
          {
              "@id": "/dummies/59",
              "@type": "Dummy",
              "name": "Dummy #29",
              "alias": "Alias #1",
              "description": null,
              "dummyDate": "2015-04-29T00:00:00+00:00",
              "jsonData": [],
              "relatedDummy": "/related_dummies/29",
              "dummy": null,
              "relatedDummies": [],
              "name_converted": null
          },
          {
              "@id": "/dummies/60",
              "@type": "Dummy",
              "name": "Dummy #30",
              "alias": "Alias #0",
              "description": null,
              "dummyDate": null,
              "jsonData": [],
              "relatedDummy": "/related_dummies/30",
              "dummy": null,
              "relatedDummies": [],
              "name_converted": null
          }
      ]
    }
    """

    When I send a "GET" request to "/dummies?relatedDummy.dummyDate[after]=2015-04-28&relatedDummy_dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?relatedDummy.dummyDate[after]=2015-04-28&relatedDummy_dummyDate[after]=2015-04-28",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 3,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?relatedDummy_dummyDate%5Bafter%5D=2015-04-28",
      "hydra:lastPage": "/dummies?relatedDummy_dummyDate%5Bafter%5D=2015-04-28",
      "hydra:member": [
          {
              "@id": "/dummies/58",
              "@type": "Dummy",
              "name": "Dummy #28",
              "alias": "Alias #2",
              "description": null,
              "dummyDate": "2015-04-28T00:00:00+00:00",
              "jsonData": [],
              "relatedDummy": "/related_dummies/28",
              "dummy": null,
              "relatedDummies": [],
              "name_converted": null
          },
          {
              "@id": "/dummies/59",
              "@type": "Dummy",
              "name": "Dummy #29",
              "alias": "Alias #1",
              "description": null,
              "dummyDate": "2015-04-29T00:00:00+00:00",
              "jsonData": [],
              "relatedDummy": "/related_dummies/29",
              "dummy": null,
              "relatedDummies": [],
              "name_converted": null
          },
          {
              "@id": "/dummies/60",
              "@type": "Dummy",
              "name": "Dummy #30",
              "alias": "Alias #0",
              "description": null,
              "dummyDate": null,
              "jsonData": [],
              "relatedDummy": "/related_dummies/30",
              "dummy": null,
              "relatedDummies": [],
              "name_converted": null
          }
      ]
    }
    """
