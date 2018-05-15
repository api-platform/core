Feature: Serializable item data provider
  In order to call any external API
  As a developer
  I should be able to serialize the response directly from the ItemDataProvider.

  Scenario: Get a resource containing a raw object
    When  I send a "GET" request to "/serializable_resources/1"
    Then the JSON should be equal to:
    """
    {
        "@context": "/contexts/SerializableResource",
        "@id": "/serializable_resources/1",
        "@type": "SerializableResource",
        "id": 1,
        "foo": "Lorem",
        "bar": "Ipsum"
    }
    """
