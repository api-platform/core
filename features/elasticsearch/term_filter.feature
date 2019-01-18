@elasticsearch
Feature: Term filter on collections from Elasticsearch
  In order to get specific results from a large collections of resources from Elasticsearch
  As a client software developer
  I need to search for resources containing the exact terms specified

  Scenario: Term filter on an identifier property
    When I send a "GET" request to "/users?id=%2Fusers%2Fcf875c95-41ab-48df-af66-38c74db18f72"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 1,
          "minItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/users/cf875c95-41ab-48df-af66-38c74db18f72$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?id=%2Fusers%2Fcf875c95-41ab-48df-af66-38c74db18f72$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Term filter on a property of keyword type
    When I send a "GET" request to "/users?gender=female"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 3,
          "minItems": 3,
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/users/6a457188-d1ba-45e3-8509-81e5c66a5297$"},
                  {"pattern": "^/users/89d4ae3d-73bc-4382-b01c-adf038f893c2$"},
                  {"pattern": "^/users/cf875c95-41ab-48df-af66-38c74db18f72$"}
                ]
              },
              "gender": {"pattern": "^female$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?gender=female&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Combining term filters on a property of integer type and a property of keyword type
    When I send a "GET" request to "/users?age=42&gender=female"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 2,
          "minItems": 2,
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/users/89d4ae3d-73bc-4382-b01c-adf038f893c2$"},
                  {"pattern": "^/users/fa7d4578-6692-47ec-9346-a8ab25ca613c$"}
                ]
              },
              "age": {
                "type": "integer",
                "maximum": 42,
                "minimum": 42
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?age=42&gender=female$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Combining term filters on a property of integer type and a property of keyword type
    When I send a "GET" request to "/users?age=42&gender=male"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 0,
          "minItems": 0
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?age=42&gender=male$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Term filter on a property of text type
    When I send a "GET" request to "/users?firstName=xavier"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 1,
          "minItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/users/f18eb7ab-6985-4e05-afd4-13a638c929d4$"},
              "firstName": {"pattern": "^Xavier$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?firstName=xavier$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Term filter on a nested identifier property
    When I send a "GET" request to "/users?tweets.id=%2Ftweets%2Fdcaef1db-225d-442b-960e-5de6984a44be"
    Then the response should be in JSON
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 1,
          "minItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/users/89d4ae3d-73bc-4382-b01c-adf038f893c2$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?tweets.id=%2Ftweets%2Fdcaef1db-225d-442b-960e-5de6984a44be$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Term filter on a nested property of date type
    When I send a "GET" request to "/users?tweets.date=2018-02-02%2014%3A14%3A14"
    Then the response should be in JSON
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 1,
          "minItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/users/fa7d4578-6692-47ec-9346-a8ab25ca613c$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?tweets.date=2018-02-02%2014%3A14%3A14$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
