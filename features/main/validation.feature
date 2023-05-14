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

  @!mongodb
  @createSchema
  Scenario: Create a resource with collectDenormalizationErrors
    When I add "Content-type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_collect_denormalization" with body:
    """
      {
        "foo": 3,
        "bar": "baz",
        "qux": true,
        "uuid": "y",
        "relatedDummy": 8,
        "relatedDummies": 76
      }
    """
    Then the response status code should be 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ConstraintViolationList",
      "@type": "ConstraintViolationList",
      "hydra:title": "An error occurred",
      "hydra:description": "This value should be of type unknown.\nqux: This value should be of type string.\nfoo: This value should be of type bool.\nbar: This value should be of type int.\nuuid: This value should be of type uuid.\nrelatedDummy: This value should be of type array|string.\nrelatedDummies: This value should be of type array.",
      "violations": [
        {
          "propertyPath": "",
          "message": "This value should be of type unknown.",
          "code": "0",
          "hint": "Failed to create object because the class misses the \"baz\" property."
        },
        {
          "propertyPath": "qux",
          "message": "This value should be of type string.",
          "code": "0"
        },
        {
          "propertyPath": "foo",
          "message": "This value should be of type bool.",
          "code": "0"
        },
        {
          "propertyPath": "bar",
          "message": "This value should be of type int.",
          "code": "0"
        },
        {
          "propertyPath": "uuid",
          "message": "This value should be of type uuid.",
          "code": "0",
          "hint": "Invalid UUID string: y"
        },
        {
          "propertyPath": "relatedDummy",
          "message": "This value should be of type array|string.",
          "code": "0"
        },
        {
          "propertyPath": "relatedDummies",
          "message": "This value should be of type array.",
          "code": "0"
        }
      ]
    }
    """
