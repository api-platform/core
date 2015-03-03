Feature: JSON-LD contexts generation
  In order to have an hypermedia, Linked Data enabled API
  As a client software developer
  I need to access to a JSON-LD context describing data types

  Scenario: Retrieve Dummy context
    Given I send a "GET" request to "/contexts/Dummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
        "@context": {
            "@vocab": "http://example.com/vocab#",
            "hydra": "http://www.w3.org/ns/hydra/core#",
            "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
            "rdfs": "http://www.w3.org/2000/01/rdf-schema#",
            "owl": "http://www.w3.org/2002/07/owl#",
            "domain": {
                "@id": "rdfs:domain",
                "@type": "@id"
            },
            "range": {
                "@id": "rdfs:range",
                "@type": "@id"
            },
            "subClassOf": {
                "@id": "rdfs:subClassOf",
                "@type": "@id"
            },
            "dummyDate": {
                "@type": "http://www.w3.org/2001/XMLSchema#dateTime"
            },
            "relatedDummy": {
                "@type": "@id"
            },
            "relatedDummies": {
                "@type": "@id"
            }
        }
    }
    """
