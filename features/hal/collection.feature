Feature: HAL Collections support
  In order to retrieve large collections of resources
  As a client software developer
  I need to retrieve paged collections respecting the HAL specification

  @createSchema
  Scenario: Retrieve an empty collection
    When I add "Accept" header equal to "application/hal+json"
    When I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "/dummies"
        }
      },
      "totalItems": 0,
      "itemsPerPage": 3
    }
    """

  Scenario: Retrieve the first page of a collection
    Given there are 10 dummy objects
    And I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "/dummies?page=1"
        },
        "first": {
          "href": "/dummies?page=1"
        },
        "last": {
          "href": "/dummies?page=4"
        },
        "next": {
          "href": "/dummies?page=2"
        },
        "item": [
          {
            "href": "/dummies/1"
          },
          {
            "href": "/dummies/2"
          },
          {
            "href": "/dummies/3"
          }
        ]
      },
      "totalItems": 10,
      "itemsPerPage": 3,
      "_embedded": {
        "item": [
          {
            "_links": {
              "self": {
                "href": "/dummies/1"
              }
            },
            "description": "Smart dummy.",
            "dummy": "SomeDummyTest1",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 1,
            "name": "Dummy #1",
            "alias": "Alias #9",
            "foo": null
          },
          {
            "_links": {
              "self": {
                "href": "/dummies/2"
              }
            },
            "description": "Not so smart dummy.",
            "dummy": "SomeDummyTest2",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 2,
            "name": "Dummy #2",
            "alias": "Alias #8",
            "foo": null
          },
          {
            "_links": {
              "self": {
                "href": "/dummies/3"
              }
            },
            "description": "Smart dummy.",
            "dummy": "SomeDummyTest3",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 3,
            "name": "Dummy #3",
            "alias": "Alias #7",
            "foo": null
          }
        ]
      }
    }
    """

  Scenario: Retrieve a page of a collection
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies?page=3"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "/dummies?page=3"
        },
        "first": {
          "href": "/dummies?page=1"
        },
        "last": {
          "href": "/dummies?page=4"
        },
        "prev": {
          "href": "/dummies?page=2"
        },
        "next": {
          "href": "/dummies?page=4"
        },
        "item": [
          {
            "href": "/dummies/7"
          },
          {
            "href": "/dummies/8"
          },
          {
            "href": "/dummies/9"
          }
        ]
      },
      "totalItems": 10,
      "itemsPerPage": 3,
      "_embedded": {
        "item": [
          {
            "_links": {
              "self": {
                "href": "/dummies/7"
              }
            },
            "description": "Smart dummy.",
            "dummy": "SomeDummyTest7",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 7,
            "name": "Dummy #7",
            "alias": "Alias #3",
            "foo": null
          },
          {
            "_links": {
              "self": {
                "href": "/dummies/8"
              }
            },
            "description": "Not so smart dummy.",
            "dummy": "SomeDummyTest8",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 8,
            "name": "Dummy #8",
            "alias": "Alias #2",
            "foo": null
          },
          {
            "_links": {
              "self": {
                "href": "/dummies/9"
              }
            },
            "description": "Smart dummy.",
            "dummy": "SomeDummyTest9",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 9,
            "name": "Dummy #9",
            "alias": "Alias #1",
            "foo": null
          }
        ]
      }
    }
    """

  Scenario: Retrieve the last page of a collection
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies?page=4"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "/dummies?page=4"
        },
        "first": {
          "href": "/dummies?page=1"
        },
        "last": {
          "href": "/dummies?page=4"
        },
        "prev": {
          "href": "/dummies?page=3"
        },
        "item": [
          {
            "href": "/dummies/10"
          }
        ]
      },
      "totalItems": 10,
      "itemsPerPage": 3,
      "_embedded": {
        "item": [
          {
            "_links": {
              "self": {
                "href": "/dummies/10"
              }
            },
            "description": "Not so smart dummy.",
            "dummy": "SomeDummyTest10",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 10,
            "name": "Dummy #10",
            "alias": "Alias #0",
            "foo": null
          }
        ]
      }
    }
    """

  @!mongodb
  Scenario: Enable the partial pagination client side
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies?page=2&partial=1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "/dummies?partial=1&page=2"
        },
        "prev": {
          "href": "/dummies?partial=1&page=1"
        },
        "next": {
          "href": "/dummies?partial=1&page=3"
        },
        "item": [
          {
            "href": "/dummies/4"
          },
          {
            "href": "/dummies/5"
          },
          {
            "href": "/dummies/6"
          }
        ]
      },
      "itemsPerPage": 3,
      "_embedded": {
        "item": [
          {
            "_links": {
              "self": {
                "href": "/dummies/4"
              }
            },
            "description": "Not so smart dummy.",
            "dummy": "SomeDummyTest4",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 4,
            "name": "Dummy #4",
            "alias": "Alias #6",
            "foo": null
          },
          {
            "_links": {
              "self": {
                "href": "/dummies/5"
              }
            },
            "description": "Smart dummy.",
            "dummy": "SomeDummyTest5",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 5,
            "name": "Dummy #5",
            "alias": "Alias #5",
            "foo": null
          },
          {
            "_links": {
              "self": {
                "href": "/dummies/6"
              }
            },
            "description": "Not so smart dummy.",
            "dummy": "SomeDummyTest6",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 6,
            "name": "Dummy #6",
            "alias": "Alias #4",
            "foo": null
          }
        ]
      }
    }
    """

  Scenario: Disable the pagination client side
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies?pagination=0"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "_links": {
          "type": "object",
          "properties": {
            "self": {
              "type": "object",
              "properties": {"href": {"pattern": "^/dummies\\?pagination=0$"}}
            },
            "item": {
              "type": "array",
              "minItems": 10,
              "maxItems": 10,
              "items": {
                "type": "object",
                "properties": {"href": {"pattern": "^/dummies/[0-9]+$"}}
              }
            }
          }
        },
        "totalItems": {"type":"number", "minimum": 10, "maximum": 10},
        "_embedded": {
          "type": "object",
          "properties": {
            "item": {
              "type": "array",
              "minItems": 10,
              "maxItems": 10,
              "items": {
                "type": "object",
                "properties": {
                  "_links": {
                    "type": "object",
                    "properties": {
                      "self": {
                        "type": "object",
                        "properties": {"href": {"pattern": "^/dummies/[0-9]+$"}}
                      }
                    }
                  },
                  "description": {"pattern": "(Smart dummy.|Not so smart dummy.)"}
                }
              }
            }
          }
        }
      },
      "additionalProperties": false
    }
    """

  Scenario: Change the number of element by page client side
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies?page=2&itemsPerPage=1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "/dummies?itemsPerPage=1&page=2"
        },
        "first": {
          "href": "/dummies?itemsPerPage=1&page=1"
        },
        "last": {
          "href": "/dummies?itemsPerPage=1&page=10"
        },
        "prev": {
          "href": "/dummies?itemsPerPage=1&page=1"
        },
        "next": {
          "href": "/dummies?itemsPerPage=1&page=3"
        },
        "item": [
          {
            "href": "/dummies/2"
          }
        ]
      },
      "totalItems": 10,
      "itemsPerPage": 1,
      "_embedded": {
        "item": [
          {
            "_links": {
              "self": {
                "href": "/dummies/2"
              }
            },
            "description": "Not so smart dummy.",
            "dummy": "SomeDummyTest2",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 2,
            "name": "Dummy #2",
            "alias": "Alias #8",
            "foo": null
          }
        ]
      }
    }
    """

  Scenario: Filter with a raw URL
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies?id=%2fdummies%2f8"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "/dummies?id=%2Fdummies%2F8"
        },
        "item": [
          {
            "href": "/dummies/8"
          }
        ]
      },
      "totalItems": 1,
      "itemsPerPage": 3,
      "_embedded": {
        "item": [
          {
            "_links": {
              "self": {
                "href": "/dummies/8"
              }
            },
            "description": "Not so smart dummy.",
            "dummy": "SomeDummyTest8",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 8,
            "name": "Dummy #8",
            "alias": "Alias #2",
            "foo": null
          }
        ]
      }
}
    """

  Scenario: Filter with non-exact match
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies?name=Dummy%20%238"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "/dummies?name=Dummy%20%238"
        },
        "item": [
          {
            "href": "/dummies/8"
          }
        ]
      },
      "totalItems": 1,
      "itemsPerPage": 3,
      "_embedded": {
        "item": [
          {
            "_links": {
              "self": {
                "href": "/dummies/8"
              }
            },
            "description": "Not so smart dummy.",
            "dummy": "SomeDummyTest8",
            "dummyBoolean": null,
            "dummyDate": null,
            "dummyFloat": null,
            "dummyPrice": null,
            "jsonData": [],
            "arrayData": [],
            "name_converted": null,
            "id": 8,
            "name": "Dummy #8",
            "alias": "Alias #2",
            "foo": null
          }
        ]
      }
    }
    """

  @!mongodb
  Scenario: Allow passing 0 to `itemsPerPage`
    When I add "Accept" header equal to "application/hal+json"
    And I send a "GET" request to "/dummies?itemsPerPage=0"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON HAL schema
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
       "_links":{
          "self":{
             "href":"/dummies?itemsPerPage=0"
          }
       },
       "totalItems":10,
       "itemsPerPage":0
    }
    """
