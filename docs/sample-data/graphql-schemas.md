# GraphQL Schema Sample Data

## 1. ブログシステム GraphQL Schema

```graphql
# ブログ記事の入力型
input BlogPostInput {
  title: String!
  content: String!
  excerpt: String
  slug: String
  status: PostStatus = DRAFT
  publishedAt: DateTime
  allowComments: Boolean = true
  isFeatured: Boolean = false
  author: AuthorInput!
  categories: [CategoryInput!]!
  tags: [String!]
  featuredImage: ImageInput
  seoMeta: SEOMetaInput
}

# 著者情報入力型
input AuthorInput {
  id: ID
  name: String!
  email: String!
  bio: String
  avatar: String # URL
  socialMedia: SocialMediaInput
}

# ソーシャルメディア入力型
input SocialMediaInput {
  twitter: String # @handle
  linkedin: String # URL
  github: String # username
  website: String # URL
}

# カテゴリ入力型
input CategoryInput {
  id: ID
  name: String!
  slug: String!
  description: String
  parentId: ID
  isVisible: Boolean = true
}

# 画像入力型
input ImageInput {
  url: String!
  altText: String!
  caption: String
  width: Int
  height: Int
  fileSize: Int
}

# SEOメタデータ入力型
input SEOMetaInput {
  title: String
  description: String
  keywords: [String!]
  canonicalUrl: String
  ogTitle: String
  ogDescription: String
  ogImage: String
  twitterCard: TwitterCardType = SUMMARY_LARGE_IMAGE
}

# 記事検索フィルター
input PostSearchInput {
  query: String
  authorId: ID
  categoryIds: [ID!]
  tags: [String!]
  status: PostStatus
  publishedAfter: DateTime
  publishedBefore: DateTime
  isFeatured: Boolean
}

# ページネーション入力型
input PaginationInput {
  page: Int = 1
  limit: Int = 20
  sortBy: String = "createdAt"
  sortOrder: SortOrder = DESC
}

# 列挙型定義
enum PostStatus {
  DRAFT
  PENDING
  PUBLISHED
  ARCHIVED
}

enum TwitterCardType {
  SUMMARY
  SUMMARY_LARGE_IMAGE
  APP
  PLAYER
}

enum SortOrder {
  ASC
  DESC
}

# ミューテーション
type Mutation {
  createPost(input: BlogPostInput!): Post!
  updatePost(id: ID!, input: BlogPostInput!): Post!
  deletePost(id: ID!): Boolean!
  publishPost(id: ID!, publishedAt: DateTime): Post!
}

# クエリ
type Query {
  posts(
    search: PostSearchInput
    pagination: PaginationInput
  ): PostConnection!
  
  post(id: ID, slug: String): Post
  
  categories(
    parentId: ID
    isVisible: Boolean
  ): [Category!]!
}
```

## 2. Eコマース GraphQL Schema

```graphql
# 商品入力型
input ProductInput {
  basicInfo: ProductBasicInfoInput!
  pricing: ProductPricingInput!
  inventory: ProductInventoryInput!
  categories: [ProductCategoryInput!]!
  attributes: ProductAttributesInput
  images: [ProductImageInput!]!
  shipping: ProductShippingInput
  seo: ProductSEOInput
  status: ProductStatus = DRAFT
  publishDate: DateTime
}

# 商品基本情報
input ProductBasicInfoInput {
  name: String!
  description: String!
  shortDescription: String
  sku: String!
  brand: String
  model: String
  barcode: String
  weight: Float
  dimensions: DimensionsInput
}

# 寸法入力型
input DimensionsInput {
  length: Float!
  width: Float!
  height: Float!
  unit: DimensionUnit = CM
}

# 価格情報入力型
input ProductPricingInput {
  basePrice: Float!
  salePrice: Float
  currency: Currency = JPY
  taxRate: Float
  costPrice: Float
  msrp: Float # 希望小売価格
  discountRules: [DiscountRuleInput!]
}

# 割引ルール入力型
input DiscountRuleInput {
  type: DiscountType!
  value: Float!
  minQuantity: Int = 1
  maxQuantity: Int
  startDate: DateTime
  endDate: DateTime
  isActive: Boolean = true
}

# 在庫情報入力型
input ProductInventoryInput {
  trackInventory: Boolean = true
  stockQuantity: Int
  lowStockThreshold: Int = 5
  allowBackorder: Boolean = false
  maxOrderQuantity: Int
  restockDate: Date
  stockStatus: StockStatus = IN_STOCK
}

# 商品カテゴリ入力型
input ProductCategoryInput {
  id: ID!
  name: String!
  isPrimary: Boolean = false
  sortOrder: Int = 0
}

# 商品属性入力型
input ProductAttributesInput {
  color: String
  size: ProductSize
  material: String
  countryOfOrigin: String
  warranty: String
  customAttributes: [CustomAttributeInput!]
}

# カスタム属性入力型
input CustomAttributeInput {
  name: String!
  value: String!
  type: AttributeType = TEXT
  isVisible: Boolean = true
}

# 商品画像入力型
input ProductImageInput {
  url: String!
  altText: String!
  caption: String
  isPrimary: Boolean = false
  sortOrder: Int = 0
  tags: [String!]
}

# 配送情報入力型
input ProductShippingInput {
  requiresShipping: Boolean = true
  shippingClass: ShippingClass = STANDARD
  handlingTime: Int = 1 # 日数
  freeShippingThreshold: Float
  shippingCost: Float
  internationalShipping: Boolean = false
}

# 商品SEO入力型
input ProductSEOInput {
  metaTitle: String
  metaDescription: String
  slug: String
  keywords: [String!]
  structuredData: JSON
}

# 注文入力型
input OrderInput {
  customer: CustomerInput!
  shippingAddress: AddressInput!
  billingAddress: AddressInput
  billingAddressSameAsShipping: Boolean = true
  paymentMethod: PaymentMethodInput!
  items: [OrderItemInput!]!
  shipping: ShippingOptionsInput!
  coupons: [CouponInput!]
  giftOptions: GiftOptionsInput
  specialInstructions: String
}

# 顧客情報入力型
input CustomerInput {
  id: ID
  firstName: String!
  lastName: String!
  email: String!
  phone: String
  dateOfBirth: Date
  isGuest: Boolean = false
  marketing: MarketingPreferencesInput
}

# 住所入力型
input AddressInput {
  street: String!
  street2: String
  city: String!
  state: String!
  postalCode: String!
  country: String!
  isDefault: Boolean = false
  type: AddressType = SHIPPING
}

# 支払い方法入力型
input PaymentMethodInput {
  type: PaymentType!
  creditCard: CreditCardInput
  bankTransfer: BankTransferInput
  digitalWallet: DigitalWalletInput
  installments: InstallmentInput
}

# クレジットカード入力型
input CreditCardInput {
  number: String!
  holderName: String!
  expiryMonth: Int!
  expiryYear: Int!
  cvv: String!
  saveForFuture: Boolean = false
}

# 注文アイテム入力型
input OrderItemInput {
  productId: ID!
  variantId: ID
  quantity: Int!
  customization: ProductCustomizationInput
  giftMessage: String
}

# 商品カスタマイゼーション入力型
input ProductCustomizationInput {
  engraving: String
  giftWrap: Boolean = false
  giftWrapStyle: String
  personalMessage: String
}

# 配送オプション入力型
input ShippingOptionsInput {
  method: ShippingMethod!
  speed: ShippingSpeed = STANDARD
  deliveryDate: Date
  timeSlot: DeliveryTimeSlot
  signature: Boolean = false
  insurance: Boolean = false
}

# ギフトオプション入力型
input GiftOptionsInput {
  isGift: Boolean = false
  giftWrap: Boolean = false
  giftMessage: String
  giftReceipt: Boolean = false
  senderName: String
  recipientName: String
}

# 列挙型定義
enum ProductStatus {
  DRAFT
  PENDING
  ACTIVE
  INACTIVE
  DISCONTINUED
}

enum ProductSize {
  XS
  S
  M
  L
  XL
  XXL
  XXXL
  ONE_SIZE
}

enum DiscountType {
  PERCENTAGE
  FIXED_AMOUNT
  BUY_X_GET_Y
  FREE_SHIPPING
}

enum StockStatus {
  IN_STOCK
  LOW_STOCK
  OUT_OF_STOCK
  BACKORDER
  DISCONTINUED
}

enum ShippingClass {
  STANDARD
  HEAVY
  FRAGILE
  HAZARDOUS
  OVERSIZED
}

enum PaymentType {
  CREDIT_CARD
  DEBIT_CARD
  BANK_TRANSFER
  PAYPAL
  APPLE_PAY
  GOOGLE_PAY
  CRYPTOCURRENCY
}

enum ShippingMethod {
  STANDARD
  EXPRESS
  OVERNIGHT
  SAME_DAY
  PICKUP
}

enum ShippingSpeed {
  STANDARD
  EXPRESS
  OVERNIGHT
  SAME_DAY
}

# ミューテーション
type Mutation {
  createProduct(input: ProductInput!): Product!
  updateProduct(id: ID!, input: ProductInput!): Product!
  deleteProduct(id: ID!): Boolean!
  
  createOrder(input: OrderInput!): Order!
  updateOrderStatus(id: ID!, status: OrderStatus!): Order!
  cancelOrder(id: ID!, reason: String): Order!
  
  addToCart(productId: ID!, quantity: Int!, variantId: ID): Cart!
  updateCartItem(itemId: ID!, quantity: Int!): Cart!
  removeFromCart(itemId: ID!): Cart!
}
```

## 3. ユーザー管理 GraphQL Schema

```graphql
# ユーザー登録入力型
input UserRegistrationInput {
  personalInfo: PersonalInfoInput!
  credentials: UserCredentialsInput!
  address: AddressInput
  preferences: UserPreferencesInput
  consent: ConsentInput!
  invitationCode: String
}

# 個人情報入力型
input PersonalInfoInput {
  firstName: String!
  lastName: String!
  email: String!
  phone: String
  dateOfBirth: Date
  gender: Gender
  nationality: String
  occupation: String
  profilePicture: ImageUploadInput
}

# 認証情報入力型
input UserCredentialsInput {
  username: String!
  password: String!
  passwordConfirm: String!
  preferredLanguage: Language = JA
  timezone: String = "Asia/Tokyo"
}

# ユーザー設定入力型
input UserPreferencesInput {
  theme: Theme = AUTO
  language: Language = JA
  dateFormat: String = "YYYY-MM-DD"
  timeFormat: TimeFormat = TWENTY_FOUR
  currency: Currency = JPY
  notifications: NotificationPreferencesInput!
  privacy: PrivacyPreferencesInput!
  accessibility: AccessibilityPreferencesInput
}

# 通知設定入力型
input NotificationPreferencesInput {
  email: EmailNotificationInput!
  sms: SMSNotificationInput!
  push: PushNotificationInput!
  inApp: InAppNotificationInput!
  frequency: NotificationFrequency = REAL_TIME
}

# メール通知設定
input EmailNotificationInput {
  newsletter: Boolean = false
  promotions: Boolean = false
  orderUpdates: Boolean = true
  securityAlerts: Boolean = true
  socialActivity: Boolean = false
  weeklyDigest: Boolean = false
}

# SMS通知設定
input SMSNotificationInput {
  orderUpdates: Boolean = false
  securityAlerts: Boolean = true
  deliveryUpdates: Boolean = false
  promotions: Boolean = false
}

# プッシュ通知設定
input PushNotificationInput {
  enabled: Boolean = true
  orderUpdates: Boolean = true
  socialActivity: Boolean = false
  news: Boolean = false
  promotions: Boolean = false
}

# アプリ内通知設定
input InAppNotificationInput {
  messages: Boolean = true
  mentions: Boolean = true
  likes: Boolean = false
  follows: Boolean = true
}

# プライバシー設定入力型
input PrivacyPreferencesInput {
  profileVisibility: ProfileVisibility = FRIENDS
  showOnlineStatus: Boolean = true
  showLastSeen: Boolean = false
  allowDataCollection: Boolean = false
  allowPersonalization: Boolean = true
  allowThirdPartySharing: Boolean = false
  allowLocationTracking: Boolean = false
  searchable: Boolean = true
}

# アクセシビリティ設定入力型
input AccessibilityPreferencesInput {
  highContrast: Boolean = false
  largeText: Boolean = false
  reduceMotion: Boolean = false
  screenReader: Boolean = false
  keyboardNavigation: Boolean = false
  voiceInput: Boolean = false
}

# 同意情報入力型
input ConsentInput {
  termsOfService: Boolean!
  privacyPolicy: Boolean!
  cookiePolicy: Boolean = false
  marketingEmails: Boolean = false
  dataProcessing: Boolean = false
  locationTracking: Boolean = false
  analyticsTracking: Boolean = false
}

# 画像アップロード入力型
input ImageUploadInput {
  filename: String!
  mimeType: String!
  content: String! # Base64 encoded
  width: Int
  height: Int
  fileSize: Int
}

# ユーザー更新入力型
input UserUpdateInput {
  personalInfo: PersonalInfoInput
  preferences: UserPreferencesInput
  address: AddressInput
}

# パスワード変更入力型
input PasswordChangeInput {
  currentPassword: String!
  newPassword: String!
  newPasswordConfirm: String!
  logoutOtherSessions: Boolean = false
}

# 二段階認証設定入力型
input TwoFactorAuthSetupInput {
  method: TwoFactorMethod!
  phoneNumber: String
  backupCodes: [String!]
  authenticatorSecret: String
}

# ユーザー検索入力型
input UserSearchInput {
  query: String
  filters: UserSearchFiltersInput
  pagination: PaginationInput
  sorting: UserSortingInput
}

# ユーザー検索フィルター
input UserSearchFiltersInput {
  status: UserStatus
  role: UserRole
  registeredAfter: DateTime
  registeredBefore: DateTime
  lastLoginAfter: DateTime
  lastLoginBefore: DateTime
  emailVerified: Boolean
  phoneVerified: Boolean
  hasProfile: Boolean
  location: LocationFilterInput
}

# 位置フィルター
input LocationFilterInput {
  country: String
  city: String
  radius: Int # km
  coordinates: CoordinatesInput
}

# 座標入力型
input CoordinatesInput {
  latitude: Float!
  longitude: Float!
}

# ユーザーソート入力型
input UserSortingInput {
  field: UserSortField = CREATED_AT
  order: SortOrder = DESC
}

# 列挙型定義
enum Gender {
  MALE
  FEMALE
  OTHER
  PREFER_NOT_TO_SAY
}

enum Language {
  JA
  EN
  ZH
  KO
  ES
  FR
  DE
}

enum Theme {
  LIGHT
  DARK
  AUTO
}

enum TimeFormat {
  TWELVE
  TWENTY_FOUR
}

enum Currency {
  JPY
  USD
  EUR
  GBP
  CNY
  KRW
}

enum NotificationFrequency {
  REAL_TIME
  HOURLY
  DAILY
  WEEKLY
  NEVER
}

enum ProfileVisibility {
  PUBLIC
  FRIENDS
  PRIVATE
}

enum TwoFactorMethod {
  SMS
  EMAIL
  AUTHENTICATOR_APP
  HARDWARE_KEY
}

enum UserStatus {
  ACTIVE
  INACTIVE
  SUSPENDED
  BANNED
  PENDING_VERIFICATION
}

enum UserRole {
  USER
  PREMIUM
  MODERATOR
  ADMIN
  SUPER_ADMIN
}

enum UserSortField {
  CREATED_AT
  UPDATED_AT
  LAST_LOGIN
  USERNAME
  EMAIL
  FIRST_NAME
  LAST_NAME
}

# ミューテーション
type Mutation {
  registerUser(input: UserRegistrationInput!): AuthPayload!
  updateUser(id: ID!, input: UserUpdateInput!): User!
  deleteUser(id: ID!, reason: String): Boolean!
  
  changePassword(input: PasswordChangeInput!): Boolean!
  setupTwoFactorAuth(input: TwoFactorAuthSetupInput!): TwoFactorAuthSetupPayload!
  
  verifyEmail(token: String!): Boolean!
  resendEmailVerification: Boolean!
  
  updatePreferences(input: UserPreferencesInput!): User!
  updatePrivacySettings(input: PrivacyPreferencesInput!): User!
}

# クエリ
type Query {
  me: User
  user(id: ID, username: String): User
  users(search: UserSearchInput): UserConnection!
  
  userPreferences: UserPreferences!
  userSessions: [UserSession!]!
  userActivityLog(limit: Int = 50): [UserActivity!]!
}

# スカラー型定義
scalar Date
scalar DateTime
scalar JSON
```
