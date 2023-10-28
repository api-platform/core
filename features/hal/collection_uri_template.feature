@php8
@v3
Feature: Exposing a property being a collection of resources
  can return an IRI instead of an array
  when the uriTemplate is set on the ApiProperty attribute

  @createSchema
  Scenario: Retrieve Resource with uriTemplate collection Property
    Given there are propertyCollectionIriOnly with relations
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/property_collection_iri_onlies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
      """
      {
        "_links": {
          "self": {
            "href": "/property_collection_iri_onlies/1"
          },
          "propertyCollectionIriOnlyRelation": {
            "href": "/property-collection-relations"
          },
          "iterableIri": {
            "href": "/parent/1/another-collection-operations"
          },
          "toOneRelation": {
            "href": "/parent/1/property-uri-template/one-to-ones/1"
          }
        },
        "_embedded": {
          "propertyCollectionIriOnlyRelation": [
            {
              "_links": {
                "self": {
                  "href": "/property_collection_iri_only_relations/1"
                }
              },
              "name": "asb"
            }
          ],
          "iterableIri": [
            {
              "_links": {
                "self": {
                  "href": "/property_collection_iri_only_relations/9999"
                }
              },
              "name": "Michel"
            }
          ],
          "toOneRelation": {
            "_links": {
              "self": {
                "href": "/parent/1/property-uri-template/one-to-ones/1"
              }
            },
            "name": "xarguš"
          }
        }
      }
      """
