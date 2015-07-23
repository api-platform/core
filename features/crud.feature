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
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "jsonData": {
        "key": [
          "value1",
          "value2"
        ]
      }
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
      "jsonData": {
        "key": [
          "value1",
          "value2"
        ]
      },
      "dummy": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "name_converted": null
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
      "jsonData": {
        "key": [
          "value1",
          "value2"
        ]
      },
      "dummy": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "name_converted": null
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
          "jsonData": {
            "key": [
              "value1",
              "value2"
            ]
          },
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": [],
          "name_converted": null
        }
      ],
      "hydra:search": {
              "@type": "hydra:IriTemplate",
              "hydra:template": "\/dummies{?id,name,relatedDummies[],order[id],order[name],dummyDate[before],dummyDate[after]}",
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
                      "variable": "relatedDummies[]",
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
                      "variable": "dummyDate[before]",
                      "property": "dummyDate",
                      "required": false
                  },
                  {
                      "@type": "IriTemplateMapping",
                      "variable": "dummyDate[after]",
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
        "name": "A nice dummy",
        "jsonData": [{
            "key": "value1"
          },
          {
            "key": "value2"
          }
        ]
      }
      """
      Then the response status code should be 200
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
        "jsonData": [{
            "key": "value1"
          },
          {
            "key": "value2"
          }
        ],
        "dummy": null,
        "relatedDummy": null,
        "relatedDummies": [],
        "name_converted": null
      }
      """

  @dropSchema
  Scenario: Delete a resource
    When I send a "DELETE" request to "/dummies/1"
    Then the response status code should be 204
    And the response should be empty
