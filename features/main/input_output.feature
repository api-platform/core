Feature: DTO input and output
  In order to use a hypermedia API
  As a client software developer
  I need to be able to use DTOs on my resources as Input or Output objects.

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  @!mongodb
  Scenario: Fetch a collection of outputs with an entityClass as state option
    When I send a "GET" request to "/output_and_entity_classes"
    And the JSON node "hydra:member[0].@type" should be equal to "OutputAndEntityClassEntity"


