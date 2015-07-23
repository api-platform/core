Feature: Order filter on collections
  In order to retrieve ordered large collections of resources
  As a client software developer
  I need to retrieve collections ordered properties

  @createSchema
  Scenario: Get collection ordered in ascending order on an integer property and on which order filter has been enabled in whitelist mode
    Given there is "30" dummy objects
    When I send a "GET" request to "/dummies?order[id]=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[id]=asc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bid%5D=asc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bid%5D=asc",
      "hydra:lastPage": "/dummies?order%5Bid%5D=asc&page=10",
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
            "relatedDummies": [],
            "name_converted": null
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
            "relatedDummies": [],
            "name_converted": null
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

  Scenario: Get collection ordered in descending order on an integer property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[id]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[id]=desc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bid%5D=desc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bid%5D=desc",
      "hydra:lastPage": "/dummies?order%5Bid%5D=desc&page=10",
      "hydra:member": [
          {
            "@id": "/dummies/30",
            "@type": "Dummy",
            "name": "Dummy #30",
            "alias": "Alias #0",
            "dummyDate": null,
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
            "dummyDate": null,
            "jsonData": [],
            "dummy": null,
            "relatedDummy": null,
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/28",
            "@type": "Dummy",
            "name": "Dummy #28",
            "alias": "Alias #2",
            "dummyDate": null,
            "jsonData": [],
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

  Scenario: Get collection ordered in ascending order on a string property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[name]=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[name]=asc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bname%5D=asc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bname%5D=asc",
      "hydra:lastPage": "/dummies?order%5Bname%5D=asc&page=10",
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
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/10",
            "@type": "Dummy",
            "name": "Dummy #10",
            "alias": "Alias #20",
            "dummyDate": null,
            "jsonData": [],
            "dummy": null,
            "relatedDummy": null,
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/11",
            "@type": "Dummy",
            "name": "Dummy #11",
            "alias": "Alias #19",
            "dummyDate": null,
            "jsonData": [],
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

  Scenario: Get collection ordered in descending order on a string property and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[name]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[name]=desc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bname%5D=desc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bname%5D=desc",
      "hydra:lastPage": "/dummies?order%5Bname%5D=desc&page=10",
      "hydra:member": [
          {
            "@id": "/dummies/9",
            "@type": "Dummy",
            "name": "Dummy #9",
            "alias": "Alias #21",
            "dummyDate": null,
            "jsonData": [],
            "dummy": null,
            "relatedDummy": null,
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/8",
            "@type": "Dummy",
            "name": "Dummy #8",
            "alias": "Alias #22",
            "dummyDate": null,
            "jsonData": [],
            "dummy": null,
            "relatedDummy": null,
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/7",
            "@type": "Dummy",
            "name": "Dummy #7",
            "alias": "Alias #23",
            "dummyDate": null,
            "jsonData": [],
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

  Scenario: Get collection ordered by default configured order on a string property and on which order filter has been enabled in whitelist mode with default descending order
    When I send a "GET" request to "/dummies?order[name]"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[name]",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bname%5D=&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bname%5D=",
      "hydra:lastPage": "/dummies?order%5Bname%5D=&page=10",
      "hydra:member": [
          {
            "@id": "/dummies/9",
            "@type": "Dummy",
            "name": "Dummy #9",
            "alias": "Alias #21",
            "dummyDate": null,
            "jsonData": [],
            "dummy": null,
            "relatedDummy": null,
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/8",
            "@type": "Dummy",
            "name": "Dummy #8",
            "alias": "Alias #22",
            "dummyDate": null,
            "jsonData": [],
            "dummy": null,
            "relatedDummy": null,
            "relatedDummies": [],
            "name_converted": null
          },
          {
            "@id": "/dummies/7",
            "@type": "Dummy",
            "name": "Dummy #7",
            "alias": "Alias #23",
            "dummyDate": null,
            "jsonData": [],
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

  @dropSchema
  Scenario: Get collection ordered by a non valid properties and on which order filter has been enabled in whitelist mode
    When I send a "GET" request to "/dummies?order[alias]=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[alias]=asc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Balias%5D=asc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Balias%5D=asc",
      "hydra:lastPage": "/dummies?order%5Balias%5D=asc&page=10",
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
          "relatedDummies": [],
          "name_converted": null
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
          "relatedDummies": [],
          "name_converted": null
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

    When I send a "GET" request to "/dummies?order[alias]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[alias]=desc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Balias%5D=desc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Balias%5D=desc",
      "hydra:lastPage": "/dummies?order%5Balias%5D=desc&page=10",
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
          "relatedDummies": [],
          "name_converted": null
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
          "relatedDummies": [],
          "name_converted": null
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

    When I send a "GET" request to "/dummies?order[unknown]=asc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[unknown]=asc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bunknown%5D=asc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bunknown%5D=asc",
      "hydra:lastPage": "/dummies?order%5Bunknown%5D=asc&page=10",
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
          "relatedDummies": [],
          "name_converted": null
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
          "relatedDummies": [],
          "name_converted": null
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

    When I send a "GET" request to "/dummies?order[unknown]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies?order[unknown]=desc",
      "@type": "hydra:PagedCollection",
      "hydra:nextPage": "/dummies?order%5Bunknown%5D=desc&page=2",
      "hydra:totalItems": 30,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/dummies?order%5Bunknown%5D=desc",
      "hydra:lastPage": "/dummies?order%5Bunknown%5D=desc&page=10",
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
          "relatedDummies": [],
          "name_converted": null
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
          "relatedDummies": [],
          "name_converted": null
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
