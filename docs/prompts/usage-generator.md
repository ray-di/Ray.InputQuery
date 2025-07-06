# Ray.InputQuery Usage Example Generator

You are an expert in Ray.InputQuery and the Ray.* ecosystem (Ray.MediaQuery, Ray.Di) as well as BEAR.Sunday framework. Generate comprehensive usage examples that demonstrate how to use Input classes in real-world applications.

## Ray.InputQuery Overview

Ray.InputQuery is a library that:
- Converts flat query data (from forms, APIs, etc.) into typed PHP objects
- Automatically handles nested object creation from prefixed fields
- Integrates with dependency injection for mixed data/service parameters
- Works seamlessly with Ray.MediaQuery for database operations
- Integrates with BEAR.Resource for REST resources

## Key Concepts

1. **Automatic Nesting**: Fields with common prefixes become nested objects
   ```
   assigneeId, assigneeName → assignee: UserInput { id, name }
   ```

2. **Parameter Resolution**:
   - With `#[Input]`: From query data
   - Without `#[Input]`: From dependency injection

3. **Flattening for SQL**: When used with Ray.MediaQuery, nested objects are flattened back for SQL binding

## Usage Contexts to Generate

Based on the provided Input classes, generate examples for:

### 1. Basic Ray.InputQuery Usage

```php
use Ray\InputQuery\InputQuery;
use Ray\Di\Injector;

$injector = new Injector();
$inputQuery = new InputQuery($injector);

// Direct object creation
$input = $inputQuery->newInstance(ExampleInput::class, $_POST);

// Method argument resolution
$method = new ReflectionMethod(Controller::class, 'action');
$args = $inputQuery->getArguments($method, $_POST);
```

### 2. BEAR.Resource Integration
```php
use BEAR\Resource\ResourceObject;

class Article extends ResourceObject
{
    public function onPost(ArticleInput $input): static
    {
        // Ray.InputQuery automatically injects the ArticleInput
        $id = $this->repository->save($input);
        
        $this->code = 201;
        $this->headers['Location'] = "/articles/{$id}";
        
        return $this;
    }
}
```

### 3. Ray.MediaQuery Integration
```php
interface TodoRepositoryInterface
{
    #[DbQuery('todo_add')]
    public function add(TodoInput $todo): string;
    
    #[DbQuery('todo_list_by_assignee')]
    public function findByAssignee(string $assigneeId): array;
}
```

For each repository method, provide multiple SQL implementation options:

#### Option A: Simple and Maintainable
```sql
-- todo_add.sql
INSERT INTO todos (title, assignee_id, due_date)
VALUES (:title, :assigneeId, :dueDate);

SELECT LAST_INSERT_ID() as id;
```

#### Option B: With Audit Trail
```sql
-- todo_add.sql
BEGIN;

INSERT INTO todos (title, assignee_id, due_date, created_by, created_at)
VALUES (:title, :assigneeId, :dueDate, :currentUserId, NOW());

SET @todo_id = LAST_INSERT_ID();

INSERT INTO audit_log (entity_type, entity_id, action, user_id, details)
VALUES ('todo', @todo_id, 'create', :currentUserId, JSON_OBJECT(
    'title', :title,
    'assignee_id', :assigneeId
));

SELECT @todo_id as id;

COMMIT;
```

#### Option C: With Denormalized Data
```sql
-- todo_add.sql
INSERT INTO todos (
    title, 
    assignee_id,
    assignee_name,     -- Denormalized for performance
    assignee_email,    -- Denormalized for performance
    due_date,
    search_text        -- For full-text search
)
SELECT 
    :title,
    :assigneeId,
    u.name,
    u.email,
    :dueDate,
    LOWER(CONCAT(:title, ' ', u.name))
FROM users u
WHERE u.id = :assigneeId;

SELECT LAST_INSERT_ID() as id;
```

### 4. Form Processing Flow
Show the complete flow from HTML form to database:
- HTML form structure
- POST data format
- Input object creation
- Business logic processing
- Database storage

### 5. API Endpoint Implementation
```php
class Api extends ResourceObject
{
    public function onPost(string $id, UpdateInput $input): static
    {
        $result = $this->service->update($id, $input);
        $this->body = ['status' => 'success', 'data' => $result];
        
        return $this;
    }
}
```

### 6. Testing Examples
```php
use PHPUnit\Framework\TestCase;

class TodoInputTest extends TestCase
{
    private InputQuery $inputQuery;
    
    protected function setUp(): void
    {
        $injector = new Injector();
        $this->inputQuery = new InputQuery($injector);
    }
    
    public function testCreateTodoInput(): void
    {
        $data = [
            'title' => 'Buy milk',
            'assigneeId' => '123',
            'assigneeName' => 'John Doe',
            'dueDate' => '2024-12-25'
        ];
        
        $todo = $this->inputQuery->create(TodoInput::class, $data);
        
        $this->assertSame('Buy milk', $todo->title);
        $this->assertInstanceOf(UserInput::class, $todo->assignee);
        $this->assertSame('123', $todo->assignee->id);
    }
}
```

### 7. Complex Scenarios

#### CSV Import
```php
class CsvImportResource extends ResourceObject
{
    public function onPost(): static
    {
        $csv = array_map('str_getcsv', file($_FILES['csv']['tmp_name']));
        $headers = array_shift($csv);
        
        foreach ($csv as $row) {
            $data = array_combine($headers, $row);
            $input = $this->inputQuery->create(ImportRowInput::class, $data);
            $this->repository->import($input);
        }
        
        return $this;
    }
}
```

#### Multi-step Form

```php
// Step 1: Basic Info
$step1 = $inputQuery->newInstance(Step1Input::class, $_SESSION['step1']);

// Step 2: Details  
$step2 = $inputQuery->newInstance(Step2Input::class, $_SESSION['step2']);

// Combine for final submission
$order = new OrderInput($step1, $step2, $paymentInput);
```

## Code Generation Guidelines

1. **Show complete, runnable examples** - Include all necessary imports and setup
2. **Add helpful comments** - Explain what Ray.InputQuery is doing automatically
3. **Demonstrate the value** - Show before/after comparisons when helpful
4. **Real-world patterns** - Use realistic scenarios, not just "foo/bar" examples

## Output Format

For each Input class provided, generate:

1. **Basic Usage Example** - Standalone Ray.InputQuery usage
2. **Integration Example** - With BEAR.Resource or Ray.MediaQuery
3. **Test Example** - PHPUnit test showing expected behavior
4. **Real-world Scenario** - Complete flow from input to output

## Special Considerations

### When Showing Database Integration
- Include the SQL file content
- Show how nested objects are flattened
- Demonstrate the parameter binding
- Suggest database schema designs (see below)

### When Showing Form Processing  
- Include relevant HTML form snippet
- Show the POST data structure
- Demonstrate the object creation

### When Showing API Usage
- Include request/response examples
- Show error handling
- Demonstrate content negotiation if relevant

Generate comprehensive usage examples for the following Input classes:

[Paste Input classes here]

## Database Schema Design Suggestions

When generating Ray.MediaQuery integration examples, also provide database schema suggestions based on the Input class structure:

### 1. Maintainability-First Schema
```sql
-- Normalized design with clear relationships
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);

CREATE TABLE user_profiles (
    user_id BIGINT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    display_name VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Separate tables for different concerns
-- Clear foreign key relationships
-- Easy to understand and modify
```

### 2. Performance-First Schema
```sql
-- Denormalized for read performance
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    display_name VARCHAR(100),
    full_name VARCHAR(200) GENERATED ALWAYS AS (CONCAT(first_name, ' ', last_name)) STORED,
    search_text TEXT GENERATED ALWAYS AS (LOWER(CONCAT(email, ' ', first_name, ' ', last_name))) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_full_name (full_name),
    FULLTEXT idx_search (search_text)
);

-- Single table reduces joins
-- Generated columns for search optimization
-- Strategic indexes for common queries
```

### 3. Flexibility-First Schema (JSON)
```sql
-- Using JSON for variable structure
CREATE TABLE entities (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50) NOT NULL,
    data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_created (created_at)
);

-- Query examples:
-- SELECT * FROM entities WHERE type = 'user' AND JSON_EXTRACT(data, '$.email') = ?
-- UPDATE entities SET data = JSON_SET(data, '$.status', ?) WHERE id = ?
```

### 4. Event Sourcing Schema
```sql
-- Immutable event log
CREATE TABLE events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    aggregate_id VARCHAR(36) NOT NULL,
    aggregate_type VARCHAR(50) NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON NOT NULL,
    event_version INT NOT NULL,
    occurred_at TIMESTAMP(6) DEFAULT CURRENT_TIMESTAMP(6),
    INDEX idx_aggregate (aggregate_id, event_version),
    INDEX idx_type_time (aggregate_type, occurred_at)
);

-- Current state materialized view
CREATE TABLE user_projections (
    id VARCHAR(36) PRIMARY KEY,
    current_state JSON NOT NULL,
    version INT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Schema Design Considerations

Based on the Input class structure, suggest:

1. **Table Structure**
   - How nested Input objects map to tables
   - When to normalize vs denormalize
   - Appropriate data types for each field

2. **Indexing Strategy**
   ```sql
   -- For TodoInput with assignee
   CREATE INDEX idx_todo_assignee ON todos(assignee_id, due_date);
   CREATE INDEX idx_todo_status_date ON todos(status, due_date);
   ```

3. **Constraints and Validations**
   ```sql
   -- Reflecting Input class validations in database
   ALTER TABLE orders ADD CONSTRAINT chk_total 
   CHECK (total = subtotal + tax + shipping);
   ```

4. **Query Optimization Hints**
   ```sql
   -- For common access patterns
   CREATE VIEW active_todos AS
   SELECT t.*, u.name as assignee_name, u.email as assignee_email
   FROM todos t
   JOIN users u ON t.assignee_id = u.id
   WHERE t.status = 'active';
   ```

5. **Migration Considerations**
   ```sql
   -- When refactoring from flat to structured
   -- Step 1: Add new columns
   ALTER TABLE orders ADD COLUMN customer_data JSON;
   
   -- Step 2: Migrate data
   UPDATE orders SET customer_data = JSON_OBJECT(
       'name', customer_name,
       'email', customer_email,
       'phone', customer_phone
   );
   
   -- Step 3: Drop old columns (after verification)
   ALTER TABLE orders 
   DROP COLUMN customer_name,
   DROP COLUMN customer_email,
   DROP COLUMN customer_phone;
   ```

### Complex Query Patterns

#### Bulk Operations
```sql
-- todos_bulk_update.sql
-- For updating multiple todos with different assignees
WITH input_data AS (
    SELECT 
        :todoIds AS todo_ids,
        :assigneeIds AS assignee_ids
),
parsed_data AS (
    SELECT 
        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(todo_ids, ',', n.n), ',', -1) AS UNSIGNED) as todo_id,
        SUBSTRING_INDEX(SUBSTRING_INDEX(assignee_ids, ',', n.n), ',', -1) as assignee_id
    FROM input_data
    CROSS JOIN (
        SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
        UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8
    ) n
    WHERE n.n <= 1 + LENGTH(todo_ids) - LENGTH(REPLACE(todo_ids, ',', ''))
)
UPDATE todos t
JOIN parsed_data p ON t.id = p.todo_id
SET t.assignee_id = p.assignee_id,
    t.updated_at = NOW();
```

#### Hierarchical Data Query
```sql
-- project_with_tasks_and_assignees.sql
-- Using GROUP_CONCAT for 1-to-many relationships (Koriym.CsvEntities compatible)
SELECT 
    p.id,
    p.name,
    p.description,
    p.status,
    GROUP_CONCAT(DISTINCT t.id ORDER BY t.created_at) as task_ids,
    GROUP_CONCAT(DISTINCT t.title ORDER BY t.created_at) as task_titles,
    GROUP_CONCAT(DISTINCT t.assignee_id ORDER BY t.created_at) as task_assignee_ids,
    GROUP_CONCAT(DISTINCT u.name ORDER BY t.created_at) as task_assignee_names,
    COUNT(DISTINCT t.id) as task_count,
    COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_count
FROM projects p
LEFT JOIN tasks t ON p.id = t.project_id
LEFT JOIN users u ON t.assignee_id = u.id
WHERE p.id = :projectId
GROUP BY p.id;
```

#### Search with Filtering
```sql
-- todos_search.sql
-- Full-text search with multiple filters
SELECT SQL_CALC_FOUND_ROWS
    t.*,
    u.name as assignee_name,
    u.email as assignee_email,
    MATCH(t.title, t.description) AGAINST(:searchTerm IN NATURAL LANGUAGE MODE) as relevance
FROM todos t
LEFT JOIN users u ON t.assignee_id = u.id
WHERE 1=1
    AND (:searchTerm IS NULL OR MATCH(t.title, t.description) AGAINST(:searchTerm IN NATURAL LANGUAGE MODE))
    AND (:status IS NULL OR t.status = :status)
    AND (:assigneeId IS NULL OR t.assignee_id = :assigneeId)
    AND (:fromDate IS NULL OR t.due_date >= :fromDate)
    AND (:toDate IS NULL OR t.due_date <= :toDate)
ORDER BY 
    CASE WHEN :orderBy = 'relevance' THEN relevance END DESC,
    CASE WHEN :orderBy = 'due_date' THEN t.due_date END ASC,
    CASE WHEN :orderBy = 'created' THEN t.created_at END DESC
LIMIT :offset, :limit;

SELECT FOUND_ROWS() as total_count;
```
