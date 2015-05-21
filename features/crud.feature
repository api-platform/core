Feature: Create-Retrieve-Update-Delete
  In order to use an hypermedia API
  As a client software developer
  I need to be able to retrieve, create, update and delete JSON-LD encoded resources.

  @createSchema
  Scenario: Create a resource
    When I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "My Dummy",
      "dummyDate": "2015-03-01T10:00:00+00:00"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "name": "My Dummy",
      "alias": null,
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "dummy": null,
      "relatedDummy": null,
      "relatedDummies": []
    }
    """

  Scenario: Get a resource
    When I send a "GET" request to "/dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "name": "My Dummy",
      "alias": null,
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "dummy": null,
      "relatedDummy": null,
      "relatedDummies": []
    }
    """

  Scenario: Get a collection
    When I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies",
      "hydra:lastPage": "/dummies",
      "hydra:member": [
        {
          "@id":"/dummies/1",
          "@type":"Dummy",
          "name":"My Dummy",
          "alias": null,
          "dummyDate": "2015-03-01T10:00:00+00:00",
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": []
        }
      ],
      "hydra:search": {
              "@type": "hydra:IriTemplate",
              "hydra:template": "\/dummies{?id,name,relatedDummy,relatedDummies,order[id],order[name],string}",
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
                      "variable": "relatedDummy",
                      "property": "relatedDummy",
                      "required": false
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "relatedDummies",
                      "property": "relatedDummies",
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
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "string",
                      "property": "dummyDate",
                      "required": false
                  }
              ]
      }
    }
    """

  Scenario: Update a resource
      When I send a "PUT" request to "/dummies/1" with body:
      """
      {
        "@id": "/dummies/1",
        "name": "A nice dummy"
      }
      """
      Then the response status code should be 202
      And the response should be in JSON
      And the header "Content-Type" should be equal to "application/ld+json"
      And the JSON should be equal to:
      """
      {
        "@context": "/contexts/Dummy",
        "@id": "/dummies/1",
        "@type": "Dummy",
        "name": "A nice dummy",
        "alias": null,
        "dummyDate": "2015-03-01T10:00:00+00:00",
        "dummy": null,
        "relatedDummy": null,
        "relatedDummies": []
      }
      """

  @dropSchema
  Scenario: Delete a resource
    When I send a "DELETE" request to "/dummies/1"
    Then the response status code should be 204
    And the response should be empty
