Feature: Order filter on collections
  In order to retrieve ordered large collections of resources
  As a client software developer
  I need to retrieve collections ordered properties

  @createSchema
  Scenario: Get collection ordered in ascending order on an integer property and on which order filter has been enabled in whitelist mode
    Given there is "30" dummy objects
    When I send a "GET" request to "/dummies?order[id]=asc"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/1$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/2$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/3$"
                }
              }
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?order%5Bid%5D=asc"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection ordered in descending order on an integer property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[id]=desc"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/30$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/29$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/28$"
                }
              }
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?order%5Bid%5D=desc"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection ordered in ascending order on a string property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[name]=asc"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/1$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/10$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/11$"
                }
              }
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?order%5Bname%5D=asc"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection ordered in descending order on a string property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[name]=desc"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/9$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/8$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/7$"
                }
              }
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?order%5Bname%5D=desc"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection ordered by default configured order on a string property and on which order filter has been enabled in whitelist mode with default descending order
    When I send a "GET" request to "/dummies?order[name]"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/9$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/8$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/7$"
                }
              }
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?order%5Bname%5D="},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection ordered in ascending order on an association and on which order filter has been enabled in whitelist mode
    Given there is "30" dummy objects with relatedDummy
    When I send a "GET" request to "/dummies?order[relatedDummy]=asc"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/1$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/2$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/3$"
                }
              }
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?order%5BrelatedDummy%5D=asc"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  @dropSchema
  Scenario: Get collection ordered by a non valid properties and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[alias]=asc"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/1$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/2$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/3$"
                }
              }
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?order%5Balias%5D=asc"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?order[alias]=desc"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/1$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/2$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/3$"
                }
              }
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?order%5Balias%5D=desc"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?order[unknown]=asc"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/1$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/2$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/3$"
                }
              }
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?order%5Bunknown%5D=asc"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?order[unknown]=desc"
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
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/1$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/2$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/dummies/3$"
                }
              }
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummies\\?order%5Bunknown%5D=desc"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
