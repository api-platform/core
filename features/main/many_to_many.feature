Feature: Add/remove on a ManytoMany relation
  In order to use a hypermedia API
  As a client software developer
  I need to be able to update ManytoMany relations between resources

  @createSchema
  Scenario: Add a DummyCar to the Brand
    Given there is a brand and 5 DummyCar
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/brands/1" with body:
    """
    {"car": ["/dummy_cars/1"]}
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Brand",
      "@id": "/brands/1",
      "@type": "Brand",
      "car": [
          "/dummy_cars/1"
      ],
      "id": 1
    }
    """

  Scenario: Add another DummyCar to the Brand
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/brands/1" with body:
    """
    {"car": ["/dummy_cars/1", "/dummy_cars/2"]}
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Brand",
      "@id": "/brands/1",
      "@type": "Brand",
      "car": [
          "/dummy_cars/1",
          "/dummy_cars/2"
      ],
      "id": 1
    }
    """

  @dropSchema
  Scenario: Remove the first car of the relation
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/brands/1" with body:
    """
    {"car": ["/dummy_cars/2"]}
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Brand",
      "@id": "/brands/1",
      "@type": "Brand",
      "car": [
          "/dummy_cars/2"
      ],
      "id": 1
    }
    """

