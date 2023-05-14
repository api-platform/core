Feature: Table inheritance
  In order to use the api with Doctrine table inheritance
  As a client software developer
  I need to be able to create resources and fetch them on the upper entity

  Background:
    Given I add "Accept" header equal to "application/hal+json"
    And I add "Content-Type" header equal to "application/json"

  @createSchema
  Scenario: Create a table inherited resource
    And I send a "POST" request to "/dummy_table_inheritance_children" with body:
      """
      {
        "name": "foo",
        "nickname": "bar"
      }
      """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
      """
      {
        "_links": {
          "self": {
            "href": "/dummy_table_inheritance_children/1"
          }
        },
        "nickname": "bar",
        "id": 1,
        "name": "foo"
      }
      """

  Scenario: Get the parent entity collection
    When some dummy table inheritance data but not api resource child are created
    When I send a "GET" request to "/dummy_table_inheritances"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
      """
      {
        "_links": {
          "self": {
            "href": "/dummy_table_inheritances"
          },
          "item": [
            {
              "href": "/dummy_table_inheritance_children/1"
            },
            {
              "href": "/dummy_table_inheritances/2"
            }
          ]
        },
        "totalItems": 2,
        "itemsPerPage": 3,
        "_embedded": {
          "item": [
            {
              "_links": {
                "self": {
                  "href": "/dummy_table_inheritance_children/1"
                }
              },
              "nickname": "bar",
              "id": 1,
              "name": "foo"
            },
            {
              "_links": {
                "self": {
                  "href": "/dummy_table_inheritances/2"
                }
              },
              "id": 2,
              "name": "Foobarbaz inheritance"
            }
          ]
        }
      }
      """


  Scenario: Get related entity with multiple inherited children types
    And I send a "POST" request to "/dummy_table_inheritance_relateds" with body:
      """
      {
        "children": [
          "/dummy_table_inheritance_children/1",
          "/dummy_table_inheritances/2"
        ]
      }
      """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
      """
      {
        "_links": {
          "self": {
            "href": "/dummy_table_inheritance_relateds/1"
          },
          "children": [
            {
              "href": "/dummy_table_inheritance_children/1"
            },
            {
              "href": "/dummy_table_inheritances/2"
            }
          ]
        },
        "_embedded": {
          "children": [
            {
              "_links": {
                "self": {
                  "href": "/dummy_table_inheritance_children/1"
                }
              },
              "nickname": "bar",
              "id": 1,
              "name": "foo"
            },
            {
              "_links": {
                "self": {
                  "href": "/dummy_table_inheritances/2"
                }
              },
              "id": 2,
              "name": "Foobarbaz inheritance"
            }
          ]
        },
        "id": 1
      }
      """