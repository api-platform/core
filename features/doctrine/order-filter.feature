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
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?order\\[id\\]=asc$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
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
        }
      }
    }
    """

  Scenario: Get collection ordered in descending order on an integer property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[id]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?order\\[id\\]=desc$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/30$"},
                  {"pattern": "^/dummies/29$"},
                  {"pattern": "^/dummies/28$"}
                ]
              }
            }
          }
        }
      }
    }
    """

  Scenario: Get collection ordered in ascending order on a string property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[name]=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?order\\[name\\]=asc$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/1$"},
                  {"pattern": "^/dummies/10$"},
                  {"pattern": "^/dummies/11$"}
                ]
              }
            }
          }
        }
      }
    }
    """

  Scenario: Get collection ordered in descending order on a string property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[name]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?order\\[name\\]=desc$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/9$"},
                  {"pattern": "^/dummies/8$"},
                  {"pattern": "^/dummies/7$"}
                ]
              }
            }
          }
        }
      }
    }
    """

  Scenario: Get collection ordered by default configured order on a string property and on which order filter has been enabled in whitelist mode with default descending order
    When I send a "GET" request to "/dummies?order[name]"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?order\\[name\\]$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/dummies/9$"},
                  {"pattern": "^/dummies/8$"},
                  {"pattern": "^/dummies/7$"}
                ]
              }
            }
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
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?order\\[relatedDummy\\]=asc$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
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
        }
      }
    }
    """

  @dropSchema
  Scenario: Get collection ordered by a non valid properties and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[alias]=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?order\\[alias\\]=asc$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
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
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?order[alias]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?order\\[alias\\]=desc$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
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
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?order[unknown]=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?order\\[unknown\\]=asc$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
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
        }
      }
    }
    """

    When I send a "GET" request to "/dummies?order[unknown]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Dummy$"},
        "@id": {"pattern": "^/dummies\\?order\\[unknown\\]=desc$"},
        "@type": {"pattern": "^hydra:PagedCollection$"},
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
        }
      }
    }
    """
