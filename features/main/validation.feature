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
    Then the response status code should be 422
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ConstraintViolationList",
      "@type": "ConstraintViolationList",
      "hydra:title": "An error occurred",
      "hydra:description": "name: This value should not be null.",
      "violations": [
         {
             "propertyPath": "name",
             "message": "This value should not be null.",
             "code": "ad32d13f-c3d4-423b-909a-857b961eb720"
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
    Then the response status code should be 422
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ConstraintViolationList",
      "@type": "ConstraintViolationList",
      "hydra:title": "An error occurred",
      "hydra:description": "title: This value should not be null.",
      "violations": [
         {
             "propertyPath": "title",
             "message": "This value should not be null.",
             "code": "ad32d13f-c3d4-423b-909a-857b961eb720"
         }
      ]
    }
    """
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  @createSchema
  Scenario: Create a resource with collectDenormalizationErrors
    When I add "Content-type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_collect_denormalization" with body:
    """
      {
        "foo": 3,
        "bar": "baz"
      }
    """
    Then the response status code should be 422
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ConstraintViolationList",
      "@type": "ConstraintViolationList",
      "hydra:title": "An error occurred",
      "hydra:description": "foo: The type of the \"foo\" attribute must be \"bool\", \"int\" given.\nbar: The type of the \"bar\" attribute must be \"int\", \"string\" given.",
      "violations": [
        {
          "propertyPath": "foo",
          "message": "The type of the \"foo\" attribute must be \"bool\", \"int\" given.",
          "code": "0"
        },
        {
          "propertyPath": "bar",
          "message": "The type of the \"bar\" attribute must be \"int\", \"string\" given.",
          "code": "0"
        }
      ]
    }
    """
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
