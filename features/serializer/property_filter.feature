Feature: Filter with serialization attributes on items and collections
  In order to retrieve, create and update resources or large collection of resources
  As a client software developer
  I need to retrieve, create and update resources or collections of resources with serialization attributes

  @createSchema
  Scenario: Get a collection of resources by attributes id, foo and bar
    Given there are 10 dummy property objects
    When I send a "GET" request to "/dummy_properties?properties[]=id&properties[]=foo&properties[]=bar"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_properties\\?properties%5B%5D=id&properties%5B%5D=foo&properties%5B%5D=bar&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a collection of resources by attributes foo, bar, group.baz and group.qux
    When I send a "GET" request to "/dummy_properties?properties[]=foo&properties[]=bar&properties[group][]=baz&properties[group][]=qux"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {},
                "bar": {},
                "group": {
                  "type": "object",
                  "properties": {
                    "@id": {},
                    "@type": {},
                    "baz": {}
                  },
                  "additionalProperties": false,
                  "required": ["@id", "@type", "baz"]
                }
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo", "bar"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {},
                "bar": {},
                "group": {
                  "type": "object",
                  "properties": {
                    "@id": {},
                    "@type": {},
                    "baz": {}
                  },
                  "additionalProperties": false,
                  "required": ["@id", "@type", "baz"]
                }
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo", "bar"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {},
                "bar": {},
                "group": {
                  "type": "object",
                  "properties": {
                    "@id": {},
                    "@type": {},
                    "baz": {}
                  },
                  "additionalProperties": false,
                  "required": ["@id", "@type", "baz"]
                }
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo", "bar"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_properties\\?properties%5B%5D=foo&properties%5B%5D=bar&properties%5Bgroup%5D%5B%5D=baz&properties%5Bgroup%5D%5B%5D=qux&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a collection of resources by attributes foo, bar
    When I send a "GET" request to "/dummy_properties?whitelisted_properties[]=foo&whitelisted_properties[]=bar"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_properties\\?whitelisted_properties%5B%5D=foo&whitelisted_properties%5B%5D=bar&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a collection of resources by attributes foo, bar, group.baz and group.qux
    When I send a "GET" request to "/dummy_properties?whitelisted_nested_properties[]=foo&whitelisted_nested_properties[]=bar&whitelisted_nested_properties[group][]=baz"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {},
                "group": {
                  "type": "object",
                  "properties": {
                    "@id": {},
                    "@type": {},
                    "baz": {}
                  },
                  "additionalProperties": false,
                  "required": ["@id", "@type", "baz"]
                }
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {},
                "group": {
                  "type": "object",
                  "properties": {
                    "@id": {},
                    "@type": {},
                    "baz": {}
                  },
                  "additionalProperties": false,
                  "required": ["@id", "@type", "baz"]
                }
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {},
                "group": {
                  "type": "object",
                  "properties": {
                    "@id": {},
                    "@type": {},
                    "baz": {}
                  },
                  "additionalProperties": false,
                  "required": ["@id", "@type"]
                }
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_properties\\?whitelisted_nested_properties%5B%5D=foo&whitelisted_nested_properties%5B%5D=bar&whitelisted_nested_properties%5Bgroup%5D%5B%5D=baz&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a collection of resources by attributes bar not allowed
    When I send a "GET" request to "/dummy_properties?whitelisted_properties[]=bar"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_properties\\?whitelisted_properties%5B%5D=bar&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a collection of resources by attributes empty
    When I send a "GET" request to "/dummy_properties?properties[]=&properties[group][]="
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "group": {
                  "type": "object",
                  "properties": {
                    "@id": {},
                    "@type": {}
                  },
                  "additionalProperties": false,
                  "required": ["@id", "@type"]
                }
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "group"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "group": {
                  "type": "object",
                  "properties": {
                    "@id": {},
                    "@type": {}
                  },
                  "additionalProperties": false,
                  "required": ["@id", "@type"]
                }
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "group"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "group": {
                  "type": "object",
                  "properties": {
                    "@id": {},
                    "@type": {}
                  },
                  "additionalProperties": false,
                  "required": ["@id", "@type"]
                }
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "group"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_properties\\?properties%5B%5D=&properties%5Bgroup%5D%5B%5D=&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a resource by attributes id, foo and bar
    When I send a "GET" request to "/dummy_properties/1?properties[]=id&properties[]=foo&properties[]=bar"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties/1$"},
        "@type": {"pattern": "^DummyProperty$"},
        "id": {},
        "foo": {},
        "bar": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "id", "foo", "bar"]
    }
    """

  Scenario: Get a resource by attributes foo, bar, group.baz and group.qux
    When I send a "GET" request to "/dummy_properties/1?properties[]=foo&properties[]=bar&properties[group][]=baz&properties[group][]=qux"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties/1$"},
        "@type": {"pattern": "^DummyProperty$"},
        "foo": {},
        "bar": {},
        "group": {
          "type": "object",
          "properties": {
            "@id": {},
            "@type": {},
            "baz": {}
          },
          "additionalProperties": false,
          "required": ["@id", "@type", "baz"]
        }
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "foo", "bar", "group"]
    }
    """

  Scenario: Get a resource by attributes empty
    When I send a "GET" request to "/dummy_properties/1?properties[]=&properties[group][]="
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties/1$"},
        "@type": {"pattern": "^DummyProperty$"},
        "group": {
          "type": "object",
          "properties": {
            "@id": {},
            "@type": {}
          },
          "additionalProperties": false,
          "required": ["@id", "@type"]
        }
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "group"]
    }
    """

  Scenario: Create a resource by attributes foo and bar
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_properties?properties[]=foo&properties[]=bar" with body:
    """
    {
      "foo": "Foo",
      "bar": "Bar"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/DummyProperty",
      "@id": "\/dummy_properties\/11",
      "@type": "DummyProperty",
      "foo": "Foo",
      "bar": "Bar"
    }
    """

  Scenario: Create a resource by attributes foo, bar, group.foo, group.baz and group.qux
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_properties?properties[]=foo&properties[]=bar&properties[group][]=foo&properties[group][]=baz&properties[group][]=qux" with body:
    """
    {
      "foo": "Foo",
      "bar": "Bar",
      "group": {
        "foo": "Foo",
        "baz": "Baz",
        "qux": "Qux"
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/DummyProperty",
      "@id": "\/dummy_properties\/12",
      "@type": "DummyProperty",
      "foo": "Foo",
      "bar": "Bar",
      "group": {
        "@id": "\/dummy_groups\/11",
        "@type": "DummyGroup",
        "foo": "Foo",
        "baz": null
      }
    }
    """
