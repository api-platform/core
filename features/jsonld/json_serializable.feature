Feature: JSON-LD using JsonSerializable types
  In order to use JsonSerializable in resource and non-resource types
  As a developer
  I should be able to serialize objects of JsonSerializable type.

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  @createSchema
  Scenario: Create a Content
    When I send a "POST" request to "/contents" with body:
    """
    {
      "contentType": "homepage",
      "fields": [
        {
          "name": "title",
          "value": "Labore reprehenderit dolorem repellendus asperiores."
        },
        {
          "name": "content",
          "value": "Minus sed repellendus corporis nemo. Aut aut veniam at aut aliquid. Architecto tempora quia neque numquam voluptas sint est delectus.\n\nUnde voluptatem animi non ut aut dicta. Omnis vero dolorum aliquid laudantium magni asperiores. Et tempora eveniet soluta modi occaecati.\n\nEa dolorum tenetur voluptatum temporibus illo fuga. Quibusdam et doloribus debitis omnis sed. Tempora in aperiam ullam non odit. Praesentium sunt accusantium dolorem commodi labore eum nostrum quia."
        }
      ]
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Content",
      "@id": "/contents/1",
      "@type": "Content",
      "id": 1,
      "contentType": "homepage",
      "status": {
        "key": "DRAFT",
        "value": "draft"
      },
      "fieldValues": {
        "title": "Labore reprehenderit dolorem repellendus asperiores.",
        "content": "Minus sed repellendus corporis nemo. Aut aut veniam at aut aliquid. Architecto tempora quia neque numquam voluptas sint est delectus.\n\nUnde voluptatem animi non ut aut dicta. Omnis vero dolorum aliquid laudantium magni asperiores. Et tempora eveniet soluta modi occaecati.\n\nEa dolorum tenetur voluptatum temporibus illo fuga. Quibusdam et doloribus debitis omnis sed. Tempora in aperiam ullam non odit. Praesentium sunt accusantium dolorem commodi labore eum nostrum quia."
      }
    }
    """

  Scenario: Retrieve a Content
    When I send a "GET" request to "/contents/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Content",
      "@id": "/contents/1",
      "@type": "Content",
      "id": 1,
      "contentType": "homepage",
      "status": {
        "key": "DRAFT",
        "value": "draft"
      },
      "fieldValues": {
        "title": "Labore reprehenderit dolorem repellendus asperiores.",
        "content": "Minus sed repellendus corporis nemo. Aut aut veniam at aut aliquid. Architecto tempora quia neque numquam voluptas sint est delectus.\n\nUnde voluptatem animi non ut aut dicta. Omnis vero dolorum aliquid laudantium magni asperiores. Et tempora eveniet soluta modi occaecati.\n\nEa dolorum tenetur voluptatum temporibus illo fuga. Quibusdam et doloribus debitis omnis sed. Tempora in aperiam ullam non odit. Praesentium sunt accusantium dolorem commodi labore eum nostrum quia."
      }
    }
    """
