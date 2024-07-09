Feature: Spec-compliant PUT support
  As a client software developer
  I need to be able to create or replace resources using the PUT HTTP method

  @createSchema
  Scenario: Create a new resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/standard_puts/5" with body:
    """
    {
      "foo": "a",
      "bar": "b"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/StandardPut",
      "@id": "/standard_puts/5",
      "@type": "StandardPut",
      "id": 5,
      "foo": "a",
      "bar": "b"
    }
    """

  Scenario: Create a new resource with JSON-LD attributes
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/standard_puts/6" with body:
    """
    {
      "@id": "/standard_puts/6",
      "@context": "/contexts/StandardPut",
      "@type": "StandardPut",
      "foo": "a",
      "bar": "b"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/StandardPut",
      "@id": "/standard_puts/6",
      "@type": "StandardPut",
      "id": 6,
      "foo": "a",
      "bar": "b"
    }
    """

  Scenario: Fails to create a new resource with the wrong JSON-LD @id
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/standard_puts/7" with body:
    """
    {
      "@id": "/dummies/6",
      "@context": "/contexts/StandardPut",
      "@type": "StandardPut",
      "foo": "a",
      "bar": "b"
    }
    """
    Then the response status code should be 400

  Scenario: Fails to create a new resource when the JSON-LD @id doesn't match the URI
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/standard_puts/7" with body:
    """
    {
      "@id": "/standard_puts/6",
      "@context": "/contexts/StandardPut",
      "@type": "StandardPut",
      "foo": "a",
      "bar": "b"
    }
    """
    Then the response status code should be 400

  Scenario: Replace an existing resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/standard_puts/5" with body:
    """
    {
      "foo": "c"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/StandardPut",
      "@id": "/standard_puts/5",
      "@type": "StandardPut",
      "id": 5,
      "foo": "c",
      "bar": ""
    }
    """

  @createSchema
  @!mongodb
  Scenario: Create a new resource identified by an uid
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/uid_identifieds/fbcf5910-d915-4f7d-ba39-6b2957c57335" with body:
    """
    {
      "name": "test"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/UidIdentified",
      "@id": "/uid_identifieds/fbcf5910-d915-4f7d-ba39-6b2957c57335",
      "@type": "UidIdentified",
      "id": "fbcf5910-d915-4f7d-ba39-6b2957c57335",
      "name": "test"
    }
    """

  @!mongodb
  Scenario: Replace an existing resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/uid_identifieds/fbcf5910-d915-4f7d-ba39-6b2957c57335" with body:
    """
    {
      "name": "bar"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/UidIdentified",
      "@id": "/uid_identifieds/fbcf5910-d915-4f7d-ba39-6b2957c57335",
      "@type": "UidIdentified",
      "id": "fbcf5910-d915-4f7d-ba39-6b2957c57335",
      "name": "bar"
    }
    """
