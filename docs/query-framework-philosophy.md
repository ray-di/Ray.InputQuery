# Queryフレームワーク - 透明性と調和の設計哲学

## Queryフレームワークとは

メディア間の境界を明確にし、各メディアの構造をそのまま活かすアプローチです。Ray.MediaQueryとRay.InputQueryは、HTTPフォーム・PHPオブジェクト・SQLという異なるメディア間の境界で、構造を保持したまま型安全な橋渡しを実現します。

### 二つの核心

#### 1. 構造の保持
```
HTMLフォーム                  →  Inputオブジェクト
<input name="author_name">    →  AuthorInput { name, email }
<input name="author_email">   

SQL結果                       →  エンティティ
author_name, author_email     →  Author { name, email }
```

#### 2. 境界の明確化
```
[HTTP境界]     [アプリケーション境界]     [データベース境界]
    ↓                   ↓                      ↓
フォーム → InputQuery → ビジネスロジック → MediaQuery → SQL
    ↑                   ↑                      ↑
  構造保持            型安全              構造保持
```

各境界で何が起きているかが明確で、追跡可能です。

## なぜ境界が重要か

### 境界は責任の分離点

各メディアには固有の制約と可能性があります：

- **HTTPフォーム**: フラットな名前付きデータ
- **PHPオブジェクト**: 型付き構造データ  
- **SQL**: リレーショナルな集合操作
- **テンプレート**: 表示用の階層構造

境界を明確にすることで、各層がその特性に最適化された形で動作できます。

### 境界での変換は明示的に

```php
// HTTP → PHP（Ray.InputQuery）
$input = $inputQuery(OrderInput::class, $_POST);

// PHP → SQL（Ray.MediaQuery）
$order = $repository->save($input);

// SQL → PHP（Ray.MediaQuery + Koriym.CsvEntities）
$orderWithItems = $repository->findWithItems($orderId);
```

各変換が何をしているか、どこで起きているかが明確です。

## 設計アプローチの違い

### 従来：境界を消して抽象化

### ORMの思想
```php
// SQLを隠蔽する
$users = User::where('active', true)
             ->with('posts.comments.author')
             ->orderBy('created_at', 'desc')
             ->get();

// 実際のSQLは見えない、見たくない、見せたくない
```

### フレームワークの抽象化
```php
// 入力を隠蔽する
$validated = $request->validate([
    'title' => 'required|max:255',
    'email' => 'required|email'
]);

// 生のHTTPデータは隠される
```

これらは「すべてをオブジェクト指向で統一する」という設計思想に基づいています。境界を曖昧にすることで、複雑さを隠そうとするアプローチです。

## Queryフレームワークのアプローチ

### 境界を尊重し、各層を第一級市民として扱う

#### 1. SQLを第一級市民に
```sql
-- user_dashboard_stats.sql
WITH user_activity AS (
    SELECT 
        user_id,
        COUNT(DISTINCT DATE(created_at)) as active_days
    FROM activities
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    GROUP BY user_id
)
SELECT 
    u.*,
    COALESCE(ua.active_days, 0) as active_days
FROM users u
LEFT JOIN user_activity ua ON u.id = ua.user_id
WHERE u.id = :userId
```

- SQLファイルとして独立して存在
- SQLの表現力をフルに活用
- DBエンジンの機能を直接使える

#### 2. 入力を第一級市民に
```php
final class UserSearchInput
{
    public function __construct(
        #[Input] public readonly string $keyword,
        #[Input] public readonly DateRangeInput $dateRange,
        #[Input] public readonly ?int $limit = 20
    ) {}
}
```

- Input専用クラスとして独立
- フォームの構造を素直に表現
- 入力固有のバリデーションロジック

#### 3. HTTPを第一級市民に
```
GET /users?keyword=john&date_range_from=2024-01-01&date_range_to=2024-12-31&limit=50
POST /articles
Content-Type: application/x-www-form-urlencoded

title=My+Article&content=Lorem+ipsum&author_name=John&author_email=john@example.com
```

- クエリーパラメータの構造をそのまま尊重
- 名前付きパラメータとしての本質を活かす

## 名前付きパラメータという共通言語

Queryフレームワークが機能する理由は、すべての層が名前付きパラメータという共通のパラダイムを持っているからです：

```
HTTP:  ?userId=123&status=active
  ↓
PHP:   function getUser(string $userId, string $status)
  ↓
SQL:   WHERE user_id = :userId AND status = :status
  ↓
Twig:  {{ user.id }} {{ user.status }}
```

## 透明な変換

### Ray.InputQuery
```php
// HTTPクエリー → 型付きオブジェクト
$input = $inputQuery(UserSearchInput::class, $_GET);
```

### Ray.MediaQuery  
```php
// オブジェクト → SQL実行
interface UserRepositoryInterface
{
    #[DbQuery('user_dashboard_stats')]
    public function getStats(string $userId): UserStats;
}
```

名前付きパラメータが各層をつなぐ共通の仕組みとなっています。

## 設計思想の比較

### 従来のアプローチ
- SQLの詳細を抽象化
- オブジェクト指向での統一を重視
- 下位層の詳細を隠蔽
- フレームワークによる包括的な管理

### Queryフレームワーク
- SQLを開発ツールとして活用
- 各層の最適な表現を尊重
- 境界を明確にして理解を促進
- 開発者の専門性を活かす

## 実践的な利点

### 1. AI時代との親和性
各層が明確で標準的な形式を持つため：
- SQLをAIが理解・最適化できる
- フォームからInputクラスを自動生成
- 仕様（JSON Schema）からコード生成

### 2. 専門性の活用
- **SQLエキスパート**：最適なクエリーを書ける
- **フロントエンド開発者**：フォーム構造に集中
- **バックエンド開発者**：ビジネスロジックに集中
- 各専門家が力を発揮できる

### 3. 透明性によるメリット
- 何が起きているか明確に見える
- パフォーマンスボトルネックを特定しやすい
- デバッグが容易
- 学習曲線が緩やか

### 4. 進化への適応性
- 新しいSQL機能をすぐに使える
- 新しい入力形式に対応しやすい
- 部分的な最適化が可能

## まとめ

Queryフレームワークの設計方針：

- **構造の保持** - メディアの構造をそのままオブジェクトに
- **境界の明確化** - 各層の境界を尊重し、透明に接続
- **透明性** - 各層で何が起きているか明確にする
- **調和** - 各層の特性を活かしながら協調させる

これは技術的な選択であると同時に、開発者の専門性を尊重し、各技術の強みを活かすという立場の表明でもあります。

複雑さを隠すのではなく、整理する。
統一するのではなく、調和させる。
境界を消すのではなく、明確にする。

このアプローチが、より持続可能で理解しやすいシステム構築につながると考えています。