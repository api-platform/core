Feature: Entrypoint support
  In order to build an auto-discoverable API
  As a client software developer
  I need to access to an entrypoint listing top-level resources

  Scenario: Retrieve the Entrypoint
    When I send a "GET" request to "/"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "@context" should be equal to "/contexts/Entrypoint"
    And the JSON node "@id" should be equal to "/"
    And the JSON node "@type" should be equal to "Entrypoint"
    And the JSON node "abstractDummy" should be equal to "/abstract_dummies"
    And the JSON node "circularReference" should be equal to "/circular_references"
    And the JSON node "compositeItem" should be equal to "/composite_items"
    And the JSON node "compositeLabel" should be equal to "/composite_labels"
    And the JSON node "compositeRelation" should be equal to "/composite_relations"
    And the JSON node "concreteDummy" should be equal to "/concrete_dummies"
    And the JSON node "customIdentifierDummy" should be equal to "/custom_identifier_dummies"
    And the JSON node "customNormalizedDummy" should be equal to "/custom_normalized_dummies"
    And the JSON node "customWritableIdentifierDummy" should be equal to "/custom_writable_identifier_dummies"
    And the JSON node "dummy" should be equal to "/dummies"
    And the JSON node "relatedDummy" should be equal to "/related_dummies"
    And the JSON node "relationEmbedder" should be equal to "/relation_embedders"
    And the JSON node "thirdLevel" should be equal to "/third_levels"
    And the JSON node "user" should be equal to "/users"
    And the JSON node "fileconfigdummy" should be equal to "/fileconfigdummies"
