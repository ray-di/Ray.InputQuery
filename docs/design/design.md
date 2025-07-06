# Ray.InputQuery 設計

## 概要

Ray.InputQueryは、フラットなクエリーデータから型安全なPHPオブジェクトを生成する基盤ライブラリです。単体で動作し、Ray.MediaQueryやBEAR.Resourceなど他のライブラリから利用されることを前提に設計されています。

HTTPリクエストやフォームデータなどの外部入力を、アプリケーションが扱いやすい型付きオブジェクトに変換することで、型安全性を外部境界まで拡張します。

## アーキテクチャ

```
[HTTPリクエスト/クエリー] 
    ↓ (フラットな構造)
[Ray.InputQuery]
    ↓ (Inputオブジェクト生成)
[アプリケーション層]
    ├→ [BEAR.Resource] → ResourceObject
    └→ [Ray.MediaQuery] → Database
```

### データフローの例

```
POST /articles/123/comments
author_name=John&author_email=john@example.com&content=Great!
    ↓
Ray.InputQuery
    ↓
CommentInput {
    content: "Great!",
    author: AuthorInput {
        name: "John",
        email: "john@example.com"
    }
}
    ↓
アプリケーションロジック
```

## コア設計

## 動機と解決する問題

### 従来のアプローチの問題点

```php
// 型安全性の欠如
public function createArticle(Request $request)
{
    $title = $request->input('title');        // string? null? array?
    $authorName = $request->input('author_name');
    $authorEmail = $request->input('author_email');
    
    // 手動でのバリデーションと変換
    if (empty($title)) {
        throw new ValidationException('Title is required');
    }
    
    // 構造が不明確
    $author = new Author($authorName, $authorEmail);
    $article = new Article($title, $content, $author);
}
```

### Ray.InputQueryによる解決

```php
// 型安全で構造が明確
public function createArticle(ArticleInput $input)
{
    // すでに型チェック済み、構造化済み
    return $this->repository->save(
        Article::fromInput($input)
    );
}
```

## Input専用クラスという新しいパラダイム

Ray.InputQueryは「入力を第一級市民として扱う」という設計思想に基づいています：

- **一方向性**: 外部から内部への入力専用
- **構造の表現**: フォームやAPIの構造をそのまま反映
- **一時性**: 処理の入口でのみ存在
- **組み合わせ可能**: 小さなInputを組み合わせて複雑な入力を表現

### 主要インターフェース

```php
namespace Ray\InputQuery;

interface InputQueryInterface
{
    /**
     * メソッドの引数リストを取得
     * 
     * @param \ReflectionMethod $method
     * @param array<string, mixed> $query
     * @return array<int, mixed> Position-based arguments
     */
    public function getArguments(\ReflectionMethod $method, array $query): array;
    
    /**
     * Inputオブジェクトを生成
     * 
     * @param class-string $class
     * @param array<string, mixed> $query
     * @return object
     */
    public function create(string $class, array $query): object;
}
```

### 実装クラス

```php
namespace Ray\InputQuery;

use Ray\Di\InjectorInterface;

final class InputQuery implements InputQueryInterface
{
    public function __construct(
        private InjectorInterface $injector
    ) {}
    
    public function getArguments(\ReflectionMethod $method, array $query): array
    {
        // メソッドの引数を解析し、引数リストを生成
        $args = [];
        foreach ($method->getParameters() as $param) {
            $args[] = $this->resolveParameter($param, $query);
        }
        return $args;
    }
    
    public function create(string $class, array $query): object
    {
        // クラスのコンストラクタを解析し、オブジェクトを生成
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return new $class();
        }
        
        $args = $this->getArguments($constructor, $query);
        return $reflection->newInstanceArgs($args);
    }
}
```

## Input属性

```php
namespace Ray\InputQuery\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Input
{
}
```

### 使用方法

Input属性は**パラメータレベル**で使用し、そのパラメータがクエリーデータから取得されることを示します：

```php
final class ExampleInput
{
    public function __construct(
        #[Input] public readonly string $name,      // クエリーから
        #[Input] public readonly ?int $age,         // クエリーから（nullable）
        #[Input] public readonly UserInput $user,   // クエリーから（ネスト）
        private LoggerInterface $logger             // DIから（#[Input]なし）
    ) {}
}
```

### 重要な設計決定

- **クラスレベルではなくパラメータレベル**で属性を使用
- これにより、同一クラス内でクエリーデータとDIの両方を受け取ることが可能
- データの出所が明確になり、保守性が向上

## クエリー処理の詳細設計

### 1. キー名の正規化

すべてのキーをキャメルケースに統一：
- `user_name` → `userName`
- `user-name` → `userName`
- `UserName` → `userName`

### 2. パラメータの解決ルール

```php
final class ExampleInput
{
    public function __construct(
        #[Input] public readonly string $title,          // クエリーから
        #[Input] public readonly ?string $description,   // クエリーから
        #[Input] public readonly UserInput $user,        // クエリーから（ネスト）
        #[Named('app.timezone')] private string $zone,   // DIから
        private LoggerInterface $logger                  // DIから
    ) {}
}
```

**シンプルなルール**：
- `#[Input]` → クエリーから（スカラー・オブジェクト問わず）
- それ以外 → DIから

### 3. ネストしたオブジェクトの解決

```php
// フラットなクエリー
[
    'title' => 'Buy milk',
    'assigneeId' => '123',
    'assigneeName' => 'John',
    'assigneeEmail' => 'john@example.com'
]

// assigneeプレフィックスを持つキーを検出し、UserInputを構築
UserInput(
    id: '123',
    name: 'John', 
    email: 'john@example.com'
)
```

### 4. 引数解決のフロー

1. **パラメータの#[Input]属性を確認**
   - ある場合 → クエリーから取得
   - ない場合 → DIから取得

2. **クエリーからの取得（#[Input]がある場合）**
   - スカラー型 → 直接取得・型変換
   - オブジェクト型 → ネスト解決

3. **DIからの取得（#[Input]がない場合）**
   - オブジェクト → インジェクターから取得
   - スカラー → #[Named]があれば名前付きバインディング

## 実装の重要ポイント

### エラーハンドリング
- 必須パラメータが見つからない場合は、型のデフォルト値やnullを使用
- 型変換エラーは適切に処理

### パフォーマンス
- リフレクションの結果はキャッシュ可能な設計に
- 深い再帰を避ける（実用上3階層程度まで）

### 拡張性
- 将来的なJSON Schema統合を考慮した設計
- カスタムコンバーターを追加可能な構造

## テスト駆動開発の指針

### 基本的な使用例のテスト

```php
public function testCreateObject(): void
{
    $injector = new Injector();
    $inputQuery = new InputQuery($injector);
    
    $query = [
        'name' => 'John',
        'email' => 'john@example.com'
    ];
    
    $user = $inputQuery->create(UserInput::class, $query);
    
    assert($user->name === 'John');
    assert($user->email === 'john@example.com');
}

public function testGetArguments(): void
{
    $inputQuery = new InputQuery(new Injector());
    $method = new \ReflectionMethod(TodoController::class, 'create');
    
    $query = [
        'title' => 'Buy milk',
        'assignee_id' => '123',
        'assignee_name' => 'John'
    ];
    
    $args = $inputQuery->getArguments($method, $query);
    
    assert($args[0] instanceof TodoInput);
    assert($args[0]->title === 'Buy milk');
}
```

### ネストしたInputのテスト

```php
public function testNestedInput(): void
{
    $inputQuery = new InputQuery(new Injector());
    
    $query = [
        'title' => 'Buy milk',
        'assignee_id' => '123',  
        'assignee_name' => 'John',
        'assignee_email' => 'john@example.com'
    ];
    
    $todo = $inputQuery->create(TodoCreateInput::class, $query);
    
    assert($todo->title === 'Buy milk');
    assert($todo->assignee instanceof UserInput);
    assert($todo->assignee->id === '123');
}
```

## 実際の動作例

### 1. シンプルな例

```php
// HTTPリクエスト
POST /users
name=John+Doe&email=john@example.com

// Inputクラス
final class UserInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email
    ) {}
}

// 使用
$user = $inputQuery->newInstance(UserInput::class, $_POST);
echo $user->name;  // "John Doe"
echo $user->email; // "john@example.com"
```

### 2. ネストした構造の例

```php
// HTTPリクエスト
POST /articles
title=Hello+World&content=Lorem+ipsum&author_name=John&author_email=john@example.com

// Inputクラス
final class ArticleInput
{
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly string $content,
        #[Input] public readonly AuthorInput $author
    ) {}
}

final class AuthorInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email
    ) {}
}

// 自動的にネスト構造を構築
$article = $inputQuery->newInstance(ArticleInput::class, $_POST);
echo $article->author->name; // "John"
```

### 3. DIとの組み合わせ

```php
final class ProcessOrderInput
{
    public function __construct(
        #[Input] public readonly string $orderId,
        #[Input] public readonly CustomerInput $customer,
        private OrderService $orderService,      // DIから注入
        private MailerInterface $mailer          // DIから注入
    ) {}
    
    public function process(): Order
    {
        $order = $this->orderService->create($this->orderId, $this->customer);
        $this->mailer->sendConfirmation($order);
        return $order;
    }
}
```

## Ray.*エコシステムでの位置づけ

Ray.InputQueryは、Ray.MediaQueryと組み合わせることで、完全な型安全データフローを実現します：

```
[Webフォーム/API] 
    ↓ Ray.InputQuery（入力の構造化）
[型付きInputオブジェクト]
    ↓ ビジネスロジック
[Ray.MediaQuery]
    ↓ 自動展開
[SQL/WebAPI]
```

### 統合例：BEAR.Sunday

```php
class Article extends ResourceObject
{
    public function __construct(
        private ArticleRepositoryInterface $repository
    ) {}
    
    public function onPost(ArticleInput $article): static
    {
        // Ray.InputQueryが自動的に：
        // 1. POSTデータを解析
        // 2. ArticleInputオブジェクトを生成
        // 3. メソッドに注入
        
        $id = $this->repository->save($article);
        
        $this->code = 201;
        $this->headers['Location'] = "/articles/{$id}";
        
        return $this;
    }
}
```

### 統合例：Ray.MediaQuery

```php
interface ArticleRepositoryInterface
{
    #[DbQuery('article_add')]
    public function save(ArticleInput $article): string;
}

// Ray.MediaQueryが自動的にInputオブジェクトを展開：
// ArticleInput { title: "Hello", author: { name: "John", email: "john@example.com" }}
// ↓
// :title = "Hello", :authorName = "John", :authorEmail = "john@example.com"
```

## 設計原則

1. **シンプルさ** - 特別な設定なしで動作
2. **型安全性** - PHPの型システムを最大限活用
3. **拡張性** - インターフェースベースの設計
4. **相互運用性** - 既存のエコシステムとの統合
5. **テスタビリティ** - 各コンポーネントが独立してテスト可能

## 将来の拡張

### JSON Schema統合（Phase 2）

JSON Schemaは**メソッドレベル**で指定し、コンテキストに応じたバリデーションを実現します：

```php
class UserController extends ResourceObject
{
    #[JsonSchema('schemas/user-create.json')]
    public function onPost(UserInput $user): static
    {
        // 作成時のバリデーション：email, password必須
        $id = $this->repository->create($user);
        $this->code = 201;
        return $this;
    }
    
    #[JsonSchema('schemas/user-update.json')]
    public function onPut(string $id, UserInput $user): static
    {
        // 更新時のバリデーション：passwordは任意
        $this->repository->update($id, $user);
        return $this;
    }
}
```

#### メソッドレベルの利点

1. **コンテキスト依存のバリデーション**
   - 同じInputクラスでも、作成・更新・参照で異なるルール適用可能
   - 権限によって異なるバリデーション（管理者 vs 一般ユーザー）

2. **APIドキュメントとの統合**
   ```php
   #[JsonSchema('schemas/article-post.json')]
   #[OpenApi(summary: 'Create new article', tags: ['Articles'])]
   public function onPost(ArticleInput $article): static
   ```

3. **段階的なバリデーション**
   - Ray.InputQuery: 型レベルの基本チェック
   - JSON Schema: ビジネスルールのバリデーション

4. **再利用性**
   ```php
   // 同じInputクラスを異なるコンテキストで使用
   final class ProductInput { /* ... */ }
   
   #[JsonSchema('schemas/product-admin.json')]  // 全フィールド必須
   public function adminCreate(ProductInput $product): void
   
   #[JsonSchema('schemas/product-draft.json')]  // 最小限のフィールド
   public function saveDraft(ProductInput $product): void
   ```

### 実装イメージ

```php
// Ray.MediaQueryやBEAR.Resourceでの統合
class JsonSchemaInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        $method = $invocation->getMethod();
        $schema = $method->getAttributes(JsonSchema::class)[0] ?? null;
        
        if ($schema) {
            $args = $invocation->getArguments();
            // Inputオブジェクトを検証
            $this->validator->validate($args, $schema->newInstance()->path);
        }
        
        return $invocation->proceed();
    }
}
```

### AIとの親和性

HTMLフォームからInputクラスを自動生成：

```
入力: <form>...</form>
出力: Inputクラスのコード
```

## まとめ

Ray.InputQueryは、フラットなクエリーデータから型安全なPHPオブジェクトを生成する基盤ライブラリです。

### 提供する価値

1. **型安全性の境界拡張** - HTTPリクエストレベルから型チェック
2. **構造の明確化** - フォームとコードの構造が一致
3. **開発効率の向上** - ボイラープレートコードの削減
4. **エコシステムとの統合** - Ray.MediaQuery、BEAR.Resourceとのシームレスな連携

### 核となる機能
- **getArguments()** - メソッドの引数リストを生成（主要用途）
- **create()** - オブジェクトを直接生成（補助用途）

### 設計の特徴
- パラメータレベルでの#[Input]属性によるデータソースの明示
- DIとの自然な統合
- ネストしたオブジェクトの自動解決
- 最小限の依存（ray/diのみ）

これにより、Webアプリケーション開発における「入力」を第一級市民として扱い、より安全で保守性の高いコードベースを実現します。
