@orm_v3
Feature: Create-Retrieve-Update-Delete
  In order to use an hypermedia API
  As a client software developer
  I need to be able to retrieve, create, update and delete JSON-LD encoded resources.

  @createSchema
  Scenario: Get a resource in v3 configured in YAML
    Given there is a Program
    When I send a "GET" request to "/programs/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Program",
      "@id": "/programs/1",
      "@type": "Program",
      "id": 1,
      "name": "Lorem ipsum 1",
      "date": "2015-03-01T10:00:00+00:00",
      "author": "/users/1"
    }
    """

  Scenario: Get a collection resource in v3 configured in YAML
    Given there are 3 Programs
    When I send a "GET" request to "/users/1/programs"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Program",
      "@id": "/users/1/programs",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/users/1/programs/1",
          "@type": "Program",
          "id": 1,
          "name": "Lorem ipsum 1",
          "date": "2015-03-01T10:00:00+00:00",
          "author": "/users/1"
        },
        {
          "@id": "/users/1/programs/2",
          "@type": "Program",
          "id": 1,
          "name": "Lorem ipsum 2",
          "date": "2015-03-02T10:00:00+00:00",
          "author": "/users/1"
        },
        {
          "@id": "/users/1/programs/3",
          "@type": "Program",
          "id": 1,
          "name": "Lorem ipsum 3",
          "date": "2015-03-03T10:00:00+00:00",
          "author": "/users/1"
        }
      ],
      "hydra:totalItems": 3
    }
    """

  Scenario: Get a resource in v3 configured in XML
    Given there is a Comment
    When I send a "GET" request to "/comments/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Comment",
      "@id": "/comments/1",
      "@type": "Comment",
      "id": 1,
      "comment": "Lorem ipsum dolor sit amet 1",
      "date": "2015-03-01T10:00:00+00:00",
      "author": "/users/1"
    }
    """

  Scenario: Get a collection resource in v3 configured in XML
    Given there are 3 Comments
    When I send a "GET" request to "/users/1/comments"
    And print last response
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/users/1/comments",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/users/1/comments/1",
          "@type": "Comment",
          "id": 1,
          "comment": "Lorem ipsum dolor sit amet 1",
          "date": "2015-03-01T10:00:00+00:00",
          "author": "/users/1"
        },
        {
          "@id": "/users/1/comments/2",
          "@type": "Comment",
          "id": 1,
          "comment": "Lorem ipsum dolor sit amet 2",
          "date": "2015-03-02T10:00:00+00:00",
          "author": "/users/1"
        },
        {
          "@id": "/users/1/comments/3",
          "@type": "Comment",
          "id": 1,
          "comment": "Lorem ipsum dolor sit amet 3",
          "date": "2015-03-03T10:00:00+00:00",
          "author": "/users/1"
        }
      ],
      "hydra:totalItems": 3
    }
    """
