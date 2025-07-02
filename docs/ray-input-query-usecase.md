# Ray.InputQuery ユースケース：ブログシステム

## 概要

ブログ記事とコメントシステムを例に、フォームデータがどのようにRay.InputQuery、Ray.MediaQuery、Koriym.CsvEntitiesを通じて処理され、最終的にテンプレートで表示されるまでの流れを示します。

## システム構成

```
[Webフォーム] 
    ↓ POST /articles/123/comments
[Ray.InputQuery] 
    ↓ CommentInputオブジェクト生成
[BEAR.Resource / Controller]
    ↓ ビジネスロジック実行
[Ray.MediaQuery]
    ↓ データベース操作
[Koriym.CsvEntities]
    ↓ 1対多の関係を効率的に取得
[Twigテンプレート]
    ↓ 表示
[ユーザー]
```

## 1. コメント投稿フロー

### 1.1 HTMLフォーム

```html
<!-- article.html.twig -->
<article>
    <h1>{{ article.title }}</h1>
    <div>{{ article.content }}</div>
    
    <!-- コメント投稿フォーム -->
    <form action="/articles/{{ article.id }}/comments" method="POST">
        <h3>Leave a Comment</h3>
        
        <!-- フラットな名前付け -->
        <input type="text" name="author_name" placeholder="Your name" required>
        <input type="email" name="author_email" placeholder="Your email" required>
        <input type="url" name="author_website" placeholder="Website (optional)">
        
        <textarea name="content" placeholder="Your comment" required></textarea>
        
        <!-- 返信の場合 -->
        <input type="hidden" name="parent_comment_id" value="">
        
        <!-- 評価 -->
        <select name="rating">
            <option value="">No rating</option>
            <option value="1">1 star</option>
            <option value="2">2 stars</option>
            <option value="3">3 stars</option>
            <option value="4">4 stars</option>
            <option value="5">5 stars</option>
        </select>
        
        <button type="submit">Post Comment</button>
    </form>
</article>
```

### 1.2 Inputクラス定義

```php
use Ray\InputQuery\Attribute\Input;
use Ray\Di\Di\Named;

final class CommentInput
{
    public function __construct(
        #[Input] public readonly string $content,
        #[Input] public readonly AuthorInput $author,
        #[Input] public readonly ?string $parentCommentId = null,
        #[Input] public readonly ?int $rating = null
    ) {}
}

final class AuthorInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly ?string $website = null
    ) {}
}
```

### 1.3 ResourceObjectでの処理

```php
use BEAR\Resource\ResourceObject;
use Ray\MediaQuery\Annotation\DbQuery;

class Comment extends ResourceObject
{
    public function __construct(
        private CommentAddInterface $commentAdd,
        private NotificationService $notifier  // DIから注入
    ) {}
    
    public function onPost(string $articleId, CommentInput $comment): static
    {
        // Ray.InputQueryが内部で自動的に以下を実行:
        // 1. メソッドのパラメータを解析
        // 2. CommentInputパラメータに#[Input]があることを検出
        // 3. クエリーからCommentInputオブジェクトを生成
        //    - author_name, author_email → AuthorInputオブジェクト
        //    - content, rating, parent_comment_id → CommentInputのプロパティ
        
        $commentId = $this->commentAdd->add($articleId, $comment);
        
        // 通知サービス（DIから注入されたもの）を使用
        $this->notifier->notifyNewComment($articleId, $commentId);
        
        $this->code = 201;
        $this->headers['Location'] = "/articles/{$articleId}#comment-{$commentId}";
        
        return $this;
    }
}
```

### 1.4 Ray.MediaQueryでのDB保存

```php
interface CommentAddInterface
{
    #[DbQuery('comment_add')]
    public function add(string $articleId, CommentInput $comment): string;
}
```

```sql
-- comment_add.sql
INSERT INTO comments (
    article_id,
    content,
    author_name,
    author_email,
    author_website,
    parent_comment_id,
    rating,
    created_at
) VALUES (
    :articleId,
    :content,
    :authorName,      -- CommentInput->author->name が自動展開
    :authorEmail,     -- CommentInput->author->email が自動展開
    :authorWebsite,   -- CommentInput->author->website が自動展開
    :parentCommentId,
    :rating,
    NOW()
);

SELECT LAST_INSERT_ID() as id;
```

## 2. 記事とコメント表示フロー

### 2.1 記事とコメントの取得

```php
interface ArticleDetailInterface
{
    #[DbQuery('article_with_comments')]
    public function get(string $articleId): ArticleDetail;
}

final class ArticleDetail
{
    /** @var Comment[] */
    public array $comments;
    
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $content,
        public readonly string $authorName,
        public readonly DateTime $publishedAt,
        ?string $commentIds,
        ?string $commentContents,
        ?string $commentAuthorNames,
        ?string $commentAuthorEmails,
        ?string $commentRatings,
        ?string $commentCreatedAts
    ) {
        // Koriym.CsvEntitiesで1対多の関係を構築
        $this->comments = (new CsvEntities())(
            Comment::class,
            $commentIds,
            $commentContents,
            $commentAuthorNames,
            $commentAuthorEmails,
            $commentRatings,
            $commentCreatedAts
        );
    }
}

final class Comment
{
    public function __construct(
        public readonly string $id,
        public readonly string $content,
        public readonly string $authorName,
        public readonly string $authorEmail,
        public readonly ?int $rating,
        public readonly DateTime $createdAt
    ) {}
}
```

### 2.2 SQL（GROUP_CONCATを使用）

```sql
-- article_with_comments.sql
SELECT 
    a.id,
    a.title,
    a.content,
    a.author_name,
    a.published_at,
    GROUP_CONCAT(c.id ORDER BY c.created_at) as comment_ids,
    GROUP_CONCAT(c.content ORDER BY c.created_at) as comment_contents,
    GROUP_CONCAT(c.author_name ORDER BY c.created_at) as comment_author_names,
    GROUP_CONCAT(c.author_email ORDER BY c.created_at) as comment_author_emails,
    GROUP_CONCAT(c.rating ORDER BY c.created_at) as comment_ratings,
    GROUP_CONCAT(c.created_at ORDER BY c.created_at) as comment_created_ats
FROM articles a
LEFT JOIN comments c ON c.article_id = a.id
WHERE a.id = :articleId
GROUP BY a.id;
```

### 2.3 ResourceObjectでの取得

```php
class Article extends ResourceObject
{
    public function __construct(
        private ArticleDetailInterface $articleDetail
    ) {}
    
    public function onGet(string $id): static
    {
        $article = $this->articleDetail->get($id);
        
        $this->body = [
            'article' => $article,
            'commentCount' => count($article->comments),
            'averageRating' => $this->calculateAverageRating($article->comments)
        ];
        
        return $this;
    }
    
    private function calculateAverageRating(array $comments): ?float
    {
        $ratings = array_filter(
            array_column($comments, 'rating'),
            fn($rating) => $rating !== null
        );
        
        return $ratings ? array_sum($ratings) / count($ratings) : null;
    }
}
```

### 2.4 Twigテンプレートでの表示

```twig
{# article_detail.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <title>{{ article.title }}</title>
</head>
<body>
    <article>
        <header>
            <h1>{{ article.title }}</h1>
            <p>By {{ article.authorName }} on {{ article.publishedAt|date('F j, Y') }}</p>
        </header>
        
        <div class="content">
            {{ article.content|markdown }}
        </div>
        
        <section class="comments">
            <h2>Comments ({{ commentCount }})</h2>
            
            {% if averageRating %}
                <p>Average Rating: {{ averageRating|round(1) }} / 5</p>
            {% endif %}
            
            {% for comment in article.comments %}
                <article class="comment" id="comment-{{ comment.id }}">
                    <header>
                        <strong>{{ comment.authorName }}</strong>
                        <time>{{ comment.createdAt|date('F j, Y g:i A') }}</time>
                        {% if comment.rating %}
                            <span class="rating">{{ '⭐'|repeat(comment.rating) }}</span>
                        {% endif %}
                    </header>
                    <div>{{ comment.content|nl2br }}</div>
                </article>
            {% endfor %}
            
            {# コメントフォームをインクルード #}
            {% include 'comment_form.html.twig' with {'articleId': article.id} %}
        </section>
    </article>
</body>
</html>
```

## 3. データフローまとめ

### 投稿時
1. **フォーム送信**: `author_name=John&author_email=john@example.com&content=Great!`
2. **Ray.InputQuery**: 
   - `getArguments()`でメソッドの引数リストを生成
   - フラットなデータ → `CommentInput { author: AuthorInput { ... } }`
3. **Ray.MediaQuery**: オブジェクト → 自動展開してSQL実行
4. **レスポンス**: 201 Created

### 表示時
1. **リクエスト**: GET /articles/123
2. **Ray.MediaQuery**: SQL実行（GROUP_CONCAT使用）
3. **Koriym.CsvEntities**: CSV形式のデータ → `Comment[]`配列
4. **Twig**: オブジェクトのプロパティに自然にアクセス

## 利点

1. **型安全性**: フォームからDBまで一貫した型チェック
2. **N+1問題の回避**: GROUP_CONCATで1クエリで取得
3. **シンプルなテンプレート**: オブジェクトとして自然にアクセス
4. **保守性**: SQLが明確で、データフローが追跡しやすい
5. **パフォーマンス**: 最小限のクエリで必要なデータを取得