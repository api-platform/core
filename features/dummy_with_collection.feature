Feature: Create-Update an element with a collection
  In order to use an hypermedia API
  As a client software developer
  I need to be able to create, update JSON-LD encoded resources with collection.

  @createSchema
  @dropSchema
  Scenario: Update a dummy with collection with another collection values
    Given there is 3 dummy with collection objects with Dummy
    When I send a "PUT" request to "/dummy_with_collections/1" with body:
    """
    {
      "elements": ["/dummies/3", "/dummies/2"]
    }
    """
    Then the response status code should be 200
    And the JSON node "elements[0]" should be equal to "/dummies/3"
    And the JSON node "elements[1]" should be equal to "/dummies/2"
