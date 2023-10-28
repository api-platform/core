Feature: GraphQL subscription support

  @createSchema
  Scenario: Introspect subscription type
    When I send the following GraphQL request:
    """
    {
      __type(name: "Subscription") {
        fields {
          name
          description
          type {
            name
            kind
          }
          args {
            name
            type {
              name
              kind
              ofType {
                name
                kind
              }
            }
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "required": [
        "data"
      ],
      "properties": {
        "data": {
          "type": "object",
          "required": [
            "__type"
          ],
          "properties": {
            "__type": {
              "type": "object",
              "required": [
                "fields"
              ],
              "properties": {
                "fields": {
                  "type": "array",
                  "minItems": 1,
                  "items": {
                    "type": "object",
                    "required": [
                      "name",
                      "description",
                      "type",
                      "args"
                    ],
                    "properties": {
                      "name": {
                        "pattern": "^update[A-z0-9]+Subscribe"
                      },
                      "description": {
                        "pattern": "^Subscribes to the update event of a [A-z0-9]+.$"
                      },
                      "type": {
                        "type": "object",
                        "required": [
                          "name",
                          "kind"
                        ],
                        "properties": {
                          "name": {
                            "pattern": "^update[A-z0-9]+SubscriptionPayload$"
                          },
                          "kind": {
                            "enum": ["OBJECT"]
                          }
                        }
                      },
                      "args": {
                        "type": "array",
                        "minItems": 1,
                        "maxItems": 1,
                        "items": [
                          {
                            "type": "object",
                            "required": [
                              "name",
                              "type"
                            ],
                            "properties": {
                              "name": {
                                "enum": ["input"]
                              },
                              "type": {
                                "type": "object",
                                "required": [
                                  "kind",
                                  "ofType"
                                ],
                                "properties": {
                                  "kind": {
                                    "enum": ["NON_NULL"]
                                  },
                                  "ofType": {
                                    "type": "object",
                                    "required": [
                                      "name",
                                      "kind"
                                    ],
                                    "properties": {
                                      "name": {
                                        "pattern": "^update[A-z0-9]+SubscriptionInput$"
                                      },
                                      "kind": {
                                        "enum": ["INPUT_OBJECT"]
                                      }
                                    }
                                  }
                                }
                              }
                            }
                          }
                        ]
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    """

  Scenario: Subscribe to updates
    Given there are 2 dummy mercure objects
    When I send the following GraphQL request:
    """
    subscription {
      updateDummyMercureSubscribe(input: {id: "/dummy_mercures/1", clientSubscriptionId: "myId"}) {
        dummyMercure {
          id
          name
          relatedDummy {
            name
          }
        }
        mercureUrl
        clientSubscriptionId
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateDummyMercureSubscribe.dummyMercure.id" should be equal to "/dummy_mercures/1"
    And the JSON node "data.updateDummyMercureSubscribe.dummyMercure.name" should be equal to "Dummy Mercure #1"
    And the JSON node "data.updateDummyMercureSubscribe.mercureUrl" should match "@^https://demo.mercure.rocks\?topic=http://example.com/subscriptions/[a-f0-9]+$@"
    And the JSON node "data.updateDummyMercureSubscribe.clientSubscriptionId" should be equal to "myId"

    When I send the following GraphQL request:
    """
    subscription {
      updateDummyMercureSubscribe(input: {id: "/dummy_mercures/2"}) {
        dummyMercure {
          id
        }
        mercureUrl
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON node "data.updateDummyMercureSubscribe.dummyMercure.id" should be equal to "/dummy_mercures/2"
    And the JSON node "data.updateDummyMercureSubscribe.mercureUrl" should match "@^https://demo.mercure.rocks\?topic=http://example.com/subscriptions/[a-f0-9]+$@"

  Scenario: Receive Mercure updates with different payloads from subscriptions (legacy PUT in non-standard mode)
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummy_mercures/1" with body:
    """
    {
        "name": "Dummy Mercure #1 updated"
    }
    """
    Then the following Mercure update with topics "http://example.com/subscriptions/[a-f0-9]+" should have been sent:
    """
    {
        "dummyMercure": {
            "id": 1,
            "name": "Dummy Mercure #1 updated",
            "relatedDummy": {
                "name": "RelatedDummy #1"
            }
        }
    }
    """

    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummy_mercures/2" with body:
    """
    {
        "name": "Dummy Mercure #2 updated"
    }
    """
    Then the following Mercure update with topics "http://example.com/subscriptions/[a-f0-9]+" should have been sent:
    """
    {
        "dummyMercure": {
            "id": 2
        }
    }
    """
