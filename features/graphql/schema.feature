Feature: GraphQL schema-related features

  @createSchema
  Scenario: Export the GraphQL schema in SDL
    When I run the command "api:graphql:export"
    Then the command output should contain:
    """
   ###Dummy Friend.###
    type DummyFriend implements Node {
      id: ID!

      ###The id###
      _id: Int!

      ###The dummy name###
      name: String!
    }

    ###Dummy Friend.###
    type DummyFriendCollection implements Node {
      id: ID!

      ###The id###
      _id: Int!

      ###The dummy name###
      name: String!
    }

    ###Connection for DummyFriendCollection.###
    type DummyFriendCollectionConnection {
      edges: [DummyFriendCollectionEdge]
      pageInfo: DummyFriendCollectionPageInfo!
      totalCount: Int!
    }

    ###Edge of DummyFriendCollection.###
    type DummyFriendCollectionEdge {
      node: DummyFriendCollection
      cursor: String!
    }

    ###Information about the current page.###
    type DummyFriendCollectionPageInfo {
      endCursor: String
      startCursor: String
      hasNextPage: Boolean!
      hasPreviousPage: Boolean!
    }

    ###Dummy Friend.###
    type DummyFriendItem implements Node {
      id: ID!

      ###The id###
      _id: Int!

      ###The dummy name###
      name: String!
    }
    """

  Scenario: Export the GraphQL schema in SDL with comment descriptions
    When I run the command "api:graphql:export" with options:
      | --comment-descriptions | true |
    Then the command output should contain:
    """
    # Dummy Friend.
    type DummyFriend implements Node {
      id: ID!

      # The id
      _id: Int!

      # The dummy name
      name: String!
    }

    # Dummy Friend.
    type DummyFriendCollection implements Node {
      id: ID!

      # The id
      _id: Int!

      # The dummy name
      name: String!
    }

    # Connection for DummyFriendCollection.
    type DummyFriendCollectionConnection {
      edges: [DummyFriendCollectionEdge]
      pageInfo: DummyFriendCollectionPageInfo!
      totalCount: Int!
    }

    # Edge of DummyFriendCollection.
    type DummyFriendCollectionEdge {
      node: DummyFriendCollection
      cursor: String!
    }

    # Information about the current page.
    type DummyFriendCollectionPageInfo {
      endCursor: String
      startCursor: String
      hasNextPage: Boolean!
      hasPreviousPage: Boolean!
    }

    # Dummy Friend.
    type DummyFriendItem implements Node {
      id: ID!

      # The id
      _id: Int!

      # The dummy name
      name: String!
    }
    """
