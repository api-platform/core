Feature: Planning handling
  In order to use an object as primary key
  As a developer
  I should be able serialize types with object primary key.

  @createSchema
  @dropSchema
  Scenario: Get a resource containing a raw object
    When  I send a "GET" request to "/object_primary_keys/2017-01-30"
    Then print last JSON response
  Scenario: Get a resource
    When I send a "GET" request to "/object_primary_keys/2017-01-30"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ObjectPrimaryKey",
      "@id": "/object_primary_keys/2017-01-30",
      "@type": "ObjectPrimaryKey",
      "date": "2017-01-30"
    }
    """
