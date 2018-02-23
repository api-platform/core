Feature: Boolean filter on collections
  In order to retrieve ordered large collections of resources
  As a client software developer
  I need to retrieve collections with boolean value

  @createSchema
  Scenario: Get collection by dummyBoolean true
    Given there are 15 dummy objects with dummyBoolean true
    And there are 10 dummy objects with dummyBoolean false
    When I send a "GET" request to "/dummies?dummyBoolean=true"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyBoolean=true"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
    And the JSON node "hydra:totalItems" should be equal to 15

  Scenario: Get collection by dummyBoolean true
    When I send a "GET" request to "/dummies?dummyBoolean=1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyBoolean=1"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
    And the JSON node "hydra:totalItems" should be equal to 15

  Scenario: Get collection by dummyBoolean false
    When I send a "GET" request to "/dummies?dummyBoolean=false"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/16$"},
                  {"pattern": "^/dummies/17$"},
                  {"pattern": "^/dummies/18$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyBoolean=false"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
    And the JSON node "hydra:totalItems" should be equal to 10

  Scenario: Get collection by dummyBoolean false
    When I send a "GET" request to "/dummies?dummyBoolean=0"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/16$"},
                  {"pattern": "^/dummies/17$"},
                  {"pattern": "^/dummies/18$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?dummyBoolean=0"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
    And the JSON node "hydra:totalItems" should be equal to 10

  Scenario: Get collection by embeddedDummy.dummyBoolean true
    Given there are 15 embedded dummy objects with embeddedDummy.dummyBoolean true
    And there are 10 embedded dummy objects with embeddedDummy.dummyBoolean false
    When I send a "GET" request to "/embedded_dummies?embeddedDummy.dummyBoolean=true"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/EmbeddedDummy$"},
        "@id": {"pattern": "^/embedded_dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/embedded_dummies/1$"},
                  {"pattern": "^/embedded_dummies/2$"},
                  {"pattern": "^/embedded_dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/embedded_dummies\\?embeddedDummy\\.dummyBoolean=true"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
    And the JSON node "hydra:totalItems" should be equal to 15

  Scenario: Get collection by embeddedDummy.dummyBoolean true
    When I send a "GET" request to "/embedded_dummies?embeddedDummy.dummyBoolean=1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/EmbeddedDummy$"},
        "@id": {"pattern": "^/embedded_dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/embedded_dummies/1$"},
                  {"pattern": "^/embedded_dummies/2$"},
                  {"pattern": "^/embedded_dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/embedded_dummies\\?embeddedDummy\\.dummyBoolean=1"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
    And the JSON node "hydra:totalItems" should be equal to 15

  Scenario: Get collection by embeddedDummy.dummyBoolean false
    When I send a "GET" request to "/embedded_dummies?embeddedDummy.dummyBoolean=false"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/EmbeddedDummy$"},
        "@id": {"pattern": "^/embedded_dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/embedded_dummies/16$"},
                  {"pattern": "^/embedded_dummies/17$"},
                  {"pattern": "^/embedded_dummies/18$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/embedded_dummies\\?embeddedDummy\\.dummyBoolean=false"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
    And the JSON node "hydra:totalItems" should be equal to 10

  Scenario: Get collection by embeddedDummy.dummyBoolean false
    When I send a "GET" request to "/embedded_dummies?embeddedDummy.dummyBoolean=0"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/EmbeddedDummy$"},
        "@id": {"pattern": "^/embedded_dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/embedded_dummies/16$"},
                  {"pattern": "^/embedded_dummies/17$"},
                  {"pattern": "^/embedded_dummies/18$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/embedded_dummies\\?embeddedDummy\\.dummyBoolean=0"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
    And the JSON node "hydra:totalItems" should be equal to 10

  Scenario: Get collection by association with embed relatedDummy.embeddedDummy.dummyBoolean true
    Given there are 15 embedded dummy objects with relatedDummy.embeddedDummy.dummyBoolean true
    And there are 10 embedded dummy objects with relatedDummy.embeddedDummy.dummyBoolean false
    When I send a "GET" request to "/embedded_dummies?relatedDummy.embeddedDummy.dummyBoolean=true"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/EmbeddedDummy$"},
        "@id": {"pattern": "^/embedded_dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/embedded_dummies/26$"},
                  {"pattern": "^/embedded_dummies/27$"},
                  {"pattern": "^/embedded_dummies/28$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/embedded_dummies\\?relatedDummy.embeddedDummy\\.dummyBoolean=true"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
    And the JSON node "hydra:totalItems" should be equal to 15

  Scenario: Get collection filtered by non valid properties
    When I send a "GET" request to "/dummies?unknown=0"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?unknown=0"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
    And the JSON node "hydra:totalItems" should be equal to 25

    When I send a "GET" request to "/dummies?unknown=1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/2$"},
                  {"pattern": "^/dummies/3$"}
                ]
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?unknown=1"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
    And the JSON node "hydra:totalItems" should be equal to 25
