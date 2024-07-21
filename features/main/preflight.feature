Feature: Reply to preflight requests
  If the web frontend and the api are not on the same host,
  the browser sends preflight requests to know if
  requests with this method are allowed.

  Scenario: A preflight requests is answered with the correct headers
    When I add "Origin" header equal to "http://localhost"
    When I add "Access-Control-Request-Method" header equal to "GET"
    When I add "Access-Control-Request-Headers" header equal to "Origin, Content-Type, Accept, Authorization"
    And I send a "OPTIONS" request to "/"
    Then the response status code should be 200
    And the response should be empty
