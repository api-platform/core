Feature: Date filter on collections
  In order to retrieve large collections of resources filtered by date
  As a client software developer
  I need to retrieve collections filtered by date

  @createSchema
  Scenario: Get collection filtered by date
    Given there is "30" dummy objects with dummyDate
    When I send a "GET" request to "/dummies?dummyDate[after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?dummyDate[after]=2015-04-28",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 2,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?dummyDate%5Bafter%5D=2015-04-28",
      "hydra:lastPage": "/dummies?dummyDate%5Bafter%5D=2015-04-28",
      "hydra:member": [
          {
              "@id": "/dummies/28",
              "@type": "Dummy",
              "name": "Dummy #28",
              "alias": "Alias #2",
              "dummyDate": "2015-04-28T00:00:00+00:00",
              "jsonData": [],
              "dummy": null,
              "relatedDummy": null,
              "relatedDummies": [],
              "name_converted": null
          },
          {
              "@id": "/dummies/29",
              "@type": "Dummy",
              "name": "Dummy #29",
              "alias": "Alias #1",
              "dummyDate": "2015-04-29T00:00:00+00:00",
              "jsonData": [],
              "dummy": null,
              "relatedDummy": null,
              "relatedDummies": [],
              "name_converted": null
          }
      ],
      "hydra:search": {
          "@type": "hydra:IriTemplate",
          "hydra:template": "/dummies{?id,name,relatedDummies[],order[id],order[name],dummyDate[before],dummyDate[after]}",
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
              "jsonData": [],
              "dummy": null,
              "relatedDummy": null,
              "relatedDummies": [],
              "name_converted": null
          },
          {
              "@id": "/dummies/2",
              "@type": "Dummy",
              "name": "Dummy #2",
              "alias": "Alias #28",
              "dummyDate": "2015-04-02T00:00:00+00:00",
              "jsonData": [],
              "dummy": null,
              "relatedDummy": null,
              "relatedDummies": [],
              "name_converted": null
          },
          {
              "@id": "/dummies/3",
              "@type": "Dummy",
              "name": "Dummy #3",
              "alias": "Alias #27",
              "dummyDate": "2015-04-03T00:00:00+00:00",
              "jsonData": [],
              "dummy": null,
              "relatedDummy": null,
              "relatedDummies": [],
              "name_converted": null
          }
      ],
      "hydra:search": {
          "@type": "hydra:IriTemplate",
          "hydra:template": "/dummies{?id,name,relatedDummies[],order[id],order[name],dummyDate[before],dummyDate[after]}",
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
              "jsonData": [],
              "dummy": null,
              "relatedDummy": null,
              "relatedDummies": [],
              "name_converted": null
          }
      ],
      "hydra:search": {
          "@type": "hydra:IriTemplate",
          "hydra:template": "/dummies{?id,name,relatedDummies[],order[id],order[name],dummyDate[before],dummyDate[after]}",
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
              "jsonData": [],
              "dummy": null,
              "relatedDummy": null,
              "relatedDummies": [],
              "name_converted": null
          }
      ],
      "hydra:search": {
          "@type": "hydra:IriTemplate",
          "hydra:template": "/dummies{?id,name,relatedDummies[],order[id],order[name],dummyDate[before],dummyDate[after]}",
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
          "hydra:template": "/dummies{?id,name,relatedDummies[],order[id],order[name],dummyDate[before],dummyDate[after]}",
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
