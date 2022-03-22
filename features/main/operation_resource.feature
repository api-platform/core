Feature: Resource operations
  In order to use the Resource Operation
  As a developer
  I should be able to persist data from a processor

  @php8
  @v3
  @createSchema
  @!mongodb
  Scenario: Create an operation resource
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/operation_resources" with body:
    """
    {
      "identifier": 1,
      "dummy": null,
      "name": "string"
    }
    """
    Then the response status code should be 201

  @php8
  @v3
  @!mongodb
  Scenario: Patch an operation resource
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/operation_resources/1" with body:
    """
    {"name": "Patched"}
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/OperationResource",
      "@id": "/operation_resources/1",
      "@type": "OperationResource",
      "identifier": 1,
      "name": "Patched"
    }
    """

  @php8
  @v3
  @!mongodb
  Scenario: Update an operation resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/operation_resources/1" with body:
    """
    {
      "name": "Modified"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/operation_resources/1"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/OperationResource",
      "@id": "/operation_resources/1",
      "@type": "OperationResource",
      "identifier": 1,
      "name": "Modified"
    }
    """
