@elasticsearch
Feature: Retrieve from Elasticsearch
  In order to use an hypermedia API
  As a client software developer
  I need to be able to retrieve JSON-LD encoded resources from Elasticsearch

  Scenario: Get a resource
    When I send a "GET" request to "/users/116b83f8-6c32-48d8-8e28-c5c247532d3f"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/User",
      "@id": "/users/116b83f8-6c32-48d8-8e28-c5c247532d3f",
      "@type": "User",
      "id": "116b83f8-6c32-48d8-8e28-c5c247532d3f",
      "gender": "male",
      "age": 31,
      "firstName": "Kilian",
      "lastName": "Jornet",
      "tweets": [
        {
          "@id": "/tweets/f36a0026-0635-4865-86a6-5adb21d94d64",
          "@type": "Tweet",
          "id": "f36a0026-0635-4865-86a6-5adb21d94d64",
          "date": "2017-01-01T01:01:01+00:00",
          "message": "The north summit, Store Vengetind Thanks for t... These Top 10 Women of a fk... Francois is the field which."
        },
        {
          "@id": "/tweets/89601e1c-3ef2-4ef7-bca2-7511d38611c6",
          "@type": "Tweet",
          "id": "89601e1c-3ef2-4ef7-bca2-7511d38611c6",
          "date": "2017-02-02T02:02:02+00:00",
          "message": "Great day in any endur... During the Himalayas were very talented skimo racer junior podiums, top 10."
        },
        {
          "@id": "/tweets/9da70727-d656-42d9-876a-1be6321f171b",
          "@type": "Tweet",
          "id": "9da70727-d656-42d9-876a-1be6321f171b",
          "date": "2017-03-03T03:03:03+00:00",
          "message": "During the path and his Summits Of My Life project. Next Wednesday, Kilian Jornet..."
        }
      ]
    }
    """

  Scenario: Get a not found exception
    When I send a "GET" request to "/users/12345678-abcd-1234-abcdefgh"
    Then the response status code should be 404

  Scenario: Get the first page of a collection
    When I send a "GET" request to "/tweets"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Tweet",
      "@id": "/tweets",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/tweets/0acfd90d-5bfe-4e42-b708-dc38bf20677c",
          "@type": "Tweet",
          "id": "0acfd90d-5bfe-4e42-b708-dc38bf20677c",
          "author": {
            "@id": "/users/c81d5151-0d28-4b06-baeb-150bd2b2bbf8",
            "@type": "User",
            "id": "c81d5151-0d28-4b06-baeb-150bd2b2bbf8",
            "gender": "male",
            "age": 35,
            "firstName": "Anton",
            "lastName": "Krupicka"
          },
          "date": "2017-08-08T08:08:08+00:00",
          "message": "Whoever curates the Marathon yesterday. Truly inspiring stuff. The new is straight. Such a couple!"
        },
        {
          "@id": "/tweets/0cfe3d33-6116-416b-8c50-3b8319331998",
          "@type": "Tweet",
          "id": "0cfe3d33-6116-416b-8c50-3b8319331998",
          "author": {
            "@id": "/users/15fce6f1-18fd-4ef6-acab-7e6a3333ec7f",
            "@type": "User",
            "id": "15fce6f1-18fd-4ef6-acab-7e6a3333ec7f",
            "gender": "male",
            "age": 28,
            "firstName": "Jim",
            "lastName": "Walmsley"
          },
          "date": "2017-09-09T09:09:09+00:00",
          "message": "Thanks! Fun day with Next up one of our 2018 cover: One look into what races we'll be running that they!"
        },
        {
          "@id": "/tweets/1c9e0545-1b37-4a9a-83e0-30400d0b354e",
          "@type": "Tweet",
          "id": "1c9e0545-1b37-4a9a-83e0-30400d0b354e",
          "author": {
            "@id": "/users/fbf60054-004f-4d21-a178-cb364d1ef875",
            "@type": "User",
            "id": "fbf60054-004f-4d21-a178-cb364d1ef875",
            "gender": "male",
            "age": 30,
            "firstName": "Zach",
            "lastName": "Miller"
          },
          "date": "2017-10-10T10:10:10+00:00",
          "message": "Way to go for me I think it was great holiday season yourself!! I'm still working on the awesome as I."
        }
      ],
      "hydra:totalItems": 20,
      "hydra:view": {
        "@id": "/tweets?page=1",
        "@type": "hydra:PartialCollectionView",
        "hydra:first": "/tweets?page=1",
        "hydra:last": "/tweets?page=7",
        "hydra:next": "/tweets?page=2"
      },
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "/tweets{?order[id],order[author.id],message,message[],author.firstName,author.firstName[]}",
        "hydra:variableRepresentation": "BasicRepresentation",
        "hydra:mapping": [
          {
            "@type": "IriTemplateMapping",
            "variable": "order[id]",
            "property": "id",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "order[author.id]",
            "property": "author.id",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "message",
            "property": "message",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "message[]",
            "property": "message",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "author.firstName",
            "property": "author.firstName",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "author.firstName[]",
            "property": "author.firstName",
            "required": false
          }
        ]
      }
    }
    """

  Scenario: Get a page of a collection
    When I send a "GET" request to "/tweets?page=3"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Tweet",
      "@id": "/tweets",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/tweets/6d82a76c-8ba2-4e78-9ab3-6a456e4470c3",
          "@type": "Tweet",
          "id": "6d82a76c-8ba2-4e78-9ab3-6a456e4470c3",
          "author": {
            "@id": "/users/fa7d4578-6692-47ec-9346-a8ab25ca613c",
            "@type": "User",
            "id": "fa7d4578-6692-47ec-9346-a8ab25ca613c",
            "gender": "female",
            "age": 42,
            "firstName": "Caroline",
            "lastName": "Chaverot"
          },
          "date": "2018-01-01T13:13:13+00:00",
          "message": "Prior to not run in paradise ! I should have listened to ! What a little more of hesitation, I?"
        },
        {
          "@id": "/tweets/7cdadcda-3fb5-4312-9e32-72acba323cc0",
          "@type": "Tweet",
          "id": "7cdadcda-3fb5-4312-9e32-72acba323cc0",
          "author": {
            "@id": "/users/fbf60054-004f-4d21-a178-cb364d1ef875",
            "@type": "User",
            "id": "fbf60054-004f-4d21-a178-cb364d1ef875",
            "gender": "male",
            "age": 30,
            "firstName": "Zach",
            "lastName": "Miller"
          },
          "date": "2017-12-12T12:12:12+00:00",
          "message": "Thanks! Thanks Senseman! Good luck at again! Open air sleeps! 669 now. Message me. You bet Kyle! PT: Try."
        },
        {
          "@id": "/tweets/811e4d1c-df3f-4d24-a9da-2a28080c85f5",
          "@type": "Tweet",
          "id": "811e4d1c-df3f-4d24-a9da-2a28080c85f5",
          "author": {
            "@id": "/users/fbf60054-004f-4d21-a178-cb364d1ef875",
            "@type": "User",
            "id": "fbf60054-004f-4d21-a178-cb364d1ef875",
            "gender": "male",
            "age": 30,
            "firstName": "Zach",
            "lastName": "Miller"
          },
          "date": "2017-11-11T11:11:11+00:00",
          "message": "DES!!!!!!! For that in LA airport skills: chugging water, one-handed bathroom maneuvers, and the!"
        }
      ],
      "hydra:totalItems": 20,
      "hydra:view": {
        "@id": "/tweets?page=3",
        "@type": "hydra:PartialCollectionView",
        "hydra:first": "/tweets?page=1",
        "hydra:last": "/tweets?page=7",
        "hydra:previous": "/tweets?page=2",
        "hydra:next": "/tweets?page=4"
      },
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "/tweets{?order[id],order[author.id],message,message[],author.firstName,author.firstName[]}",
        "hydra:variableRepresentation": "BasicRepresentation",
        "hydra:mapping": [
          {
            "@type": "IriTemplateMapping",
            "variable": "order[id]",
            "property": "id",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "order[author.id]",
            "property": "author.id",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "message",
            "property": "message",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "message[]",
            "property": "message",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "author.firstName",
            "property": "author.firstName",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "author.firstName[]",
            "property": "author.firstName",
            "required": false
          }
        ]
      }
    }
    """

  Scenario: Get the last page of a collection
    When I send a "GET" request to "/tweets?page=7"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Tweet",
      "@id": "/tweets",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/tweets/f36a0026-0635-4865-86a6-5adb21d94d64",
          "@type": "Tweet",
          "id": "f36a0026-0635-4865-86a6-5adb21d94d64",
          "author": {
            "@id": "/users/116b83f8-6c32-48d8-8e28-c5c247532d3f",
            "@type": "User",
            "id": "116b83f8-6c32-48d8-8e28-c5c247532d3f",
            "gender": "male",
            "age": 31,
            "firstName": "Kilian",
            "lastName": "Jornet"
          },
          "date": "2017-01-01T01:01:01+00:00",
          "message": "The north summit, Store Vengetind Thanks for t... These Top 10 Women of a fk... Francois is the field which."
        },
        {
          "@id": "/tweets/f91bca21-b5f8-405b-9b08-d5a5dc476a92",
          "@type": "Tweet",
          "id": "f91bca21-b5f8-405b-9b08-d5a5dc476a92",
          "author": {
            "@id": "/users/fa7d4578-6692-47ec-9346-a8ab25ca613c",
            "@type": "User",
            "id": "fa7d4578-6692-47ec-9346-a8ab25ca613c",
            "gender": "female",
            "age": 42,
            "firstName": "Caroline",
            "lastName": "Chaverot"
          },
          "date": "2018-02-02T14:14:14+00:00",
          "message": "Good job girls ! Chacun de publier un outil innovant repertoriant des prochains championnats du!"
        }
      ],
      "hydra:totalItems": 20,
      "hydra:view": {
        "@id": "/tweets?page=7",
        "@type": "hydra:PartialCollectionView",
        "hydra:first": "/tweets?page=1",
        "hydra:last": "/tweets?page=7",
        "hydra:previous": "/tweets?page=6"
      },
      "hydra:search": {
        "@type": "hydra:IriTemplate",
        "hydra:template": "/tweets{?order[id],order[author.id],message,message[],author.firstName,author.firstName[]}",
        "hydra:variableRepresentation": "BasicRepresentation",
        "hydra:mapping": [
          {
            "@type": "IriTemplateMapping",
            "variable": "order[id]",
            "property": "id",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "order[author.id]",
            "property": "author.id",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "message",
            "property": "message",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "message[]",
            "property": "message",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "author.firstName",
            "property": "author.firstName",
            "required": false
          },
          {
            "@type": "IriTemplateMapping",
            "variable": "author.firstName[]",
            "property": "author.firstName",
            "required": false
          }
        ]
      }
    }
    """
