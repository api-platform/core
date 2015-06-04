Feature: Collections support
  In order to retrieve large collections of resources
  As a client software developer
  I need to retrieve paged collections respecting the Hydra specification

  @createSchema
  Scenario: Retrieve an empty collection
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
      "hydra:totalItems": 0,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies",
      "hydra:lastPage": "/dummies",
      "hydra:member": [],
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "\/dummies{?id,name,order[id],order[name],dummyDate[before],dummyDate[after]}",
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

  Scenario: Retrieve the first page of a collection
    Given there is "30" dummy objects
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies",
      "hydra:lastPage": "/dummies?page=10",
      "hydra:member": [
        {
          "@id": "/dummies/1",
          "@type": "Dummy",
          "name": "Dummy #1",
          "alias": "Alias #29",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": []
        },
        {
          "@id": "/dummies/2",
          "@type": "Dummy",
          "name": "Dummy #2",
          "alias": "Alias #28",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": []
        },
        {
          "@id": "/dummies/3",
          "@type": "Dummy",
          "name": "Dummy #3",
          "alias": "Alias #27",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": []
        }
      ],
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "\/dummies{?id,name,order[id],order[name],dummyDate[before],dummyDate[after]}",
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

  Scenario: Retrieve a page of a collection
    When I send a "GET" request to "/dummies?page=7"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?page=7",
      "@type": "hydra:PagedCollection",
      "hydra:previousPage": "/dummies?page=6",
      "hydra:nextPage": "/dummies?page=8",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies",
      "hydra:lastPage": "/dummies?page=10",
      "hydra:member": [
        {
          "@id": "/dummies/19",
          "@type": "Dummy",
          "name": "Dummy #19",
          "alias": "Alias #11",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": []
        },
        {
          "@id": "/dummies/20",
          "@type": "Dummy",
          "name": "Dummy #20",
          "alias": "Alias #10",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": []
        },
        {
          "@id": "/dummies/21",
          "@type": "Dummy",
          "name": "Dummy #21",
          "alias": "Alias #9",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": []
        }
      ],
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "\/dummies{?id,name,order[id],order[name],dummyDate[before],dummyDate[after]}",
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

  Scenario: Retrieve the last page of a collection
    When I send a "GET" request to "/dummies?page=10"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?page=10",
      "@type": "hydra:PagedCollection",
      "hydra:previousPage": "/dummies?page=9",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies",
      "hydra:lastPage": "/dummies?page=10",
      "hydra:member": [
          {
            "@id": "/dummies/28",
            "@type": "Dummy",
            "name": "Dummy #28",
            "alias": "Alias #2",
            "dummyDate": null,
            "jsonData": [],
            "dummy": null,
            "relatedDummy": null,
            "relatedDummies": []
          },
          {
            "@id": "/dummies/29",
            "@type": "Dummy",
            "name": "Dummy #29",
            "alias": "Alias #1",
            "dummyDate": null,
            "jsonData": [],
            "dummy": null,
            "relatedDummy": null,
            "relatedDummies": []
          },
          {
            "@id": "/dummies/30",
            "@type": "Dummy",
            "name": "Dummy #30",
            "alias": "Alias #0",
            "dummyDate": null,
            "jsonData": [],
            "dummy": null,
            "relatedDummy": null,
            "relatedDummies": []
          }
      ],
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "\/dummies{?id,name,order[id],order[name],dummyDate[before],dummyDate[after]}",
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

  Scenario: Retrieve a non-existing page of the collection
    When I send a "GET" request to "/dummies?page=11"
    Then the response status code should be 404

  Scenario: Change the number of element by page client side
    When I send a "GET" request to "/dummies?page=2&itemsPerPage=10"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?page=2&itemsPerPage=10",
      "@type": "hydra:PagedCollection",
      "hydra:previousPage": "/dummies?itemsPerPage=10",
      "hydra:nextPage": "/dummies?itemsPerPage=10&page=3",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 10,
      "hydra:firstPage": "/dummies?itemsPerPage=10",
      "hydra:lastPage": "/dummies?itemsPerPage=10&page=3",
      "hydra:member": [
        {
          "@id": "/dummies/11",
          "@type": "Dummy",
          "name": "Dummy #11",
          "alias": "Alias #19",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": [

          ]
        },
        {
          "@id": "/dummies/12",
          "@type": "Dummy",
          "name": "Dummy #12",
          "alias": "Alias #18",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": [

          ]
        },
        {
          "@id": "/dummies/13",
          "@type": "Dummy",
          "name": "Dummy #13",
          "alias": "Alias #17",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": [

          ]
        },
        {
          "@id": "/dummies/14",
          "@type": "Dummy",
          "name": "Dummy #14",
          "alias": "Alias #16",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": [

          ]
        },
        {
          "@id": "/dummies/15",
          "@type": "Dummy",
          "name": "Dummy #15",
          "alias": "Alias #15",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": [

          ]
        },
        {
          "@id": "/dummies/16",
          "@type": "Dummy",
          "name": "Dummy #16",
          "alias": "Alias #14",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": [

          ]
        },
        {
          "@id": "/dummies/17",
          "@type": "Dummy",
          "name": "Dummy #17",
          "alias": "Alias #13",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": [

          ]
        },
        {
          "@id": "/dummies/18",
          "@type": "Dummy",
          "name": "Dummy #18",
          "alias": "Alias #12",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": [

          ]
        },
        {
          "@id": "/dummies/19",
          "@type": "Dummy",
          "name": "Dummy #19",
          "alias": "Alias #11",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": [

          ]
        },
        {
          "@id": "/dummies/20",
          "@type": "Dummy",
          "name": "Dummy #20",
          "alias": "Alias #10",
          "dummyDate": null,
          "jsonData": [],
          "dummy": null,
          "relatedDummy": null,
          "relatedDummies": [

          ]
        }
      ],
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "\/dummies{?id,name,order[id],order[name],dummyDate[before],dummyDate[after]}",
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

  Scenario: Filter with exact match
    When I send a "GET" request to "/dummies?id=8"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?id=8",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?id=8",
      "hydra:lastPage": "/dummies?id=8",
      "hydra:member": [
          {
            "@id": "/dummies/8",
            "@type": "Dummy",
            "name": "Dummy #8",
            "alias": "Alias #22",
            "dummyDate": null,
            "jsonData": [],
            "dummy": null,
            "relatedDummy": null,
            "relatedDummies": []
          }
      ],
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "\/dummies{?id,name,order[id],order[name],dummyDate[before],dummyDate[after]}",
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

  Scenario: Filter with a raw URL
    When I send a "GET" request to "/dummies?id=%2fdummies%2f8"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?id=%2fdummies%2f8",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?id=%2Fdummies%2F8",
      "hydra:lastPage": "/dummies?id=%2Fdummies%2F8",
      "hydra:member": [
          {
            "@id": "/dummies/8",
            "@type": "Dummy",
            "name": "Dummy #8",
            "alias": "Alias #22",
            "dummyDate": null,
            "jsonData": [],
            "dummy": null,
            "relatedDummy": null,
            "relatedDummies": []
          }
      ],
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "\/dummies{?id,name,order[id],order[name],dummyDate[before],dummyDate[after]}",
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
  Scenario: Filter with non-exact match
    When I send a "GET" request to "/dummies?name=Dummy%20%238"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?name=Dummy%20%238",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?name=Dummy+%238",
      "hydra:lastPage": "/dummies?name=Dummy+%238",
      "hydra:member": [
          {
            "@id": "/dummies/8",
            "@type": "Dummy",
            "name": "Dummy #8",
            "alias": "Alias #22",
            "dummyDate": null,
            "jsonData": [],
            "dummy": null,
            "relatedDummy": null,
            "relatedDummies": []
          }
      ],
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "\/dummies{?id,name,order[id],order[name],dummyDate[before],dummyDate[after]}",
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
