@!mongodb
Feature: Error handling
  In order to be able to handle error client side
  As a client software developer
  I need to retrieve an Hydra serialization of errors
  That is compatible with the JSON Problem specification

  Scenario: Get an rfc 7807 error
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/exception_problems" with body:
    """
    {}
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the header "Link" should contain '<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"'
    And the JSON node "type" should exist
    And the JSON node "title" should not exists
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "detail" should exist
    And the JSON node "description" should not exist
    And the JSON node "hydra:description" should exist
    And the JSON node "trace" should exist
    And the JSON node "status" should exist
    And the JSON node "@context" should exist

  Scenario: Get validation constraint violations
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_problems" with body:
    """
    {}
    """
    Then the response status code should be 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
          "@context": "/contexts/ConstraintViolation",
          "@id": "/validation_errors/c1051bb4-d103-4f74-8988-acbcafc7fdc3",
          "@type": "ConstraintViolation",
          "status": 422,
          "violations": [
              {
                  "propertyPath": "name",
                  "message": "This value should not be blank.",
                  "code": "c1051bb4-d103-4f74-8988-acbcafc7fdc3"
              }
          ],
          "detail": "name: This value should not be blank.",
          "hydra:title": "An error occurred",
          "hydra:description": "name: This value should not be blank.",
          "type": "/validation_errors/c1051bb4-d103-4f74-8988-acbcafc7fdc3"
      }
    """

  Scenario: Get an rfc 7807 bad request error
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/exception_problems" with body:
    """
    {}
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the header "Link" should contain '<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"'
    And the JSON node "@context" should exist
    And the JSON node "type" should exist
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "detail" should exist

  Scenario: Get an rfc 7807 not found error
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/does_not_exist" with body:
    """
    {}
    """
    Then the response status code should be 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the header "Link" should contain '<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"'
    And the JSON node "@context" should exist
    And the JSON node "type" should exist
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "detail" should exist
    And the JSON node "description" should not exist

  Scenario: Get an rfc 7807 bad method error
    When I add "Content-Type" header equal to "application/ld+json"
    And I add "Accept" header equal to "application/ld+json"
    And I send a "PATCH" request to "/dummy_problems" with body:
    """
    {}
    """
    Then the response status code should be 405
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the header "Link" should contain '<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"'
    And the JSON node "@context" should exist
    And the JSON node "type" should exist
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "detail" should exist
    And the JSON node "description" should not exist

  Scenario: Get an rfc 7807 validation error
    When I add "Content-Type" header equal to "application/ld+json"
    And I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/validation_exception_problems" with body:
    """
    {}
    """
    Then the response status code should be 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the header "Link" should contain '<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"'
    And the JSON node "@context" should exist
    And the JSON node "type" should exist
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "detail" should exist
    And the JSON node "violations" should exist

  Scenario: Get an rfc 7807 error
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/exception_problems_without_prefix" with body:
    """
    {}
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the header "Link" should contain '<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"'
    And the JSON node "type" should exist
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "detail" should exist
    And the JSON node "description" should not exist
    And the JSON node "trace" should exist
    And the JSON node "status" should exist
