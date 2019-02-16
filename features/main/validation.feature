Feature: Using validations groups
  As a client software developer
  I need to be able to use validation groups

  @createSchema
  Scenario: Create a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_validation" with body:
    """
    {
      "code": "My Dummy"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  @createSchema
  Scenario: Create a resource with validation
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_validation/validation_groups" with body:
    """
    {
      "code": "My Dummy"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/ConstraintViolationList",
      "@type": "ConstraintViolationList",
      "hydra:title": "An error occurred",
      "hydra:description": "name: This value should not be null.",
      "violations": [
         {
             "propertyPath": "name",
             "message": "This value should not be null."
         }
      ]
    }
    """
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  @createSchema
  Scenario: Create a resource with validation group sequence
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_validation/validation_sequence" with body:
    """
    {
      "code": "My Dummy"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/ConstraintViolationList",
      "@type": "ConstraintViolationList",
      "hydra:title": "An error occurred",
      "hydra:description": "title: This value should not be null.",
      "violations": [
         {
             "propertyPath": "title",
             "message": "This value should not be null."
         }
      ]
    }
    """
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
