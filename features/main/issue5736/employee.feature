@issue5736 @5736_employee
Feature: Resources, subresources and their subresources with uri variables that are not `id`
  As a client software developer
  I need to be able to update subresources and their deeper subresources

  @createSchema
  Scenario: PUT Team with POST Employee
    Given there are 2 companies
    And there are 2 teams in company 2
    And I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/issue5736_companies/2/issue5736_teams/2" with body:
    """
    {
      "name": "Team #2 - edited",
      "employees": [
        {
          "name": "Employee #1"
        }
      ]
    }
    """

    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Team",
          "@id": "/issue5736_companies/2/issue5736_teams/2",
          "@type": "Team",
          "id": 2,
          "company": "/issue5736_companies/2",
          "name": "Team #2 - edited",
          "employees": [
              {
                  "@id": "/issue5736_companies/2/issue5736_teams/2/employees/1",
                  "@type": "Employee",
                  "id": 1,
                  "team": "/issue5736_companies/2/issue5736_teams/2",
                  "name": "Employee #1"
              }
          ]
    }
    """

  @createSchema
  Scenario: PUT Team with PUT Employee
    Given there are 2 companies
    And there are 2 teams in company 2
    And there are 3 employees in team 2
    And I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/issue5736_companies/2/issue5736_teams/2" with body:
    """
    {
      "employees": [
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/1",
          "@type": "Employee",
          "id": 1,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #1"
        },
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/2",
          "@type": "Employee",
          "id": 2,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #2"
        },
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/3",
          "@type": "Employee",
          "id": 3,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #3 - edited"
        }
      ]
    }
    """

    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Team",
      "@id": "/issue5736_companies/2/issue5736_teams/2",
      "@type": "Team",
      "id": 2,
      "company": "/issue5736_companies/2",
      "name": "Team #2",
      "employees": [
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/1",
          "@type": "Employee",
          "id": 1,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #1"
        },
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/2",
          "@type": "Employee",
          "id": 2,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #2"
        },
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/3",
          "@type": "Employee",
          "id": 3,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #3 - edited"
        }
      ]
    }
    """

  @createSchema
  Scenario: PUT Team with DELETE Employee
    Given there are 2 companies
    And there are 2 teams in company 2
    And there are 3 employees in team 2
    And I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/issue5736_companies/2/issue5736_teams/2" with body:
    """
    {
      "employees": [
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/1",
          "@type": "Employee",
          "id": 1,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #1"
        },
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/2",
          "@type": "Employee",
          "id": 2,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #2"
        }
      ]
    }
    """

    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Team",
      "@id": "/issue5736_companies/2/issue5736_teams/2",
      "@type": "Team",
      "id": 2,
      "company": "/issue5736_companies/2",
      "name": "Team #2",
      "employees": [
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/1",
          "@type": "Employee",
          "id": 1,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #1"
        },
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/2",
          "@type": "Employee",
          "id": 2,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #2"
        }
      ]
    }
    """

  @createSchema
  Scenario: PUT Team with PUT Employee and DELETE Employee
    Given there are 2 companies
    And there are 2 teams in company 2
    And there are 3 employees in team 2
    And I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/issue5736_companies/2/issue5736_teams/2" with body:
    """
    {
      "employees": [
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/1",
          "@type": "Employee",
          "id": 1,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #1"
        },
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/2",
          "@type": "Employee",
          "id": 2,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #2 - edited"
        }
      ]
    }
    """

    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Team",
      "@id": "/issue5736_companies/2/issue5736_teams/2",
      "@type": "Team",
      "id": 2,
      "company": "/issue5736_companies/2",
      "name": "Team #2",
      "employees": [
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/1",
          "@type": "Employee",
          "id": 1,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #1"
        },
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/2",
          "@type": "Employee",
          "id": 2,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #2 - edited"
        }
      ]
    }
    """

  @createSchema
  Scenario: PUT Team with PUT Employee and DELETE Employee and POST Employee
    Given there are 2 companies
    And there are 2 teams in company 2
    And there are 3 employees in team 2
    And I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/issue5736_companies/2/issue5736_teams/2" with body:
    """
    {
      "employees": [
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/1",
          "@type": "Employee",
          "id": 1,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #1"
        },
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/2",
          "@type": "Employee",
          "id": 2,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #2 - edited"
        },
        {
          "name": "Employee #4"
        }
      ]
    }
    """

    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Team",
      "@id": "/issue5736_companies/2/issue5736_teams/2",
      "@type": "Team",
      "id": 2,
      "company": "/issue5736_companies/2",
      "name": "Team #2",
      "employees": [
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/1",
          "@type": "Employee",
          "id": 1,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #1"
        },
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/2",
          "@type": "Employee",
          "id": 2,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #2 - edited"
        },
        {
          "@id": "/issue5736_companies/2/issue5736_teams/2/employees/4",
          "@type": "Employee",
          "id": 4,
          "team": "/issue5736_companies/2/issue5736_teams/2",
          "name": "Employee #4"
        }
      ]
    }
    """
