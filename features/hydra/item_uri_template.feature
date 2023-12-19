@!mongodb
@v3
Feature: Exposing a collection of objects should use the specified operation to generate the IRI
  Background:
    Given I add "Accept" header equal to "application/ld+json"

  Scenario: Get a collection of objects without any itemUriTemplate should generate the IRI from the first Get operation
    When I send a "GET" request to "/cars"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "hydra:member", "hydra:totalItems"],
      "properties": {
        "@context": {"pattern": "^/contexts/Car$"},
        "@id": {"pattern": "^/cars$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "minItems": 2,
          "maxItems": 2,
          "uniqueItems": true,
          "items": {
            "type": "object",
            "additionalProperties": false,
            "required": ["@id", "@type", "id", "owner"],
            "properties": {
              "@id": {"pattern": "^/cars/.+$"},
              "@type": {"pattern": "^Car$"},
              "id": {"type": "string"},
              "owner": {"type": "string"}
            }
          }
        },
        "hydra:totalItems": {"type": "integer", "minimum": 2, "maximum": 2}
      }
    }
    """

  Scenario: Get a collection of objects with an itemUriTemplate should generate the IRI from the correct operation
    When I send a "GET" request to "/brands/renault/cars"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "hydra:member", "hydra:totalItems"],
      "properties": {
        "@context": {"pattern": "^/contexts/Car$"},
        "@id": {"pattern": "^/brands/renault/cars$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "minItems": 2,
          "maxItems": 2,
          "uniqueItems": true,
          "items": {
            "type": "object",
            "additionalProperties": false,
            "required": ["@id", "@type", "id", "owner"],
            "properties": {
              "@id": {"pattern": "^/brands/renault/cars/.+$"},
              "@type": {"pattern": "^Car$"},
              "id": {"type": "string"},
              "owner": {"type": "string"}
            }
          }
        },
        "hydra:totalItems": {"type": "integer", "minimum": 2, "maximum": 2}
      }
    }
    """

  Scenario: Create an object without an itemUriTemplate should generate the IRI from the first Get operation
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/cars" with body:
    """
    {
      "owner": "Vincent"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["@id", "@type", "id", "owner"],
      "properties": {
        "@context": {"pattern": "^/contexts/Car$"},
        "@id": {"pattern": "^/cars/.+$"},
        "@type": {"pattern": "^Car$"},
        "id": {"type": "string"},
        "owner": {"type": "string"}
      }
    }
    """

  Scenario: Create an object with an itemUriTemplate should generate the IRI from the correct operation
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/brands/renault/cars" with body:
    """
    {
      "owner": "Vincent"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "additionalProperties": false,
      "required": ["@id", "@type", "id", "owner"],
      "properties": {
        "@context": {"pattern": "^/contexts/Car$"},
        "@id": {"pattern": "^/brands/renault/cars/.+$"},
        "@type": {"pattern": "^Car$"},
        "id": {"type": "string"},
        "owner": {"type": "string"}
      }
    }
    """

  Scenario: Get a collection referencing another resource for its IRI
    When I send a "GET" request to "/item_referenced_in_collection"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context":"/contexts/CollectionReferencingItem",
      "@id":"/item_referenced_in_collection",
      "@type":"hydra:Collection",
      "hydra:member":[
        {
          "@id":"/item_referenced_in_collection/a",
          "@type":"CollectionReferencingItem",
          "id":"a",
          "name":"hello"
        },
        {
          "@id":"/item_referenced_in_collection/b",
          "@type":"CollectionReferencingItem",
          "id":"b",
          "name":"you"
        }
      ],
      "hydra:totalItems":2
    }
    """

  Scenario: Get a collection referencing an itemUriTemplate
    When I send a "GET" request to "/issue5662/books/a/reviews"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context":"/contexts/Review",
      "@id":"/issue5662/books/a/reviews",
      "@type":"hydra:Collection",
      "hydra:member":[
        {
          "@id":"/issue5662/books/a/reviews/1",
          "@type":"Review",
          "book":"/issue5662/books/a",
          "id":1,
          "body":"Best book ever!"
        },
        {
          "@id":"/issue5662/books/b/reviews/2",
          "@type":"Review",
          "book":"/issue5662/books/b",
          "id":2,
          "body":"Worst book ever!"
        }
      ],
      "hydra:totalItems":2
    }
    """

  Scenario: Get a collection referencing an invalid itemUriTemplate
    When I send a "GET" request to "/issue5662/admin/reviews"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/Review",
        "@id": "/issue5662/admin/reviews",
        "@type": "hydra:Collection",
        "hydra:totalItems": 2,
        "hydra:member": [
            {
                "@id": "/issue5662/admin/reviews/1",
                "@type": "Review",
                "book": "/issue5662/books/a",
                "id": 1,
                "body": "Best book ever!"
            },
            {
                "@id": "/issue5662/admin/reviews/2",
                "@type": "Review",
                "book": "/issue5662/books/b",
                "id": 2,
                "body": "Worst book ever!"
            }
        ]
    }
    """

  Scenario: Create an object with an itemUriTemplate should generate the IRI according to the specified itemUriTemplate
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/issue5662/books/a/reviews" with body:
    """
    {
      "body": "Good book"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "@id" should be equal to "/issue5662/books/a/reviews/0"
