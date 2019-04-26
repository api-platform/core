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
          "pattern": "^DummyTableInheritanceChild$",
          "required": "true"
        },
        "@context": {
          "type": "string",
          "pattern": "^/contexts/DummyTableInheritanceChild$",
          "required": "true"
        },
        "@id": {
          "type": "string",
          "pattern": "^/dummy_table_inheritance_children/1$",
          "required": "true"
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
                "pattern": "^DummyTableInheritanceChild$",
                "required": "true"
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
          "items": {
            "type": "object",
            "properties": {
              "@type": {
                "type": "string",
                "pattern": "^DummyTableInheritance(Child)?$",
                "required": "true"
              },
              "name": {
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
          "pattern": "^DummyTableInheritanceChild$",
          "required": "true"
        },
        "@context": {
          "type": "string",
          "pattern": "^/contexts/DummyTableInheritanceChild$",
          "required": "true"
        },
        "@id": {
          "type": "string",
          "pattern": "^/dummy_table_inheritance_children/3$",
          "required": "true"
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
          "pattern": "^DummyTableInheritanceDifferentChild$",
          "required": "true"
        },
        "@context": {
          "type": "string",
          "pattern": "^/contexts/DummyTableInheritanceDifferentChild$",
          "required": "true"
        },
        "@id": {
          "type": "string",
          "pattern": "^/dummy_table_inheritance_different_children/4$",
          "required": "true"
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
          "pattern": "^DummyTableInheritanceRelated$",
          "required": "true"
        },
        "@context": {
          "type": "string",
          "pattern": "^/contexts/DummyTableInheritanceRelated$",
          "required": "true"
        },
        "@id": {
          "type": "string",
          "pattern": "^/dummy_table_inheritance_relateds/1$",
          "required": "true"
        },
        "children": {
          "items": {
            "type": "object",
            "anyOf": [
              {
                "properties": {
                  "@type": {
                    "type": "string",
                    "pattern": "^DummyTableInheritanceChild$",
                    "required": "true"
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
                    "pattern": "^DummyTableInheritance$",
                    "required": "true"
                  },
                  "name": {
                    "type": "string",
                    "required": "true"
                  }
                }
              },
              {
                "properties": {
                  "@type": {
                    "type": "string",
                    "pattern": "^DummyTableInheritanceDifferentChild$",
                    "required": "true"
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
                    "pattern": "^DummyTableInheritanceChild$",
                    "required": "true"
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
                    "pattern": "^DummyTableInheritance$",
                    "required": "true"
                  },
                  "name": {
                    "type": "string",
                    "required": "true"
                  }
                }
              },
              {
                "properties": {
                  "@type": {
                    "type": "string",
                    "pattern": "^DummyTableInheritanceDifferentChild$",
                    "required": "true"
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
           "items": {
             "type": "object",
             "properties": {
               "@type": {
                 "type": "string",
                 "pattern": "^ResourceInterface$",
                 "required": "true"
               },
               "@id": {
                 "type": "string",
                 "pattern": "^/resource_interfaces/",
                 "required": "true"
               },
               "foo": {
                 "type": "string",
                 "required": "true"
               },
               "fooz": {
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
        "context": {
          "type": "string",
          "pattern": "ResourceInterface$"
        },
        "@id": {
          "type": "string",
          "pattern": "^/resource_interfaces",
          "required": "true"
        },
        "@type": {
          "type": "string",
          "pattern": "^ResourceInterface$",
          "required": "true"
        },
        "foo": {
          "type": "string",
          "required": "true"
        },
        "fooz": {
          "type": "string",
          "required": "true",
          "pattern": "fooz"
        }
      }
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
          "items": {
            "type": "object",
            "properties": {
              "@type": {
                "type": "string",
                "pattern": "^Site$",
                "required": "true"
              },
              "@id": {
                "type": "string",
                "pattern": "^/sites/\\d+$",
                "required": "true"
              },
              "id": {
                "type": "integer",
                "required": "true"
              },
              "title": {
                "type": "string",
                "required": "true"
              },
              "description": {
                "type": "string",
                "required": "true"
              },
              "owner": {
                "type": "string",
                "pattern": "^/custom_users/\\d+$",
                "required": "true"
              }
            }
          },
          "minItems": 3,
          "maxItems": 3,
          "required": "true"
        }
      }
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
          "items": {
            "type": "object",
            "properties": {
              "@type": {
                "type": "string",
                "pattern": "^Site$",
                "required": "true"
              },
              "@id": {
                "type": "string",
                "pattern": "^/sites/\\d+$",
                "required": "true"
              },
              "id": {
                "type": "integer",
                "required": "true"
              },
              "title": {
                "type": "string",
                "required": "true"
              },
              "description": {
                "type": "string",
                "required": "true"
              },
              "owner": {
                "type": "string",
                "pattern": "^/external_users/\\d+$",
                "required": "true"
              }
            }
          },
          "minItems": 3,
          "maxItems": 3,
          "required": "true"
        }
      }
    }
    """
