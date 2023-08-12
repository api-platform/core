Feature: Resources, subresources and their subresources with uri variables that are not `id`
  @issue5736
  As a client software developer
  I need to be able to update subresources and their deeper subresources

  @createSchema
  Scenario: PUT Team with POST Employee
    Given I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/issue5736_companies/" with body:
    """
    {
      "name": "Company 1"
    }
    """

    And I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/issue5736_companies/1/issue5736_teams" with body:
    """
    {
      "name": "Team 1",
      "employees": [
        {
          "name": "Employee 1"
        },
        {
          "name": "Employee 2"
        },
        {
          "name": "Employee 3"
        }
      ]
    }
    """

    And I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/issue5736_companies/1/issue5736_teams/1" with body:
    """
    {
      "name": "Team 1",
      "employees": [
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/1",
          "name": "Employee 1"
        },
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/2",
          "name": "Employee 2"
        },
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/3",
          "name": "Employee 3"
        },
        {
          "name": "Employee 4"
        }
      ]
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@id": "/issue5736_companies/1/issue5736_teams/1",
      "@type": "Issue5736Team",
      "name": "Team 1",
      "employees": [
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/1",
          "@type": "Issue5736Employee",
          "name": "Employee 1"
        },
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/2",
          "@type": "Issue5736Employee",
          "name": "Employee 2"
        },
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/3",
          "@type": "Issue5736Employee",
          "name": "Employee 3"
        },
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/4",
          "@type": "Issue5736Employee",
          "name": "Employee 4"
        }
      ]
    }
    """

  @createSchema
  Scenario: PUT Team with PUT Employee
    Given I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/issue5736_companies/" with body:
    """
    {
      "name": "Company 1"
    }
    """

    And I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/issue5736_companies/1/issue5736_teams" with body:
    """
    {
      "name": "Team 1",
      "employees": [
        {
          "name": "Employee 1"
        },
        {
          "name": "Employee 2"
        },
        {
          "name": "Employee 3"
        }
      ]
    }
    """

    And I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/issue5736_companies/1/issue5736_teams/1" with body:
    """
    {
      "name": "Team 1",
      "employees": [
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/1",
          "name": "Employee 1"
        },
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/2",
          "name": "Employee 2"
        },
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/3",
          "name": "Employee 3 edited"
        }
      ]
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@id": "/issue5736_companies/1/issue5736_teams/1",
      "@type": "Issue5736Team",
      "name": "Team 1",
      "employees": [
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/1",
          "@type": "Issue5736Employee",
          "name": "Employee 1"
        },
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/2",
          "@type": "Issue5736Employee",
          "name": "Employee 2"
        },
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/3",
          "@type": "Issue5736Employee",
          "name": "Employee 3 edited"
        }
      ]
    }
    """

  @createSchema
  Scenario: PUT Team with DELETE Employee
    Given I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/issue5736_companies/" with body:
    """
    {
      "name": "Company 1"
    }
    """

    And I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/issue5736_companies/1/issue5736_teams" with body:
    """
    {
      "name": "Team 1",
      "employees": [
        {
          "name": "Employee 1"
        },
        {
          "name": "Employee 2"
        },
        {
          "name": "Employee 3"
        }
      ]
    }
    """

    And I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/issue5736_companies/1/issue5736_teams/1" with body:
    """
    {
      "name": "Team 1",
      "employees": [
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/1",
          "name": "Employee 1"
        },
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/2",
          "name": "Employee 2"
        }
      ]
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@id": "/issue5736_companies/1/issue5736_teams/1",
      "@type": "Issue5736Team",
      "name": "Team 1",
      "employees": [
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/1",
          "@type": "Issue5736Employee",
          "name": "Employee 1"
        },
        {
          "@id": "/issue5736_companies/1/issue5736_teams/1/issue5736_employees/2",
          "@type": "Issue5736Employee",
          "name": "Employee 2"
        }
      ]
    }
    """
