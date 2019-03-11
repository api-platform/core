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

    ###Connection for DummyFriend.###
    type DummyFriendConnection {
      edges: [DummyFriendEdge]
      pageInfo: DummyFriendPageInfo!
      totalCount: Int!
    }

    ###Edge of DummyFriend.###
    type DummyFriendEdge {
      node: DummyFriend
      cursor: String!
    }

    ###Information about the current page.###
    type DummyFriendPageInfo {
      endCursor: String
      startCursor: String
      hasNextPage: Boolean!
      hasPreviousPage: Boolean!
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

    # Connection for DummyFriend.
    type DummyFriendConnection {
      edges: [DummyFriendEdge]
      pageInfo: DummyFriendPageInfo!
      totalCount: Int!
    }

    # Edge of DummyFriend.
    type DummyFriendEdge {
      node: DummyFriend
      cursor: String!
    }

    # Information about the current page.
    type DummyFriendPageInfo {
      endCursor: String
      startCursor: String
      hasNextPage: Boolean!
      hasPreviousPage: Boolean!
    }
    """
