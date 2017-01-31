Feature: Non-resources handling
  In order to handle use non-resource types
  As a developer
  I should be able serialize types not mapped to an API resource.

  Scenario: Get a resource containing a raw object
    When  I send a "GET" request to "/contain_non_resources/1"
    Then the JSON should be equal to:
    """
    {
        "@context": "/contexts/ContainNonResource",
        "@id": "/contain_non_resources/1",
        "@type": "ContainNonResource",
        "id": "1",
        "nested": {
            "@id": "/contain_non_resources/1-nested",
            "@type": "ContainNonResource",
            "id": "1-nested",
            "nested": null,
            "notAResource": {
                "foo": "f2",
                "bar": "b2"
            }
        },
        "notAResource": {
            "foo": "f1",
            "bar": "b1"
        }
    }
    """
