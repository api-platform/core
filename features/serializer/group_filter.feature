Feature: Filter with serialization groups on items and collections
  In order to retrieve, create and update resources or large collections of resources
  As a client software developer
  I need to retrieve, create and update resources or collections of resources with serialization groups

  @createSchema
  Scenario: Get a collection of resources by group dummy_foo without overriding
    Given there are 10 dummy group objects
    When I send a "GET" request to "/dummy_groups?groups[]=dummy_foo"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
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
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?groups%5B%5D=dummy_foo&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a collection of resources by group dummy_foo with overriding
    When I send a "GET" request to "/dummy_groups?override_groups[]=dummy_foo"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
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
            "@id": {"pattern": "^/dummy_groups\\?override_groups%5B%5D=dummy_foo&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a collection of resources by groups dummy_foo, dummy_qux and without overriding
    When I send a "GET" request to "/dummy_groups?groups[]=dummy_foo&groups[]=dummy_qux"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
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
                "bar": {},
                "baz": {},
                "qux": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz", "qux"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {},
                "qux": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz", "qux"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {},
                "qux": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz", "qux"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?groups%5B%5D=dummy_foo&groups%5B%5D=dummy_qux&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a collection of resources by groups dummy_foo, dummy_qux and with overriding
    When I send a "GET" request to "/dummy_groups?override_groups[]=dummy_foo&override_groups[]=dummy_qux"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
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
                "qux": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo", "qux"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {},
                "qux": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo", "qux"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {},
                "qux": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo", "qux"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?override_groups%5B%5D=dummy_foo&override_groups%5B%5D=dummy_qux&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """


  Scenario: Get a collection of resources by groups dummy_foo, dummy_qux, without overriding and with whitelist
    When I send a "GET" request to "/dummy_groups?whitelisted_groups[]=dummy_foo&whitelisted_groups[]=dummy_qux"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
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
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?whitelisted_groups%5B%5D=dummy_foo&whitelisted_groups%5B%5D=dummy_qux&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a collection of resources by groups dummy_foo, dummy_qux with overriding and with whitelist
    When I send a "GET" request to "/dummy_groups?override_whitelisted_groups[]=dummy_foo&override_whitelisted_groups[]=dummy_qux"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
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
            "@id": {"pattern": "^/dummy_groups\\?override_whitelisted_groups%5B%5D=dummy_foo&override_whitelisted_groups%5B%5D=dummy_qux&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a collection of resources by group empty and without overriding
    When I send a "GET" request to "/dummy_groups?groups[]="
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
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
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?groups%5B%5D=&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a collection of resources by group empty and with overriding
    When I send a "GET" request to "/dummy_groups?override_groups[]="
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
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
            "@id": {"pattern": "^/dummy_groups\\?override_groups%5B%5D=&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get a resource by group dummy_foo without overriding
    When I send a "GET" request to "/dummy_groups/1?groups[]=dummy_foo"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "id": {},
        "foo": {},
        "bar": {},
        "baz": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "id", "foo", "bar", "baz"]
    }
    """

  Scenario: Get a resource by group dummy_foo with overriding
    When I send a "GET" request to "/dummy_groups/1?override_groups[]=dummy_foo"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "foo": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "foo"]
    }
    """

  Scenario: Get a resource by groups dummy_foo, dummy_qux and without overriding
    When I send a "GET" request to "/dummy_groups/1?groups[]=dummy_foo&groups[]=dummy_qux"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "id": {},
        "foo": {},
        "bar": {},
        "baz": {},
        "qux": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "id", "foo", "bar", "baz", "qux"]
    }
    """

  Scenario: Get a resource by groups dummy_foo, dummy_qux and with overriding
    When I send a "GET" request to "/dummy_groups/1?override_groups[]=dummy_foo&override_groups[]=dummy_qux"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "foo": {},
        "qux": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "foo", "qux"]
    }
    """

  Scenario: Get a resource by groups dummy_foo, dummy_qux and without overriding and with whitelist
    When I send a "GET" request to "/dummy_groups/1?whitelisted_groups[]=dummy_foo&whitelisted_groups[]=dummy_qux"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "id": {},
        "foo": {},
        "bar": {},
        "baz": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "id", "foo", "bar", "baz"]
    }
    """

  Scenario: Get a resource by groups dummy_foo, dummy_qux and with overriding and with whitelist
    When I send a "GET" request to "/dummy_groups/1?override_whitelisted_groups[]=dummy_foo&override_whitelisted_groups[]=dummy_qux"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "foo": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "foo"]
    }
    """

  Scenario: Get a resource by group empty and without overriding
    When I send a "GET" request to "/dummy_groups/1?groups[]="
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "id": {},
        "foo": {},
        "bar": {},
        "baz": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "id", "foo", "bar", "baz"]
    }
    """

  Scenario: Get a resource by group empty and with overriding
    When I send a "GET" request to "/dummy_groups/1?override_groups[]="
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type"]
    }
    """

  Scenario: Create a resource by group dummy_foo and without overriding
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_groups?groups[]=dummy_foo" with body:
    """
    {
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/DummyGroup",
      "@id": "\/dummy_groups\/11",
      "@type": "DummyGroup",
      "id": 11,
      "foo": "Foo",
      "bar": "Bar",
      "baz": null
    }
    """

  Scenario: Create a resource by group dummy_foo and with overriding
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_groups?override_groups[]=dummy_foo" with body:
    """
    {
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/DummyGroup",
      "@id": "\/dummy_groups\/12",
      "@type": "DummyGroup",
      "foo": "Foo"
    }
    """

  Scenario: Create a resource by groups dummy_foo, dummy_baz, dummy_qux and without overriding
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_groups?groups[]=dummy_foo&groups[]=dummy_baz&groups[]=dummy_qux" with body:
    """
    {
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/DummyGroup",
      "@id": "\/dummy_groups\/13",
      "@type": "DummyGroup",
      "id": 13,
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """

  Scenario: Create a resource by groups dummy_foo, dummy_baz, dummy_qux and with overriding
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_groups?override_groups[]=dummy_foo&override_groups[]=dummy_baz&override_groups[]=dummy_qux" with body:
    """
    {
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/DummyGroup",
      "@id": "\/dummy_groups\/14",
      "@type": "DummyGroup",
      "foo": "Foo",
      "baz": "Baz",
      "qux": "Qux"
    }
    """

  Scenario: Create a resource by groups dummy, dummy_baz, without overriding
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_groups?groups[]=dummy&groups[]=dummy_baz" with body:
    """
    {
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/DummyGroup",
      "@id": "\/dummy_groups\/15",
      "@type": "DummyGroup",
      "id": 15,
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """

  Scenario: Create a resource by groups dummy, dummy_baz and with overriding
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_groups?override_groups[]=dummy&override_groups[]=dummy_baz" with body:
    """
    {
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/DummyGroup",
      "@id": "\/dummy_groups\/16",
      "@type": "DummyGroup",
      "id": 16,
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """

  Scenario: Create a resource by group empty and without overriding
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_groups?groups[]=" with body:
    """
    {
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/DummyGroup",
      "@id": "\/dummy_groups\/17",
      "@type": "DummyGroup",
      "id": 17,
      "foo": "Foo",
      "bar": "Bar",
      "baz": null
    }
    """

  Scenario: Create a resource by group empty and with overriding
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_groups?override_groups[]=" with body:
    """
    {
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/DummyGroup",
      "@id": "\/dummy_groups\/18",
      "@type": "DummyGroup"
    }
    """

  Scenario: Create a resource by groups dummy, dummy_baz, without overriding and with whitelist
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_groups?whitelisted_groups[]=dummy&whitelisted_groups[]=dummy_baz" with body:
    """
    {
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/DummyGroup",
      "@id": "\/dummy_groups\/19",
      "@type": "DummyGroup",
      "id": 19,
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz"
    }
    """

  Scenario: Create a resource by groups dummy, dummy_baz, with overriding and with whitelist
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_groups?override_whitelisted_groups[]=dummy&override_whitelisted_groups[]=dummy_baz" with body:
    """
    {
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "\/contexts\/DummyGroup",
      "@id": "\/dummy_groups\/20",
      "@type": "DummyGroup",
      "baz": "Baz"
    }
    """
