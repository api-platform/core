Feature: Sending PATCH requets
  As a client software developer
  I need to be able to send partial updates

  @createSchema
  Scenario: Detect accepted patch formats
    Given I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/patch_dummies" with body:
    """
    {"name": "Hello"}
    """
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/patch_dummies/1"
    Then the header "Accept-Patch" should be equal to "application/merge-patch+json, application/vnd.api+json"

  Scenario: Patch an item
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/patch_dummies/1" with body:
    """
    {"name": "Patched"}
    """
    Then the JSON node "name" should contain "Patched"

  Scenario: Remove a property according to RFC 7386
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/patch_dummies/1" with body:
    """
    {"name": null}
    """
    Then the JSON node "name" should not exist
