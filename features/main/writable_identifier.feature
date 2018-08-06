Feature: Writable Identifier
  In order to use a hypermedia API
  As a client software developer
  I need to be able to create resources with a writable identifier

  @createSchema
  Scenario: Create a resource with a custom identifier
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/writable_ids" with body:
    """
    {
      "id": "7995560a-4e09-4bba-8950-963528d004f0",
      "name": "Foo"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/WritableId",
      "@id": "/writable_ids/7995560a-4e09-4bba-8950-963528d004f0",
      "@type": "WritableId",
      "id": "7995560a-4e09-4bba-8950-963528d004f0",
      "name": "Foo"
    }
    """

  @dropSchema
  Scenario: Update a resource with a custom identifier
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/writable_ids/7995560a-4e09-4bba-8950-963528d004f0" with body:
    """
    {
      "@id": "/writable_ids/7995560a-4e09-4bba-8950-963528d004f1",
      "id": "7995560a-4e09-4bba-8950-963528d004f1",
      "name": "Foo"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/WritableId",
      "@id": "/writable_ids/7995560a-4e09-4bba-8950-963528d004f1",
      "@type": "WritableId",
      "id": "7995560a-4e09-4bba-8950-963528d004f1",
      "name": "Foo"
    }
    """
