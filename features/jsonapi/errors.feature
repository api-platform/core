# TODO: Create an error test to a POST request
  # Scenario: Create a ThirdLevel with some missing data
  #   When I add "Content-Type" header equal to "application/vnd.api+json"
  #   And I add "Accept" header equal to "application/vnd.api+json"
  #   And I send a "POST" request to "/third_levels" with body:
  #   """
  #   {
  #     "data": {
  #       "type": "third-level",
  #       "attributes": {
  #         "level": 3
  #       }
  #     }
  #   }
  #   """
  #   Then the response status code should be 201
  #   # TODO: The response should have a Location header identifying the newly created resource
  #   And print last JSON response
  #   And I save the response
  #   And I valide it with jsonapi-validator
