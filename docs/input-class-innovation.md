# Input専用クラスの革新性 - 新しいパラダイム

## 「入力」を第一級市民として扱う

これまでのWebアプリケーション開発では、入力は単なる「通過点」として扱われてきました。Ray.InputQueryは、**入力を独立したドメイン**として認識し、専用の抽象化を提供する革新的なアプローチです。

## 従来のパターンとの違い

### エンティティ（Entity）
```php
class User {
    private $id;
    private $name;
    public function changeName($name) { ... }  // ビジネスロジック
}
```
永続化され、アイデンティティを持つビジネスオブジェクト

### DTO（Data Transfer Object）
```php
class UserDto {
    public $id;
    public $name;
    // ロジックなし、でも入出力両方に使われる
}
```
データの運搬役、しかし構造を表現しない

### Input専用クラス（革新的アプローチ）
```php
final class UserRegistrationInput {
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly PasswordInput $password,
        #[Input] public readonly bool $agreeToTerms  // UIの状態も表現
    ) {}
}
```

## Input専用クラスの特徴

### 1. **一方向性**
外部（フォーム、API）から内部（アプリケーション）への入力専用。出力には使われない。

### 2. **構造の表現**
```php
final class CheckoutInput {
    public function __construct(
        #[Input] public readonly CartInput $cart,
        #[Input] public readonly ShippingAddressInput $shipping,
        #[Input] public readonly PaymentMethodInput $payment,
        #[Input] public readonly ?string $couponCode = null
    ) {}
}
```
これはチェックアウト画面の構造そのもの！

### 3. **一時性**
永続化されない。処理の入口でのみ存在し、その役割を終えると消える。

### 4. **入力固有の表現**
```php
final class PasswordInput {
    public function __construct(
        #[Input] public readonly string $password,
        #[Input] public readonly string $passwordConfirm  // 入力時だけ必要
    ) {
        if ($password !== $passwordConfirm) {
            throw new PasswordMismatchException();
        }
    }
}
```
エンティティには不要だが、入力時には必要な`passwordConfirm`を自然に表現。

## なぜこれが画期的か

### 1. **型の境界を押し広げる**

従来：
```
[型なし領域] → 手動変換 → [型あり領域]
$_POST         バリデーション    Entity
```

Ray.InputQuery：
```
[型あり領域 ←→ 型あり領域 ←→ 型あり領域]
フォーム        Input         Entity
```

型安全性をシステムの最外縁まで拡張！

### 2. **宣言的プログラミング**

命令的（従来）：
```php
$title = $_POST['title'] ?? '';
$authorName = $_POST['author_name'] ?? '';
if (empty($title)) {
    throw new ValidationException('Title required');
}
$author = new Author($authorName, $_POST['author_email']);
$todo = new Todo($title, $author);
```

宣言的（Ray.InputQuery）：
```php
final class TodoInput {
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly AuthorInput $author
    ) {}
}
```

**「どう変換するか」から「何であるべきか」へ**

### 3. **Webの構造との自然な一致**

HTTPリクエスト：
```
POST /articles/123/comments
author_name=John&author_email=john@example.com&content=Great!
```

Inputクラス：
```php
final class CommentInput {
    public function __construct(
        #[Input] public readonly string $content,
        #[Input] public readonly AuthorInput $author
    ) {}
}
```

**Webの基本構造（クエリー/フォームデータ）がそのままオブジェクトに！**

### 4. **フォームとコードの統合**

HTML：
```html
<form>
    <fieldset>
        <legend>Shipping Address</legend>
        <input name="shipping_street">
        <input name="shipping_city">
    </fieldset>
</form>
```

PHP：
```php
final class OrderInput {
    public function __construct(
        #[Input] public readonly ShippingAddressInput $shipping
    ) {}
}
```

**HTMLの構造とPHPの構造が一致！**

## 三位一体の設計（将来）

```
     Inputクラス（PHP）
         ／  ＼
        ／    ＼
    Form    JSON Schema
   (HTML)   (仕様/契約)
```

- **Inputクラス**：型安全な実装
- **HTMLフォーム**：ユーザーインターフェース
- **JSON Schema**：仕様とバリデーション

すべてが同じ構造を異なる形式で表現。

## 革新的な開発フロー

### 現在
```
開発者がフォームを見る
    ↓
手動でバリデーションコードを書く
    ↓
手動でマッピングコードを書く
```

### Ray.InputQuery
```
フォームを定義
    ↓
Inputクラスを定義（構造を宣言）
    ↓
自動的に変換される
```

### 将来（AI時代）
```
HTMLフォーム
    ↓
AI：「このフォームに対応するInputクラスを生成」
    ↓
完成！
```

## データと振る舞いの新しい関係

```php
final class OrderProcessInput {
    public function __construct(
        // データ（外部入力）
        #[Input] public readonly string $orderId,
        #[Input] public readonly CustomerInput $customer,
        #[Input] public readonly ?string $couponCode,
        
        // サービス（内部機能）
        private OrderService $orderService,
        private NotificationService $notifier
    ) {}
    
    public function process(): OrderResult {
        // 入力データとサービスを組み合わせた処理
        $order = $this->orderService->create($this->orderId, $this->customer);
        
        if ($this->couponCode) {
            $order->applyCoupon($this->couponCode);
        }
        
        $this->notifier->notify($order);
        return $order;
    }
}
```

**入力を単なるデータではなく、処理能力を持つオブジェクトとして扱う！**

## まとめ：パラダイムシフト

Ray.InputQueryは、以下の点で従来の開発手法を根本的に変えます：

1. **入力の独立性**：入力を独立したドメインとして認識
2. **構造の一致**：フォーム、コード、仕様が同じ構造を共有
3. **型の拡張**：型安全性を外部境界まで拡張
4. **宣言的設計**：「何を」に集中し、「どうやって」は自動化

これは単なる便利ツールではなく、**Webアプリケーション開発における新しい思考法**の提案です。

「フォームからデータを取得する」のではなく、「入力の構造を定義する」という発想の転換が、より良いWebアプリケーション開発への道を開きます。