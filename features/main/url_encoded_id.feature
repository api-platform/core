Feature: Allowing resource identifiers with characters that should be URL encoded
  In order to have a resource with an id with special characters
  As a client software developer
  I need to be able to set and retrieve these resources with the URL encoded ID

  @createSchema
  Scenario Outline: Get a resource whether or not the id is URL encoded
    Given there is a UrlEncodedId resource
    And I add "Content-Type" header equal to "application/ld+json"
    When I send a "GET" request to "<url>"
    Then the response status code should be 200
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/UrlEncodedId",
        "@id": "/url_encoded_ids/%25encode:id",
        "@type": "UrlEncodedId",
        "id": "%encode:id"
    }
    """
    Examples:
      | url                              |
      | /url_encoded_ids/%encode:id      |
      | /url_encoded_ids/%25encode%3Aid  |
      | /url_encoded_ids/%25encode:id    |
      | /url_encoded_ids/%encode%3Aid    |
