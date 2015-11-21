Feature: Order filter on collections
  In order to retrieve ordered large collections of resources
  As a client software developer
  I need to retrieve collections ordered properties

  @createSchema
  Scenario: Get collection ordered in ascending order on an integer property and on which order filter has been enabled in whitelist mode
    Given there is "30" dummy objects
    When I send a "GET" request to "/dummies?order[id]=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[id]=asc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bid%5D=asc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bid%5D=asc",
      "hydra:lastPage": "/dummies?order%5Bid%5D=asc&page=10",
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

  Scenario: Get collection ordered in descending order on an integer property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[id]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[id]=desc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bid%5D=desc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bid%5D=desc",
      "hydra:lastPage": "/dummies?order%5Bid%5D=desc&page=10",
      "hydra:member": [
          {
            "@id": "/dummies/30",
            "@type": "Dummy",
            "name": "Dummy #30",
            "alias": "Alias #0",
            "description": "Not so smart dummy.",
            "dummyDate": null,
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
            "dummyDate": null,
            "jsonData": [],
            "relatedDummy": null,
            "dummy": null,
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/28",
            "@type": "Dummy",
            "name": "Dummy #28",
            "alias": "Alias #2",
            "description": "Not so smart dummy.",
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

  Scenario: Get collection ordered in ascending order on a string property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[name]=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[name]=asc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bname%5D=asc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bname%5D=asc",
      "hydra:lastPage": "/dummies?order%5Bname%5D=asc&page=10",
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
            "@id": "/dummies/10",
            "@type": "Dummy",
            "name": "Dummy #10",
            "alias": "Alias #20",
            "description": "Not so smart dummy.",
            "dummyDate": null,
            "jsonData": [],
            "relatedDummy": null,
            "dummy": null,
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/11",
            "@type": "Dummy",
            "name": "Dummy #11",
            "alias": "Alias #19",
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

  Scenario: Get collection ordered in descending order on a string property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[name]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[name]=desc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bname%5D=desc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bname%5D=desc",
      "hydra:lastPage": "/dummies?order%5Bname%5D=desc&page=10",
      "hydra:member": [
          {
            "@id": "/dummies/9",
            "@type": "Dummy",
            "name": "Dummy #9",
            "alias": "Alias #21",
            "description": "Smart dummy.",
            "dummyDate": null,
            "jsonData": [],
            "relatedDummy": null,
            "dummy": null,
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/8",
            "@type": "Dummy",
            "name": "Dummy #8",
            "alias": "Alias #22",
            "description": "Not so smart dummy.",
            "dummyDate": null,
            "jsonData": [],
            "relatedDummy": null,
            "dummy": null,
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/7",
            "@type": "Dummy",
            "name": "Dummy #7",
            "alias": "Alias #23",
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

  Scenario: Get collection ordered by default configured order on a string property and on which order filter has been enabled in whitelist mode with default descending order
    When I send a "GET" request to "/dummies?order[name]"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[name]",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bname%5D=&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bname%5D=",
      "hydra:lastPage": "/dummies?order%5Bname%5D=&page=10",
      "hydra:member": [
          {
            "@id": "/dummies/9",
            "@type": "Dummy",
            "name": "Dummy #9",
            "alias": "Alias #21",
            "description": "Smart dummy.",
            "dummyDate": null,
            "jsonData": [],
            "relatedDummy": null,
            "dummy": null,
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/8",
            "@type": "Dummy",
            "name": "Dummy #8",
            "alias": "Alias #22",
            "description": "Not so smart dummy.",
            "dummyDate": null,
            "jsonData": [],
            "relatedDummy": null,
            "dummy": null,
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/7",
            "@type": "Dummy",
            "name": "Dummy #7",
            "alias": "Alias #23",
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

  Scenario: Get collection ordered in ascending order on an association and on which order filter has been enabled in whitelist mode
    Given there is "30" dummy objects with relatedDummy
    When I send a "GET" request to "/dummies?order[relatedDummy]=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
        "@context": "/contexts/Dummy",
        "@id": "/dummies?order[relatedDummy]=asc",
        "@type": "hydra:PagedCollection",
        "hydra:nextPage": "/dummies?order%5BrelatedDummy%5D=asc&page=2",
        "hydra:totalItems": 60,
        "hydra:itemsPerPage": 3,
        "hydra:firstPage": "/dummies?order%5BrelatedDummy%5D=asc",
        "hydra:lastPage": "/dummies?order%5BrelatedDummy%5D=asc&page=20",
        "hydra:member": [
            {
                "@id": "/dummies/1",
                "@type": "Dummy",
                "name": "Dummy #1",
                "alias": "Alias #29",
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
  Scenario: Get collection ordered by a non valid properties and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[alias]=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[alias]=asc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Balias%5D=asc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Balias%5D=asc",
      "hydra:lastPage": "/dummies?order%5Balias%5D=asc&page=10",
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

    When I send a "GET" request to "/dummies?order[alias]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[alias]=desc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Balias%5D=desc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Balias%5D=desc",
      "hydra:lastPage": "/dummies?order%5Balias%5D=desc&page=10",
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

    When I send a "GET" request to "/dummies?order[unknown]=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[unknown]=asc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bunknown%5D=asc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bunknown%5D=asc",
      "hydra:lastPage": "/dummies?order%5Bunknown%5D=asc&page=10",
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

    When I send a "GET" request to "/dummies?order[unknown]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[unknown]=desc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bunknown%5D=desc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bunknown%5D=desc",
      "hydra:lastPage": "/dummies?order%5Bunknown%5D=desc&page=10",
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
