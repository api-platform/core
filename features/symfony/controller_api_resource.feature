Feature: ApiResource within Symfony controller
  In order to use an ApiResource inside a Symfony controller 
  As a developer
  I should be able to use the API

  Scenario: Send a GET request
    When I send a "GET" request to "/api_resource_controller/1"
    Then print last JSON response
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ControllerResource",
      "@id": "/api_resource_controller/1",
      "@type": "ControllerResource",
      "id": 1,
      "name": "soyuka"
    }
    """

  Scenario: Send a GET request in JSON
    When I send a "GET" request to "/api_resource_controller_json/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the JSON should be equal to:
    """
    {"id": 1, "name": "soyuka"}
    """
    
  Scenario: Send a POST request 
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/api_resource_controller" with body:
    """
    {
      "name": "soyuka",
      "id": "2"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ControllerResource",
      "@id": "/api_resource_controller/2",
      "@type": "ControllerResource",
      "id": 2,
      "name": "soyuka"
    }
    """
