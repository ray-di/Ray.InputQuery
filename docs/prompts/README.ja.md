# docs/prompts/ のプロンプトファイルの使い方

このディレクトリには、Ray.InputQueryのInputクラス自動生成や利用例生成のためのプロンプトが格納されています。

## 使い方

### input-class-generator.md
  - 
  - Inputクラスの自動生成やリファクタリングをAIに依頼する際に、このプロンプトを与えてください。
  - 既存のフラットなパラメータリストを構造化されたInputオブジェクトへ変換したい場合に活用します。
- 例: 

- ```html
  <!-- このHTMLフォームからInputクラスを生成してください -->
  <form>
      <input name="user_name" type="text">
      <input name="user_email" type="email">
      <input name="order_id" type="number">
      <input name="order_total" type="number">
  </form>
  ```

### usage-generator.md
  - 
  - Inputクラスの利用例や、Ray.MediaQuery・BEAR.Resourceとの連携例をAIに生成させたい場合に、このプロンプトを与えてください。
  - Inputクラスの使い方や実装例を知りたい場合に活用します。
  - 例: 

```php
final class UserRegistrationInput
{
    public function __construct(
        #[Input] public readonly AccountInput $account,
        #[Input] public readonly ProfileInput $profile,
        #[Input] public readonly PreferencesInput $preferences
    ) {}
}

final class AccountInput
{
    public function __construct(
        #[Input] public readonly string $email,
        #[Input] public readonly string $password,
        #[Input] public readonly string $passwordConfirm
    ) {}
}

final class ProfileInput
{
    public function __construct(
        #[Input] public readonly string $firstName,
        #[Input] public readonly string $lastName,
        #[Input] public readonly ?string $displayName = null
    ) {}
}```

---

これらのプロンプトは、Ray.InputQueryの導入・活用・リファクタリング・ドキュメント生成など、AIを活用した開発支援に役立ちます。
