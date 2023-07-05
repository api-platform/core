Feature: Update an embed collection with PUT
  As a client software developer
  I need to be able to update an embed collection

  Background:
    Given I add "Content-Type" header equal to "application/ld+json"

  @createSchema
  @!mongodb
  Scenario: Update embed collection
    And I send a "POST" request to "/issue5584_employees" with body:
    """
    {"name": "One"}
    """
    Then I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/issue5584_employees" with body:
    """
    {"name": "Two"}
    """
    Then print last JSON response
    Then I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/issue5584_businesses" with body:
    """
    {"name": "Business"}
    """
    Then I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/issue5584_businesses/1" with body:
    """
    {"name": "Business", "businessEmployees": [{"@id": "/issue5584_employees/1", "id": 1}, {"@id": "/issue5584_employees/2", "id": 2}]}
    """
    And the JSON node "businessEmployees[0].name" should contain 'One'
    And the JSON node "businessEmployees[1].name" should contain 'Two'
