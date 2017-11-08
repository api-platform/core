@mongodb
Feature: Create-Retrieve-Update-Delete
  In order to use an hypermedia API
  As a client software developer
  I need to be able to retrieve, create, update and delete JSON-LD encoded resources.

  Scenario: Create a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "My Dummy",
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "jsonData": {
        "key": [
          "value1",
          "value2"
        ]
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "dummyFloat": null,
      "dummyPrice": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "jsonData": {
        "key": [
          "value1",
          "value2"
        ]
      },
      "name_converted": null,
      "name": "My Dummy",
      "alias": null
    }
    """

  Scenario: Get a resource
    When I send a "GET" request to "/dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "dummyFloat": null,
      "dummyPrice": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "jsonData": {
        "key": [
          "value1",
          "value2"
        ]
      },
      "name_converted": null,
      "name": "My Dummy",
      "alias": null
    }
    """

  Scenario: Get a not found exception
    When I send a "GET" request to "/dummies/42"
    Then the response status code should be 404


  Scenario: Update a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummies/1" with body:
    """
    {
      "@id": "/dummies/1",
      "name": "A nice dummy",
      "jsonData": [{
          "key": "value1"
        },
        {
          "key": "value2"
        }
      ]
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "dummyFloat": null,
      "dummyPrice": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "jsonData": [
        {
          "key": "value1"
        },
        {
          "key": "value2"
        }
      ],
      "name_converted": null,
      "name": "A nice dummy",
      "alias": null
    }
    """

  Scenario: Update a resource with empty body
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "dummyFloat": null,
      "dummyPrice": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "jsonData": [
        {
          "key": "value1"
        },
        {
          "key": "value2"
        }
      ],
      "name_converted": null,
      "name": "A nice dummy",
      "alias": null
    }
    """

  Scenario: Delete a resource
    When I send a "DELETE" request to "/dummies/1"
    Then the response status code should be 204
    And the response should be empty
