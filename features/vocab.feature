Feature: Documentation support
  In order to build an auto-discoverable API
  As a client software developer
  I need to know Hydra specifications of objects I send and receive

  Scenario: Retrieve the API vocabulary
    Given I send a "GET" request to "/vocab"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ApiDocumentation",
      "@id": "/vocab",
      "hydra:title": "My Dummy API",
      "hydra:description": "This is a test API.",
      "hydra:entrypoint": "/",
      "hydra:supportedClass": [
        {
          "@id": "ConstraintViolation",
          "@type": "hydra:class",
          "hydra:title": "A constraint violation",
          "hydra:supportedProperty": [
            {
              "@type": "hydra:SupportedProperty",
              "hydra:property": {
                "@id": "ConstraintViolation/propertyPath",
                "@type": "rdf:Property",
                "rdfs:label": "propertyPath",
                "domain": "ConstraintViolation",
                "range": "rdf:string"
              },
              "hydra:title": "propertyPath",
              "hydra:description": "The property path of the violation",
              "hydra:readable": true,
              "hydra:writable": false
            },
            {
              "@type": "hydra:SupportedProperty",
              "hydra:property": {
                "@id": "ConstraintViolation/message",
                "@type": "rdf:Property",
                "rdfs:label": "message",
                "domain": "ConstraintViolation",
                "range": "rdf:string"
              },
              "hydra:title": "message",
              "hydra:description": "The message associated with the violation",
              "hydra:readable": true,
              "hydra:writable": false
            }
          ]
        },
        {
          "@id": "ConstraintViolationList",
          "@type": "hydra:Class",
          "subClassOf": "hydra:Error",
          "hydra:title": "A constraint violation list",
          "hydra:supportedProperty": [
            {
              "@type": "hydra:SupportedProperty",
              "hydra:property": {
                "@id": "ConstraintViolationList/violation",
                "@type": "rdf:Property",
                "rdfs:label": "violation",
                "domain": "ConstraintViolationList",
                "range": "ConstraintViolation"
              },
              "hydra:title": "violation",
              "hydra:description": "The violations",
              "hydra:readable": true,
              "hydra:writable": false
            }
          ]
        },
        {
          "@id": "Entrypoint",
          "@type": "hydra:class",
          "hydra:title": "The API entrypoint",
          "hydra:supportedProperty": [
            {
              "@type": "hydra:SupportedProperty",
              "hydra:property": {
                "@id": "dummies",
                "@type": "rdf:Property",
                "rdfs:label": "The collection of Dummy resources",
                "domain": "Entrypoint",
                "range": "dummies"
              },
              "hydra:title": "The collection of Dummy resources",
              "hydra:readable": true,
              "hydra:writable": false,
              "hydra:supportedOperation": [
                {
                  "@type": "hydra:Operation",
                  "hydra:title": "Retrieves the collection of Dummy resources.",
                  "hydra:returns": "hydra:PagedCollection",
                  "hydra:method": "GET"
                },
                {
                  "@type": "hydra:CreateResourceOperation",
                  "hydra:title": "Creates a Dummy resource.",
                  "hydra:expects": "Dummy",
                  "hydra:returns": "Dummy",
                  "hydra:method": "POST"
                }
              ]
            },
            {
              "@type": "hydra:SupportedProperty",
              "hydra:property": {
                "@id": "related_dummies",
                "@type": "rdf:Property",
                "rdfs:label": "The collection of RelatedDummy resources",
                "domain": "Entrypoint",
                "range": "related_dummies"
              },
              "hydra:title": "The collection of RelatedDummy resources",
              "hydra:readable": true,
              "hydra:writable": false,
              "hydra:supportedOperation": [
                {
                  "@type": "hydra:Operation",
                  "hydra:title": "Retrieves the collection of RelatedDummy resources.",
                  "hydra:returns": "hydra:PagedCollection",
                  "hydra:method": "GET"
                },
                {
                  "@type": "hydra:CreateResourceOperation",
                  "hydra:title": "Creates a RelatedDummy resource.",
                  "hydra:expects": "RelatedDummy",
                  "hydra:returns": "RelatedDummy",
                  "hydra:method": "POST"
                }
              ]
            }
          ]
        },
        {
          "@id": "Dummy",
          "@type": "hydra:Class",
          "hydra:title": "Dummy",
          "hydra:description": "Dummy.",
          "hydra:supportedProperty": [
            {
              "@type": "hydra:SupportedProperty",
              "hydra:property": {
                "@id": "Dummy/id",
                "@type": "rdf:Property",
                "rdfs:label": "id",
                "domain": "Dummy"
              },
              "hydra:title": "id",
              "hydra:required": false,
              "hydra:readable": true,
              "hydra:writable": false
            },
            {
              "@type": "hydra:SupportedProperty",
              "hydra:property": {
                "@id": "Dummy/name",
                "@type": "rdf:Property",
                "rdfs:label": "name",
                "domain": "Dummy"
              },
              "hydra:title": "name",
              "hydra:required": true,
              "hydra:readable": true,
              "hydra:writable": true
            },
            {
              "@type": "hydra:SupportedProperty",
              "hydra:property": {
                "@id": "Dummy/dummy",
                "@type": "rdf:Property",
                "rdfs:label": "dummy",
                "domain": "Dummy"
              },
              "hydra:title": "dummy",
              "hydra:required": false,
              "hydra:readable": true,
              "hydra:writable": true
            },
            {
              "@type": "hydra:SupportedProperty",
              "hydra:property": {
                "@id": "Dummy/relatedDummy",
                "@type": "rdf:Property",
                "rdfs:label": "relatedDummy",
                "domain": "Dummy"
              },
              "hydra:title": "relatedDummy",
              "hydra:required": false,
              "hydra:readable": true,
              "hydra:writable": true
            },
            {
              "@type": "hydra:SupportedProperty",
              "hydra:property": {
                "@id": "Dummy/relatedDummies",
                "@type": "rdf:Property",
                "rdfs:label": "relatedDummies",
                "domain": "Dummy"
              },
              "hydra:title": "relatedDummies",
              "hydra:required": false,
              "hydra:readable": true,
              "hydra:writable": true
            }
          ],
          "hydra:supportedOperation": [
            {
              "@type": "hydra:Operation",
              "hydra:title": "Retrieves Dummy resource.",
              "hydra:returns": "Dummy",
              "hydra:method": "GET"
            },
            {
              "@type": "hydra:ReplaceResourceOperation",
              "hydra:title": "Replaces the Dummy resource.",
              "hydra:expects": "Dummy",
              "hydra:returns": "Dummy",
              "hydra:method": "PUT"
            },
            {
              "@type": "hydra:Operation",
              "hydra:title": "Deletes the Dummy resource.",
              "hydra:expects": "Dummy",
              "hydra:method": "DELETE"
            }
          ]
        },
        {
          "@id": "RelatedDummy",
          "@type": "hydra:Class",
          "hydra:title": "RelatedDummy",
          "hydra:description": "Related Dummy.",
          "hydra:supportedProperty": [
            {
              "@type": "hydra:SupportedProperty",
              "hydra:property": {
                "@id": "RelatedDummy/id",
                "@type": "rdf:Property",
                "rdfs:label": "id",
                "domain": "RelatedDummy"
              },
              "hydra:title": "id",
              "hydra:required": false,
              "hydra:readable": true,
              "hydra:writable": false
            }
          ],
          "hydra:supportedOperation": [
            {
              "@type": "hydra:Operation",
              "hydra:title": "Retrieves RelatedDummy resource.",
              "hydra:returns": "RelatedDummy",
              "hydra:method": "GET"
            },
            {
              "@type": "hydra:ReplaceResourceOperation",
              "hydra:title": "Replaces the RelatedDummy resource.",
              "hydra:expects": "RelatedDummy",
              "hydra:returns": "RelatedDummy",
              "hydra:method": "PUT"
            },
            {
              "@type": "hydra:Operation",
              "hydra:title": "Deletes the RelatedDummy resource.",
              "hydra:expects": "RelatedDummy",
              "hydra:method": "DELETE"
            }
          ]
        }
      ]
    }
    """
