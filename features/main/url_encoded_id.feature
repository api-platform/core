Feature: Allowing resource identifiers with characters that should be URL encoded
  In order to have a resource with an id with special characters
  As a client software developer
  I need to be able to set and retrieve these resources with the URL encoded ID

  # Symfony\Component\Routing\Generator\UrlGenerator::generate uses rawurlencode so @id/iri will be encoded.
  @createSchema
  Scenario Outline: Get a resource whether or not the id is URL encoded
    Given there is a UrlEncodedId resource
    And I add "Content-Type" header equal to "application/ld+json"
    When I send a "GET" request to "/url_encoded_ids/<id>"
    Then the response status code should be 200
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/UrlEncodedId",
        "@id": "/url_encoded_ids/%25./encode:001",
        "@type": "UrlEncodedId",
        "id": "%./encode:001"
    }
    """
    Examples:
      | id                     |
      | %25%2E%2Fencode%3A001  |
      | %./encode%3A001        |
      | %./encode:001          |

  @createSchema
  Scenario Outline: Add a resource relation which contains special URL characters
    Given there is a UrlEncodedId resource
    And I add "Content-Type" header equal to "application/ld+json"
    When I send a "POST" request to "/related_to_url_encoded_ids" with body:
    """
    {
      "urlEncodedIdResource": "/url_encoded_ids/<id>"
    }
    """
    Then the response status code should be 201
    Examples:
      | id                     |
      | %25%2E%2Fencode%3A001  |
      | %./encode%3A001        |
      | %./encode:001          |
