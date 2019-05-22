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
          "pattern": "^foo$"
        },
        "nickname": {
          "type": "string",
          "pattern": "^bar$"
        }
      },
      "required": [
        "@type",
        "@context",
        "@id",
        "name",
        "nickname"
      ]
    }
    """

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
          "items": [
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^DummyTableInheritanceChild$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/dummy_table_inheritance_children/1$"
                },
                "name": {
                  "type": "string"
                },
                "nickname": {
                  "type": "string"
                }
              },
              "required": [
                "@type",
                "@id",
                "name",
                "nickname"
              ]
            }
          ],
          "additionalItems": false
        }
      },
      "required": [
        "hydra:member"
      ]
    }
    """

  Scenario: Some children not api resources are created in the app
    When some dummy table inheritance data but not api resource child are created
    And I send a "GET" request to "/dummy_table_inheritances"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^DummyTableInheritanceChild$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/dummy_table_inheritance_children/1$"
                },
                "name": {
                  "type": "string"
                }
              },
              "required": [
                "@type",
                "@id",
                "name"
              ]
            },
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^DummyTableInheritance$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/dummy_table_inheritances/2$"
                },
                "name": {
                  "type": "string"
                }
              },
              "required": [
                "@type",
                "@id",
                "name"
              ]
            }
          ],
          "additionalItems": false
        },
        "hydra:totalItems": {
          "type": "integer",
          "minimum": 2,
          "maximum": 2
        }
      },
      "required": [
        "hydra:member",
        "hydra:totalItems"
      ]
    }
    """

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
          "pattern": "^/dummy_table_inheritance_children/3$"
        },
        "name": {
          "type": "string",
          "pattern": "^foo$"
        },
        "nickname": {
          "type": "string",
          "pattern": "^bar$"
        }
      },
      "required": [
        "@type",
        "@context",
        "@id",
        "name",
        "nickname"
      ]
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
          "pattern": "^/dummy_table_inheritance_different_children/4$"
        },
        "name": {
          "type": "string",
          "pattern": "^foo$"
        },
        "email": {
          "type": "string",
          "pattern": "^bar\\@localhost$"
        }
      },
      "required": [
        "@type",
        "@context",
        "@id",
        "name",
        "email"
      ]
    }
    """

  Scenario: Get related entity with multiple inherited children types
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_table_inheritance_relateds" with body:
    """
    {
      "children": [
        "/dummy_table_inheritance_children/1",
        "/dummy_table_inheritance_different_children/4"
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
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^DummyTableInheritanceChild$"
                },
                "name": {
                  "type": "string"
                },
                "nickname": {
                  "type": "string"
                }
              },
              "required": [
                "@type",
                "name",
                "nickname"
              ]
            },
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^DummyTableInheritanceDifferentChild$"
                },
                "name": {
                  "type": "string"
                },
                "email": {
                  "type": "string"
                }
              },
              "required": [
                "@type",
                "name",
                "email"
              ]
            }
          ],
          "additionalItems": false
        }
      },
      "required": [
        "@type",
        "@context",
        "@id",
        "children"
      ]
    }
    """

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
          "items": [
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^DummyTableInheritanceChild$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/dummy_table_inheritance_children/1$"
                },
                "name": {
                  "type": "string"
                },
                "nickname": {
                  "type": "string"
                }
              },
              "required": [
                "@type",
                "@id",
                "name",
                "nickname"
              ]
            },
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^DummyTableInheritance$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/dummy_table_inheritances/2$"
                },
                "name": {
                  "type": "string"
                }
              },
              "required": [
                "@type",
                "@id",
                "name"
              ]
            },
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^DummyTableInheritanceChild$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/dummy_table_inheritance_children/3$"
                },
                "name": {
                  "type": "string"
                },
                "nickname": {
                  "type": "string"
                }
              },
              "required": [
                "@type",
                "@id",
                "name",
                "nickname"
              ]
            }
          ],
          "additionalItems": false
        },
        "hydra:totalItems": {
          "type": "integer",
          "minimum": 4,
          "maximum": 4
        }
      },
      "required": [
        "hydra:member",
        "hydra:totalItems"
      ]
    }
    """

   Scenario: Get the parent interface collection
    When I send a "GET" request to "/resource_interfaces"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^ResourceInterface$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/resource_interfaces/item1"
                },
                "foo": {
                  "type": "string",
                  "pattern": "^item1$"
                },
                "fooz": {
                  "type": "string",
                  "pattern": "^fooz$"
                }
              },
              "required": [
                "@type",
                "@id",
                "foo",
                "fooz"
              ]
            },
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^ResourceInterface$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/resource_interfaces/item2"
                },
                "foo": {
                  "type": "string",
                  "pattern": "^item2$"
                },
                "fooz": {
                  "type": "string",
                  "pattern": "^fooz$"
                }
              },
              "required": [
                "@type",
                "@id",
                "foo",
                "fooz"
              ]
            }
          ],
          "additionalItems": false
        }
      },
      "required": [
        "hydra:member"
      ]
    }
    """

  Scenario: Get an interface resource item
    When I send a "GET" request to "/resource_interfaces/some-id"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {
          "type": "string",
          "pattern": "^/contexts/ResourceInterface$"
        },
        "@id": {
          "type": "string",
          "pattern": "^/resource_interfaces/single%2520item$"
        },
        "@type": {
          "type": "string",
          "pattern": "^ResourceInterface$"
        },
        "foo": {
          "type": "string",
          "pattern": "^single item$"
        },
        "fooz": {
          "type": "string",
          "pattern": "fooz"
        }
      },
      "required": [
        "@context",
        "@id",
        "@type",
        "foo",
        "fooz"
      ],
      "additionalProperties": false
    }
    """

  @!mongodb
  Scenario: Generate iri from parent resource
    Given there are 3 sites with internal owner
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/sites"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^Site$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/sites/1$"
                },
                "title": {
                  "type": "string"
                },
                "description": {
                  "type": "string"
                },
                "owner": {
                  "type": "string",
                  "pattern": "^/custom_users/1$"
                }
              },
              "required": [
                "@type",
                "@id",
                "title",
                "description",
                "owner"
              ]
            },
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^Site$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/sites/2$"
                },
                "title": {
                  "type": "string"
                },
                "description": {
                  "type": "string"
                },
                "owner": {
                  "type": "string",
                  "pattern": "^/custom_users/2$"
                }
              },
              "required": [
                "@type",
                "@id",
                "title",
                "description",
                "owner"
              ]
            },
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^Site$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/sites/3$"
                },
                "title": {
                  "type": "string"
                },
                "description": {
                  "type": "string"
                },
                "owner": {
                  "type": "string",
                  "pattern": "^/custom_users/3$"
                }
              },
              "required": [
                "@type",
                "@id",
                "title",
                "description",
                "owner"
              ]
            }
          ],
          "additionalItems": false
        }
      },
      "required": [
        "hydra:member"
      ]
    }
    """

  @!mongodb
  @createSchema
  Scenario: Generate iri from current resource even if parent class is a resource
    Given there are 3 sites with external owner
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/sites"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^Site$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/sites/1$"
                },
                "title": {
                  "type": "string"
                },
                "description": {
                  "type": "string"
                },
                "owner": {
                  "type": "string",
                  "pattern": "^/external_users/1$"
                }
              },
              "required": [
                "@type",
                "@id",
                "title",
                "description",
                "owner"
              ]
            },
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^Site$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/sites/2$"
                },
                "title": {
                  "type": "string"
                },
                "description": {
                  "type": "string"
                },
                "owner": {
                  "type": "string",
                  "pattern": "^/external_users/2$"
                }
              },
              "required": [
                "@type",
                "@id",
                "title",
                "description",
                "owner"
              ]
            },
            {
              "type": "object",
              "properties": {
                "@type": {
                  "type": "string",
                  "pattern": "^Site$"
                },
                "@id": {
                  "type": "string",
                  "pattern": "^/sites/3$"
                },
                "title": {
                  "type": "string"
                },
                "description": {
                  "type": "string"
                },
                "owner": {
                  "type": "string",
                  "pattern": "^/external_users/3$"
                }
              },
              "required": [
                "@type",
                "@id",
                "title",
                "description",
                "owner"
              ]
            }
          ],
          "additionalItems": false
        }
      },
      "required": [
        "hydra:member"
      ]
    }
    """
