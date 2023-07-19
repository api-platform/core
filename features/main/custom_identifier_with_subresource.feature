Feature: Using custom parent identifier for resources
  In order to use an hypermedia API
  As a client software developer
  I need to be able to use custom identifiers and query resources

  @createSchema
  Scenario: Create a parent dummy
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/slug_parent_dummies" with body:
    """
    {
      "slug": "parent-dummy"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/SlugParentDummy",
      "@id": "/slug_parent_dummies/parent-dummy",
      "@type": "SlugParentDummy",
      "id": 1,
      "slug": "parent-dummy",
      "childDummies": []
    }
    """

  Scenario: Create a child dummy
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/slug_child_dummies" with body:
    """
    {
      "slug": "child-dummy",
      "parentDummy": "/slug_parent_dummies/parent-dummy"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/SlugChildDummy",
      "@id": "/slug_child_dummies/child-dummy",
      "@type": "SlugChildDummy",
      "id": 1,
      "slug": "child-dummy",
      "parentDummy": "/slug_parent_dummies/parent-dummy"
    }
    """

  Scenario: Get child dummies of parent dummy
    When I send a "GET" request to "/slug_parent_dummies/parent-dummy/child_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/SlugChildDummy",
      "@id": "/slug_parent_dummies/parent-dummy/child_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/slug_child_dummies/child-dummy",
          "@type": "SlugChildDummy",
          "id": 1,
          "slug": "child-dummy",
          "parentDummy": "/slug_parent_dummies/parent-dummy"
        }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: Get parent dummy of child dummy
    When I send a "GET" request to "/slug_child_dummies/child-dummy/parent_dummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/SlugParentDummy",
      "@id": "/slug_child_dummies/child-dummy/parent_dummy",
      "@type": "SlugParentDummy",
      "id": 1,
      "slug": "parent-dummy",
      "childDummies": [
          "/slug_child_dummies/child-dummy"
      ]
    }
    """
  @mongodb
  Scenario: Create a new study and analysis, and query analyses
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/studies" with body:
    """
    {
      "id": "64b703fc1d65f957cce5eb33",
      "content": "study for the app"
    }
    """
    Then the response status code should be 201
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Study",
      "@id": "/studies/64b703fc1d65f957cce5eb33",
      "@type": "Study",
      "id": "64b703fc1d65f957cce5eb33",
      "content": "study for the app"
    }
    """
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/studies/64b703fc1d65f957cce5eb33/analyses" with body:
    """
    {
      "id": "64b70696f2d88fe04a86f905",
      "content": "a",
      "study": "/studies/64b703fc1d65f957cce5eb33"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Analysis",
      "@id": "/analyses/64b70696f2d88fe04a86f905",
      "@type": "Analysis",
      "id": "64b70696f2d88fe04a86f905",
      "content": "a",
      "study": "/studies/64b703fc1d65f957cce5eb33"
    }
    """
    When I send a "GET" request to "/studies/64b703fc1d65f957cce5eb33/analyses"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Analysis",
      "@id": "/studies/64b703fc1d65f957cce5eb33/analyses",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/analyses/64b70696f2d88fe04a86f905",
          "@type": "Analysis",
          "id": "64b70696f2d88fe04a86f905",
          "content": "a",
          "study": "/studies/64b703fc1d65f957cce5eb33"
        }
      ],
      "hydra:totalItems": 1
    }
    """
