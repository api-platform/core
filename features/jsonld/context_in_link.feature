Feature: JSON-LD context in link
  As a client software developer
  I need to access to a JSON-LD context describing data types in header response

  Scenario: Retrieve a Resource with "context_in_link" flag set to true
    When I send a "GET" request to "/context_in_links"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Link" should be equal to '<http://example.com/docs.jsonld>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation",</contexts/ContextInLink>; rel="http://www.w3.org/ns/json-ld#context"'
    And the JSON should be equal to:
    """
    {
      "@id": "/context_in_links",
      "@type": "hydra:Collection",
      "hydra:totalItems": 0,
      "hydra:member": []
    }
    """
