Feature: Configurable resource CRUD
  As a client software developer
  I need to be able to configure api resources through YAML

  @createSchema
  Scenario: Retrieve the ConfigDummy resource
    Given there is a FileConfigDummy object
    When I send a "GET" request to "/fileconfigdummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/fileconfigdummy",
      "@id": "/fileconfigdummies",
      "@type": "hydra:Collection",
      "hydra:member": [
          {
              "@id": "/fileconfigdummies/1",
              "@type": "fileconfigdummy",
              "id": 1,
              "name": "ConfigDummy",
              "foo": "Foo"
          }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: Get a single file configured resource
    When I send a "GET" request to "/single_file_configs"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/single_file_config",
      "@id": "/single_file_configs",
      "@type": "hydra:Collection",
      "hydra:member": [],
      "hydra:totalItems": 0
    }
    """

  Scenario: Retrieve the ConfigDummy resource
    When I send a "GET" request to "/fileconfigdummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/fileconfigdummy",
      "@id": "/fileconfigdummies/1",
      "@type": "fileconfigdummy",
      "id": 1,
      "name": "ConfigDummy",
      "foo": "Foo"
    }
    """
