Feature: Default values of HTTP cache headers
  In order to make API responses caheable
  As an API software developer
  I need to be able to set default cache headers values

  @createSchema
  @dropSchema
  Scenario: Cache headers default value
    When I send a "GET" request to "/relation_embedders"
    Then the response status code should be 200
    And the header "Etag" should be equal to '"21248afbca1f242fd3009ac7cdf13293"'
    And the header "Cache-Control" should be equal to "max-age=60, public, s-maxage=3600"
    And the header "Vary" should be equal to "Content-Type, Cookie"
