@php8
@v3
Feature: Exposing a property being a collection of resources
  can return an IRI instead of an array
  when the uriTemplate is set on the ApiProperty attribute

  Background:
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/vnd.api+json"

  @createSchema
  Scenario: Retrieve Resource with uriTemplate collection Property
    Given there are propertyCollectionIriOnly with relations
    And I send a "GET" request to "/property_collection_iri_onlies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
    And the JSON should be equal to:
      """
      {
        "links": {
          "propertyCollectionIriOnlyRelation": "/property-collection-relations",
          "iterableIri": "/parent/1/another-collection-operations",
          "toOneRelation": "/parent/1/property-uri-template/one-to-ones/1"
        },
        "data": {
          "id": "/property_collection_iri_onlies/1",
          "type": "PropertyCollectionIriOnly",
          "relationships": {
            "propertyCollectionIriOnlyRelation": {
              "data": [
                {
                  "type": "PropertyCollectionIriOnlyRelation",
                  "id": "/property_collection_iri_only_relations/1"
                }
              ]
            },
            "iterableIri": {
              "data": [
                {
                  "type": "PropertyCollectionIriOnlyRelation",
                  "id": "/property_collection_iri_only_relations/9999"
                }
              ]
            },
            "toOneRelation": {
              "data": {
                "type": "PropertyUriTemplateOneToOneRelation",
                "id": "/parent/1/property-uri-template/one-to-ones/1"
              }
            }
          }
        }
      }
      """
