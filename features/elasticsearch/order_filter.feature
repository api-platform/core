@elasticsearch
Feature: Order filter on collections from Elasticsearch
  In order to retrieve ordered large collections of resources from Elasticsearch
  As a client software developer
  I need to retrieve collections ordered properties

  Scenario: Get collection ordered in ascending order on an identifier property
    When I send a "GET" request to "/tweets?order%5Bid%5D=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/0acfd90d-5bfe-4e42-b708-dc38bf20677c$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/0cfe3d33-6116-416b-8c50-3b8319331998$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/1c9e0545-1b37-4a9a-83e0-30400d0b354e$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?order%5Bid%5D=asc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection ordered in descending order on an identifier property
    When I send a "GET" request to "/tweets?order%5Bid%5D=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f91bca21-b5f8-405b-9b08-d5a5dc476a92$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f36a0026-0635-4865-86a6-5adb21d94d64$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f2e65123-e063-44a0-b640-b0a04554d19e$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?order%5Bid%5D=desc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection ordered in ascending order on an identifier property and in ascending order on a nested identifier property
    When I send a "GET" request to "/tweets?order%5Bauthor.id%5D=asc&order%5Bid%5D=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/89601e1c-3ef2-4ef7-bca2-7511d38611c6$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/9da70727-d656-42d9-876a-1be6321f171b$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f36a0026-0635-4865-86a6-5adb21d94d64$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?order%5Bauthor.id%5D=asc&order%5Bid%5D=asc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection ordered in descending order on an identifier property and in ascending order on a nested identifier property
    When I send a "GET" request to "/tweets?order%5Bauthor.id%5D=asc&order%5Bid%5D=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f36a0026-0635-4865-86a6-5adb21d94d64$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/9da70727-d656-42d9-876a-1be6321f171b$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/89601e1c-3ef2-4ef7-bca2-7511d38611c6$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?order%5Bauthor.id%5D=asc&order%5Bid%5D=desc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection ordered in ascending order on an identifier property and in descending order on a nested identifier property
    When I send a "GET" request to "/tweets?order%5Bauthor.id%5D=desc&order%5Bid%5D=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/3a1d02fa-2347-41ff-80ef-ed9b9c0efea9$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/1c9e0545-1b37-4a9a-83e0-30400d0b354e$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/7cdadcda-3fb5-4312-9e32-72acba323cc0$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?order%5Bauthor.id%5D=desc&order%5Bid%5D=asc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """

  Scenario: Get collection ordered in descending order on an identifier property and in descending order on a nested identifier property
    When I send a "GET" request to "/tweets?order%5Bauthor.id%5D=desc&order%5Bid%5D=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/3a1d02fa-2347-41ff-80ef-ed9b9c0efea9$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/811e4d1c-df3f-4d24-a9da-2a28080c85f5$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/7cdadcda-3fb5-4312-9e32-72acba323cc0$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?order%5Bauthor.id%5D=desc&order%5Bid%5D=desc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
    """
