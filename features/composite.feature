Feature: Retrieve data with Composite identifiers
  In order to retrieve relations with composite identifiers
  As a client software developer
  I need to retrieve all collections 

  @dropSchema
  @createSchema
  Scenario: Get collection with composite identifiers
    Given there are Composite identifier objects
    When I send a "GET" request to "/composite_items"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/CompositeItem$"},
        "@id": {"pattern": "^/composite_items$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:totalItems": {"type":"number", "maximum": 2},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/composite_items/[0-9]{1,}$"},
              "@type": {"pattern": "^CompositeItem$"},
              "field1": {"type": "string", "required": true},
              "compositeValues": {
                "type": "array", 
                "required": true,
                "maxItems": 4,
                "items": {
                  "type": "object",
                  "properties": {
                    "@id": {"pattern": "^/composite_relations/[0-9]{1,}-[0-9]{1,}"},
                    "@type": {"pattern": "CompositeRelation"},
                    "value": {"type": "string", "required": true},
                    "compositeLabel": {
                      "type": "object",
                      "properties": {
                        "@id": {"pattern": "^/composite_labels/[0-9]{1,}"},
                        "@type": {"pattern": "CompositeLabel"},
                        "value": {"type": "string"}
                      }
                    }
                  }
                }
              }
            }
          },
          "maxItems": 2
        }
      }
    }
    """
