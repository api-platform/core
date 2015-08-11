Feature: Documentation support
  In order to build an auto-discoverable API
  As a client software developer
  I need to know Hydra specifications of objects I send and receive

  Scenario: Checks that the Link pointing to the Hydra documentation is set
    Given I send a "GET" request to "/"
    Then the header "Link" should be equal to '<http://example.com/apidoc>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"'

  Scenario: Retrieve the API vocabulary
    Given I send a "GET" request to "/apidoc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
        "@context": {
            "@vocab": "http:\/\/example.com\/apidoc#",
            "hydra": "http:\/\/www.w3.org\/ns\/hydra\/core#",
            "rdf": "http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#",
            "rdfs": "http:\/\/www.w3.org\/2000\/01\/rdf-schema#",
            "xmls": "http:\/\/www.w3.org\/2001\/XMLSchema#",
            "owl": "http:\/\/www.w3.org\/2002\/07\/owl#",
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
        "@id": "\/apidoc",
        "hydra:title": "My Dummy API",
        "hydra:description": "This is a test API.",
        "hydra:entrypoint": "\/",
        "hydra:supportedClass": [
            {
                "@id": "#User",
                "@type": "hydra:Class",
                "rdfs:label": "User",
                "hydra:title": "User",
                "hydra:description": "",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#User\/email",
                            "@type": "rdf:Property",
                            "rdfs:label": "email",
                            "domain": "#User",
                            "range": "xmls:string"
                        },
                        "hydra:title": "email",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#User\/fullname",
                            "@type": "rdf:Property",
                            "rdfs:label": "fullname",
                            "domain": "#User",
                            "range": "xmls:string"
                        },
                        "hydra:title": "fullname",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#User\/plainPassword",
                            "@type": "rdf:Property",
                            "rdfs:label": "plainPassword",
                            "domain": "#User",
                            "range": "xmls:string"
                        },
                        "hydra:title": "plainPassword",
                        "hydra:required": false,
                        "hydra:readable": false,
                        "hydra:writable": true
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#User\/username",
                            "@type": "rdf:Property",
                            "rdfs:label": "username",
                            "domain": "#User",
                            "range": "xmls:string"
                        },
                        "hydra:title": "username",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    }
                ],
                "hydra:supportedOperation": [
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "GET",
                        "hydra:title": "Retrieves User resource.",
                        "rdfs:label": "Retrieves User resource.",
                        "returns": "#User"
                    },
                    {
                        "@type": "hydra:ReplaceResourceOperation",
                        "expects": "#User",
                        "hydra:method": "PUT",
                        "hydra:title": "Replaces the User resource.",
                        "rdfs:label": "Replaces the User resource.",
                        "returns": "#User"
                    },
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "DELETE",
                        "hydra:title": "Deletes the User resource.",
                        "rdfs:label": "Deletes the User resource.",
                        "returns": "owl:Nothing"
                    }
                ]
            },
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
                            "@id": "http:\/\/schema.org\/name",
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
                            "@id": "https:\/\/schema.org\/alternateName",
                            "@type": "rdf:Property",
                            "rdfs:label": "alias",
                            "domain": "#Dummy",
                            "range": "xmls:string"
                        },
                        "hydra:title": "alias",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true,
                        "hydra:description": "The dummy name alias."
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Dummy\/dummyDate",
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
                            "@id": "#Dummy\/jsonData",
                            "@type": "rdf:Property",
                            "rdfs:label": "jsonData",
                            "domain": "#Dummy"
                        },
                        "hydra:title": "jsonData",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true,
                        "hydra:description": "serialize data."
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Dummy\/dummy",
                            "@type": "rdf:Property",
                            "rdfs:label": "dummy",
                            "domain": "#Dummy",
                            "range": "xmls:string"
                        },
                        "hydra:title": "dummy",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true,
                        "hydra:description": "A dummy."
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Dummy\/relatedDummy",
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
                            "@id": "#Dummy\/relatedDummies",
                            "@type": "Hydra:Link",
                            "rdfs:label": "relatedDummies",
                            "domain": "#Dummy",
                            "range": "#RelatedDummy"
                        },
                        "hydra:title": "relatedDummies",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true,
                        "hydra:description": "Several dummies."
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Dummy\/nameConverted",
                            "@type": "rdf:Property",
                            "rdfs:label": "nameConverted",
                            "domain": "#Dummy",
                            "range": "xmls:string"
                        },
                        "hydra:title": "nameConverted",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    }
                ],
                "hydra:supportedOperation": [
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "GET",
                        "hydra:title": "Retrieves Dummy resource.",
                        "rdfs:label": "Retrieves Dummy resource.",
                        "returns": "#Dummy"
                    },
                    {
                        "@type": "hydra:ReplaceResourceOperation",
                        "expects": "#Dummy",
                        "hydra:method": "PUT",
                        "hydra:title": "Replaces the Dummy resource.",
                        "rdfs:label": "Replaces the Dummy resource.",
                        "returns": "#Dummy"
                    },
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "DELETE",
                        "hydra:title": "Deletes the Dummy resource.",
                        "rdfs:label": "Deletes the Dummy resource.",
                        "returns": "owl:Nothing"
                    }
                ]
            },
            {
                "@id": "https:\/\/schema.org\/Product",
                "@type": "hydra:Class",
                "rdfs:label": "RelatedDummy",
                "hydra:title": "RelatedDummy",
                "hydra:description": "Related dummy.",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#RelatedDummy\/symfony",
                            "@type": "rdf:Property",
                            "rdfs:label": "symfony",
                            "domain": "https:\/\/schema.org\/Product",
                            "range": "xmls:string"
                        },
                        "hydra:title": "symfony",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#RelatedDummy\/age",
                            "@type": "rdf:Property",
                            "rdfs:label": "age",
                            "domain": "https:\/\/schema.org\/Product",
                            "range": "xmls:integer"
                        },
                        "hydra:title": "age",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": false,
                        "hydra:description": "The age."
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#RelatedDummy\/thirdLevel",
                            "@type": "Hydra:Link",
                            "rdfs:label": "thirdLevel",
                            "domain": "https:\/\/schema.org\/Product",
                            "range": "#ThirdLevel"
                        },
                        "hydra:title": "thirdLevel",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#RelatedDummy\/unknown",
                            "@type": "rdf:Property",
                            "rdfs:label": "unknown",
                            "domain": "https:\/\/schema.org\/Product"
                        },
                        "hydra:title": "unknown",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    }
                ],
                "hydra:supportedOperation": [
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "GET",
                        "hydra:title": "Retrieves RelatedDummy resource.",
                        "rdfs:label": "Retrieves RelatedDummy resource.",
                        "returns": "https:\/\/schema.org\/Product"
                    },
                    {
                        "@type": "hydra:ReplaceResourceOperation",
                        "expects": "https:\/\/schema.org\/Product",
                        "hydra:method": "PUT",
                        "hydra:title": "Replaces the RelatedDummy resource.",
                        "rdfs:label": "Replaces the RelatedDummy resource.",
                        "returns": "https:\/\/schema.org\/Product"
                    },
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "DELETE",
                        "hydra:title": "Deletes the RelatedDummy resource.",
                        "rdfs:label": "Deletes the RelatedDummy resource.",
                        "returns": "owl:Nothing"
                    }
                ]
            },
            {
                "@id": "#RelationEmbedder",
                "@type": "hydra:Class",
                "rdfs:label": "RelationEmbedder",
                "hydra:title": "RelationEmbedder",
                "hydra:description": "Relation embedder.",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#RelationEmbedder\/paris",
                            "@type": "rdf:Property",
                            "rdfs:label": "paris",
                            "domain": "#RelationEmbedder",
                            "range": "xmls:string"
                        },
                        "hydra:title": "paris",
                        "hydra:required": false,
                        "hydra:readable": false,
                        "hydra:writable": true
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#RelationEmbedder\/krondstadt",
                            "@type": "rdf:Property",
                            "rdfs:label": "krondstadt",
                            "domain": "#RelationEmbedder",
                            "range": "xmls:string"
                        },
                        "hydra:title": "krondstadt",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#RelationEmbedder\/anotherRelated",
                            "@type": "rdf:Property",
                            "rdfs:label": "anotherRelated",
                            "domain": "#RelationEmbedder",
                            "range": "#RelatedDummy"
                        },
                        "hydra:title": "anotherRelated",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#RelationEmbedder\/related",
                            "@type": "rdf:Property",
                            "rdfs:label": "related",
                            "domain": "#RelationEmbedder",
                            "range": "#RelatedDummy"
                        },
                        "hydra:title": "related",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    }
                ],
                "hydra:supportedOperation": [
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "GET",
                        "hydra:title": "Retrieves RelationEmbedder resource.",
                        "rdfs:label": "Retrieves RelationEmbedder resource.",
                        "returns": "#RelationEmbedder"
                    },
                    {
                        "@type": "hydra:ReplaceResourceOperation",
                        "expects": "#RelationEmbedder",
                        "hydra:method": "PUT",
                        "hydra:title": "Replaces the RelationEmbedder resource.",
                        "rdfs:label": "Replaces the RelationEmbedder resource.",
                        "returns": "#RelationEmbedder"
                    },
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "GET",
                        "hydra:title": "A custom operation",
                        "rdfs:label": "A custom operation",
                        "returns": "xmls:string"
                    }
                ]
            },
            {
                "@id": "#Custom",
                "@type": "hydra:Class",
                "rdfs:label": "Custom",
                "hydra:title": "Custom",
                "hydra:description": "Custom.",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Custom\/id",
                            "@type": "rdf:Property",
                            "rdfs:label": "id",
                            "domain": "#Custom",
                            "range": "xmls:integer"
                        },
                        "hydra:title": "id",
                        "hydra:required": false,
                        "hydra:readable": false,
                        "hydra:writable": true,
                        "hydra:description": "The id."
                    }
                ],
                "hydra:supportedOperation": [
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "GET",
                        "hydra:title": "Retrieves Custom resource.",
                        "rdfs:label": "Retrieves Custom resource.",
                        "returns": "#Custom"
                    }
                ]
            },
            {
                "@id": "#ThirdLevel",
                "@type": "hydra:Class",
                "rdfs:label": "ThirdLevel",
                "hydra:title": "ThirdLevel",
                "hydra:description": "ThirdLevel.",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#ThirdLevel\/level",
                            "@type": "rdf:Property",
                            "rdfs:label": "level",
                            "domain": "#ThirdLevel",
                            "range": "xmls:integer"
                        },
                        "hydra:title": "level",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#ThirdLevel\/test",
                            "@type": "rdf:Property",
                            "rdfs:label": "test",
                            "domain": "#ThirdLevel",
                            "range": "xmls:boolean"
                        },
                        "hydra:title": "test",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    }
                ],
                "hydra:supportedOperation": [
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "GET",
                        "hydra:title": "Retrieves ThirdLevel resource.",
                        "rdfs:label": "Retrieves ThirdLevel resource.",
                        "returns": "#ThirdLevel"
                    },
                    {
                        "@type": "hydra:ReplaceResourceOperation",
                        "expects": "#ThirdLevel",
                        "hydra:method": "PUT",
                        "hydra:title": "Replaces the ThirdLevel resource.",
                        "rdfs:label": "Replaces the ThirdLevel resource.",
                        "returns": "#ThirdLevel"
                    },
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "DELETE",
                        "hydra:title": "Deletes the ThirdLevel resource.",
                        "rdfs:label": "Deletes the ThirdLevel resource.",
                        "returns": "owl:Nothing"
                    }
                ]
            },
            {
                "@id": "#CircularReference",
                "@type": "hydra:Class",
                "rdfs:label": "CircularReference",
                "hydra:title": "CircularReference",
                "hydra:description": "Circular Reference.",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#CircularReference\/parent",
                            "@type": "rdf:Property",
                            "rdfs:label": "parent",
                            "domain": "#CircularReference",
                            "range": "#CircularReference"
                        },
                        "hydra:title": "parent",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#CircularReference\/children",
                            "@type": "rdf:Property",
                            "rdfs:label": "children",
                            "domain": "#CircularReference",
                            "range": "#CircularReference"
                        },
                        "hydra:title": "children",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#CircularReference\/id",
                            "@type": "rdf:Property",
                            "rdfs:label": "id",
                            "domain": "#CircularReference",
                            "range": "xmls:integer"
                        },
                        "hydra:title": "id",
                        "hydra:required": false,
                        "hydra:readable": false,
                        "hydra:writable": true
                    }
                ],
                "hydra:supportedOperation": [
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "GET",
                        "hydra:title": "Retrieves CircularReference resource.",
                        "rdfs:label": "Retrieves CircularReference resource.",
                        "returns": "#CircularReference"
                    },
                    {
                        "@type": "hydra:ReplaceResourceOperation",
                        "expects": "#CircularReference",
                        "hydra:method": "PUT",
                        "hydra:title": "Replaces the CircularReference resource.",
                        "rdfs:label": "Replaces the CircularReference resource.",
                        "returns": "#CircularReference"
                    },
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "DELETE",
                        "hydra:title": "Deletes the CircularReference resource.",
                        "rdfs:label": "Deletes the CircularReference resource.",
                        "returns": "owl:Nothing"
                    }
                ]
            },
            {
                "@id": "#CustomIdentifierDummy",
                "@type": "hydra:Class",
                "rdfs:label": "CustomIdentifierDummy",
                "hydra:title": "CustomIdentifierDummy",
                "hydra:description": "Custom identifier dummy.",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#CustomIdentifierDummy\/name",
                            "@type": "rdf:Property",
                            "rdfs:label": "name",
                            "domain": "#CustomIdentifierDummy",
                            "range": "xmls:string"
                        },
                        "hydra:title": "name",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true,
                        "hydra:description": "The dummy name."
                    }
                ],
                "hydra:supportedOperation": [
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "GET",
                        "hydra:title": "Retrieves CustomIdentifierDummy resource.",
                        "rdfs:label": "Retrieves CustomIdentifierDummy resource.",
                        "returns": "#CustomIdentifierDummy"
                    },
                    {
                        "@type": "hydra:ReplaceResourceOperation",
                        "expects": "#CustomIdentifierDummy",
                        "hydra:method": "PUT",
                        "hydra:title": "Replaces the CustomIdentifierDummy resource.",
                        "rdfs:label": "Replaces the CustomIdentifierDummy resource.",
                        "returns": "#CustomIdentifierDummy"
                    },
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "DELETE",
                        "hydra:title": "Deletes the CustomIdentifierDummy resource.",
                        "rdfs:label": "Deletes the CustomIdentifierDummy resource.",
                        "returns": "owl:Nothing"
                    }
                ]
            },
            {
                "@id": "#CustomWritableIdentifierDummy",
                "@type": "hydra:Class",
                "rdfs:label": "CustomWritableIdentifierDummy",
                "hydra:title": "CustomWritableIdentifierDummy",
                "hydra:description": "Custom writable identifier dummy.",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#CustomWritableIdentifierDummy\/slug",
                            "@type": "rdf:Property",
                            "rdfs:label": "slug",
                            "domain": "#CustomWritableIdentifierDummy",
                            "range": "xmls:string"
                        },
                        "hydra:title": "slug",
                        "hydra:required": false,
                        "hydra:readable": false,
                        "hydra:writable": true,
                        "hydra:description": "The special identifier."
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#CustomWritableIdentifierDummy\/name",
                            "@type": "rdf:Property",
                            "rdfs:label": "name",
                            "domain": "#CustomWritableIdentifierDummy",
                            "range": "xmls:string"
                        },
                        "hydra:title": "name",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true,
                        "hydra:description": "The dummy name."
                    }
                ],
                "hydra:supportedOperation": [
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "GET",
                        "hydra:title": "Retrieves CustomWritableIdentifierDummy resource.",
                        "rdfs:label": "Retrieves CustomWritableIdentifierDummy resource.",
                        "returns": "#CustomWritableIdentifierDummy"
                    },
                    {
                        "@type": "hydra:ReplaceResourceOperation",
                        "expects": "#CustomWritableIdentifierDummy",
                        "hydra:method": "PUT",
                        "hydra:title": "Replaces the CustomWritableIdentifierDummy resource.",
                        "rdfs:label": "Replaces the CustomWritableIdentifierDummy resource.",
                        "returns": "#CustomWritableIdentifierDummy"
                    },
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "DELETE",
                        "hydra:title": "Deletes the CustomWritableIdentifierDummy resource.",
                        "rdfs:label": "Deletes the CustomWritableIdentifierDummy resource.",
                        "returns": "owl:Nothing"
                    }
                ]
            },
            {
                "@id": "#CustomNormalizedDummy",
                "@type": "hydra:Class",
                "rdfs:label": "CustomNormalizedDummy",
                "hydra:title": "CustomNormalizedDummy",
                "hydra:description": "Custom normalized dummy.",
                "hydra:supportedProperty": [
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "http:\/\/schema.org\/name",
                            "@type": "rdf:Property",
                            "rdfs:label": "name",
                            "domain": "#CustomNormalizedDummy",
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
                            "@id": "https:\/\/schema.org\/alternateName",
                            "@type": "rdf:Property",
                            "rdfs:label": "alias",
                            "domain": "#CustomNormalizedDummy",
                            "range": "xmls:string"
                        },
                        "hydra:title": "alias",
                        "hydra:required": false,
                        "hydra:readable": true,
                        "hydra:writable": true,
                        "hydra:description": "The dummy name alias."
                    }
                ],
                "hydra:supportedOperation": [
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "GET",
                        "hydra:title": "Retrieves CustomNormalizedDummy resource.",
                        "rdfs:label": "Retrieves CustomNormalizedDummy resource.",
                        "returns": "#CustomNormalizedDummy"
                    },
                    {
                        "@type": "hydra:ReplaceResourceOperation",
                        "expects": "#CustomNormalizedDummy",
                        "hydra:method": "PUT",
                        "hydra:title": "Replaces the CustomNormalizedDummy resource.",
                        "rdfs:label": "Replaces the CustomNormalizedDummy resource.",
                        "returns": "#CustomNormalizedDummy"
                    },
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "DELETE",
                        "hydra:title": "Deletes the CustomNormalizedDummy resource.",
                        "rdfs:label": "Deletes the CustomNormalizedDummy resource.",
                        "returns": "owl:Nothing"
                    }
                ]
            },
            {
                "@id": "#NoCollectionDummy",
                "@type": "hydra:Class",
                "rdfs:label": "NoCollectionDummy",
                "hydra:title": "NoCollectionDummy",
                "hydra:description": "No collection dummy.",
                "hydra:supportedProperty": [],
                "hydra:supportedOperation": [
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "GET",
                        "hydra:title": "Retrieves NoCollectionDummy resource.",
                        "rdfs:label": "Retrieves NoCollectionDummy resource.",
                        "returns": "#NoCollectionDummy"
                    },
                    {
                        "@type": "hydra:ReplaceResourceOperation",
                        "expects": "#NoCollectionDummy",
                        "hydra:method": "PUT",
                        "hydra:title": "Replaces the NoCollectionDummy resource.",
                        "rdfs:label": "Replaces the NoCollectionDummy resource.",
                        "returns": "#NoCollectionDummy"
                    },
                    {
                        "@type": "hydra:Operation",
                        "hydra:method": "DELETE",
                        "hydra:title": "Deletes the NoCollectionDummy resource.",
                        "rdfs:label": "Deletes the NoCollectionDummy resource.",
                        "returns": "owl:Nothing"
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
                            "@id": "#Entrypoint\/user",
                            "@type": "hydra:Link",
                            "domain": "#Entrypoint",
                            "rdfs:label": "The collection of User resources",
                            "range": "hydra:PagedCollection",
                            "hydra:supportedOperation": [
                                {
                                    "@type": "hydra:Operation",
                                    "hydra:method": "GET",
                                    "hydra:title": "Retrieves the collection of User resources.",
                                    "rdfs:label": "Retrieves the collection of User resources.",
                                    "returns": "hydra:PagedCollection"
                                },
                                {
                                    "@type": "hydra:CreateResourceOperation",
                                    "expects": "#User",
                                    "hydra:method": "POST",
                                    "hydra:title": "Creates a User resource.",
                                    "rdfs:label": "Creates a User resource.",
                                    "returns": "#User"
                                }
                            ]
                        },
                        "hydra:title": "The collection of User resources",
                        "hydra:readable": true,
                        "hydra:writable": false
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Entrypoint\/dummy",
                            "@type": "hydra:Link",
                            "domain": "#Entrypoint",
                            "rdfs:label": "The collection of Dummy resources",
                            "range": "hydra:PagedCollection",
                            "hydra:supportedOperation": [
                                {
                                    "@type": "hydra:Operation",
                                    "hydra:method": "GET",
                                    "hydra:title": "Retrieves the collection of Dummy resources.",
                                    "rdfs:label": "Retrieves the collection of Dummy resources.",
                                    "returns": "hydra:PagedCollection"
                                },
                                {
                                    "@type": "hydra:CreateResourceOperation",
                                    "expects": "#Dummy",
                                    "hydra:method": "POST",
                                    "hydra:title": "Creates a Dummy resource.",
                                    "rdfs:label": "Creates a Dummy resource.",
                                    "returns": "#Dummy"
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
                            "@id": "#Entrypoint\/relatedDummy",
                            "@type": "hydra:Link",
                            "domain": "#Entrypoint",
                            "rdfs:label": "The collection of RelatedDummy resources",
                            "range": "hydra:PagedCollection",
                            "hydra:supportedOperation": [
                                {
                                    "@type": "hydra:Operation",
                                    "hydra:method": "GET",
                                    "hydra:title": "Retrieves the collection of RelatedDummy resources.",
                                    "rdfs:label": "Retrieves the collection of RelatedDummy resources.",
                                    "returns": "hydra:PagedCollection"
                                },
                                {
                                    "@type": "hydra:CreateResourceOperation",
                                    "expects": "https:\/\/schema.org\/Product",
                                    "hydra:method": "POST",
                                    "hydra:title": "Creates a RelatedDummy resource.",
                                    "rdfs:label": "Creates a RelatedDummy resource.",
                                    "returns": "https:\/\/schema.org\/Product"
                                }
                            ]
                        },
                        "hydra:title": "The collection of RelatedDummy resources",
                        "hydra:readable": true,
                        "hydra:writable": false
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Entrypoint\/relationEmbedder",
                            "@type": "hydra:Link",
                            "domain": "#Entrypoint",
                            "rdfs:label": "The collection of RelationEmbedder resources",
                            "range": "hydra:PagedCollection",
                            "hydra:supportedOperation": [
                                {
                                    "@type": "hydra:Operation",
                                    "hydra:method": "GET",
                                    "hydra:title": "Retrieves the collection of RelationEmbedder resources.",
                                    "rdfs:label": "Retrieves the collection of RelationEmbedder resources.",
                                    "returns": "hydra:PagedCollection"
                                },
                                {
                                    "@type": "hydra:CreateResourceOperation",
                                    "expects": "#RelationEmbedder",
                                    "hydra:method": "POST",
                                    "hydra:title": "Creates a RelationEmbedder resource.",
                                    "rdfs:label": "Creates a RelationEmbedder resource.",
                                    "returns": "#RelationEmbedder"
                                }
                            ]
                        },
                        "hydra:title": "The collection of RelationEmbedder resources",
                        "hydra:readable": true,
                        "hydra:writable": false
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Entrypoint\/custom",
                            "@type": "hydra:Link",
                            "domain": "#Entrypoint",
                            "rdfs:label": "The collection of Custom resources",
                            "range": "hydra:PagedCollection",
                            "hydra:supportedOperation": [
                                {
                                    "@type": "hydra:Operation",
                                    "hydra:method": "GET",
                                    "hydra:title": "Retrieves the collection of Custom resources.",
                                    "rdfs:label": "Retrieves the collection of Custom resources.",
                                    "returns": "hydra:PagedCollection"
                                }
                            ]
                        },
                        "hydra:title": "The collection of Custom resources",
                        "hydra:readable": true,
                        "hydra:writable": false
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Entrypoint\/thirdLevel",
                            "@type": "hydra:Link",
                            "domain": "#Entrypoint",
                            "rdfs:label": "The collection of ThirdLevel resources",
                            "range": "hydra:PagedCollection",
                            "hydra:supportedOperation": [
                                {
                                    "@type": "hydra:Operation",
                                    "hydra:method": "GET",
                                    "hydra:title": "Retrieves the collection of ThirdLevel resources.",
                                    "rdfs:label": "Retrieves the collection of ThirdLevel resources.",
                                    "returns": "hydra:PagedCollection"
                                },
                                {
                                    "@type": "hydra:CreateResourceOperation",
                                    "expects": "#ThirdLevel",
                                    "hydra:method": "POST",
                                    "hydra:title": "Creates a ThirdLevel resource.",
                                    "rdfs:label": "Creates a ThirdLevel resource.",
                                    "returns": "#ThirdLevel"
                                }
                            ]
                        },
                        "hydra:title": "The collection of ThirdLevel resources",
                        "hydra:readable": true,
                        "hydra:writable": false
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Entrypoint\/circularReference",
                            "@type": "hydra:Link",
                            "domain": "#Entrypoint",
                            "rdfs:label": "The collection of CircularReference resources",
                            "range": "hydra:PagedCollection",
                            "hydra:supportedOperation": [
                                {
                                    "@type": "hydra:Operation",
                                    "hydra:method": "GET",
                                    "hydra:title": "Retrieves the collection of CircularReference resources.",
                                    "rdfs:label": "Retrieves the collection of CircularReference resources.",
                                    "returns": "hydra:PagedCollection"
                                },
                                {
                                    "@type": "hydra:CreateResourceOperation",
                                    "expects": "#CircularReference",
                                    "hydra:method": "POST",
                                    "hydra:title": "Creates a CircularReference resource.",
                                    "rdfs:label": "Creates a CircularReference resource.",
                                    "returns": "#CircularReference"
                                }
                            ]
                        },
                        "hydra:title": "The collection of CircularReference resources",
                        "hydra:readable": true,
                        "hydra:writable": false
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Entrypoint\/customIdentifierDummy",
                            "@type": "hydra:Link",
                            "domain": "#Entrypoint",
                            "rdfs:label": "The collection of CustomIdentifierDummy resources",
                            "range": "hydra:PagedCollection",
                            "hydra:supportedOperation": [
                                {
                                    "@type": "hydra:Operation",
                                    "hydra:method": "GET",
                                    "hydra:title": "Retrieves the collection of CustomIdentifierDummy resources.",
                                    "rdfs:label": "Retrieves the collection of CustomIdentifierDummy resources.",
                                    "returns": "hydra:PagedCollection"
                                },
                                {
                                    "@type": "hydra:CreateResourceOperation",
                                    "expects": "#CustomIdentifierDummy",
                                    "hydra:method": "POST",
                                    "hydra:title": "Creates a CustomIdentifierDummy resource.",
                                    "rdfs:label": "Creates a CustomIdentifierDummy resource.",
                                    "returns": "#CustomIdentifierDummy"
                                }
                            ]
                        },
                        "hydra:title": "The collection of CustomIdentifierDummy resources",
                        "hydra:readable": true,
                        "hydra:writable": false
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Entrypoint\/customWritableIdentifierDummy",
                            "@type": "hydra:Link",
                            "domain": "#Entrypoint",
                            "rdfs:label": "The collection of CustomWritableIdentifierDummy resources",
                            "range": "hydra:PagedCollection",
                            "hydra:supportedOperation": [
                                {
                                    "@type": "hydra:Operation",
                                    "hydra:method": "GET",
                                    "hydra:title": "Retrieves the collection of CustomWritableIdentifierDummy resources.",
                                    "rdfs:label": "Retrieves the collection of CustomWritableIdentifierDummy resources.",
                                    "returns": "hydra:PagedCollection"
                                },
                                {
                                    "@type": "hydra:CreateResourceOperation",
                                    "expects": "#CustomWritableIdentifierDummy",
                                    "hydra:method": "POST",
                                    "hydra:title": "Creates a CustomWritableIdentifierDummy resource.",
                                    "rdfs:label": "Creates a CustomWritableIdentifierDummy resource.",
                                    "returns": "#CustomWritableIdentifierDummy"
                                }
                            ]
                        },
                        "hydra:title": "The collection of CustomWritableIdentifierDummy resources",
                        "hydra:readable": true,
                        "hydra:writable": false
                    },
                    {
                        "@type": "hydra:SupportedProperty",
                        "hydra:property": {
                            "@id": "#Entrypoint\/customNormalizedDummy",
                            "@type": "hydra:Link",
                            "domain": "#Entrypoint",
                            "rdfs:label": "The collection of CustomNormalizedDummy resources",
                            "range": "hydra:PagedCollection",
                            "hydra:supportedOperation": [
                                {
                                    "@type": "hydra:Operation",
                                    "hydra:method": "GET",
                                    "hydra:title": "Retrieves the collection of CustomNormalizedDummy resources.",
                                    "rdfs:label": "Retrieves the collection of CustomNormalizedDummy resources.",
                                    "returns": "hydra:PagedCollection"
                                },
                                {
                                    "@type": "hydra:CreateResourceOperation",
                                    "expects": "#CustomNormalizedDummy",
                                    "hydra:method": "POST",
                                    "hydra:title": "Creates a CustomNormalizedDummy resource.",
                                    "rdfs:label": "Creates a CustomNormalizedDummy resource.",
                                    "returns": "#CustomNormalizedDummy"
                                }
                            ]
                        },
                        "hydra:title": "The collection of CustomNormalizedDummy resources",
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
                            "@id": "#ConstraintViolation\/propertyPath",
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
                            "@id": "#ConstraintViolation\/message",
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
                            "@id": "#ConstraintViolationList\/violation",
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
