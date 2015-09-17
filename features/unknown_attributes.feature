Feature: Ignore unknown attributes
  In order to be robust
  As a client software developer
  I can send unsupported attributes that will be ignored

  @createSchema
  @dropSchema
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
      "name": "Not existing",
      "alias": null,
      "dummyDate": null,
      "jsonData": [],
      "dummy": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "name_converted": null
    }
    """
