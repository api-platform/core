@mongodb
Feature: Filters on collections
  In order to retrieve large collections of resources
  As a client software developer
  I need to retrieve collections with filters

  @createSchema
  Scenario: Error when getting collection with nested properties if references are not correctly stored (owning side)
    Given there is a dummy object with a fourth level relation
    When I send a "GET" request to "/dummies?relatedDummy.thirdLevel.badFourthLevel.level=4"
    Then the response status code should be 500
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "hydra:Error"
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "hydra:description" should be equal to "Cannot use reference 'badFourthLevel' in class 'ThirdLevel' for lookup or graphLookup: dbRef references are not supported."
    And the JSON node "trace" should exist

  Scenario: Error when getting collection with nested properties if references are not correctly stored (not owning side)
    When I send a "GET" request to "/dummies?relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level=3"
    Then the response status code should be 500
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "hydra:Error"
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "hydra:description" should be equal to "Cannot use reference 'badThirdLevel' in class 'FourthLevel' for lookup or graphLookup: dbRef references are not supported."
    And the JSON node "trace" should exist
