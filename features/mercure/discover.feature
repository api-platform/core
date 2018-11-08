Feature: Mercure discovery support
  In order to let the client discovering the Mercure hub
  As a client software developer
  I need to retrieve the hub URL through a Link HTTP header

  @createSchema
  Scenario: Checks that the Mercure Link is added
    Given I send a "GET" request to "/dummy_mercures"
    Then the header "Link" should be equal to '<http://example.com/docs.jsonld>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation",<https://demo.mercure.rocks/hub>; rel="mercure"'

  Scenario: Checks that the Mercure Link is not added on endpoints where updates are not dispatched
    Given I send a "GET" request to "/"
    Then the header "Link" should be equal to '<http://example.com/docs.jsonld>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"'
