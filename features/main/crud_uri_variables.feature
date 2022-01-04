Feature: Uri Variables

  @createSchema
  @php8
  @v3
  Scenario: Create a resource Company
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/companies" with body:
    """
    {
      "name": "Foo Company 1"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/companies/1"
    And the header "Location" should be equal to "/companies/1"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Company",
      "@id": "/companies/1",
      "@type": "Company",
      "id": 1,
      "name": "Foo Company 1",
      "employees": null
    }
    """

  @php8
  @v3
  Scenario: Create a second resource Company
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/companies" with body:
    """
    {
      "name": "Foo Company 2"
    }
    """
    Then the response status code should be 201

  @php8
  @v3
  Scenario: Create first Employee
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/employees" with body:
    """
    {
      "name": "foo",
      "company": "/companies/1"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/companies/1/employees/1"
    And the header "Location" should be equal to "/companies/1/employees/1"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Employee",
      "@id": "/companies/1/employees/1",
      "@type": "Employee",
      "id": 1,
      "name": "foo",
      "company": "/companies/1"
    }
    """

  @php8
  @v3
  Scenario: Create second Employee
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/employees" with body:
    """
    {
      "name": "foo2",
      "company": "/companies/2"
    }
    """
    Then the response status code should be 201

  @php8
  @v3
  Scenario: Create third Employee
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/employees" with body:
    """
    {
      "name": "foo3",
      "company": "/companies/2"
    }
    """
    Then the response status code should be 201

  @php8
  @v3
  Scenario: Retrieve the collection of employees
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/companies/2/employees"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/Employee",
        "@id": "/companies/2/employees",
        "@type": "hydra:Collection",
        "hydra:member": [
            {
                "@id": "/companies/2/employees/2",
                "@type": "Employee",
                "id": 2,
                "name": "foo2",
                "company": "/companies/2"
            },
            {
                "@id": "/companies/2/employees/3",
                "@type": "Employee",
                "id": 3,
                "name": "foo3",
                "company": "/companies/2"
            }
        ],
        "hydra:totalItems": 2
    }
    """
    When I send the following GraphQL request:
    """
    {
      companies {
        edges {
          node {
            name
            employees {
              edges {
                node {
                  name
                }
              }
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.companies.edges[0].node.name" should be equal to "Foo Company 1"
    And the JSON node "data.companies.edges[0].node.employees.edges" should have 1 element
    And the JSON node "data.companies.edges[0].node.employees.edges[0].node.name" should be equal to "foo"
    And the JSON node "data.companies.edges[1].node.name" should be equal to "Foo Company 2"
    And the JSON node "data.companies.edges[1].node.employees.edges" should have 2 elements
    And the JSON node "data.companies.edges[1].node.employees.edges[0].node.name" should be equal to "foo2"
    And the JSON node "data.companies.edges[1].node.employees.edges[1].node.name" should be equal to "foo3"

  @php8
  @v3
  Scenario: Retrieve the company of an employee
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/employees/1/company"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/Company",
        "@id": "/companies/1",
        "@type": "Company",
        "id": 1,
        "name": "Foo Company 1",
        "employees": null
    }
    """

  @php8
  @v3
  Scenario: Retrieve an employee
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/companies/1/employees/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/Employee",
        "@id": "/companies/1/employees/1",
        "@type": "Employee",
        "id": 1,
        "name": "foo",
        "company": "/companies/1"
    }
    """

  @php8
  @v3
  Scenario: Trying to get an employee of wrong company
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/companies/1/employees/2"
    Then the response status code should be 404
