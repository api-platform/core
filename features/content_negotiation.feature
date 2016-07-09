Feature: Content Negotiation support
  In order to make the API supporting several input and output formats
  As an API developer
  I need to be able to specify the format I want to use

  @createSchema
  Scenario: Post an XML body
    When I add "Accept" header equal to "application/xml"
    And I send a "POST" request to "/dummies" with body:
    """
    <root>
        <name>XML!</name>
    </root>
    """
    Then the response status code should be 201
    And the header "Content-Type" should be equal to "application/xml"
    And the response should be equal to
    """
<?xml version="1.0"?>
<response><id>1</id><name>XML!</name><alias/><description/><dummyDate/><dummyPrice/><jsonData/><relatedDummy/><dummyBoolean/><dummy/><relatedDummies/><nameConverted/></response>
    """

  Scenario:  Retrieve a collection in XML
    When I add "Accept" header equal to "text/xml"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/xml"
    And the response should be equal to
    """
<?xml version="1.0"?>
<response><item key="0"><id>1</id><name>XML!</name><alias/><description/><dummyDate/><dummyPrice/><jsonData/><relatedDummy/><dummyBoolean/><dummy/><relatedDummies/><nameConverted/></item></response>
    """

  Scenario:  Retrieve a collection in XML using the .xml URL
    When I send a "GET" request to "/dummies.xml"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/xml"
    And the response should be equal to
    """
<?xml version="1.0"?>
<response><item key="0"><id>1</id><name>XML!</name><alias/><description/><dummyDate/><dummyPrice/><jsonData/><relatedDummy/><dummyBoolean/><dummy/><relatedDummies/><nameConverted/></item></response>
    """

  Scenario:  Retrieve a collection in JSON
    When I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/json"
    And the response should be in JSON
    And the JSON should be equal to:
    """
[
  {
    "id": 1,
    "name": "XML!",
    "alias": null,
    "description": null,
    "dummyDate": null,
    "dummyPrice": null,
    "jsonData": [],
    "relatedDummy": null,
    "dummyBoolean": null,
    "dummy": null,
    "relatedDummies": [],
    "nameConverted": null
  }
]
    """

  @dropSchema
  Scenario: Requesting an unknown format should return JSON-LD
    When I add "Accept" header equal to "text/plain"
    And I send a "GET" request to "/dummies/1"
    Then the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {        
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": null,
      "dummyPrice": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "jsonData": [],
      "name_converted": null,
      "name": "XML!",
      "alias": null
    }
    """
