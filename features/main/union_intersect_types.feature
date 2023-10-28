Feature: Union/Intersect types

  Scenario Outline: Create a resource with union type
    When I add "Content-Type" header equal to "application/ld+json"
    And I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/issue-5452/books" with body:
    """
    {
      "number": <number>,
      "isbn": "978-3-16-148410-0"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@type": {
          "type": "string",
          "pattern": "^Book$"
        },
        "@context": {
          "type": "string",
          "pattern": "^/contexts/Book$"
        },
        "@id": {
          "type": "string",
          "pattern": "^/.well-known/genid/.+$"
        },
        "number": {
          "type": "<type>"
        },
        "isbn": {
          "type": "string",
          "pattern": "^978-3-16-148410-0$"
        }
      },
      "required": [
        "@type",
        "@context",
        "@id",
        "number",
        "isbn"
      ]
    }
    """
    Examples:
    | number | type    |
    | "1"    | string  |
    | 1      | integer |

  Scenario: Create a resource with valid intersect type
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/issue-5452/books" with body:
    """
    {
      "number": 1,
      "isbn": "978-3-16-148410-0",
      "author": "/issue-5452/authors/1"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@type": {
          "type": "string",
          "pattern": "^Book$"
        },
        "@context": {
          "type": "string",
          "pattern": "^/contexts/Book$"
        },
        "@id": {
          "type": "string",
          "pattern": "^/.well-known/genid/.+$"
        },
        "number": {
          "type": "integer"
        },
        "isbn": {
          "type": "string",
          "pattern": "^978-3-16-148410-0$"
        },
        "author": {
          "type": "string",
          "pattern": "^/issue-5452/authors/1$"
        }
      },
      "required": [
        "@type",
        "@context",
        "@id",
        "number",
        "isbn",
        "author"
      ]
    }
    """

  Scenario: Create a resource with invalid intersect type
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/issue-5452/books" with body:
    """
    {
      "number": 1,
      "isbn": "978-3-16-148410-0",
      "library": "/issue-5452/libraries/1"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the JSON node "hydra:description" should be equal to 'Could not denormalize object of type "ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452\ActivableInterface", no supporting normalizer found.'
