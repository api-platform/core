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
    And the JSON should be a superset of:
    """
    {
      "@context": "/contexts/ConstraintViolation",
      "@type": "ConstraintViolation",
      "detail": "name: This value should not be null.",
      "violations": [
         {
             "propertyPath": "name",
             "message": "This value should not be null.",
             "code": "ad32d13f-c3d4-423b-909a-857b961eb720"
         }
      ]
    }
    """
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"

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
    And the JSON should be a superset of:
    """
    {
      "@context": "/contexts/ConstraintViolation",
      "@type": "ConstraintViolation",
      "detail": "title: This value should not be null.",
      "violations": [
         {
             "propertyPath": "title",
             "message": "This value should not be null.",
             "code": "ad32d13f-c3d4-423b-909a-857b961eb720"
         }
      ]
    }
    """
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"

  @createSchema
  Scenario: Create a resource with serializedName property
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "dummy_validation_serialized_name" with body:
    """
    {
      "code": "My Dummy"
    }
    """
    Then the response status code should be 422
    And the response should be in JSON
    And the JSON node "violations[0].message" should be equal to "This value should not be null."
    And the JSON node "violations[0].propertyPath" should be equal to "test"
    And the JSON node "detail" should be equal to "test: This value should not be null."
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"

  @createSchema
  @!mongodb
  Scenario: Get violations constraints
    When I add "Accept" header equal to "application/json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/issue5912s" with body:
    """
    {
      "title": ""
    }
    """
    Then the response status code should be 422
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "status": 422,
      "violations": [
        {
          "propertyPath": "title",
          "message": "This value should not be blank.",
          "code": "c1051bb4-d103-4f74-8988-acbcafc7fdc3"
        }
      ],
      "detail": "title: This value should not be blank.",
      "type": "/validation_errors/c1051bb4-d103-4f74-8988-acbcafc7fdc3",
      "title": "An error occurred"
    }
    """
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"

