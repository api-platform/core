Feature: JSON-LD using interface as resource
  In order to use interface as resource
  As a developer
  I should be able to serialize objects of an interface as API resource.

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  @createSchema
  Scenario: Retrieve a taxon
    Given there is the following taxon:
    """
    {
      "code": "WONDERFUL_TAXON"
    }
    """
    When I send a "GET" request to "/taxons/WONDERFUL_TAXON"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Taxon",
      "@id": "/taxons/WONDERFUL_TAXON",
      "@type": "Taxon",
      "code": "WONDERFUL_TAXON"
    }
    """

  Scenario: Retrieve a product with a main taxon
    Given there is the following product:
    """
    {
      "code": "GREAT_PRODUCT",
      "mainTaxon": "/taxons/WONDERFUL_TAXON"
    }
    """
    When I send a "GET" request to "/products/GREAT_PRODUCT"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Product",
      "@id": "/products/GREAT_PRODUCT",
      "@type": "Product",
      "code": "GREAT_PRODUCT",
      "mainTaxon": {
        "@id": "/taxons/WONDERFUL_TAXON",
        "@type": "Taxon",
        "code": "WONDERFUL_TAXON"
      }
    }
    """
