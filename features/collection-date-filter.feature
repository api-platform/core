Feature: Order filter on collections
  In order to retrieve ordered large collections of resources
  As a client software developer
  I need to retrieve collections ordered properties

  @createSchema
  Scenario: Get collection filtered by date
    Given there is "30" dummy objects with dummyDate
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?dummyDate[after]=2015-04-05",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?dummyDate%5Bafter%5D=2015-04-05&page=2",
      "hydra:totalItems": 26,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?dummyDate%5Bafter%5D=2015-04-05",
      "hydra:lastPage": "/dummies?dummyDate%5Bafter%5D=2015-04-05&page=9",
      "hydra:member": [
              {
                  "@id": "/dummies/5",
                  "@type": "Dummy",
                  "name": "Dummy #5",
                  "alias": "Alias #25",
                  "dummyDate": "2015-04-05T00:00:00+00:00",
                  "dummy": null,
                  "relatedDummy": null,
                  "relatedDummies": []
              },
              {
                  "@id": "/dummies/6",
                  "@type": "Dummy",
                  "name": "Dummy #6",
                  "alias": "Alias #24",
                  "dummyDate": "2015-04-06T00:00:00+00:00",
                  "dummy": null,
                  "relatedDummy": null,
                  "relatedDummies": []
              },
              {
                  "@id": "/dummies/7",
                  "@type": "Dummy",
                  "name": "Dummy #7",
                  "alias": "Alias #23",
                  "dummyDate": "2015-04-07T00:00:00+00:00",
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

    When I send a "GET" request to "/dummies?dummyDate[before]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?dummyDate[before]=2015-04-05",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?dummyDate%5Bbefore%5D=2015-04-05&page=2",
      "hydra:totalItems": 5,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?dummyDate%5Bbefore%5D=2015-04-05",
      "hydra:lastPage": "/dummies?dummyDate%5Bbefore%5D=2015-04-05&page=2",
      "hydra:member": [
              {
                  "@id": "/dummies/1",
                  "@type": "Dummy",
                  "name": "Dummy #1",
                  "alias": "Alias #29",
                  "dummyDate": "2015-04-01T00:00:00+00:00",
                  "dummy": null,
                  "relatedDummy": null,
                  "relatedDummies": []
              },
              {
                  "@id": "/dummies/2",
                  "@type": "Dummy",
                  "name": "Dummy #2",
                  "alias": "Alias #28",
                  "dummyDate": "2015-04-02T00:00:00+00:00",
                  "dummy": null,
                  "relatedDummy": null,
                  "relatedDummies": []
              },
              {
                  "@id": "/dummies/3",
                  "@type": "Dummy",
                  "name": "Dummy #3",
                  "alias": "Alias #27",
                  "dummyDate": "2015-04-03T00:00:00+00:00",
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

  Scenario: Search for entities within a range
    # The order should not influence the search
    When I send a "GET" request to "/dummies?dummyDate[before]=2015-04-05&dummyDate[after]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?dummyDate[before]=2015-04-05&dummyDate[after]=2015-04-05",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?dummyDate%5Bbefore%5D=2015-04-05&dummyDate%5Bafter%5D=2015-04-05",
      "hydra:lastPage": "/dummies?dummyDate%5Bbefore%5D=2015-04-05&dummyDate%5Bafter%5D=2015-04-05",
      "hydra:member": [
              {
                  "@id": "/dummies/5",
                  "@type": "Dummy",
                  "name": "Dummy #5",
                  "alias": "Alias #25",
                  "dummyDate": "2015-04-05T00:00:00+00:00",
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

    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-05&dummyDate[before]=2015-04-05"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?dummyDate[after]=2015-04-05&dummyDate[before]=2015-04-05",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?dummyDate%5Bafter%5D=2015-04-05&dummyDate%5Bbefore%5D=2015-04-05",
      "hydra:lastPage": "/dummies?dummyDate%5Bafter%5D=2015-04-05&dummyDate%5Bbefore%5D=2015-04-05",
      "hydra:member": [
              {
                  "@id": "/dummies/5",
                  "@type": "Dummy",
                  "name": "Dummy #5",
                  "alias": "Alias #25",
                  "dummyDate": "2015-04-05T00:00:00+00:00",
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

  @dropSchema
  Scenario: Search for entities within an impossible range
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-06&dummyDate[before]=2015-04-04"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?dummyDate[after]=2015-04-06&dummyDate[before]=2015-04-04",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 0,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?dummyDate%5Bafter%5D=2015-04-06&dummyDate%5Bbefore%5D=2015-04-04",
      "hydra:lastPage": "/dummies?dummyDate%5Bafter%5D=2015-04-06&dummyDate%5Bbefore%5D=2015-04-04",
      "hydra:member": [],
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
