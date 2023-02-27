Feature: Default values of HTTP cache headers
  In order to make API responses cacheable
  As an API software developer
  I need to be able to set default cache headers values

  @createSchema
  Scenario: Cache headers default value
    When I send a "GET" request to "/relation_embedders"
    Then the response status code should be 200
    And the header "Etag" should be equal to '"7bfa587950d675e222660f68623f5f89"'
    And the header "Cache-Control" should be equal to "max-age=60, public, s-maxage=3600"
    And the header "Vary" should be equal to "Accept, Cookie"
