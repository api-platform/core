Feature: Table inheritance
  In order to use the api with Doctrine table inheritance
  As a client software developer
  I need to be able to create resources and fetch them on the upper entity

  @createSchema
  Scenario: Create a table inherited resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_table_inheritance_children" with body:
    """
    {"name": "foo", "nickname": "bar"}
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@type": {
          "type": "string",
          "pattern": "^DummyTableInheritanceChild$"
        },
        "@context": {
          "type": "string",
          "pattern": "^/contexts/DummyTableInheritanceChild$"
        },
        "@id": {
          "type": "string",
          "pattern": "^/dummy_table_inheritance_children/1$"
        },
        "name": {
          "type": "string",
          "pattern": "^foo$",
          "required": "true"
        },
        "nickname": {
          "type": "string",
          "pattern": "^bar$",
          "required": "true"
        }
      }
    }
    """

  @dropSchema
  Scenario: Get the parent entity collection
    When I send a "GET" request to "/dummy_table_inheritances"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@type": {
                "type": "string",
                "pattern": "^DummyTableInheritanceChild$"
              },
              "name": {
                "type": "string",
                "required": "true"
              },
              "nickname": {
                "type": "string",
                "required": "true"
              }
            }
          },
          "minItems": 1
        }
      },
      "required": ["hydra:member"]
    }
    """

  @createSchema
  Scenario: Create a table inherited resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_table_inheritance_children" with body:
    """
    {"name": "foo", "nickname": "bar"}
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@type": {
          "type": "string",
          "pattern": "^DummyTableInheritanceChild$"
        },
        "@context": {
          "type": "string",
          "pattern": "^/contexts/DummyTableInheritanceChild$"
        },
        "@id": {
          "type": "string",
          "pattern": "^/dummy_table_inheritance_children/1$"
        },
        "name": {
          "type": "string",
          "pattern": "^foo$",
          "required": "true"
        },
        "nickname": {
          "type": "string",
          "pattern": "^bar$",
          "required": "true"
        }
      }
    }
    """

  Scenario: Create a different table inherited resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_table_inheritance_different_children" with body:
    """
    {"name": "foo", "email": "bar@localhost"}
    """
    Then the response status code should be 201
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@type": {
          "type": "string",
          "pattern": "^DummyTableInheritanceDifferentChild$"
        },
        "@context": {
          "type": "string",
          "pattern": "^/contexts/DummyTableInheritanceDifferentChild$"
        },
        "@id": {
          "type": "string",
          "pattern": "^/dummy_table_inheritance_different_children/2$"
        },
        "name": {
          "type": "string",
          "pattern": "^foo$",
          "required": "true"
        },
        "email": {
          "type": "string",
          "pattern": "^bar\\@localhost$",
          "required": "true"
        }
      }
    }
    """

  Scenario: Get related entity with multiple inherited children types
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_table_inheritance_relateds" with body:
    """
    {
      "children": [
        "/dummy_table_inheritance_children/1",
        "/dummy_table_inheritance_different_children/2"
      ]
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@type": {
          "type": "string",
          "pattern": "^DummyTableInheritanceRelated$"
        },
        "@context": {
          "type": "string",
          "pattern": "^/contexts/DummyTableInheritanceRelated$"
        },
        "@id": {
          "type": "string",
          "pattern": "^/dummy_table_inheritance_relateds/1$"
        },
        "children": {
          "items": {
            "type": "object",
            "anyOf": [
              {
                "properties": {
                  "@type": {
                    "type": "string",
                    "pattern": "^DummyTableInheritanceChild$"
                  },
                  "name": {
                    "type": "string",
                    "required": "true"
                  },
                  "nickname": {
                    "type": "string",
                    "required": "true"
                  }
                }
              },
              {
                "properties": {
                  "@type": {
                    "type": "string",
                    "pattern": "^DummyTableInheritanceDifferentChild$"
                  },
                  "name": {
                    "type": "string",
                    "required": "true"
                  },
                  "email": {
                    "type": "string",
                    "required": "true"
                  }
                }
              }
            ]
          },
          "minItems": 2,
          "maxItems": 2
        }
      }
    }
    """

  @dropSchema
  Scenario: Get the parent entity collection which contains multiple inherited children type
    When I send a "GET" request to "/dummy_table_inheritances"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "anyOf": [
              {
                "properties": {
                  "@type": {
                    "type": "string",
                    "pattern": "^DummyTableInheritanceChild$"
                  },
                  "name": {
                    "type": "string",
                    "required": "true"
                  },
                  "nickname": {
                    "type": "string",
                    "required": "true"
                  }
                }
              },
              {
                "properties": {
                  "@type": {
                    "type": "string",
                    "pattern": "^DummyTableInheritanceDifferentChild$"
                  },
                  "name": {
                    "type": "string",
                    "required": "true"
                  },
                  "email": {
                    "type": "string",
                    "required": "true"
                  }
                }
              }
            ]
          },
          "minItems": 2
        }
      },
      "required": ["hydra:member"]
    }
    """
