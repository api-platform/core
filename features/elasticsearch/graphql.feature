Feature: GraphQL query support

  @elasticsearch
  Scenario: Execute a GraphQL query on an ElasticSearch model with SubResources
    When I send the following GraphQL request:
    """
    query {
      users {
        edges {
          node {
            id,
            gender,
            tweets {
              edges {
                node {
                  id,
                  message,
                }
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
    And the JSON should be equal to:
    """
    {
      "data": {
        "users": {
          "edges": [
            {
              "node": {
                "id": "/users/116b83f8-6c32-48d8-8e28-c5c247532d3f",
                "gender": "male",
                "tweets": {
                  "edges": [
                    {
                      "node": {
                        "id": "/tweets/89601e1c-3ef2-4ef7-bca2-7511d38611c6",
                        "message": "Great day in any endur... During the Himalayas were very talented skimo racer junior podiums, top 10."
                      }
                    },
                    {
                      "node": {
                        "id": "/tweets/9da70727-d656-42d9-876a-1be6321f171b",
                        "message": "During the path and his Summits Of My Life project. Next Wednesday, Kilian Jornet..."
                      }
                    },
                    {
                      "node": {
                        "id": "/tweets/f36a0026-0635-4865-86a6-5adb21d94d64",
                        "message": "The north summit, Store Vengetind Thanks for t... These Top 10 Women of a fk... Francois is the field which."
                      }
                    }
                  ]
                }
              }
            },
            {
              "node": {
                "id": "/users/15fce6f1-18fd-4ef6-acab-7e6a3333ec7f",
                "gender": "male",
                "tweets": {
                  "edges": [
                    {
                      "node": {
                        "id": "/tweets/0cfe3d33-6116-416b-8c50-3b8319331998",
                        "message": "Thanks! Fun day with Next up one of our 2018 cover: One look into what races we'll be running that they!"
                      }
                    }
                  ]
                }
              }
            },
            {
              "node": {
                "id": "/users/6a457188-d1ba-45e3-8509-81e5c66a5297",
                "gender": "female",
                "tweets": {
                  "edges": [
                    {
                      "node": {
                        "id": "/tweets/9de3308c-6f82-4a57-a33c-4e3cd5d5a3f6",
                        "message": "In case you do! A humble beginning to traverse... Im so now until you can't tell how strong she run at!"
                      }
                    }
                  ]
                }
              }
            }
          ]
        }
      }
    }
    """
