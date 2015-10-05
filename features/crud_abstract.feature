Feature: Create-Retrieve-Update-Delete on abstract resource
  In order to use an hypermedia API
  As a client software developer
  I need to be able to retrieve, create, update and delete JSON-LD encoded resources even if they are abstract.

  @createSchema
  Scenario: Create a concrete resource
    When I send a "POST" request to "/concrete_dummies" with body:
    """
    {
      "instance": "Concrete",
      "name": "My Dummy"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ConcreteDummy",
      "@id": "/concrete_dummies/1",
      "@type": "ConcreteDummy",
      "instance": "Concrete",
      "name": "My Dummy"
    }
    """

  Scenario: Get a resource
    When I send a "GET" request to "/abstract_dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ConcreteDummy",
      "@id": "/concrete_dummies/1",
      "@type": "ConcreteDummy",
      "instance": "Concrete",
      "name": "My Dummy"
    }
    """

  Scenario: Get a collection
    When I send a "GET" request to "/abstract_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/AbstractDummy",
        "@id": "/abstract_dummies",
        "@type": "hydra:PagedCollection",
        "hydra:totalItems": 1,
        "hydra:itemsPerPage": 3,
        "hydra:firstPage": "/abstract_dummies",
        "hydra:lastPage": "/abstract_dummies",
        "hydra:member": [
            {
                "@id": "/concrete_dummies/1",
                "@type": "ConcreteDummy",
                "instance": "Concrete",
                "name": "My Dummy"
            }
        ],
        "hydra:search": {
            "@type": "hydra:IriTemplate",
            "hydra:template": "/abstract_dummies{?id,name,relatedDummy.name,order[id],order[name]}",
            "hydra:variableRepresentation": "BasicRepresentation",
            "hydra:mapping": [
                {
                    "@type": "IriTemplateMapping",
                    "variable": "id",
                    "property": "id",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "name",
                    "property": "name",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "relatedDummy.name",
                    "property": "relatedDummy.name",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "order[id]",
                    "property": "id",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "order[name]",
                    "property": "name",
                    "required": false
                }
            ]
        }
    }
    """

  Scenario: Update a concrete resource
      When I send a "PUT" request to "/concrete_dummies/1" with body:
      """
      {
        "@id": "/concrete_dummies/1",
        "instance": "Become real",
        "name": "A nice dummy"
      }
      """
      Then the response status code should be 200
      And the response should be in JSON
      And the header "Content-Type" should be equal to "application/ld+json"
      And the JSON should be equal to:
      """
      {
        "@context": "/contexts/ConcreteDummy",
        "@id": "/concrete_dummies/1",
        "@type": "ConcreteDummy",
        "instance": "Become real",
        "name": "A nice dummy"
      }
      """

  @dropSchema
  Scenario: Delete a resource
    When I send a "DELETE" request to "/abstract_dummies/1"
    Then the response status code should be 204
    And the response should be empty
