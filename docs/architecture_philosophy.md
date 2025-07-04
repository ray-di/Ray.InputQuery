# 手続きの世界から宣言の世界へ：入力処理の進化と未来

## $_FILESから始まった手続きの世界

PHP黎明期から今日まで、Web開発者は変わらず同じ手続きに向き合い続けています：

```php
// 1990年代から2020年代まで、本質的に変わらないコード
$name = $_POST['name'] ?? '';
if (empty($name)) {
    $errors[] = 'Name is required';
}

$avatar = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    if ($_FILES['avatar']['size'] > 2048000) {
        $errors[] = 'File too large';
    }
    $avatar = $_FILES['avatar'];
}
```

このコードに覚えがある方は多いでしょう。30年近くの技術進歩を経ても、私たちはまだ`$_POST`や`$_FILES`を直接操作し、手動でバリデーションし、配列を型安全なオブジェクトに変換しています。

フレームワークが発達し、ルーティング、テンプレート、ORM、依存性注入などの問題は解決されました。しかし、**境界での変換処理**という最も基本的な作業は、いまだに開発者個人の手に委ねられているのです。

## フレームワークが解決したもの、してないもの

### フレームワークの成果

現代のWebフレームワークは多くの問題を解決しました：

- **ルーティング**: URLからコントローラーメソッドへの自動分岐
- **テンプレート**: HTMLレンダリングの構造化
- **ORM**: データベースとオブジェクトの透明な橋渡し
- **依存性注入**: オブジェクト間の依存関係の自動解決

これらは開発効率を劇的に向上させ、保守しやすいアプリケーションの構築を可能にしました。

### 残された最後の手続き領域

しかし、一つだけ残された領域があります。**外部世界から内部世界への境界**です：

```php
// Laravel - 改善されているが、本質的には手続き的
public function updateProfile(UpdateProfileRequest $request)
{
    $name = $request->input('name');     // 手動抽出
    $email = $request->input('email');   // 手動抽出
    $avatar = $request->file('avatar');  // 手動抽出
    
    if ($avatar) {                       // 手動チェック
        $avatarPath = $avatar->store('avatars');  // 手動処理
    }
}

// Symfony - 同様に手続き的
public function updateProfile(Request $request)
{
    $form = $this->createForm(ProfileType::class);
    $form->handleRequest($request);
    
    if ($form->isValid()) {
        $data = $form->getData();        // 手動抽出
        $name = $data['name'];           // 手動抽出
        $avatar = $form->get('avatar')->getData();  // 手動抽出
    }
}
```

フレームワークは確かに便利なAPIを提供しています。しかし、よく見てください。**結局は手動でデータを取り出し、手動で変換しているだけです**。手続き的なコードは依然として存在し、開発者は「どうやって」データを取得し、「どうやって」変換するかを考え続けています。

## 便利を超えた何かへの希求

ここで重要なのは、私たちが求めているのは単なる「便利さ」ではないということです。`$request->input()`や`$form->getData()`は確かに`$_POST`より便利です。しかし、それは本質的な改善でしょうか？

**便利になっただけで、根本的なパラダイムは何も変わっていません。**

### 思考の枠組みの変化

真に必要なのは、**思考の枠組み自体の変化**です。**Howではなく、Whatに取り組むべき**なのです：

**従来の思考**：「どうやってデータを取得し、変換するか」
```php
// どうやって取得するか
$name = $request->input('name');
// どうやって検証するか  
if (empty($name)) { throw new Exception('Required'); }
// どうやって変換するか
$user = new User($name, $email);
```

**新しい思考**：「何を期待するか」
```php
// 何を期待するかを宣言
public function updateProfile(
    #[Input] string $name,
    #[Input] string $email,
    #[Input] FileUpload|ErrorFileUpload $avatar
) {
    // 期待したものがそのまま提供される
}
```

これは単なる記法の変化ではありません。**命令的思考から宣言的思考への根本的なパラダイムシフト**です。

## 境界の責任を明確にする

### 境界が持つ固有の価値

Web開発における各層には、それぞれ固有の表現力と制約があります：

- **HTTPフォーム**: フラットな名前付きデータ `author_name=John&author_email=john@example.com`
- **PHPオブジェクト**: 型付き構造データ `AuthorInput { name: "John", email: "john@example.com" }`  
- **SQL**: リレーショナルな集合操作 `SELECT * FROM authors WHERE name = :name`

従来のアプローチは、これらの境界を「隠蔽」することで複雑さを管理しようとしてきました。しかし、Ray.InputQueryは異なるアプローチを取ります：**境界を明確にし、各層の特性を活かしながら透明に接続する**のです。

### 名前付きパラメータという共通言語

すべての層が共有する基本的なパラダイムがあります。それは**名前付きパラメータ**です：

```
HTTP:  ?userId=123&status=active
  ↓  自動変換
PHP:   function getUser(string $userId, string $status)
  ↓  自動変換  
SQL:   WHERE user_id = :userId AND status = :status
```

この共通言語があることで、層間の変換を自動化し、開発者を手続き的な変換作業から解放できます。

## 入力を第一級市民として扱う

### Input専用クラスという革命

Ray.InputQueryの最大の革新は、**入力を独立したドメインとして認識**することです：

```php
// Entity（永続化されるビジネスオブジェクト）
class User {
    private $id;
    private $name;
    public function changeName($name) { /* ビジネスロジック */ }
}

// DTO（汎用的なデータ運搬）
class UserDto {
    public $id;
    public $name;
    // 入力と出力の両方で使われる
}

// Input（入力専用、革命的アプローチ）
final class UserRegistrationInput {
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly PasswordInput $password,
        #[Input] public readonly bool $agreeToTerms  // UI固有の状態も含む
    ) {}
}
```

Input専用クラスの特徴：

1. **単方向性**: 外部→内部のみ、出力には使用しない
2. **構造の表現**: フォームの構造をそのまま表現
3. **一時性**: エントリポイントでのみ存在、処理後は破棄
4. **入力固有の表現**: `passwordConfirm`など、入力時のみ必要な情報を自然に表現

### 型安全性の境界拡張

従来のアプローチ：
```
[型なし領域] → 手動変換 → [型付き領域]
   $_POST      バリデーション    Entity
```

Ray.InputQuery：
```
[型付き領域 ←→ 型付き領域 ←→ 型付き領域]
   Form       Input Class     Entity
```

**型安全性をシステムの最外端まで拡張**することで、実行時エラーを型エラーに変換し、IDEの支援を最大限活用できます。

## フレームワーク思想の対比

### 従来のアプローチ：境界を消して抽象化

**ORMの思想**：
```php
// SQLを隠蔽する
$users = User::where('active', true)
             ->with('posts.comments.author')
             ->orderBy('created_at', 'desc')
             ->get();
// 実際のSQLは見えない、見たくない、見せたくない
```

**フレームワークの抽象化**：
```php
// 入力を隠蔽する
$validated = $request->validate([
    'title' => 'required|max:255',
    'email' => 'required|email'
]);
// 生のHTTPデータは隠される
```

これらは「すべてをオブジェクト指向で統一する」という設計思想に基づいています。

### Ray.InputQueryのアプローチ：境界を尊重し、各層を第一級市民として扱う

**HTTPを第一級市民に**：
```
GET /users?keyword=john&date_range_from=2024-01-01&limit=50
```
クエリパラメータの構造をそのまま尊重し、名前付きパラメータとしての本質を活かします。

**入力を第一級市民に**：
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
Input専用クラスとして独立し、フォームの構造を素直に表現します。

### 設計思想の比較

**従来のアプローチ**：
- 下位層の詳細を隠蔽
- オブジェクト指向での統一を重視
- フレームワークによる包括的な管理

**Ray.InputQuery**：
- 各層の最適な表現を尊重
- 境界を明確にして理解を促進
- 開発者の専門性を活かす

複雑さを隠すのではなく、整理する。統一するのではなく、調和させる。境界を消すのではなく、明確にする。

## AI時代との親和性

### 新しい開発フローの実現

**従来**：
```
開発者がフォームを見る
    ↓
手動でバリデーションコードを書く
    ↓
手動でマッピングコードを書く
```

**Ray.InputQuery**：
```
フォームを定義
    ↓
Inputクラスを定義（構造を宣言）
    ↓
自動的に変換される
```

**将来（AI時代）**：
```
HTMLフォーム
    ↓
AI: "このフォーム用のInputクラスを生成してください"
    ↓
完了！
```

### 透明性によるAI理解

各層が明確で標準的な形式を持つため：
- HTMLフォームからInputクラスを自動生成
- 仕様（JSON Schema）からコード生成
- SQLをAIが理解・最適化

**宣言的な記述は人間にもAIにも理解しやすい**のです。

## 実践的な価値：100%カバレッジの実現

Ray.InputQueryは、プライベートメソッドを一切テストせずに100%のコードカバレッジを達成しています。これは設計哲学の現れです：

### 公開インターフェースによる完全性

```php
public function testCreateUserInput(): void
{
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => FileUpload::fromFile(__DIR__ . '/fixtures/avatar.jpg')
    ];
    
    $input = $this->inputQuery->create(UserRegistrationInput::class, $data);
    
    $this->assertSame('John Doe', $input->name);
    $this->assertInstanceOf(FileUpload::class, $input->avatar);
}
```

### 変更容易性の確保

- **プライベートメソッドのテスト無し**: 内部実装の変更がテストに影響しない
- **リファクタリング安全性**: 内部構造の変更や削除が自由に行える
- **保守性**: テストコードが実装詳細に依存しない

これは「テスタビリティと変更容易性は対立するものではない」ことを実証しています。

## フレームワーク横断的価値

### 15年の技術進化を横断

```php
// Yii Framework 1.x (2008年〜)
public function actionCreate()
{
    $method = new ReflectionMethod($this, 'handleUserCreation');
    $args = $inputQuery->getArguments($method, $_POST);
    $result = $this->handleUserCreation(...$args);
}

// Symfony (2011年〜)  
public function create(Request $request): JsonResponse
{
    $method = new ReflectionMethod($this, 'handleUserCreation');
    $args = $inputQuery->getArguments($method, $request->request->all());
    return new JsonResponse($this->handleUserCreation(...$args));
}

// 同じhandleUserCreationメソッドが両方で動作
public function handleUserCreation(
    #[Input] string $name,
    #[Input] string $email,
    #[InputFile] FileUpload|ErrorFileUpload $avatar
): array {
    // ビジネスロジックに集中
}
```

### 技術的負債を生まない設計

- **学習コストの削減**: 一度覚えたAPIが長期間使用できる
- **移植性**: プロジェクト間でのコード再利用が容易
- **進化への対応**: 新しいフレームワークへの対応が迅速

## まとめ：パラダイムシフトの価値

Ray.InputQueryが提示するのは、単なる便利なツールではありません。**Web開発における入力処理の考え方そのものの変革**です。

### 三つの核心的価値

1. **思考の変革**: 「どうやって」から「何を」への転換
2. **境界の明確化**: 各層の特性を活かした透明な接続
3. **宣言的設計**: 人間にもAIにも理解しやすい構造表現

### 持続可能な開発体験

最終的に、この変革の価値は開発者の日常的な体験の改善にあります。複雑で繰り返しの多い境界処理コードから解放されることで、開発者はビジネスロジックの実装により多くの時間と注意を向けることができます。

手続きの世界から宣言の世界へ。これは技術的な進歩であると同時に、**より良いソフトウェア開発のあり方への提案**でもあります。

30年間変わらなかった境界処理に、新しいアプローチが求められています。**入力処理フレームワーク**という概念は、次の30年のWeb開発において重要な役割を果たすかもしれません。

この変革は、単一のライブラリの話ではありません。Web開発全体の進化の方向性なのです。
---

*この記事で紹介しているコード例は、実際に動作するRay.InputQueryライブラリに基づいています。完全なドキュメントと実装は [GitHub](https://github.com/ray-di/Ray.InputQuery) で確認できます。*
