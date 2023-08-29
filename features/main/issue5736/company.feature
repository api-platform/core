@issue5736 @5736_company
Feature: Resources, subresources and their subresources with uri variables that are not `id`
  As a client software developer
  I need to be able to update subresources and their deeper subresources

  @createSchema
  Scenario: GET Companies collection
    Given there are 3 companies
    And I send a "GET" request to "/issue5736_companies"

    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/Company",
        "@id": "/issue5736_companies",
        "@type": "hydra:Collection",
        "hydra:totalItems": 3,
        "hydra:member": [
            {
                "@id": "/issue5736_companies/1",
                "@type": "Company",
                "id": 1,
                "name": "Company #1"
            },
            {
                "@id": "/issue5736_companies/2",
                "@type": "Company",
                "id": 2,
                "name": "Company #2"
            },
            {
                "@id": "/issue5736_companies/3",
                "@type": "Company",
                "id": 3,
                "name": "Company #3"
            }
        ]
    }
    """

  @createSchema
  Scenario: POST Company
    Given I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/issue5736_companies" with body:
    """
    {
      "name": "Company 1"
    }
    """

    Then the response status code should be 201
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Company",
      "@id": "/issue5736_companies/1",
      "@type": "Company",
      "id": 1,
      "name": "Company 1"
    }
    """

  @createSchema
  Scenario: GET Company
    Given there are 3 companies
    Given I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/issue5736_companies/1"

    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Company",
      "@id": "/issue5736_companies/1",
      "@type": "Company",
      "id": 1,
      "name": "Company #1"
    }
    """

  @createSchema
  Scenario: PUT Company
    Given there are 3 companies
    Given I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/issue5736_companies/1" with body:
    """
    {
      "name": "Company 1 - edited"
    }
    """

    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Company",
      "@id": "/issue5736_companies/1",
      "@type": "Company",
      "id": 1,
      "name": "Company 1 - edited"
    }
    """
