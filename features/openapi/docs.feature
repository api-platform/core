Feature: Documentation support
  In order to build an auto-discoverable API
  As a client software developer
  I need to know OpenAPI specifications of objects I send and receive

  @createSchema
  Scenario: Retrieve the OpenAPI documentation
    Given I send a "GET" request to "/docs.json"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    # Context
    And the JSON node "openapi" should be equal to "3.1.0"
    # Root properties
    And the JSON node "info.title" should be equal to "My Dummy API"
    And the JSON node "info.description" should contain "This is a test API."
    And the JSON node "info.description" should contain "Made with love"
    # Security Schemes
    And the JSON node "components.securitySchemes" should be equal to:
    """
    {
        "oauth": {
            "type": "oauth2",
            "description": "OAuth 2.0 implicit Grant",
            "flows": {
                "implicit": {
                    "authorizationUrl": "http://my-custom-server/openid-connect/auth",
                    "scopes": {}
                }
            }
        },
        "Some_Authorization_Name": {
            "type": "apiKey",
            "description": "Value for the Authorization header parameter.",
            "name": "Authorization",
            "in": "header"
        }
    }
    """
    # Supported classes
    And the OpenAPI class "AbstractDummy" exists
    And the OpenAPI class "CircularReference" exists
    And the OpenAPI class "CircularReference-circular" exists
    And the OpenAPI class "CompositeItem" exists
    And the OpenAPI class "CompositeLabel" exists
    And the OpenAPI class "ConcreteDummy" exists
    And the OpenAPI class "CustomIdentifierDummy" exists
    And the OpenAPI class "CustomNormalizedDummy-input" exists
    And the OpenAPI class "CustomNormalizedDummy-output" exists
    And the OpenAPI class "CustomWritableIdentifierDummy" exists
    And the OpenAPI class "Dummy" exists
    And the OpenAPI class "DummyBoolean" exists
    And the OpenAPI class "RelatedDummy" exists
    And the OpenAPI class "DummyTableInheritance" exists
    And the OpenAPI class "DummyTableInheritanceChild" exists
    And the OpenAPI class "OverriddenOperationDummy-overridden_operation_dummy_get" exists
    And the OpenAPI class "OverriddenOperationDummy-overridden_operation_dummy_put" exists
    And the OpenAPI class "OverriddenOperationDummy-overridden_operation_dummy_read" exists
    And the OpenAPI class "OverriddenOperationDummy-overridden_operation_dummy_write" exists
    And the OpenAPI class "Person" exists
    And the OpenAPI class "RelatedDummy" exists
    And the OpenAPI class "NoCollectionDummy" exists
    And the OpenAPI class "RelatedToDummyFriend" exists
    And the OpenAPI class "RelatedToDummyFriend-fakemanytomany" exists
    And the OpenAPI class "DummyFriend" exists
    And the OpenAPI class "RelationEmbedder-barcelona" exists
    And the OpenAPI class "RelationEmbedder-chicago" exists
    And the OpenAPI class "User-user_user-read" exists
    And the OpenAPI class "User-user_user-write" exists
    And the OpenAPI class "UuidIdentifierDummy" exists
    And the OpenAPI class "ThirdLevel" exists
    And the OpenAPI class "DummyCar" exists
    And the OpenAPI class "ParentDummy" doesn't exist
    And the OpenAPI class "UnknownDummy" doesn't exist
    And the OpenAPI path "/relation_embedders/{id}/custom" exists
    And the OpenAPI path "/override/swagger" exists
    And the OpenAPI path "/api/custom-call/{id}" exists
    And the JSON node "paths./api/custom-call/{id}.get" should exist
    And the JSON node "paths./api/custom-call/{id}.put" should exist
    # Properties
    And the "id" property exists for the OpenAPI class "Dummy"
    And the "name" property is required for the OpenAPI class "Dummy"
    And the "genderType" property exists for the OpenAPI class "Person"
    And the "genderType" property for the OpenAPI class "Person" should be equal to:
    """
    {
      "default": "male",
      "example": "male",
      "type": ["string", "null"],
      "enum": [
          "male",
          "female",
          null
      ]
    }
    """
    And the "playMode" property exists for the OpenAPI class "VideoGame"
    And the "playMode" property for the OpenAPI class "VideoGame" should be equal to:
    """
    {
      "type": "string",
      "format": "iri-reference",
      "example": "https://example.com/"
    }
    """
    # Enable these tests when SF 4.4 / PHP 7.1 support is dropped
    #And the "isDummyBoolean" property exists for the OpenAPI class "DummyBoolean"
    #And the "isDummyBoolean" property is not read only for the OpenAPI class "DummyBoolean"
    # Filters
    And the JSON node "paths./dummies.get.parameters[3].name" should be equal to "dummyBoolean"
    And the JSON node "paths./dummies.get.parameters[3].in" should be equal to "query"
    And the JSON node "paths./dummies.get.parameters[3].required" should be false
    And the JSON node "paths./dummies.get.parameters[3].schema.type" should be equal to "boolean"

    And the JSON node "paths./dummy_cars.get.parameters[8].name" should be equal to "foobar[]"
    And the JSON node "paths./dummy_cars.get.parameters[8].description" should be equal to "Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: foobar[]={propertyName}&foobar[]={anotherPropertyName}&foobar[{nestedPropertyParent}][]={nestedProperty}"

    # Subcollection - check filter on subResource
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[0].name" should be equal to "id"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[0].in" should be equal to "path"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[0].required" should be true
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[0].schema.type" should be equal to "string"

    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[1].name" should be equal to "page"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[1].in" should be equal to "query"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[1].required" should be false
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[1].schema.type" should be equal to "integer"

    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[2].name" should be equal to "itemsPerPage"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[2].in" should be equal to "query"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[2].required" should be false
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[2].schema.type" should be equal to "integer"

    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[3].name" should be equal to "pagination"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[3].in" should be equal to "query"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[3].required" should be false
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[3].schema.type" should be equal to "boolean"

    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[4].name" should be equal to "name"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[4].in" should be equal to "query"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[4].required" should be false
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[4].schema.type" should be equal to "string"

    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[5].name" should be equal to "description"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[5].in" should be equal to "query"
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters[5].required" should be false

    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.parameters" should have 6 elements

    # Subcollection - check schema
    And the JSON node "paths./related_dummies/{id}/related_to_dummy_friends.get.responses.200.content.application/ld+json.schema.properties.hydra:member.items.$ref" should be equal to "#/components/schemas/RelatedToDummyFriend.jsonld-fakemanytomany"

    # Deprecations
    And the JSON node "paths./dummies.get.deprecated" should be false
    And the JSON node "paths./deprecated_resources.get.deprecated" should be true
    And the JSON node "paths./deprecated_resources.post.deprecated" should be true
    And the JSON node "paths./deprecated_resources/{id}.get.deprecated" should be true
    And the JSON node "paths./deprecated_resources/{id}.delete.deprecated" should be true
    And the JSON node "paths./deprecated_resources/{id}.put.deprecated" should be true
    And the JSON node "paths./deprecated_resources/{id}.patch.deprecated" should be true

    # Formats
    And the OpenAPI class "Dummy.jsonld" exists
    And the "@id" property exists for the OpenAPI class "Dummy.jsonld"
    And the JSON node "paths./dummies.get.responses.200.content.application/ld+json" should be equal to:
    """
    {
        "schema": {
            "type": "object",
            "properties": {
                "hydra:member": {
                    "type": "array",
                    "items": {
                        "$ref": "#/components/schemas/Dummy.jsonld"
                    }
                },
                "hydra:totalItems": {
                    "type": "integer",
                    "minimum": 0
                },
                "hydra:view": {
                    "type": "object",
                    "properties": {
                        "@id": {
                            "type": "string",
                            "format": "iri-reference"
                        },
                        "@type": {
                            "type": "string"
                        },
                        "hydra:first": {
                            "type": "string",
                            "format": "iri-reference"
                        },
                        "hydra:last": {
                            "type": "string",
                            "format": "iri-reference"
                        },
                        "hydra:previous": {
                            "type": "string",
                            "format": "iri-reference"
                        },
                        "hydra:next": {
                            "type": "string",
                            "format": "iri-reference"
                        }
                    },
                    "example": {
                        "@id": "string",
                        "type": "string",
                        "hydra:first": "string",
                        "hydra:last": "string",
                        "hydra:previous": "string",
                        "hydra:next": "string"
                    }
                },
                "hydra:search": {
                    "type": "object",
                    "properties": {
                        "@type": {
                            "type": "string"
                        },
                        "hydra:template": {
                            "type": "string"
                        },
                        "hydra:variableRepresentation": {
                            "type": "string"
                        },
                        "hydra:mapping": {
                            "type": "array",
                            "items": {
                                "type": "object",
                                "properties": {
                                    "@type": {
                                        "type": "string"
                                    },
                                    "variable": {
                                        "type": "string"
                                    },
                                    "property": {
                                        "type": ["string", "null"]
                                    },
                                    "required": {
                                        "type": "boolean"
                                    }
                                }
                            }
                        }
                    }
                }
            },
            "required": [
                "hydra:member"
            ]
        }
    }
    """
    And the JSON node "paths./dummies.get.responses.200.content.application/json" should be equal to:
    """
    {
        "schema": {
            "type": "array",
            "items": {
                "$ref": "#/components/schemas/Dummy"
            }
        }
    }
    """

  Scenario: OpenAPI UI is enabled for docs endpoint
    Given I add "Accept" header equal to "text/html"
    And I send a "GET" request to "/docs"
    Then the response status code should be 200
    And I should see text matching "My Dummy API"
    And I should see text matching "openapi"

  Scenario: OpenAPI extension properties is enabled in JSON docs
    Given I send a "GET" request to "/docs.json"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the JSON node "paths./dummy_addresses.get.x-visibility" should be equal to "hide"

  Scenario: OpenAPI UI is enabled for an arbitrary endpoint
    Given I add "Accept" header equal to "text/html"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And I should see text matching "openapi"

  @!mongodb
  Scenario: Retrieve the OpenAPI documentation with API Gateway compatibility
    Given I send a "GET" request to "/docs.json?api_gateway=true"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the JSON node "basePath" should be equal to "/"
    And the JSON node "components.schemas.RamseyUuidDummy.properties.id.description" should be equal to "The dummy id."
    And the JSON node "components.schemas.RelatedDummy-barcelona" should not exist
    And the JSON node "components.schemas.RelatedDummybarcelona" should exist

  @!mongodb
  Scenario: Retrieve the OpenAPI documentation to see if shortName property is used
    Given I send a "GET" request to "/docs.json"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the OpenAPI class "Resource" exists
    And the OpenAPI class "ResourceRelated" exists
    And the "resourceRelated" property for the OpenAPI class "Resource" should be equal to:
    """
    {
      "readOnly": true,
      "anyOf": [
        {
          "$ref": "#/components/schemas/ResourceRelated"
        },
        {
          "type": "null"
        }
      ]
    }
    """

  Scenario: Retrieve the JSON OpenAPI documentation
    Given I add "Accept" header equal to "application/vnd.openapi+json"
    And I send a "GET" request to "/docs"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.openapi+json; charset=utf-8"
    # Context
    And the JSON node "openapi" should be equal to "3.1.0"
    # Root properties
    And the JSON node "info.title" should be equal to "My Dummy API"
    And the JSON node "info.description" should contain "This is a test API."
    And the JSON node "info.description" should contain "Made with love"
    # Security Schemes
    And the JSON node "components.securitySchemes" should be equal to:
     """
    {
        "oauth": {
            "type": "oauth2",
            "description": "OAuth 2.0 implicit Grant",
            "flows": {
                "implicit": {
                    "authorizationUrl": "http://my-custom-server/openid-connect/auth",
                    "scopes": {}
                }
            }
        },
        "Some_Authorization_Name": {
            "type": "apiKey",
            "description": "Value for the Authorization header parameter.",
            "name": "Authorization",
            "in": "header"
        }
    }
    """

    Scenario: Retrieve the YAML OpenAPI documentation
    Given I add "Accept" header equal to "application/vnd.openapi+yaml"
    And I send a "GET" request to "/docs"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/vnd.openapi+yaml; charset=utf-8"

    Scenario: Retrieve the OpenAPI documentation
    Given I add "Accept" header equal to "text/html"
    And I send a "GET" request to "/"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "text/html; charset=utf-8"

  @!mongodb
  Scenario: Retrieve the OpenAPI documentation for Entity Dto Wrappers
    Given I send a "GET" request to "/docs.json"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the OpenAPI class "WrappedResponseEntity-read" exists
    And the "id" property exists for the OpenAPI class "WrappedResponseEntity-read"
    And the "id" property for the OpenAPI class "WrappedResponseEntity-read" should be equal to:
    """
    {
      "type": "string"
    }
    """
    And the OpenAPI class "WrappedResponseEntity.CustomOutputEntityWrapperDto-read" exists
    And the "data" property exists for the OpenAPI class "WrappedResponseEntity.CustomOutputEntityWrapperDto-read"
    And the "data" property for the OpenAPI class "WrappedResponseEntity.CustomOutputEntityWrapperDto-read" should be equal to:
    """
    {
      "$ref": "#\/components\/schemas\/WrappedResponseEntity-read"
    }
    """

  Scenario: Retrieve the OpenAPI documentation with 3.0 specification
    Given I send a "GET" request to "/docs.jsonopenapi?spec_version=3.0.0"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "openapi" should be equal to "3.0.0"
    And the JSON node "components.schemas.DummyBoolean" should be equal to:
    """
    {
      "type": "object",
      "description": "",
      "deprecated": false,
      "properties": {
        "id": {
          "readOnly": true,
          "anyOf": [
            {
              "type": "integer"
            },
            {
              "type": "null"
            }
          ]
        },
        "isDummyBoolean": {
          "anyOf": [
            {
              "type": "boolean"
            },
            {
              "type": "null"
            }
          ]
        },
        "dummyBoolean": {
          "readOnly": true,
          "type": "boolean"
        }
      }
    }
    """
