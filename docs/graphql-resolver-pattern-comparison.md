# GraphQL Resolver Pattern vs Ray.InputQuery

## Introduction

Ray.InputQuery's Input-First architecture shares many conceptual similarities with GraphQL's resolver pattern. Both approaches prioritize structured data transformation, type safety, and predictable data flow. This document explores the parallels and differences between these two paradigms.

## Core Similarities

### 1. Schema-First Design

**GraphQL Schema**
```graphql
type User {
  id: ID!
  name: String!
  email: String!
  posts: [Post!]!
}

input CreateUserInput {
  name: String!
  email: String!
}

type Mutation {
  createUser(input: CreateUserInput!): User!
}
```

**Ray.InputQuery Input Objects**
```php
class CreateUserInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
    ) {}
}

class User
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly array $posts,
    ) {}
}
```

### 2. Type-Safe Resolvers

**GraphQL Resolver**
```javascript
const resolvers = {
  Mutation: {
    createUser: async (parent, { input }, context) => {
      // input is type-safe based on schema
      const user = await context.userService.createUser(input);
      return user; // Must match User type
    }
  },
  
  User: {
    posts: async (parent, args, context) => {
      // parent.id is guaranteed to exist
      return context.postService.findByUserId(parent.id);
    }
  }
};
```

**Ray.InputQuery Controller**
```php
class UserController
{
    public function createUser(CreateUserInput $input): User
    {
        // $input is type-safe from HTTP request
        $user = $this->userService->createUser($input);
        return $user; // Type-safe return
    }
    
    public function getUserPosts(User $user): array
    {
        // $user is guaranteed to be valid
        return $this->postService->findByUserId($user->id);
    }
}
```

### 3. Hierarchical Data Resolution

**GraphQL Query Execution**
```
Query: { user(id: "123") { name, posts { title } } }

Execution Flow:
1. user resolver: id → User object
2. posts resolver: User → Post[]
3. title resolver: Post → string
```

**Ray.InputQuery Data Flow**
```
HTTP Request: POST /users { "name": "John", "posts": [...] }

Execution Flow:
1. HTTP → CreateUserInput (validation)
2. CreateUserInput → User (business logic)
3. User → Database (persistence)
4. Database → User (retrieval)
```

## Key Differences

### 1. Query Language vs HTTP

**GraphQL**
```graphql
# Client-defined query shape
query GetUserWithPosts($userId: ID!) {
  user(id: $userId) {
    name
    email
    posts(first: 10) {
      title
      createdAt
    }
  }
}
```

**Ray.InputQuery**
```php
// Server-defined endpoint structure
class GetUserInput
{
    public function __construct(
        #[Input] public readonly string $userId,
        #[Input] public readonly int $postsLimit = 10,
    ) {}
}
```

### 2. Resolver Granularity

**GraphQL - Field-Level Resolvers**
```javascript
const userResolvers = {
  // Each field can have its own resolver
  name: (parent) => parent.fullName || parent.name,
  email: (parent) => parent.emailAddress,
  posts: async (parent, { first }) => {
    return await fetchUserPosts(parent.id, first);
  },
  avatar: (parent) => generateAvatarUrl(parent.id)
};
```

**Ray.InputQuery - Method-Level Processing**
```php
class UserController
{
    // Entire response structure determined by method
    public function getUser(GetUserInput $input): UserResponse
    {
        $user = $this->userRepository->find($input->userId);
        $posts = $this->postRepository->findByUserId(
            $user->id, 
            $input->postsLimit
        );
        
        return new UserResponse($user, $posts);
    }
}
```

### 3. N+1 Problem Handling

**GraphQL - DataLoader Pattern**
```javascript
const userLoader = new DataLoader(async (userIds) => {
  const users = await db.users.findByIds(userIds);
  return userIds.map(id => users.find(user => user.id === id));
});

const postResolvers = {
  author: (post) => userLoader.load(post.authorId)
};
```

**Ray.InputQuery - Eager Loading**
```php
class PostController
{
    public function getPosts(GetPostsInput $input): array
    {
        // Explicitly handle eager loading
        return $this->postRepository->findWithAuthors($input->toQueryParams());
    }
}

// In repository
public function findWithAuthors(array $params): array
{
    return $this->db->query("
        SELECT p.*, u.name as author_name 
        FROM posts p 
        JOIN users u ON p.author_id = u.id 
        WHERE " . $this->buildWhereClause($params)
    );
}
```

## Architectural Patterns Comparison

### 1. Data Loading Strategies

**GraphQL - Pull-Based**
```javascript
// Client requests exactly what it needs
query {
  user(id: "123") {
    name        # Only load if requested
    email       # Only load if requested
    posts {     # Only load if requested
      title
    }
  }
}
```

**Ray.InputQuery - Push-Based**
```php
// Server determines what to include
class UserDetailInput
{
    public function __construct(
        #[Input] public readonly string $userId,
        #[Input] public readonly bool $includePosts = false,
        #[Input] public readonly bool $includeAvatar = false,
    ) {}
}
```

### 2. Caching Strategies

**GraphQL - Query-Level Caching**
```javascript
// Cache based on query and variables
const cacheKey = hash(query + JSON.stringify(variables));
const result = cache.get(cacheKey) || await executeQuery(query, variables);
```

**Ray.InputQuery - Method-Level Caching**
```php
class UserController
{
    #[Cache(ttl: 3600)]
    public function getUser(GetUserInput $input): User
    {
        // Cached based on method + input parameters
        return $this->userService->findUser($input->userId);
    }
}
```

### 3. Error Handling

**GraphQL - Partial Success**
```javascript
{
  "data": {
    "user": {
      "name": "John Doe",
      "email": null    // Field failed but query continues
    }
  },
  "errors": [
    {
      "message": "Email service unavailable",
      "path": ["user", "email"]
    }
  ]
}
```

**Ray.InputQuery - All-or-Nothing**
```php
try {
    $userInput = $inputQuery->newInstance(GetUserInput::class, $_GET);
    $user = $this->userController->getUser($userInput);
    return new JsonResponse($user);
} catch (ValidationException $e) {
    // Complete failure on any validation error
    return new JsonResponse(['error' => $e->getMessage()], 400);
}
```

## Integration Possibilities

### 1. Ray.InputQuery as GraphQL Backend

```php
// Use Ray.InputQuery to implement GraphQL resolvers
class GraphQLUserResolver
{
    public function __construct(
        private InputQueryInterface $inputQuery,
        private UserController $userController,
    ) {}
    
    public function resolveUser(array $args): User
    {
        $input = $this->inputQuery->newInstance(GetUserInput::class, $args);
        return $this->userController->getUser($input);
    }
}
```

### 2. GraphQL-Style Field Resolution

```php
// Implement field-level resolution with Ray.InputQuery
class UserFieldResolver
{
    public function resolveName(User $user, NameInput $input): string
    {
        return $input->format === 'full' 
            ? $user->getFullName() 
            : $user->getFirstName();
    }
    
    public function resolvePosts(User $user, PostsInput $input): array
    {
        return $this->postRepository->findByUserId(
            $user->id,
            $input->limit,
            $input->offset
        );
    }
}
```

## Performance Comparison

### 1. Query Optimization

**GraphQL**
```javascript
// Automatic query optimization
query {
  users {
    id      # Single SELECT query
    name    # for all requested fields
    posts { # with JOIN for relations
      title
    }
  }
}
```

**Ray.InputQuery**
```php
// Manual query optimization
class UsersWithPostsInput
{
    public function __construct(
        #[Input] public readonly bool $includePosts = false,
    ) {}
}

public function getUsers(UsersWithPostsInput $input): array
{
    if ($input->includePosts) {
        return $this->userRepository->findAllWithPosts();
    }
    
    return $this->userRepository->findAll();
}
```

### 2. Over-fetching Prevention

**GraphQL - Automatic**
```graphql
# Client controls data shape
query MinimalUser {
  user(id: "123") {
    name  # Only fetches name field
  }
}
```

**Ray.InputQuery - Manual**
```php
// Define minimal response objects
class MinimalUserResponse
{
    public function __construct(
        public readonly string $name,
    ) {}
}

public function getMinimalUser(GetUserInput $input): MinimalUserResponse
{
    $user = $this->userRepository->findMinimal($input->userId);
    return new MinimalUserResponse($user->name);
}
```

## Best Practices from Both Worlds

### 1. Type-First Design

```php
// Define types before implementation (GraphQL influence)
interface UserServiceInterface
{
    public function createUser(CreateUserInput $input): User;
    public function updateUser(UpdateUserInput $input): User;
    public function deleteUser(DeleteUserInput $input): bool;
}
```

### 2. Resolver Composition

```php
// Compose complex operations from simple ones
class OrderResolver
{
    public function createOrder(CreateOrderInput $input): Order
    {
        $customer = $this->customerResolver->resolveCustomer($input->customerId);
        $items = $this->itemResolver->resolveItems($input->items);
        $shipping = $this->shippingResolver->resolveShipping($input->shipping);
        
        return $this->orderService->create($customer, $items, $shipping);
    }
}
```

### 3. Input Validation Layers

```php
// Multi-layer validation (GraphQL schema + custom validation)
class CreatePostInput
{
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly string $content,
        #[Input] public readonly string $authorId,
    ) {
        // Schema validation (like GraphQL types)
        if (strlen($this->title) < 3) {
            throw new ValidationException('Title too short');
        }
        
        // Business validation (like GraphQL custom scalars)
        if (!$this->isValidAuthor($this->authorId)) {
            throw new ValidationException('Invalid author');
        }
    }
}
```

## Conclusion

Ray.InputQuery and GraphQL share fundamental principles:

### Similarities
- **Type Safety**: Both enforce type contracts
- **Structured Input**: Both use structured input validation
- **Predictable Data Flow**: Both provide clear data transformation pipelines
- **Developer Experience**: Both offer excellent tooling and IDE support

### Complementary Strengths
- **GraphQL**: Client-driven queries, automatic optimization, flexible field resolution
- **Ray.InputQuery**: Server-controlled responses, explicit business logic, HTTP-native design

### Integration Opportunities
- Use Ray.InputQuery to implement GraphQL resolvers
- Apply GraphQL's field-level thinking to Ray.InputQuery controllers
- Combine both for hybrid API architectures

The patterns can work together to create robust, type-safe applications that benefit from both paradigms.

## GraphQL Problems Solved by Input Classes

While GraphQL and Ray.InputQuery take different approaches, they often solve the same fundamental problems. Here's how Input Classes address common GraphQL use cases:

### 1. Over-fetching Problem

**GraphQL Solution**: Client controls field selection
```graphql
query {
  user(id: "123") {
    name  # Only fetch name, not email
  }
}
```

**Input Class Solution**: Server provides optimized input types
```php
class MinimalUserInput
{
    public function __construct(
        #[Input] public readonly string $userId,
        #[Input] public readonly bool $includeName = true,
        #[Input] public readonly bool $includeEmail = false,
        #[Input] public readonly bool $includeProfile = false,
    ) {}
}

// Controller optimizes based on flags
public function getUser(MinimalUserInput $input): UserResponse
{
    $fields = [];
    if ($input->includeName) $fields[] = 'name';
    if ($input->includeEmail) $fields[] = 'email';
    if ($input->includeProfile) $fields[] = 'profile';
    
    return $this->userRepository->findWithFields($input->userId, $fields);
}
```

### 2. Under-fetching Problem (N+1 Queries)

**GraphQL Solution**: DataLoader pattern for batching
```javascript
const userLoader = new DataLoader(userIds => 
  User.findByIds(userIds)
);

const resolvers = {
  Post: {
    author: (post) => userLoader.load(post.authorId)
  }
};
```

**Input Class Solution**: Explicit relationship loading
```php
class PostsWithAuthorsInput
{
    public function __construct(
        #[Input] public readonly array $postIds,
        #[Input] public readonly bool $includeAuthors = false,
        #[Input] public readonly bool $includeAuthorProfiles = false,
    ) {}
}

public function getPosts(PostsWithAuthorsInput $input): array
{
    if ($input->includeAuthors) {
        return $this->postRepository->findWithAuthors(
            $input->postIds,
            $input->includeAuthorProfiles
        );
    }
    
    return $this->postRepository->findByIds($input->postIds);
}
```

### 3. Type Safety Problem

**GraphQL Solution**: Schema-first type definitions
```graphql
type User {
  id: ID!
  name: String!
  email: String!
  age: Int
}

input CreateUserInput {
  name: String!
  email: String!
  age: Int
}
```

**Input Class Solution**: PHP type system with value objects
```php
class CreateUserInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly Email $email,      // Value object ensures validity
        #[Input] public readonly ?Age $age = null,  // Nullable with default
    ) {}
}

class User
{
    public function __construct(
        public readonly UserId $id,           // Type-safe ID
        public readonly string $name,
        public readonly Email $email,
        public readonly ?Age $age,
    ) {}
}
```

### 4. Input Validation Problem

**GraphQL Solution**: Schema constraints and custom validators
```graphql
input CreateUserInput {
  name: String! @constraint(minLength: 2, maxLength: 50)
  email: String! @constraint(format: "email")
  age: Int @constraint(min: 13, max: 120)
}
```

**Input Class Solution**: Constructor validation with business rules
```php
class CreateUserInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly int $age,
    ) {
        // Comprehensive validation in constructor
        if (strlen($this->name) < 2 || strlen($this->name) > 50) {
            throw new ValidationException('Name must be 2-50 characters');
        }
        
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }
        
        if ($this->age < 13 || $this->age > 120) {
            throw new ValidationException('Age must be between 13 and 120');
        }
        
        // Business rule validation
        if ($this->age < 18 && $this->requiresParentalConsent()) {
            throw new ValidationException('Parental consent required for minors');
        }
    }
    
    private function requiresParentalConsent(): bool
    {
        // Business logic here
        return true;
    }
}
```

### 5. API Evolution Problem

**GraphQL Solution**: Field deprecation and schema evolution
```graphql
type User {
  name: String!
  fullName: String! @deprecated(reason: "Use 'name' field instead")
  firstName: String!
  lastName: String!
}
```

**Input Class Solution**: Explicit versioning with migration paths
```php
// Version 1 - Legacy
class CreateUserInputV1
{
    public function __construct(
        #[Input] public readonly string $fullName,
        #[Input] public readonly string $email,
    ) {}
    
    public function toV2(): CreateUserInputV2
    {
        [$firstName, $lastName] = explode(' ', $this->fullName, 2);
        return new CreateUserInputV2($firstName, $lastName ?: '', $this->email);
    }
}

// Version 2 - Current
class CreateUserInputV2
{
    public function __construct(
        #[Input] public readonly string $firstName,
        #[Input] public readonly string $lastName,
        #[Input] public readonly string $email,
    ) {}
}

// Controller handles both versions
public function createUser(CreateUserInputV1|CreateUserInputV2 $input): User
{
    $currentInput = $input instanceof CreateUserInputV1 
        ? $input->toV2() 
        : $input;
        
    return $this->userService->createUser($currentInput);
}
```

### 6. Complex Query Composition

**GraphQL Solution**: Arbitrary query nesting
```graphql
query {
  user(id: "123") {
    posts(first: 10) {
      comments(first: 5) {
        author {
          name
          avatar
        }
      }
    }
  }
}
```

**Input Class Solution**: Predefined composition patterns
```php
class UserWithPostsAndCommentsInput
{
    public function __construct(
        #[Input] public readonly string $userId,
        #[Input] public readonly int $postsLimit = 10,
        #[Input] public readonly bool $includePosts = false,
        #[Input] public readonly int $commentsPerPost = 5,
        #[Input] public readonly bool $includeComments = false,
        #[Input] public readonly bool $includeCommentAuthors = false,
        #[Input] public readonly bool $includeAuthorAvatars = false,
    ) {}
}

public function getUserWithRelations(UserWithPostsAndCommentsInput $input): UserResponse
{
    $user = $this->userRepository->find($input->userId);
    
    if ($input->includePosts) {
        $posts = $this->postRepository->findByUserId(
            $input->userId, 
            $input->postsLimit
        );
        
        if ($input->includeComments) {
            $posts = $this->enrichPostsWithComments(
                $posts, 
                $input->commentsPerPost,
                $input->includeCommentAuthors,
                $input->includeAuthorAvatars
            );
        }
        
        $user = $user->withPosts($posts);
    }
    
    return new UserResponse($user);
}
```

### 7. Real-time Updates

**GraphQL Solution**: Subscriptions
```graphql
subscription {
  messageAdded(channelId: "123") {
    id
    content
    author {
      name
    }
    timestamp
  }
}
```

**Input Class Solution**: Event-driven input types
```php
class MessageSubscriptionInput
{
    public function __construct(
        #[Input] public readonly string $channelId,
        #[Input] public readonly array $eventTypes = ['created', 'updated', 'deleted'],
        #[Input] public readonly bool $includeAuthor = true,
        #[Input] public readonly bool $includeMetadata = false,
    ) {}
}

class MessageEventHandler
{
    public function subscribe(MessageSubscriptionInput $input): EventStream
    {
        return $this->eventBus->subscribe(
            "channel.{$input->channelId}",
            $input->eventTypes,
            function(MessageEvent $event) use ($input) {
                return $this->formatMessage($event->getMessage(), $input);
            }
        );
    }
    
    private function formatMessage(Message $message, MessageSubscriptionInput $input): array
    {
        $data = ['id' => $message->getId(), 'content' => $message->getContent()];
        
        if ($input->includeAuthor) {
            $data['author'] = $message->getAuthor()->toArray();
        }
        
        if ($input->includeMetadata) {
            $data['metadata'] = $message->getMetadata();
        }
        
        return $data;
    }
}
```

### 8. Introspection and Documentation

**GraphQL Solution**: Built-in schema introspection
```graphql
query {
  __schema {
    types {
      name
      fields {
        name
        type {
          name
        }
        description
      }
    }
  }
}
```

**Input Class Solution**: Reflection-based documentation generation
```php
class ApiDocumentationGenerator
{
    public function generateFromInputClass(string $inputClass): array
    {
        $reflection = new ReflectionClass($inputClass);
        $constructor = $reflection->getConstructor();
        
        $documentation = [
            'class' => $inputClass,
            'description' => $this->extractClassDescription($reflection),
            'parameters' => [],
        ];
        
        foreach ($constructor->getParameters() as $param) {
            $inputAttrs = $param->getAttributes(Input::class);
            if (empty($inputAttrs)) continue;
            
            $documentation['parameters'][] = [
                'name' => $param->getName(),
                'type' => $this->getParameterType($param),
                'required' => !$param->isDefaultValueAvailable(),
                'default' => $param->isDefaultValueAvailable() 
                    ? $param->getDefaultValue() 
                    : null,
                'description' => $this->extractParameterDescription($param),
            ];
        }
        
        return $documentation;
    }
    
    public function generateOpenApiSpec(array $inputClasses): array
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => ['title' => 'API Documentation', 'version' => '1.0.0'],
            'components' => ['schemas' => []],
        ];
        
        foreach ($inputClasses as $inputClass) {
            $spec['components']['schemas'][$inputClass] = 
                $this->generateFromInputClass($inputClass);
        }
        
        return $spec;
    }
}
```

## Problem-Solution Comparison Table

| Problem Domain | GraphQL Approach | Input Class Approach | Trade-offs |
|---------------|------------------|---------------------|------------|
| **Over-fetching** | Client field selection | Server optimization flags | GraphQL: Flexible, Input: Optimized |
| **Under-fetching** | DataLoader batching | Explicit relationship loading | GraphQL: Automatic, Input: Predictable |
| **Type Safety** | Schema definitions | PHP types + Value objects | GraphQL: Cross-language, Input: Native |
| **Validation** | Schema + custom resolvers | Constructor validation | GraphQL: Declarative, Input: Programmatic |
| **API Evolution** | Field deprecation | Explicit versioning | GraphQL: Gradual, Input: Controlled |
| **Query Composition** | Arbitrary nesting | Predefined patterns | GraphQL: Flexible, Input: Performant |
| **Real-time** | Subscriptions | Event-driven inputs | GraphQL: Standard, Input: Custom |
| **Documentation** | Introspection | Reflection generation | GraphQL: Built-in, Input: Generated |

## Philosophical Differences

### GraphQL Philosophy: Client-Driven Flexibility
- **Power to the Client**: Clients decide what data they need
- **Runtime Flexibility**: Query shape determined at request time
- **Universal Interface**: Single endpoint for all operations
- **Discovery-Oriented**: Introspection enables client exploration

### Input Class Philosophy: Server-Driven Optimization
- **Power to the Server**: Server optimizes for specific use cases
- **Compile-Time Safety**: Data shape validated at build time  
- **Specialized Endpoints**: Each endpoint optimized for its purpose
- **Contract-Oriented**: Explicit contracts between client and server

### Synthesis
Both approaches solve the same fundamental problems but from opposite directions:

- **GraphQL** gives clients maximum flexibility at the cost of server complexity
- **Input Classes** give servers maximum optimization control at the cost of client flexibility

The choice depends on your architectural priorities:
- Choose **GraphQL** for client diversity and rapid frontend iteration
- Choose **Input Classes** for server performance and type safety guarantees

Both paradigms can coexist and even complement each other in hybrid architectures.