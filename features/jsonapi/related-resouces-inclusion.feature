Feature: JSON API Inclusion of Related Resources
  In order to be able to handle inclusion of related resources
  As a client software developer
  I need to be able to specify include parameters according to JSON API recommendation

  Background:
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/vnd.api+json"

  @createSchema
  Scenario: Request inclusion of a related resource (many to one)
    Given there are 3 dummy property objects
    When I send a "GET" request to "/dummy_properties/1?include=group"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be deep equal to:
    """
    {
        "data": {
            "id": "/dummy_properties/1",
            "type": "DummyProperty",
            "attributes": {
                "_id": 1,
                "foo": "Foo #1",
                "bar": "Bar #1",
                "baz": "Baz #1"
            },
            "relationships": {
                "group": {
                    "data": {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/1"
                    }
                }
            }
        },
        "included": [
            {
                "id": "\/dummy_groups\/1",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 1,
                    "foo": "Foo #1",
                    "bar": "Bar #1",
                    "baz": "Baz #1"
                }
            }
        ]
    }
  """

  Scenario: Request inclusion of a non existing related resource
    When I send a "GET" request to "/dummy_properties/1?include=foo"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be deep equal to:
    """
    {
        "data": {
            "id": "/dummy_properties/1",
            "type": "DummyProperty",
            "attributes": {
                "_id": 1,
                "foo": "Foo #1",
                "bar": "Bar #1",
                "baz": "Baz #1"
            },
            "relationships": {
                "group": {
                    "data": {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/1"
                    }
                }
            }
        }
    }
  """

  Scenario: Request inclusion of a related resource keeping main object properties unfiltered
    When I send a "GET" request to "/dummy_properties/1?include=group&fields[group]=id,foo&fields[DummyProperty]=bar,baz"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be deep equal to:
    """
    {
        "data": {
            "id": "/dummy_properties/1",
            "type": "DummyProperty",
            "attributes": {
                "bar": "Bar #1",
                "baz": "Baz #1"
            },
            "relationships": {
                "group": {
                    "data": {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/1"
                    }
                }
            }
        },
        "included": [
            {
                "id": "\/dummy_groups\/1",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 1,
                    "foo": "Foo #1"
                }
            }
        ]
    }
  """

  Scenario: Request inclusion of related resources and specific fields
    When I send a "GET" request to "/dummy_properties/1?include=group&fields[group]=id,foo"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be deep equal to:
    """
    {
        "data": {
            "id": "/dummy_properties/1",
            "type": "DummyProperty",
            "relationships": {
                "group": {
                    "data": {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/1"
                    }
                }
            }
        },
        "included": [
            {
                "id": "\/dummy_groups\/1",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 1,
                    "foo": "Foo #1"
                }
            }
        ]
    }
  """

  @createSchema
  Scenario: Request inclusion of related resources (many to many)
    Given there are 1 dummy property objects with 3 groups
    When I send a "GET" request to "/dummy_properties/1?include=groups"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be deep equal to:
    """
    {
        "data": {
            "id": "/dummy_properties/1",
            "type": "DummyProperty",
            "attributes": {
                "_id": 1,
                "foo": "Foo #1",
                "bar": "Bar #1",
                "baz": "Baz #1"
            },
            "relationships": {
                "group": {
                    "data": {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/1"
                    }
                },
                "groups": {
                    "data": [
                      {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/2"
                      },
                      {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/3"
                      },
                      {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/4"
                      }
                    ]
                }
            }
        },
        "included": [
            {
                "id": "/dummy_groups/2",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 2,
                    "foo": "Foo #11",
                    "bar": "Bar #11",
                    "baz": "Baz #11"
                }
            },
            {
                "id": "/dummy_groups/3",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 3,
                    "foo": "Foo #12",
                    "bar": "Bar #12",
                    "baz": "Baz #12"
                }
            },
            {
                "id": "/dummy_groups/4",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 4,
                    "foo": "Foo #13",
                    "bar": "Bar #13",
                    "baz": "Baz #13"
                }
            }
        ]
    }
  """

  @createSchema
  Scenario: Request inclusion of related resources (many to many and many to one)
    Given there are 1 dummy property objects with 3 groups
    When I send a "GET" request to "/dummy_properties/1?include=groups,group"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be deep equal to:
    """
    {
        "data": {
            "id": "/dummy_properties/1",
            "type": "DummyProperty",
            "attributes": {
                "_id": 1,
                "foo": "Foo #1",
                "bar": "Bar #1",
                "baz": "Baz #1"
            },
            "relationships": {
                "group": {
                    "data": {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/1"
                    }
                },
                "groups": {
                    "data": [
                      {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/2"
                      },
                      {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/3"
                      },
                      {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/4"
                      }
                    ]
                }
            }
        },
        "included": [
            {
                "id": "/dummy_groups/1",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 1,
                    "foo": "Foo #1",
                    "bar": "Bar #1",
                    "baz": "Baz #1"
                }
            },
            {
                "id": "/dummy_groups/2",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 2,
                    "foo": "Foo #11",
                    "bar": "Bar #11",
                    "baz": "Baz #11"
                }
            },
            {
                "id": "/dummy_groups/3",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 3,
                    "foo": "Foo #12",
                    "bar": "Bar #12",
                    "baz": "Baz #12"
                }
            },
            {
                "id": "/dummy_groups/4",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 4,
                    "foo": "Foo #13",
                    "bar": "Bar #13",
                    "baz": "Baz #13"
                }
            }
        ]
    }
  """

  @createSchema
  Scenario: Request inclusion of resource with relation
    Given there are 1 dummy objects with relatedDummy and its thirdLevel
    When I send a "GET" request to "/dummies/1?include=relatedDummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be deep equal to:
    """
        {
            "data": {
                "id": "/dummies/1",
                "type": "Dummy",
                "attributes": {
                    "description": null,
                    "dummy": null,
                    "dummyBoolean": null,
                    "dummyDate": null,
                    "dummyFloat": null,
                    "dummyPrice": null,
                    "jsonData": [],
                    "arrayData": [],
                    "name_converted": null,
                    "_id": 1,
                    "name": "Dummy #1",
                    "alias": "Alias #0",
                    "foo": null
                },
                "relationships": {
                    "relatedDummy": {
                        "data": {
                            "type": "RelatedDummy",
                            "id": "/related_dummies/1"
                        }
                    }
                }
            },
            "included": [
                {
                    "id": "/related_dummies/1",
                    "type": "RelatedDummy",
                    "attributes": {
                        "name": "RelatedDummy #1",
                        "dummyDate": null,
                        "dummyBoolean": null,
                        "embeddedDummy": {
                            "dummyName": null,
                            "dummyBoolean": null,
                            "dummyDate": null,
                            "dummyFloat": null,
                            "dummyPrice": null,
                            "symfony": null
                        },
                        "_id": 1,
                        "symfony": "symfony",
                        "age": null
                    },
                    "relationships": {
                        "thirdLevel": {
                            "data": {
                                "type": "ThirdLevel",
                                "id": "/third_levels/1"
                            }
                        }
                    }
                }
            ]
       }
    """

  @createSchema
  Scenario: Request inclusion of a related resources on collection
    Given there are 3 dummy property objects
    When I send a "GET" request to "/dummy_properties?include=group"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be deep equal to:
    """
    {
        "links": {
            "self": "\/dummy_properties?include=group"
        },
        "meta": {
            "totalItems": 3,
            "itemsPerPage": 3,
            "currentPage": 1
        },
        "data": [
            {
                "id": "/dummy_properties/1",
                "type": "DummyProperty",
                "attributes": {
                    "_id": 1,
                    "foo": "Foo #1",
                    "bar": "Bar #1",
                    "baz": "Baz #1"
                },
                "relationships": {
                    "group": {
                        "data": {
                            "type": "DummyGroup",
                            "id": "/dummy_groups/1"
                        }
                    }
                }
            },
            {
                "id": "/dummy_properties/2",
                "type": "DummyProperty",
                "attributes": {
                    "_id": 2,
                    "foo": "Foo #2",
                    "bar": "Bar #2",
                    "baz": "Baz #2"
                },
                "relationships": {
                    "group": {
                        "data": {
                            "type": "DummyGroup",
                            "id": "/dummy_groups/2"
                        }
                    }
                }
            },
            {
                "id": "/dummy_properties/3",
                "type": "DummyProperty",
                "attributes": {
                    "_id": 3,
                    "foo": "Foo #3",
                    "bar": "Bar #3",
                    "baz": "Baz #3"
                },
                "relationships": {
                    "group": {
                        "data": {
                            "type": "DummyGroup",
                            "id": "/dummy_groups/3"
                        }
                    }
                }
            }
        ],
        "included": [
            {
                "id": "\/dummy_groups\/1",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 1,
                    "foo": "Foo #1",
                    "bar": "Bar #1",
                    "baz": "Baz #1"
                }
            },
            {
                "id": "\/dummy_groups\/2",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 2,
                    "foo": "Foo #2",
                    "bar": "Bar #2",
                    "baz": "Baz #2"
                }
            },
            {
                "id": "\/dummy_groups\/3",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 3,
                    "foo": "Foo #3",
                    "bar": "Bar #3",
                    "baz": "Baz #3"
                }
            }
        ]
    }
  """

  @createSchema
  Scenario: Request inclusion of a related resources on collection should not duplicated included object
    Given there are 3 dummy property objects with a shared group
    When I send a "GET" request to "/dummy_properties?include=group"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be deep equal to:
    """
    {
        "links": {
            "self": "\/dummy_properties?include=group"
        },
        "meta": {
            "totalItems": 3,
            "itemsPerPage": 3,
            "currentPage": 1
        },
        "data": [
            {
                "id": "/dummy_properties/1",
                "type": "DummyProperty",
                "attributes": {
                    "_id": 1,
                    "foo": "Foo #1",
                    "bar": "Bar #1",
                    "baz": "Baz #1"
                },
                "relationships": {
                    "group": {
                        "data": {
                            "type": "DummyGroup",
                            "id": "/dummy_groups/1"
                        }
                    }
                }
            },
            {
                "id": "/dummy_properties/2",
                "type": "DummyProperty",
                "attributes": {
                    "_id": 2,
                    "foo": "Foo #2",
                    "bar": "Bar #2",
                    "baz": "Baz #2"
                },
                "relationships": {
                    "group": {
                        "data": {
                            "type": "DummyGroup",
                            "id": "/dummy_groups/1"
                        }
                    }
                }
            },
            {
                "id": "/dummy_properties/3",
                "type": "DummyProperty",
                "attributes": {
                    "_id": 3,
                    "foo": "Foo #3",
                    "bar": "Bar #3",
                    "baz": "Baz #3"
                },
                "relationships": {
                    "group": {
                        "data": {
                            "type": "DummyGroup",
                            "id": "/dummy_groups/1"
                        }
                    }
                }
            }
        ],
        "included": [
            {
                "id": "\/dummy_groups\/1",
                "type": "DummyGroup",
                "attributes": {
                    "_id": 1,
                    "foo": "Foo #shared",
                    "bar": "Bar #shared",
                    "baz": "Baz #shared"
                }
            }
        ]
    }
  """

  @createSchema
  Scenario: Request inclusion of a related resources on collection should not duplicated included object
    Given there are 2 dummy property objects with different number of related groups
    When I send a "GET" request to "/dummy_properties?include=groups"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be deep equal to:
    """
    {
        "links": {
            "self": "/dummy_properties?include=groups"
        },
        "meta": {
            "totalItems": 2,
            "itemsPerPage": 3,
            "currentPage": 1
        },
        "data": [{
            "id": "/dummy_properties/1",
            "type": "DummyProperty",
            "attributes": {
                "_id": 1,
                "foo": "Foo #1",
                "bar": "Bar #1",
                "baz": "Baz #1"
            },
            "relationships": {
                "groups": {
                    "data": [{
                        "type": "DummyGroup",
                        "id": "/dummy_groups/1"
                    }]
                }
            }
        }, {
            "id": "/dummy_properties/2",
            "type": "DummyProperty",
            "attributes": {
                "_id": 2,
                "foo": "Foo #2",
                "bar": "Bar #2",
                "baz": "Baz #2"
            },
            "relationships": {
                "groups": {
                    "data": [{
                        "type": "DummyGroup",
                        "id": "/dummy_groups/1"
                    }, {
                        "type": "DummyGroup",
                        "id": "/dummy_groups/2"
                    }]
                }
            }
        }],
        "included": [{
            "id": "/dummy_groups/1",
            "type": "DummyGroup",
            "attributes": {
                "_id": 1,
                "foo": "Foo #1",
                "bar": "Bar #1",
                "baz": "Baz #1"
            }
        }, {
            "id": "/dummy_groups/2",
            "type": "DummyGroup",
            "attributes": {
                "_id": 2,
                "foo": "Foo #2",
                "bar": "Bar #2",
                "baz": "Baz #2"
            }
        }]
    }
    """
