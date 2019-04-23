Feature: Create-Retrieve-Update-Delete
  In order to use an hypermedia API
  As a client software developer
  I need to be able to retrieve, create, update and delete JSON-LD encoded resources.

  @createSchema
  Scenario: Create a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
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
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/dummies/1"
    And the header "Location" should be equal to "/dummies/1"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "dummyFloat": null,
      "dummyPrice": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "jsonData": {
        "key": [
          "value1",
          "value2"
        ]
      },
      "arrayData": [],
      "name_converted": null,
      "relatedOwnedDummy": null,
      "relatedOwningDummy": null,
      "id": 1,
      "name": "My Dummy",
      "alias": null,
      "foo": null
    }
    """

  Scenario: Get a resource
    When I send a "GET" request to "/dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": "2015-03-01T10:00:00+00:00",
      "dummyFloat": null,
      "dummyPrice": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "jsonData": {
        "key": [
          "value1",
          "value2"
        ]
      },
      "arrayData": [],
      "name_converted": null,
      "relatedOwnedDummy": null,
      "relatedOwningDummy": null,
      "id": 1,
      "name": "My Dummy",
      "alias": null,
      "foo": null
    }
    """

  Scenario: Create a resource with empty body
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies"
    Then the response status code should be 400
    And the JSON node "hydra:description" should be equal to "Syntax error"

  Scenario: Get a not found exception
    When I send a "GET" request to "/dummies/42"
    Then the response status code should be 404

  Scenario: Get a collection
    When I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/dummies/1",
          "@type": "Dummy",
          "description": null,
          "dummy": null,
          "dummyBoolean": null,
          "dummyDate": "2015-03-01T10:00:00+00:00",
          "dummyFloat": null,
          "dummyPrice": null,
          "relatedDummy": null,
          "relatedDummies": [],
          "jsonData": {
            "key": [
              "value1",
              "value2"
            ]
          },
          "arrayData": [],
          "name_converted": null,
          "relatedOwnedDummy": null,
          "relatedOwningDummy": null,
          "id": 1,
          "name": "My Dummy",
          "alias": null,
          "foo": null
        }
      ],
      "hydra:totalItems": 1,
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "/dummies{?dummyBoolean,relatedDummy.embeddedDummy.dummyBoolean,dummyDate[before],dummyDate[strictly_before],dummyDate[after],dummyDate[strictly_after],relatedDummy.dummyDate[before],relatedDummy.dummyDate[strictly_before],relatedDummy.dummyDate[after],relatedDummy.dummyDate[strictly_after],description[exists],relatedDummy.name[exists],dummyBoolean[exists],relatedDummy[exists],dummyFloat,dummyFloat[],dummyPrice,dummyPrice[],order[id],order[name],order[description],order[relatedDummy.name],order[relatedDummy.symfony],order[dummyDate],dummyFloat[between],dummyFloat[gt],dummyFloat[gte],dummyFloat[lt],dummyFloat[lte],dummyPrice[between],dummyPrice[gt],dummyPrice[gte],dummyPrice[lt],dummyPrice[lte],id,id[],name,alias,description,relatedDummy.name,relatedDummy.name[],relatedDummies,relatedDummies[],dummy,relatedDummies.name,relatedDummy.thirdLevel.level,relatedDummy.thirdLevel.level[],relatedDummy.thirdLevel.fourthLevel.level,relatedDummy.thirdLevel.fourthLevel.level[],relatedDummy.thirdLevel.badFourthLevel.level,relatedDummy.thirdLevel.badFourthLevel.level[],relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level,relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level[],properties[]}",
        "hydra:variableRepresentation": "BasicRepresentation",
        "hydra:mapping": [
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyBoolean",
            "property": "dummyBoolean",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.embeddedDummy.dummyBoolean",
            "property": "relatedDummy.embeddedDummy.dummyBoolean",
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
            "variable": "dummyDate[strictly_before]",
            "property": "dummyDate",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyDate[after]",
            "property": "dummyDate",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyDate[strictly_after]",
            "property": "dummyDate",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.dummyDate[before]",
            "property": "relatedDummy.dummyDate",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.dummyDate[strictly_before]",
            "property": "relatedDummy.dummyDate",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.dummyDate[after]",
            "property": "relatedDummy.dummyDate",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.dummyDate[strictly_after]",
            "property": "relatedDummy.dummyDate",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "description[exists]",
            "property": "description",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.name[exists]",
            "property": "relatedDummy.name",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyBoolean[exists]",
            "property": "dummyBoolean",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy[exists]",
            "property": "relatedDummy",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyFloat",
            "property": "dummyFloat",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyFloat[]",
            "property": "dummyFloat",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyPrice",
            "property": "dummyPrice",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyPrice[]",
            "property": "dummyPrice",
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
            "variable": "order[description]",
            "property": "description",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "order[relatedDummy.name]",
            "property": "relatedDummy.name",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "order[relatedDummy.symfony]",
            "property": "relatedDummy.symfony",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "order[dummyDate]",
            "property": "dummyDate",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyFloat[between]",
            "property": "dummyFloat",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyFloat[gt]",
            "property": "dummyFloat",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyFloat[gte]",
            "property": "dummyFloat",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyFloat[lt]",
            "property": "dummyFloat",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyFloat[lte]",
            "property": "dummyFloat",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyPrice[between]",
            "property": "dummyPrice",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyPrice[gt]",
            "property": "dummyPrice",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyPrice[gte]",
            "property": "dummyPrice",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyPrice[lt]",
            "property": "dummyPrice",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummyPrice[lte]",
            "property": "dummyPrice",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "id",
            "property": "id",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "id[]",
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
            "variable": "alias",
            "property": "alias",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "description",
            "property": "description",
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
            "variable": "relatedDummy.name[]",
            "property": "relatedDummy.name",
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
            "variable": "relatedDummies[]",
            "property": "relatedDummies",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "dummy",
            "property": "dummy",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummies.name",
            "property": "relatedDummies.name",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.level",
            "property": "relatedDummy.thirdLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.level[]",
            "property": "relatedDummy.thirdLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.fourthLevel.level",
            "property": "relatedDummy.thirdLevel.fourthLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.fourthLevel.level[]",
            "property": "relatedDummy.thirdLevel.fourthLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.badFourthLevel.level",
            "property": "relatedDummy.thirdLevel.badFourthLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.badFourthLevel.level[]",
            "property": "relatedDummy.thirdLevel.badFourthLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level",
            "property": "relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level[]",
            "property": "relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level",
            "required": false
          },
          {
              "@type": "IriTemplateMapping",
              "variable": "properties[]",
              "property": null,
              "required": false
          }
        ]
      }
    }
    """

  Scenario: Update a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummies/1" with body:
    """
    {
      "@id": "/dummies/1",
      "name": "A nice dummy",
      "dummyDate": "2018-12-01 13:12",
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
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/dummies/1"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": "2018-12-01T13:12:00+00:00",
      "dummyFloat": null,
      "dummyPrice": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "jsonData": [
        {
          "key": "value1"
        },
        {
          "key": "value2"
        }
      ],
      "arrayData": [],
      "name_converted": null,
      "relatedOwnedDummy": null,
      "relatedOwningDummy": null,
      "id": 1,
      "name": "A nice dummy",
      "alias": null,
      "foo": null
    }
    """

  Scenario: Update a resource with empty body
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummies/1"
    Then the response status code should be 400
    And the JSON node "hydra:description" should be equal to "Syntax error"

  Scenario: Delete a resource
    When I send a "DELETE" request to "/dummies/1"
    Then the response status code should be 204
    And the response should be empty
