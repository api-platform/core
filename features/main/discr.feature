@createSchema
@dropSchema
Feature: Dealing with collection of discriminated entities

  Scenario: Create an entity having a collection of this type
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/discr_container_dummies" with body:
    """
    {
      "collection": [
        {
          "@type": "DiscrFirstDummy",
          "common": "1",
          "prop1": "foo"
        },
        {
          "@type": "DiscrSecondDummy",
          "common": "2",
          "prop2": "bar"
        }
      ]
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DiscrContainerDummy",
      "@id": "/discr_container_dummies/1",
      "@type": "DiscrContainerDummy",
      "collection": [
        {
          "@id": "\/discr_first_dummies\/1",
          "@type": "DiscrFirstDummy",
          "common": "1",
          "prop1": "foo"
        },
        {
          "@id": "\/discr_second_dummies\/2",
          "@type": "DiscrSecondDummy",
          "common": "2",
          "prop2": "bar"
        }
      ]
    }
    """