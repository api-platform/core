Feature: Documentation support
  In order to build an auto-discoverable API
  As a client software developer
  I need to know Hydra specifications of objects I send and receive

  Scenario: Checks that the Link pointing to the Hydra documentation is set
    Given I send a "GET" request to "/"
    Then the header "Link" should be equal to '<http://example.com/vocab>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"'

  Scenario: Retrieve the API vocabulary
    Given I send a "GET" request to "/vocab"
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
            "xmls": "http://www.w3.org/2001/XMLSchema#",
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
            "expects": {
                "@id": "hydra:expects",
                "@type": "@id"
            },
            "returns": {
                "@id": "hydra:returns",
                "@type": "@id"
            }
        },
        "@id": "/vocab",
        "hydra:title": "My Dummy API",
        "hydra:description": "This is a test API.",
        "hydra:entrypoint": "/",
        "hydra:supportedClass": [
            {
                "@id": "#Dummy",
                "@type": "hydra:Class",
                "rdfs:label": "Dummy",
                "hydra:title": "Dummy",
                "hydra:description": "Dummy.",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Dummy/name",
                            "@type": "rdf:Property",
                            "rdfs:label": "name",
                            "domain": "#Dummy",
                            "range": "xmls:string"
                        },
                        "hydra:title": "name",
                        "hydra:required": true,
                        "hydra:readable": true,
                        "hydra:writable": true,
                        "hydra:description": "The dummy name."
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Dummy/foo",
                            "@type": "rdf:Property",
                            "rdfs:label": "foo",
                            "domain": "#Dummy"
                        },
                        "hydra:title": "foo",
                        "hydra:required": false,
                        "hydra:readable": false,
                        "hydra:writable": true,
                        "hydra:description": "foo"
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Dummy/dummy",
                            "@type": "rdf:Property",
                            "rdfs:label": "dummy",
                            "domain": "#Dummy",
                            "range": "xmls:string"
                        },
                        "hydra:title": "dummy",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true,
                        "hydra:description": "foo"
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Dummy/dummyDate",
                            "@type": "rdf:Property",
                            "rdfs:label": "dummyDate",
                            "domain": "#Dummy",
                            "range": "xmls:dateTime"
                        },
                        "hydra:title": "dummyDate",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true,
                        "hydra:description": "A dummy date."
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Dummy/relatedDummy",
                            "@type": "Hydra:Link",
                            "rdfs:label": "relatedDummy",
                            "domain": "#Dummy",
                            "range": "#RelatedDummy"
                        },
                        "hydra:title": "relatedDummy",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true,
                        "hydra:description": "A related dummy."
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Dummy/relatedDummies",
                            "@type": "Hydra:Link",
                            "rdfs:label": "relatedDummies",
                            "domain": "#Dummy"
                        },
                        "hydra:title": "relatedDummies",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true,
                        "hydra:description": "Several dummies."
                    }
                ],
                "hydra:supportedOperation": [
                    {
                        "hydra:method": "GET",
                        "@type": "hydra:Operation",
                        "hydra:title": "Retrieves Dummy resource.",
                        "returns": "#Dummy",
                        "rdfs:label": "Retrieves Dummy resource."
                    },
                    {
                        "hydra:method": "PUT",
                        "@type": "hydra:ReplaceResourceOperation",
                        "hydra:title": "Replaces the Dummy resource.",
                        "returns": "#Dummy",
                        "expects": "#Dummy",
                        "rdfs:label": "Replaces the Dummy resource."
                    },
                    {
                        "hydra:method": "DELETE",
                        "@type": "hydra:Operation",
                        "hydra:title": "Deletes the Dummy resource.",
                        "returns": "owl:Nothing",
                        "rdfs:label": "Deletes the Dummy resource."
                    }
                ]
            },
            {
                "@id": "#RelatedDummy",
                "@type": "hydra:Class",
                "rdfs:label": "RelatedDummy",
                "hydra:title": "RelatedDummy",
                "hydra:description": "Related Dummy.",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#RelatedDummy/age",
                            "@type": "rdf:Property",
                            "rdfs:label": "age",
                            "domain": "#RelatedDummy",
                            "range": "xmls:integer"
                        },
                        "hydra:title": "age",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": false,
                        "hydra:description": "The age."
                    }
                ],
                "hydra:supportedOperation": [
                    {
                        "hydra:method": "GET",
                        "@type": "hydra:Operation",
                        "hydra:title": "Retrieves RelatedDummy resource.",
                        "returns": "#RelatedDummy",
                        "rdfs:label": "Retrieves RelatedDummy resource."
                    },
                    {
                        "hydra:method": "PUT",
                        "@type": "hydra:ReplaceResourceOperation",
                        "hydra:title": "Replaces the RelatedDummy resource.",
                        "returns": "#RelatedDummy",
                        "expects": "#RelatedDummy",
                        "rdfs:label": "Replaces the RelatedDummy resource."
                    },
                    {
                        "hydra:method": "DELETE",
                        "@type": "hydra:Operation",
                        "hydra:title": "Deletes the RelatedDummy resource.",
                        "returns": "owl:Nothing",
                        "rdfs:label": "Deletes the RelatedDummy resource."
                    }
                ]
            },
            {
                "@id": "#Entrypoint",
                "@type": "hydra:Class",
                "hydra:title": "The API entrypoint",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Entrypoint/dummy",
                            "@type": "hydra:Link",
                            "rdfs:label": "The collection of Dummy resources",
                            "domain": "#Entrypoint",
                            "range": "hydra:PagedCollection",
                            "hydra:supportedOperation": [
                                {
                                    "hydra:method": "GET",
                                    "@type": "hydra:Operation",
                                    "hydra:title": "Retrieves the collection of Dummy resources.",
                                    "returns": "hydra:PagedCollection",
                                    "rdfs:label": "Retrieves the collection of Dummy resources."
                                },
                                {
                                    "hydra:method": "POST",
                                    "@type": "hydra:CreateResourceOperation",
                                    "hydra:title": "Creates a Dummy resource.",
                                    "expects": "#Dummy",
                                    "returns": "#Dummy",
                                    "rdfs:label": "Creates a Dummy resource."
                                }
                            ]
                        },
                        "hydra:title": "The collection of Dummy resources",
                        "hydra:readable": true,
                        "hydra:writable": false
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Entrypoint/relatedDummy",
                            "@type": "hydra:Link",
                            "rdfs:label": "The collection of RelatedDummy resources",
                            "domain": "#Entrypoint",
                            "range": "hydra:PagedCollection",
                            "hydra:supportedOperation": [
                                {
                                    "hydra:method": "GET",
                                    "@type": "hydra:Operation",
                                    "hydra:title": "Retrieves the collection of RelatedDummy resources.",
                                    "returns": "hydra:PagedCollection",
                                    "rdfs:label": "Retrieves the collection of RelatedDummy resources."
                                },
                                {
                                    "hydra:method": "POST",
                                    "@type": "hydra:CreateResourceOperation",
                                    "hydra:title": "Creates a RelatedDummy resource.",
                                    "expects": "#RelatedDummy",
                                    "returns": "#RelatedDummy",
                                    "rdfs:label": "Creates a RelatedDummy resource."
                                }
                            ]
                        },
                        "hydra:title": "The collection of RelatedDummy resources",
                        "hydra:readable": true,
                        "hydra:writable": false
                    }
                ],
                "hydra:supportedOperation": {
                    "@type": "hydra:Operation",
                    "hydra:method": "GET",
                    "rdfs:label": "The API entrypoint.",
                    "returns": "#EntryPoint"
                }
            },
            {
                "@id": "#ConstraintViolation",
                "@type": "hydra:Class",
                "hydra:title": "A constraint violation",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#ConstraintViolation/propertyPath",
                            "@type": "rdf:Property",
                            "rdfs:label": "propertyPath",
                            "domain": "#ConstraintViolation",
                            "range": "xmls:string"
                        },
                        "hydra:title": "propertyPath",
                        "hydra:description": "The property path of the violation",
                        "hydra:readable": true,
                        "hydra:writable": false
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#ConstraintViolation/message",
                            "@type": "rdf:Property",
                            "rdfs:label": "message",
                            "domain": "#ConstraintViolation",
                            "range": "xmls:string"
                        },
                        "hydra:title": "message",
                        "hydra:description": "The message associated with the violation",
                        "hydra:readable": true,
                        "hydra:writable": false
                    }
                ]
            },
            {
                "@id": "#ConstraintViolationList",
                "@type": "hydra:Class",
                "subClassOf": "hydra:Error",
                "hydra:title": "A constraint violation list",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#ConstraintViolationList/violation",
                            "@type": "rdf:Property",
                            "rdfs:label": "violation",
                            "domain": "#ConstraintViolationList",
                            "range": "#ConstraintViolation"
                        },
                        "hydra:title": "violation",
                        "hydra:description": "The violations",
                        "hydra:readable": true,
                        "hydra:writable": false
                    }
                ]
            }
        ]
    }
    """
