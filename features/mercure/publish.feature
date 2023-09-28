Feature: Mercure publish support
  In order to publish an Update to the Mercure hub
  As a developer
  I need to specify which topics I want to send the Update on

  @createSchema
  # see https://github.com/api-platform/core/issues/5074
  Scenario: Checks that Mercure Updates are dispatched properly
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    When I send a "POST" request to "/issue5074/mercure_with_topics" with body:
    """
    {
      "name": "Hello World!",
      "description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    Then 1 Mercure update should have been sent
    And the Mercure update should have topics:
      | http://example.com/issue5074/mercure_with_topics/1 |
    And the Mercure update should have data:
    """
    {
        "@context": "/contexts/MercureWithTopics",
        "@id": "/issue5074/mercure_with_topics/1",
        "@type": "MercureWithTopics",
        "id": 1,
        "name": "Hello World!"
    }
    """

  Scenario: Checks that Mercure Updates are dispatched following topics configured with expression language
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    When I send a "POST" request to "/mercure_with_topics_and_get_operations" with body:
    """
    {
      "name": "Hello World!"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    Then 1 Mercure update should have been sent
    And the Mercure update should have topics:
      | http://example.com/mercure_with_topics_and_get_operations/1                 |
      | http://example.com/custom_resource/mercure_with_topics_and_get_operations/1 |
    And the Mercure update should have data:
    """
    {
        "@context": "/contexts/MercureWithTopicsAndGetOperation",
        "@id": "/mercure_with_topics_and_get_operations/1",
        "@type": "MercureWithTopicsAndGetOperation",
        "id": 1,
        "name": "Hello World!"
    }
    """
