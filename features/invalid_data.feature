Feature: Handle properly invalid data submitted to the API
  In order to have robust API
  As a client software developer
  I can send unsupported attributes that will be ignored

  @createSchema
  Scenario: Create a resource
    When I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Not existing",
      "unsupported": true
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyDate": null,
      "dummyPrice": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "jsonData": [],
      "name_converted": null,
      "name": "Not existing",
      "alias": null
    }
    """

  Scenario: Ignore invalid dates
    When I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Invalid date",
      "dummyDate": "Invalid"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"

  @dropSchema
  Scenario: Send non-array data when an array is expected
    When I send a "POST" request to "/dummies" with body:
        """
    {
      "name": "Invalid",
      "relatedDummies": "hello"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    """
    And the JSON should be equal to:
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/2",
      "@type": "Dummy",
      "name": "Invalid",
      "alias": null,
      "description": null,
      "dummyDate": null,
      "dummyPrice": null,
      "jsonData": [],
      "relatedDummy": null,
      "dummy": null,
      "relatedDummies": [],
      "name_converted": null
    }
    """
