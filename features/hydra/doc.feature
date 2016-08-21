Feature: Documentation support
  In order to build an auto-discoverable API
  As a client software developer
  I need to know Hydra specifications of objects I send and receive

  Scenario: Checks that the Link pointing to the Hydra documentation is set
    Given I send a "GET" request to "/"
    Then the header "Link" should be equal to '<http://example.com/apidoc.jsonld>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"'

  Scenario: Retrieve the API vocabulary
    Given I send a "GET" request to "/apidoc.jsonld"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    # Context
    And the JSON node "@context.@vocab" should be equal to "http://example.com/apidoc.jsonld#"
    And the JSON node "@context.hydra" should be equal to "http://www.w3.org/ns/hydra/core#"
    And the JSON node "@context.rdf" should be equal to "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    And the JSON node "@context.rdfs" should be equal to "http://www.w3.org/2000/01/rdf-schema#"
    And the JSON node "@context.xmls" should be equal to "http://www.w3.org/2001/XMLSchema#"
    And the JSON node "@context.owl" should be equal to "http://www.w3.org/2002/07/owl#"
    And the JSON node "@context.domain.@id" should be equal to "rdfs:domain"
    And the JSON node "@context.domain.@type" should be equal to "@id"
    And the JSON node "@context.range.@id" should be equal to "rdfs:range"
    And the JSON node "@context.range.@type" should be equal to "@id"
    And the JSON node "@context.subClassOf.@id" should be equal to "rdfs:subClassOf"
    And the JSON node "@context.subClassOf.@type" should be equal to "@id"
    And the JSON node "@context.expects.@id" should be equal to "hydra:expects"
    And the JSON node "@context.expects.@type" should be equal to "@id"
    And the JSON node "@context.returns.@id" should be equal to "hydra:returns"
    And the JSON node "@context.returns.@type" should be equal to "@id"
    # Root properties
    And the JSON node "@id" should be equal to "/apidoc.jsonld"
    And the JSON node "hydra:title" should be equal to "My Dummy API"
    And the JSON node "hydra:description" should be equal to "This is a test API."
    And the JSON node "hydra:entrypoint" should be equal to "/"
    # Supported classes
    And the Hydra class "The API entrypoint" exist
    And the Hydra class "A constraint violation" exist
    And the Hydra class "A constraint violation list" exist
    And the Hydra class "CircularReference" exist
    And the Hydra class "CustomIdentifierDummy" exist
    And the Hydra class "CustomNormalizedDummy" exist
    And the Hydra class "CustomWritableIdentifierDummy" exist
    And the Hydra class "Dummy" exist
    And the Hydra class "RelatedDummy" exist
    And the Hydra class "RelationEmbedder" exist
    And the Hydra class "ThirdLevel" exist
    And the Hydra class "ParentDummy" not exist
    And the Hydra class "UnknownDummy" not exist
    # Doc
    And the value of the node "@id" of the Hydra class "Dummy" is "#Dummy"
    And the value of the node "@type" of the Hydra class "Dummy" is "hydra:Class"
    And the value of the node "rdfs:label" of the Hydra class "Dummy" is "Dummy"
    And the value of the node "hydra:title" of the Hydra class "Dummy" is "Dummy"
    And the value of the node "hydra:description" of the Hydra class "Dummy" is "Dummy."
    # Properties
    And "id" property doesn't exist for the Hydra class "Dummy"
    And "name" property is readable for Hydra class "Dummy"
    And "name" property is writable for Hydra class "Dummy"
    And "name" property is required for Hydra class "Dummy"
    And the value of the node "@type" of the property "name" of the Hydra class "Dummy" is "hydra:SupportedProperty"
    And the value of the node "hydra:property.@id" of the property "name" of the Hydra class "Dummy" is "http://schema.org/name"
    And the value of the node "hydra:property.@type" of the property "name" of the Hydra class "Dummy" is "rdf:Property"
    And the value of the node "hydra:property.rdfs:label" of the property "name" of the Hydra class "Dummy" is "name"
    And the value of the node "hydra:property.domain" of the property "name" of the Hydra class "Dummy" is "#Dummy"
    And the value of the node "hydra:property.range" of the property "name" of the Hydra class "Dummy" is "xmls:string"
    And the value of the node "hydra:title" of the property "name" of the Hydra class "Dummy" is "name"
    And the value of the node "hydra:description" of the property "name" of the Hydra class "Dummy" is "The dummy name."
    # Operations
    And the value of the node "@type" of the operation "GET" of the Hydra class "Dummy" is "hydra:Operation"
    And the value of the node "hydra:method" of the operation "GET" of the Hydra class "Dummy" is "GET"
    And the value of the node "hydra:title" of the operation "GET" of the Hydra class "Dummy" is "Retrieves Dummy resource."
    And the value of the node "rdfs:label" of the operation "GET" of the Hydra class "Dummy" is "Retrieves Dummy resource."
    And the value of the node "returns" of the operation "GET" of the Hydra class "Dummy" is "#Dummy"
    And the value of the node "hydra:title" of the operation "PUT" of the Hydra class "Dummy" is "Replaces the Dummy resource."
    And the value of the node "hydra:title" of the operation "DELETE" of the Hydra class "Dummy" is "Deletes the Dummy resource."
    And the value of the node "returns" of the operation "DELETE" of the Hydra class "Dummy" is "owl:Nothing"
