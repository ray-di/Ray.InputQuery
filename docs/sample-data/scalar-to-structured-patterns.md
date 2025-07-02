# スカラーメソッドシグネチャからの構造化サンプル

Ray.InputQueryの革新的な価値は、**普通のスカラー値を持つメソッドシグネチャから、構造化されたInputクラスを生成できる**ことにあります。

## 1. 基本的なスカラーパラメータからの構造化

### 元のメソッドシグネチャ（スカラー値のみ）

```php
class UserController
{
    // 普通のスカラーパラメータ
    public function register(
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $birthDate,
        string $gender,
        string $username,
        string $password,
        string $passwordConfirm,
        string $language
    ): Response {}
    
    // 住所情報も全てフラット
    public function updateProfile(
        string $userId,
        string $firstName,
        string $lastName,
        string $email,
        string $postalCode,
        string $prefecture,
        string $city,
        string $streetAddress,
        string $building
    ): Response {}
}
```

### AIが生成すべき構造化されたInputクラス

```php
// Phase 1: フラット構造の認識
final class UserRegistrationFlatInput
{
    public function __construct(
        #[Input] public readonly string $firstName,
        #[Input] public readonly string $lastName,
        #[Input] public readonly string $email,
        #[Input] public readonly string $phone,
        #[Input] public readonly string $birthDate,
        #[Input] public readonly string $gender,
        #[Input] public readonly string $username,
        #[Input] public readonly string $password,
        #[Input] public readonly string $passwordConfirm,
        #[Input] public readonly string $language
    ) {}
}

// Phase 2: 構造化の提案
final class UserRegistrationInput
{
    public function __construct(
        #[Input] public readonly PersonalInfoInput $personalInfo,
        #[Input] public readonly AccountInfoInput $account,
        #[Input] public readonly PasswordInput $password
    ) {}
}

final class PersonalInfoInput
{
    public function __construct(
        #[Input] public readonly string $firstName,
        #[Input] public readonly string $lastName,
        #[Input] public readonly string $email,
        #[Input] public readonly string $phone,
        #[Input] public readonly string $birthDate,
        #[Input] public readonly string $gender
    ) {}
}

final class AccountInfoInput
{
    public function __construct(
        #[Input] public readonly string $username,
        #[Input] public readonly string $language
    ) {}
}

final class PasswordInput
{
    public function __construct(
        #[Input] public readonly string $password,
        #[Input] public readonly string $passwordConfirm
    ) {
        if ($this->password !== $this->passwordConfirm) {
            throw new \InvalidArgumentException('Passwords do not match');
        }
    }
}
```

## 2. プレフィックスパターンからの自動グループ化

### 元のメソッドシグネチャ（プレフィックス付きスカラー）

```php
class OrderController
{
    // プレフィックスで論理的なグループを示唆
    public function checkout(
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        string $shippingStreet,
        string $shippingCity,
        string $shippingPostalCode,
        string $shippingCountry,
        string $billingStreet,
        string $billingCity,
        string $billingPostalCode,
        string $billingCountry,
        string $paymentCardNumber,
        string $paymentCardHolder,
        int $paymentExpiryMonth,
        int $paymentExpiryYear,
        string $paymentCvv,
        bool $giftWrap,
        string $specialInstructions
    ): Response {}
}
```

### AIが認識すべきパターンと生成すべきクラス

```php
/**
 * AIの分析プロセス:
 * 1. プレフィックス検出: customer*, shipping*, billing*, payment*
 * 2. パターン認識: 住所フィールド(street, city, postalCode, country)
 * 3. 支払い情報のグループ化
 * 4. 単体フィールドの識別
 */

// Phase 2: 構造化された設計
final class CheckoutInput
{
    public function __construct(
        #[Input] public readonly CustomerInput $customer,
        #[Input] public readonly ShippingAddressInput $shipping,
        #[Input] public readonly BillingAddressInput $billing,
        #[Input] public readonly PaymentMethodInput $payment,
        #[Input] public readonly OrderOptionsInput $options
    ) {}
}

final class CustomerInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly string $phone
    ) {}
}

final class ShippingAddressInput
{
    public function __construct(
        #[Input] public readonly string $street,
        #[Input] public readonly string $city,
        #[Input] public readonly string $postalCode,
        #[Input] public readonly string $country
    ) {}
}

final class BillingAddressInput
{
    public function __construct(
        #[Input] public readonly string $street,
        #[Input] public readonly string $city,
        #[Input] public readonly string $postalCode,
        #[Input] public readonly string $country
    ) {}
}

final class PaymentMethodInput
{
    public function __construct(
        #[Input] public readonly string $cardNumber,
        #[Input] public readonly string $cardHolder,
        #[Input] public readonly int $expiryMonth,
        #[Input] public readonly int $expiryYear,
        #[Input] public readonly string $cvv
    ) {}
}

final class OrderOptionsInput
{
    public function __construct(
        #[Input] public readonly bool $giftWrap,
        #[Input] public readonly string $specialInstructions
    ) {}
}
```

## 3. 意味的なグループ化パターン

### 元のメソッドシグネチャ（意味的関連性）

```php
class ArticleController
{
    // 記事情報、著者情報、公開設定が混在
    public function create(
        string $title,
        string $content,
        string $excerpt,
        string $authorName,
        string $authorEmail,
        string $authorBio,
        array $tags,
        array $categoryIds,
        bool $published,
        string $publishedAt,
        bool $allowComments,
        string $metaTitle,
        string $metaDescription,
        string $slug
    ): Response {}
}
```

### AIが生成すべき意味的構造

```php
/**
 * AIの意味解析:
 * - title, content, excerpt → 記事コンテンツ
 * - author* → 著者情報
 * - tags, categoryIds → 分類情報
 * - published, publishedAt, allowComments → 公開設定
 * - meta*, slug → SEO情報
 */

final class ArticleCreateInput
{
    public function __construct(
        #[Input] public readonly ArticleContentInput $content,
        #[Input] public readonly AuthorInput $author,
        #[Input] public readonly CategoryTagInput $classification,
        #[Input] public readonly PublishingOptionsInput $publishing,
        #[Input] public readonly SEOMetaInput $seo
    ) {}
}

final class ArticleContentInput
{
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly string $content,
        #[Input] public readonly string $excerpt
    ) {}
}

final class AuthorInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly string $bio
    ) {}
}

final class CategoryTagInput
{
    public function __construct(
        #[Input] public readonly array $tags,
        #[Input] public readonly array $categoryIds
    ) {}
}

final class PublishingOptionsInput
{
    public function __construct(
        #[Input] public readonly bool $published,
        #[Input] public readonly string $publishedAt,
        #[Input] public readonly bool $allowComments
    ) {}
}

final class SEOMetaInput
{
    public function __construct(
        #[Input] public readonly string $metaTitle,
        #[Input] public readonly string $metaDescription,
        #[Input] public readonly string $slug
    ) {}
}
```

## 4. 検索・フィルタリングパターン

### 元のメソッドシグネチャ（検索条件が混在）

```php
class ProductController
{
    // 検索条件とページネーションが平坦
    public function search(
        string $keyword,
        string $category,
        int $minPrice,
        int $maxPrice,
        string $brand,
        string $color,
        string $size,
        bool $inStock,
        string $sortBy,
        string $sortOrder,
        int $page,
        int $limit
    ): Response {}
}
```

### AIが認識すべき検索パターン

```php
/**
 * 検索システムの典型的な構造:
 * - keyword → 基本検索
 * - category, brand, color, size, inStock → フィルター条件
 * - minPrice, maxPrice → 範囲フィルター
 * - sortBy, sortOrder → ソート設定
 * - page, limit → ページネーション
 */

final class ProductSearchInput
{
    public function __construct(
        #[Input] public readonly SearchCriteriaInput $criteria,
        #[Input] public readonly ProductFiltersInput $filters,
        #[Input] public readonly PriceRangeInput $priceRange,
        #[Input] public readonly SortOptionsInput $sort,
        #[Input] public readonly PaginationInput $pagination
    ) {}
}

final class SearchCriteriaInput
{
    public function __construct(
        #[Input] public readonly string $keyword
    ) {}
}

final class ProductFiltersInput
{
    public function __construct(
        #[Input] public readonly string $category,
        #[Input] public readonly string $brand,
        #[Input] public readonly string $color,
        #[Input] public readonly string $size,
        #[Input] public readonly bool $inStock
    ) {}
}

final class PriceRangeInput
{
    public function __construct(
        #[Input] public readonly int $minPrice,
        #[Input] public readonly int $maxPrice
    ) {}
}

final class SortOptionsInput
{
    public function __construct(
        #[Input] public readonly string $sortBy,
        #[Input] public readonly string $sortOrder
    ) {}
}

final class PaginationInput
{
    public function __construct(
        #[Input] public readonly int $page,
        #[Input] public readonly int $limit
    ) {}
}
```

## 5. 複雑なビジネスロジックパターン

### 元のメソッドシグネチャ（ビジネスルールが複雑）

```php
class ReservationController
{
    // 予約システム - 複雑な関連性
    public function makeReservation(
        string $guestFirstName,
        string $guestLastName,
        string $guestEmail,
        string $guestPhone,
        string $checkInDate,
        string $checkOutDate,
        int $adults,
        int $children,
        string $roomType,
        int $roomCount,
        array $specialRequests,
        bool $needsPickup,
        string $pickupLocation,
        string $pickupTime,
        string $paymentMethod,
        string $cardNumber,
        string $cardHolder,
        int $expiryMonth,
        int $expiryYear,
        string $cvv,
        bool $newsletter,
        string $promoCode
    ): Response {}
}
```

### AIが生成すべき複雑な構造

```php
/**
 * ホテル予約システムの構造分析:
 * - guest* → ゲスト情報
 * - checkIn/Out, adults, children → 予約詳細
 * - room* → 部屋選択
 * - pickup* → 送迎サービス（条件付き）
 * - payment*, card* → 支払い情報
 * - newsletter, promoCode → 追加オプション
 */

final class HotelReservationInput
{
    public function __construct(
        #[Input] public readonly GuestInformationInput $guest,
        #[Input] public readonly ReservationDetailsInput $reservation,
        #[Input] public readonly RoomSelectionInput $rooms,
        #[Input] public readonly PaymentInformationInput $payment,
        #[Input] public readonly ?PickupServiceInput $pickup = null,
        #[Input] public readonly ReservationOptionsInput $options
    ) {}
}

final class GuestInformationInput
{
    public function __construct(
        #[Input] public readonly string $firstName,
        #[Input] public readonly string $lastName,
        #[Input] public readonly string $email,
        #[Input] public readonly string $phone
    ) {}
}

final class ReservationDetailsInput
{
    public function __construct(
        #[Input] public readonly string $checkInDate,
        #[Input] public readonly string $checkOutDate,
        #[Input] public readonly int $adults,
        #[Input] public readonly int $children,
        #[Input] public readonly array $specialRequests
    ) {}
}

final class RoomSelectionInput
{
    public function __construct(
        #[Input] public readonly string $roomType,
        #[Input] public readonly int $roomCount
    ) {}
}

final class PickupServiceInput
{
    public function __construct(
        #[Input] public readonly bool $needsPickup,
        #[Input] public readonly string $pickupLocation,
        #[Input] public readonly string $pickupTime
    ) {}
}

final class PaymentInformationInput
{
    public function __construct(
        #[Input] public readonly string $paymentMethod,
        #[Input] public readonly CreditCardInput $creditCard
    ) {}
}

final class CreditCardInput
{
    public function __construct(
        #[Input] public readonly string $cardNumber,
        #[Input] public readonly string $cardHolder,
        #[Input] public readonly int $expiryMonth,
        #[Input] public readonly int $expiryYear,
        #[Input] public readonly string $cvv
    ) {}
}

final class ReservationOptionsInput
{
    public function __construct(
        #[Input] public readonly bool $newsletter,
        #[Input] public readonly ?string $promoCode = null
    ) {}
}
```

## 6. AIプロンプト用のパターン認識ルール

### 自動構造化のためのパターン

```
パターン認識ルール:

1. **プレフィックスベースのグループ化**
   - customer*, user*, author* → 人物情報
   - shipping*, billing*, delivery* → 住所情報
   - payment*, card*, bank* → 支払い情報

2. **意味的な関連性**
   - title, content, excerpt → コンテンツ情報
   - meta*, seo*, slug → SEO情報
   - published*, visible*, status → 公開設定

3. **一般的なパターン**
   - *Date, *Time, *At → 日時情報
   - min*, max* → 範囲指定
   - page, limit, offset → ページネーション
   - sortBy, sortOrder, orderBy → ソート設定

4. **住所パターン**
   - street, city, state, postal*, zip*, country → 住所
   - prefecture, municipality → 日本の住所

5. **認証・セキュリティパターン**
   - password, passwordConfirm → パスワード設定
   - username, email, phone → 認証情報

6. **ビジネスドメインパターン**
   - pickup*, delivery* → 配送・送迎
   - promo*, coupon*, discount* → 割引情報
   - newsletter, notification*, marketing* → 通知設定
```

## 7. Ray.InputQueryの価値の本質

```php
// 【BEFORE】普通のPHPメソッド（構造が見えない）
public function processOrder(
    string $customerName,
    string $customerEmail,
    string $shippingStreet,
    string $shippingCity,
    string $paymentCardNumber,
    string $paymentCvv,
    bool $giftWrap
) {}

// 【AFTER】Ray.InputQuery + AI生成（構造が明確）
public function processOrder(OrderInput $order) {}

final class OrderInput
{
    public function __construct(
        #[Input] public readonly CustomerInput $customer,
        #[Input] public readonly ShippingAddressInput $shipping,
        #[Input] public readonly PaymentMethodInput $payment,
        #[Input] public readonly OrderOptionsInput $options
    ) {}
}
```

**革新的価値:**
1. **構造の発見** - フラットなパラメータから論理構造を発見
2. **意図の明確化** - ビジネス概念をコードで表現
3. **再利用性** - 共通パターン（住所、支払い等）の抽出
4. **型安全性** - スカラー値を構造化されたオブジェクトに変換

これにより、**普通のPHPメソッドから構造化された設計への自動変換**が可能になります。
