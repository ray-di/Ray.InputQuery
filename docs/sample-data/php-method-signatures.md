# PHP Method Signatures Sample Data

## 1. コントローラーメソッドの例

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Ray\InputQuery\Attribute\Input;

class BlogController
{
    // 基本的なブログ記事作成
    public function createPost(
        #[Input] string $title,
        #[Input] string $content,
        #[Input] string $authorName,
        #[Input] string $authorEmail,
        #[Input] array $tags = [],
        #[Input] bool $published = false
    ): Response {}

    // ネストしたInputオブジェクトを使用
    public function createPostWithInput(
        #[Input] BlogPostInput $post,
        #[Input] AuthorInput $author,
        #[Input] PublishingOptionsInput $options
    ): Response {}

    // ページネーション付き検索
    public function searchPosts(
        #[Input] string $keyword,
        #[Input] SearchFiltersInput $filters,
        #[Input] PaginationInput $pagination
    ): Response {}

    // 記事更新（IDはパスパラメータ、その他はInput）
    public function updatePost(
        string $postId,  // パスパラメータ（#[Input]なし）
        #[Input] BlogPostInput $post,
        #[Input] UpdateOptionsInput $options
    ): Response {}
}

// 対応するInputクラス
final class BlogPostInput
{
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly string $content,
        #[Input] public readonly string $excerpt,
        #[Input] public readonly array $tags = []
    ) {}
}

final class AuthorInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly ?string $bio = null
    ) {}
}

final class PublishingOptionsInput
{
    public function __construct(
        #[Input] public readonly bool $published = false,
        #[Input] public readonly ?string $publishedAt = null,
        #[Input] public readonly bool $allowComments = true
    ) {}
}

final class SearchFiltersInput
{
    public function __construct(
        #[Input] public readonly ?string $category = null,
        #[Input] public readonly ?string $author = null,
        #[Input] public readonly ?string $dateFrom = null,
        #[Input] public readonly ?string $dateTo = null,
        #[Input] public readonly bool $publishedOnly = true
    ) {}
}

final class PaginationInput
{
    public function __construct(
        #[Input] public readonly int $page = 1,
        #[Input] public readonly int $perPage = 20,
        #[Input] public readonly string $sortBy = 'created_at',
        #[Input] public readonly string $sortOrder = 'desc'
    ) {}
}

final class UpdateOptionsInput
{
    public function __construct(
        #[Input] public readonly bool $notifySubscribers = false,
        #[Input] public readonly bool $regenerateSlug = false,
        #[Input] public readonly ?string $updateReason = null
    ) {}
}
```

## 2. Eコマースコントローラーの例

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Ray\InputQuery\Attribute\Input;

class OrderController
{
    // 注文作成（複雑なネスト構造）
    public function createOrder(
        #[Input] CustomerInput $customer,
        #[Input] ShippingAddressInput $shippingAddress,
        #[Input] BillingAddressInput $billingAddress,
        #[Input] PaymentMethodInput $paymentMethod,
        #[Input] OrderItemsInput $items,
        #[Input] OrderOptionsInput $options
    ): Response {}

    // 商品検索とフィルタリング
    public function searchProducts(
        #[Input] ProductSearchInput $search,
        #[Input] ProductFiltersInput $filters,
        #[Input] PaginationInput $pagination,
        #[Input] SortOptionsInput $sort
    ): Response {}

    // カート更新
    public function updateCart(
        string $cartId,
        #[Input] array $items,  // CartItemInput[]として扱われる
        #[Input] CouponInput $coupon,
        #[Input] ShippingOptionsInput $shipping
    ): Response {}

    // 複数配送先注文
    public function createMultiShippingOrder(
        #[Input] CustomerInput $customer,
        #[Input] array $shipments,  // ShipmentInput[]
        #[Input] PaymentMethodInput $payment,
        #[Input] OrderMetadataInput $metadata
    ): Response {}
}

// Eコマース用のInputクラス群
final class CustomerInput
{
    public function __construct(
        #[Input] public readonly string $firstName,
        #[Input] public readonly string $lastName,
        #[Input] public readonly string $email,
        #[Input] public readonly ?string $phone = null,
        #[Input] public readonly bool $createAccount = false
    ) {}
}

final class ShippingAddressInput
{
    public function __construct(
        #[Input] public readonly string $street,
        #[Input] public readonly string $city,
        #[Input] public readonly string $state,
        #[Input] public readonly string $postalCode,
        #[Input] public readonly string $country,
        #[Input] public readonly ?string $company = null,
        #[Input] public readonly ?string $deliveryNotes = null
    ) {}
}

final class BillingAddressInput
{
    public function __construct(
        #[Input] public readonly string $street,
        #[Input] public readonly string $city,
        #[Input] public readonly string $state,
        #[Input] public readonly string $postalCode,
        #[Input] public readonly string $country,
        #[Input] public readonly bool $sameAsShipping = false
    ) {}
}

final class PaymentMethodInput
{
    public function __construct(
        #[Input] public readonly string $type,  // 'credit_card', 'paypal', 'bank_transfer'
        #[Input] public readonly ?CreditCardInput $creditCard = null,
        #[Input] public readonly ?string $paypalEmail = null,
        #[Input] public readonly bool $saveForFuture = false
    ) {}
}

final class CreditCardInput
{
    public function __construct(
        #[Input] public readonly string $number,
        #[Input] public readonly string $holderName,
        #[Input] public readonly int $expiryMonth,
        #[Input] public readonly int $expiryYear,
        #[Input] public readonly string $cvv
    ) {}
}

final class OrderItemsInput
{
    public function __construct(
        #[Input] public readonly array $items,  // OrderItemInput[]
        #[Input] public readonly ?string $specialInstructions = null
    ) {}
}

final class OrderItemInput
{
    public function __construct(
        #[Input] public readonly string $productId,
        #[Input] public readonly int $quantity,
        #[Input] public readonly ?array $customizations = null,
        #[Input] public readonly ?string $personalMessage = null
    ) {}
}

final class OrderOptionsInput
{
    public function __construct(
        #[Input] public readonly bool $giftWrap = false,
        #[Input] public readonly ?string $giftMessage = null,
        #[Input] public readonly string $deliverySpeed = 'standard',
        #[Input] public readonly bool $requireSignature = false
    ) {}
}
```

## 3. ユーザー管理コントローラーの例

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Ray\InputQuery\Attribute\Input;

class UserController
{
    // ユーザー登録（包括的な情報）
    public function register(
        #[Input] PersonalInfoInput $personalInfo,
        #[Input] AccountCredentialsInput $credentials,
        #[Input] AddressInput $address,
        #[Input] UserPreferencesInput $preferences,
        #[Input] ConsentInput $consent
    ): Response {}

    // プロフィール更新（部分更新対応）
    public function updateProfile(
        string $userId,
        #[Input] ?PersonalInfoInput $personalInfo = null,
        #[Input] ?AddressInput $address = null,
        #[Input] ?UserPreferencesInput $preferences = null
    ): Response {}

    // パスワード変更
    public function changePassword(
        string $userId,
        #[Input] PasswordChangeInput $passwordChange
    ): Response {}

    // アカウント設定一括更新
    public function updateSettings(
        string $userId,
        #[Input] NotificationSettingsInput $notifications,
        #[Input] PrivacySettingsInput $privacy,
        #[Input] SecuritySettingsInput $security
    ): Response {}

    // ユーザー検索・フィルタリング（管理者用）
    public function searchUsers(
        #[Input] UserSearchCriteriaInput $criteria,
        #[Input] UserFiltersInput $filters,
        #[Input] PaginationInput $pagination
    ): Response {}
}

// ユーザー管理用のInputクラス群
final class PersonalInfoInput
{
    public function __construct(
        #[Input] public readonly string $firstName,
        #[Input] public readonly string $lastName,
        #[Input] public readonly string $email,
        #[Input] public readonly ?string $phone = null,
        #[Input] public readonly ?string $birthDate = null,
        #[Input] public readonly ?string $gender = null,
        #[Input] public readonly ?string $profilePicture = null
    ) {}
}

final class AccountCredentialsInput
{
    public function __construct(
        #[Input] public readonly string $username,
        #[Input] public readonly string $password,
        #[Input] public readonly string $passwordConfirm,
        #[Input] public readonly string $preferredLanguage = 'ja'
    ) {}
}

final class AddressInput
{
    public function __construct(
        #[Input] public readonly string $postalCode,
        #[Input] public readonly string $prefecture,
        #[Input] public readonly string $city,
        #[Input] public readonly string $streetAddress,
        #[Input] public readonly ?string $building = null
    ) {}
}

final class UserPreferencesInput
{
    public function __construct(
        #[Input] public readonly string $theme = 'auto',  // 'light', 'dark', 'auto'
        #[Input] public readonly string $timezone = 'Asia/Tokyo',
        #[Input] public readonly bool $emailNotifications = true,
        #[Input] public readonly bool $smsNotifications = false,
        #[Input] public readonly string $dateFormat = 'Y-m-d',
        #[Input] public readonly string $timeFormat = '24'  // '12' or '24'
    ) {}
}

final class ConsentInput
{
    public function __construct(
        #[Input] public readonly bool $termsOfService,
        #[Input] public readonly bool $privacyPolicy,
        #[Input] public readonly bool $marketingEmails = false,
        #[Input] public readonly bool $dataProcessing = false,
        #[Input] public readonly bool $cookieConsent = false
    ) {}
}

final class PasswordChangeInput
{
    public function __construct(
        #[Input] public readonly string $currentPassword,
        #[Input] public readonly string $newPassword,
        #[Input] public readonly string $newPasswordConfirm
    ) {
        if ($this->newPassword !== $this->newPasswordConfirm) {
            throw new \InvalidArgumentException('New passwords do not match');
        }
    }
}

final class NotificationSettingsInput
{
    public function __construct(
        #[Input] public readonly bool $emailNewsletter = false,
        #[Input] public readonly bool $emailPromotions = false,
        #[Input] public readonly bool $emailSecurityAlerts = true,
        #[Input] public readonly bool $smsOrderUpdates = false,
        #[Input] public readonly bool $pushNotifications = true,
        #[Input] public readonly string $frequency = 'daily'  // 'immediate', 'daily', 'weekly'
    ) {}
}

final class PrivacySettingsInput
{
    public function __construct(
        #[Input] public readonly string $profileVisibility = 'friends',  // 'public', 'friends', 'private'
        #[Input] public readonly bool $showOnlineStatus = true,
        #[Input] public readonly bool $allowDataCollection = false,
        #[Input] public readonly bool $allowThirdPartySharing = false,
        #[Input] public readonly bool $allowPersonalization = true
    ) {}
}

final class SecuritySettingsInput
{
    public function __construct(
        #[Input] public readonly bool $twoFactorAuth = false,
        #[Input] public readonly bool $loginNotifications = true,
        #[Input] public readonly bool $suspiciousActivityAlerts = true,
        #[Input] public readonly int $sessionTimeout = 3600,  // seconds
        #[Input] public readonly array $trustedDevices = []
    ) {}
}

final class UserSearchCriteriaInput
{
    public function __construct(
        #[Input] public readonly ?string $query = null,
        #[Input] public readonly ?string $email = null,
        #[Input] public readonly ?string $username = null,
        #[Input] public readonly ?string $name = null
    ) {}
}

final class UserFiltersInput
{
    public function __construct(
        #[Input] public readonly ?string $status = null,  // 'active', 'inactive', 'suspended'
        #[Input] public readonly ?string $role = null,
        #[Input] public readonly ?string $registeredAfter = null,
        #[Input] public readonly ?string $registeredBefore = null,
        #[Input] public readonly ?string $lastLoginAfter = null,
        #[Input] public readonly ?bool $emailVerified = null
    ) {}
}
```

## 4. APIリソースコントローラーの例

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Ray\InputQuery\Attribute\Input;

class ApiController
{
    // ファイルアップロード
    public function uploadFile(
        #[Input] FileUploadInput $file,
        #[Input] UploadOptionsInput $options
    ): Response {}

    // バッチ操作
    public function batchUpdate(
        #[Input] array $operations,  // BatchOperationInput[]
        #[Input] BatchOptionsInput $options
    ): Response {}

    // 複雑な検索・集計
    public function generateReport(
        #[Input] ReportCriteriaInput $criteria,
        #[Input] ReportFormatInput $format,
        #[Input] ReportOptionsInput $options
    ): Response {}

    // WebhookやCallback処理
    public function handleWebhook(
        string $provider,
        #[Input] WebhookPayloadInput $payload,
        #[Input] WebhookMetadataInput $metadata
    ): Response {}
}

// API用のInputクラス群
final class FileUploadInput
{
    public function __construct(
        #[Input] public readonly string $filename,
        #[Input] public readonly string $mimeType,
        #[Input] public readonly int $fileSize,
        #[Input] public readonly string $content,  // base64 encoded
        #[Input] public readonly ?string $description = null,
        #[Input] public readonly array $tags = []
    ) {}
}

final class UploadOptionsInput
{
    public function __construct(
        #[Input] public readonly string $visibility = 'private',  // 'public', 'private'
        #[Input] public readonly bool $autoResize = false,
        #[Input] public readonly ?int $maxWidth = null,
        #[Input] public readonly ?int $maxHeight = null,
        #[Input] public readonly int $quality = 85
    ) {}
}

final class BatchOperationInput
{
    public function __construct(
        #[Input] public readonly string $operation,  // 'create', 'update', 'delete'
        #[Input] public readonly string $resourceType,
        #[Input] public readonly ?string $resourceId = null,
        #[Input] public readonly array $data = []
    ) {}
}

final class BatchOptionsInput
{
    public function __construct(
        #[Input] public readonly bool $stopOnError = true,
        #[Input] public readonly bool $dryRun = false,
        #[Input] public readonly int $batchSize = 100,
        #[Input] public readonly bool $returnResults = false
    ) {}
}

final class ReportCriteriaInput
{
    public function __construct(
        #[Input] public readonly string $reportType,
        #[Input] public readonly string $dateFrom,
        #[Input] public readonly string $dateTo,
        #[Input] public readonly array $filters = [],
        #[Input] public readonly array $groupBy = [],
        #[Input] public readonly array $metrics = []
    ) {}
}

final class ReportFormatInput
{
    public function __construct(
        #[Input] public readonly string $format = 'json',  // 'json', 'csv', 'xlsx', 'pdf'
        #[Input] public readonly string $timezone = 'Asia/Tokyo',
        #[Input] public readonly bool $includeHeaders = true,
        #[Input] public readonly ?string $template = null
    ) {}
}

final class ReportOptionsInput
{
    public function __construct(
        #[Input] public readonly bool $emailResults = false,
        #[Input] public readonly ?string $emailAddress = null,
        #[Input] public readonly bool $cacheResults = true,
        #[Input] public readonly int $cacheDuration = 3600
    ) {}
}

final class WebhookPayloadInput
{
    public function __construct(
        #[Input] public readonly string $event,
        #[Input] public readonly array $data,
        #[Input] public readonly string $timestamp,
        #[Input] public readonly ?string $signature = null
    ) {}
}

final class WebhookMetadataInput
{
    public function __construct(
        #[Input] public readonly string $source,
        #[Input] public readonly string $version,
        #[Input] public readonly ?string $retryCount = null,
        #[Input] public readonly array $headers = []
    ) {}
}
```
