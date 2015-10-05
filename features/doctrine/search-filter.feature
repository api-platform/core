@searchFilter
Feature: Search filter on collections
  In order to get specific result from a large collections of resources
  As a client software developer
  I need to search for collections properties

  @createSchema
  Scenario: Search collection by name (partial)
    Given there is "30" dummy objects
    When I send a "GET" request to "/dummies?name=my"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
        "@context": "/contexts/Dummy",
        "@id": "/dummies?name=my",
        "@type": "hydra:PagedCollection",
        "hydra:nextPage": "/dummies?name=my&page=2",
        "hydra:totalItems": 30,
        "hydra:itemsPerPage": 3,
        "hydra:firstPage": "/dummies?name=my",
        "hydra:lastPage": "/dummies?name=my&page=10",
        "hydra:member": [
            {
                "@id": "/dummies/1",
                "@type": "Dummy",
                "name": "Dummy #1",
                "alias": "Alias #29",
                "description": "Smart dummy.",
                "dummyDate": null,
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
                "dummyDate": null,
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
                "dummyDate": null,
                "jsonData": [],
                "relatedDummy": null,
                "dummy": null,
                "relatedDummies": [],
                "name_converted": null
            }
        ]
    }
    """

  Scenario: Search collection by alias (start)
    When I send a "GET" request to "/dummies?alias=Ali"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
        "@context": "/contexts/Dummy",
        "@id": "/dummies?alias=Ali",
        "@type": "hydra:PagedCollection",
        "hydra:nextPage": "/dummies?alias=Ali&page=2",
        "hydra:totalItems": 30,
        "hydra:itemsPerPage": 3,
        "hydra:firstPage": "/dummies?alias=Ali",
        "hydra:lastPage": "/dummies?alias=Ali&page=10",
        "hydra:member": [
            {
                "@id": "/dummies/1",
                "@type": "Dummy",
                "name": "Dummy #1",
                "alias": "Alias #29",
                "description": "Smart dummy.",
                "dummyDate": null,
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
                "dummyDate": null,
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
                "dummyDate": null,
                "jsonData": [],
                "relatedDummy": null,
                "dummy": null,
                "relatedDummies": [],
                "name_converted": null
            }
        ]
    }
    """

  Scenario: Search collection by description (word_start)
    When I send a "GET" request to "/dummies?description=smart"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
        "@context": "/contexts/Dummy",
        "@id": "/dummies?description=smart",
        "@type": "hydra:PagedCollection",
        "hydra:nextPage": "/dummies?description=smart&page=2",
        "hydra:totalItems": 30,
        "hydra:itemsPerPage": 3,
        "hydra:firstPage": "/dummies?description=smart",
        "hydra:lastPage": "/dummies?description=smart&page=10",
        "hydra:member": [
            {
                "@id": "/dummies/1",
                "@type": "Dummy",
                "name": "Dummy #1",
                "alias": "Alias #29",
                "description": "Smart dummy.",
                "dummyDate": null,
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
                "dummyDate": null,
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
                "dummyDate": null,
                "jsonData": [],
                "relatedDummy": null,
                "dummy": null,
                "relatedDummies": [],
                "name_converted": null
            }
        ]
    }
    """

  @dropSchema
  Scenario: Search for entities within an impossible range
    When I send a "GET" request to "/dummies?name=MuYm"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?name=MuYm",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 0,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?name=MuYm",
      "hydra:lastPage": "/dummies?name=MuYm",
      "hydra:member": []
    }
    """
