# OpenAPI (Swagger) Specification Sample Data

## 1. ブログ管理 API OpenAPI Specification

```yaml
openapi: 3.0.3
info:
  title: Blog Management API
  description: ブログ記事とコメントを管理するためのAPI
  version: 1.0.0
  contact:
    name: API Support
    email: support@blog-api.com
  license:
    name: MIT
    url: https://opensource.org/licenses/MIT

servers:
  - url: https://api.blog-system.com/v1
    description: Production server
  - url: https://staging-api.blog-system.com/v1
    description: Staging server

paths:
  /posts:
    post:
      summary: 新しいブログ記事を作成
      description: ブログ記事を作成し、著者情報やカテゴリを設定します
      operationId: createPost
      tags:
        - Posts
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/BlogPostInput'
            example:
              content:
                title: "Ray.InputQueryの���介"
                body: "型安全なInput処理を実現するライブラリです..."
                excerpt: "Ray.InputQueryは革新的なライブラリです"
                language: "ja"
              author:
                id: 1
                name: "山田太郎"
                email: "yamada@example.com"
                bio: "フルスタックエンジニア"
              categories:
                - id: 1
                  name: "技術"
                  slug: "technology"
              tags:
                - "PHP"
                - "Ray"
                - "Input処理"
              media:
                featuredImage:
                  url: "https://example.com/featured.jpg"
                  altText: "Ray.InputQueryのロゴ"
              seo:
                metaTitle: "Ray.InputQuery入門ガイド"
                metaDescription: "型安全なInput処理の新手法"
                slug: "ray-input-query-guide"
              publishing:
                status: "published"
                publishAt: "2024-01-15T10:00:00Z"
                allowComments: true
      responses:
        '201':
          description: 記事が正常に作成されました
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/BlogPost'
        '400':
          description: リクエストデータが不正です
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'
        '401':
          description: 認証が必要です
        '403':
          description: 権限がありません

    get:
      summary: ブログ記事一覧を取得
      description: フィルターやページネーションを指定して記事一覧を取得
      operationId: listPosts
      tags:
        - Posts
      parameters:
        - name: page
          in: query
          description: ページ番号
          schema:
            type: integer
            minimum: 1
            default: 1
        - name: limit
          in: query
          description: 1ページあたりの件数
          schema:
            type: integer
            minimum: 1
            maximum: 100
            default: 20
        - name: status
          in: query
          description: 記事のステータス
          schema:
            type: string
            enum: [draft, pending, published, archived]
        - name: author_id
          in: query
          description: 著者ID
          schema:
            type: integer
        - name: category
          in: query
          description: カテゴリスラッグ
          schema:
            type: string
        - name: tags
          in: query
          description: タグ（カンマ区切り）
          schema:
            type: string
        - name: search
          in: query
          description: 検索キーワード
          schema:
            type: string
        - name: sort_by
          in: query
          description: ソートフィールド
          schema:
            type: string
            enum: [created_at, published_at, title, view_count]
            default: created_at
        - name: sort_order
          in: query
          description: ソート順序
          schema:
            type: string
            enum: [asc, desc]
            default: desc
      responses:
        '200':
          description: 記事一覧が正常に取得されました
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/BlogPost'
                  pagination:
                    $ref: '#/components/schemas/PaginationMeta'

  /posts/{id}:
    put:
      summary: ブログ記事を更新
      description: 既存のブログ記事を更新します
      operationId: updatePost
      tags:
        - Posts
      parameters:
        - name: id
          in: path
          required: true
          description: 記事ID
          schema:
            type: integer
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/BlogPostInput'
      responses:
        '200':
          description: 記事が正常に更新されました
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/BlogPost'

components:
  schemas:
    BlogPostInput:
      type: object
      required:
        - content
        - author
        - categories
      properties:
        content:
          $ref: '#/components/schemas/PostContentInput'
        author:
          $ref: '#/components/schemas/AuthorInput'
        categories:
          type: array
          items:
            $ref: '#/components/schemas/CategoryInput'
          minItems: 1
        tags:
          type: array
          items:
            type: string
            maxLength: 50
          maxItems: 20
        media:
          $ref: '#/components/schemas/MediaInput'
        seo:
          $ref: '#/components/schemas/SEOInput'
        publishing:
          $ref: '#/components/schemas/PublishingInput'
        social:
          $ref: '#/components/schemas/SocialInput'

    PostContentInput:
      type: object
      required:
        - title
        - body
      properties:
        title:
          type: string
          minLength: 5
          maxLength: 200
          description: 記事タイトル
          example: "Ray.InputQueryの紹介"
        subtitle:
          type: string
          maxLength: 300
          description: サブタイトル
        body:
          type: string
          minLength: 100
          description: 記事本文（Markdown形式）
          example: "# はじめに\n\nRay.InputQueryは..."
        excerpt:
          type: string
          maxLength: 500
          description: 記事の抜粋
        language:
          type: string
          enum: [ja, en, zh, ko]
          default: ja
          description: 記事の言語

    AuthorInput:
      type: object
      required:
        - id
      properties:
        id:
          type: integer
          minimum: 1
          description: 著者ID
        name:
          type: string
          description: 著者名
        email:
          type: string
          format: email
          description: 著者メール
        bio:
          type: string
          maxLength: 1000
          description: 著者紹介
        avatar:
          type: string
          format: uri
          description: 著者アバター画像URL

    CategoryInput:
      type: object
      required:
        - id
        - name
        - slug
      properties:
        id:
          type: integer
          minimum: 1
        name:
          type: string
          description: カテゴリ名
        slug:
          type: string
          pattern: '^[a-z0-9-]+$'
          description: カテゴリスラッグ

    MediaInput:
      type: object
      properties:
        featuredImage:
          $ref: '#/components/schemas/ImageInput'
        gallery:
          type: array
          items:
            $ref: '#/components/schemas/ImageInput'
          maxItems: 10

    ImageInput:
      type: object
      required:
        - url
        - altText
      properties:
        url:
          type: string
          format: uri
          description: 画像URL
        altText:
          type: string
          maxLength: 200
          description: 代替テキスト
        caption:
          type: string
          maxLength: 500
          description: キャプション
        order:
          type: integer
          minimum: 0
          description: 表示順序

    SEOInput:
      type: object
      properties:
        metaTitle:
          type: string
          maxLength: 60
          description: SEOタイトル
        metaDescription:
          type: string
          maxLength: 160
          description: SEO説明文
        slug:
          type: string
          pattern: '^[a-z0-9-]+$'
          maxLength: 100
          description: URLスラッグ
        canonicalUrl:
          type: string
          format: uri
          description: 正規URL
        noindex:
          type: boolean
          default: false
          description: 検索エンジンにインデックスさせない

    PublishingInput:
      type: object
      properties:
        status:
          type: string
          enum: [draft, pending, published, scheduled, archived]
          default: draft
          description: 公開ステータス
        publishAt:
          type: string
          format: date-time
          description: 公開日時
        allowComments:
          type: boolean
          default: true
          description: コメント許可
        isFeatured:
          type: boolean
          default: false
          description: 注目記事フラグ
        isSticky:
          type: boolean
          default: false
          description: トップ固定

    SocialInput:
      type: object
      properties:
        shareTitle:
          type: string
          maxLength: 100
          description: SNSシェア用タイトル
        shareDescription:
          type: string
          maxLength: 200
          description: SNSシェア用説明
        shareImage:
          type: string
          format: uri
          description: SNSシェア用画像
        twitterCard:
          type: string
          enum: [summary, summary_large_image, app, player]
          default: summary_large_image
          description: Twitterカードタイプ

    BlogPost:
      type: object
      properties:
        id:
          type: integer
        title:
          type: string
        content:
          type: string
        status:
          type: string
        author:
          $ref: '#/components/schemas/Author'
        categories:
          type: array
          items:
            $ref: '#/components/schemas/Category'
        createdAt:
          type: string
          format: date-time
        updatedAt:
          type: string
          format: date-time

    PaginationMeta:
      type: object
      properties:
        currentPage:
          type: integer
        totalPages:
          type: integer
        totalItems:
          type: integer
        itemsPerPage:
          type: integer
        hasNext:
          type: boolean
        hasPrev:
          type: boolean

    ErrorResponse:
      type: object
      properties:
        error:
          type: object
          properties:
            code:
              type: string
            message:
              type: string
            details:
              type: array
              items:
                type: object
                properties:
                  field:
                    type: string
                  message:
                    type: string

  securitySchemes:
    BearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

security:
  - BearerAuth: []
```

## 2. Eコマース API OpenAPI Specification

```yaml
openapi: 3.0.3
info:
  title: E-commerce API
  description: Eコマース商品・注文管理API
  version: 2.0.0

paths:
  /products:
    post:
      summary: 新しい商品を作成
      operationId: createProduct
      tags:
        - Products
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ProductInput'
            example:
              basicInfo:
                name: "ワイヤレスイヤホン"
                description: "高音質Bluetooth5.0対応"
                sku: "WE-001"
                brand: "TechSound"
                model: "Elite Pro"
              pricing:
                basePrice: 12800
                salePrice: 9800
                currency: "JPY"
                taxRate: 0.1
              inventory:
                trackInventory: true
                stockQuantity: 50
                lowStockThreshold: 5
                allowBackorder: false
              categories:
                - id: 1
                  name: "オーディオ機器"
                  isPrimary: true
              attributes:
                weight: 0.05
                dimensions:
                  length: 5.2
                  width: 3.1
                  height: 2.8
                color: "black"
                size: "M"
                material: "プラスチック"
              images:
                - url: "https://example.com/images/we001.jpg"
                  altText: "ワイヤレスイヤホン ブラック"
                  isPrimary: true
                  sortOrder: 0

  /orders:
    post:
      summary: 新しい注文を作成
      operationId: createOrder
      tags:
        - Orders
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/OrderInput'
            example:
              customer:
                firstName: "太郎"
                lastName: "山田"
                email: "yamada@example.com"
                phone: "090-1234-5678"
              shippingAddress:
                street: "神宮前1-1-1"
                city: "渋谷区"
                state: "東京都"
                postalCode: "150-0001"
                country: "JP"
              billingAddress:
                street: "神宮前1-1-1"
                city: "渋谷区"
                state: "東京都"
                postalCode: "150-0001"
                country: "JP"
              paymentMethod:
                type: "credit_card"
                creditCard:
                  number: "1234567890123456"
                  holderName: "YAMADA TARO"
                  expiryMonth: 12
                  expiryYear: 2025
                  cvv: "123"
              items:
                - productId: "WE-001"
                  quantity: 2
                  customizations:
                    engraving: "T.YAMADA"
              options:
                giftWrap: true
                giftMessage: "お誕生日おめでとうございます"
                deliverySpeed: "express"

components:
  schemas:
    ProductInput:
      type: object
      required:
        - basicInfo
        - pricing
        - categories
      properties:
        basicInfo:
          $ref: '#/components/schemas/ProductBasicInfoInput'
        pricing:
          $ref: '#/components/schemas/ProductPricingInput'
        inventory:
          $ref: '#/components/schemas/ProductInventoryInput'
        categories:
          type: array
          items:
            $ref: '#/components/schemas/ProductCategoryInput'
          minItems: 1
        attributes:
          $ref: '#/components/schemas/ProductAttributesInput'
        images:
          type: array
          items:
            $ref: '#/components/schemas/ProductImageInput'
          minItems: 1
          maxItems: 10
        shipping:
          $ref: '#/components/schemas/ProductShippingInput'
        seo:
          $ref: '#/components/schemas/ProductSEOInput'
        status:
          type: string
          enum: [draft, pending, active, inactive, discontinued]
          default: draft

    ProductBasicInfoInput:
      type: object
      required:
        - name
        - description
        - sku
      properties:
        name:
          type: string
          minLength: 1
          maxLength: 200
          description: 商品名
        description:
          type: string
          minLength: 10
          maxLength: 5000
          description: 商品説明
        shortDescription:
          type: string
          maxLength: 500
          description: 短い説明
        sku:
          type: string
          pattern: '^[A-Z0-9-]+$'
          minLength: 5
          maxLength: 20
          description: 商品コード
        brand:
          type: string
          maxLength: 100
          description: ブランド名
        model:
          type: string
          maxLength: 100
          description: モデル名

    ProductPricingInput:
      type: object
      required:
        - basePrice
        - currency
      properties:
        basePrice:
          type: number
          minimum: 0
          multipleOf: 0.01
          description: 基本価格
        salePrice:
          type: number
          minimum: 0
          multipleOf: 0.01
          description: セール価格
        currency:
          type: string
          enum: [JPY, USD, EUR, GBP]
          default: JPY
          description: 通貨
        taxRate:
          type: number
          minimum: 0
          maximum: 1
          multipleOf: 0.001
          description: 税率

    OrderInput:
      type: object
      required:
        - customer
        - shippingAddress
        - paymentMethod
        - items
      properties:
        customer:
          $ref: '#/components/schemas/CustomerInput'
        shippingAddress:
          $ref: '#/components/schemas/AddressInput'
        billingAddress:
          $ref: '#/components/schemas/AddressInput'
        billingAddressSameAsShipping:
          type: boolean
          default: true
        paymentMethod:
          $ref: '#/components/schemas/PaymentMethodInput'
        items:
          type: array
          items:
            $ref: '#/components/schemas/OrderItemInput'
          minItems: 1
        options:
          $ref: '#/components/schemas/OrderOptionsInput'

    CustomerInput:
      type: object
      required:
        - firstName
        - lastName
        - email
      properties:
        firstName:
          type: string
          maxLength: 50
          description: 名前
        lastName:
          type: string
          maxLength: 50
          description: 姓
        email:
          type: string
          format: email
          description: メールアドレス
        phone:
          type: string
          pattern: '^[0-9-]+$'
          description: 電話番号
        createAccount:
          type: boolean
          default: false
          description: アカウント作成フラグ

    PaymentMethodInput:
      type: object
      required:
        - type
      properties:
        type:
          type: string
          enum: [credit_card, debit_card, bank_transfer, paypal, apple_pay]
          description: 支払い方法
        creditCard:
          $ref: '#/components/schemas/CreditCardInput'
        saveForFuture:
          type: boolean
          default: false
          description: 将来使用のために保存

    CreditCardInput:
      type: object
      required:
        - number
        - holderName
        - expiryMonth
        - expiryYear
        - cvv
      properties:
        number:
          type: string
          pattern: '^[0-9]{13,19}$'
          description: カード番号
        holderName:
          type: string
          maxLength: 100
          description: カード名義人
        expiryMonth:
          type: integer
          minimum: 1
          maximum: 12
          description: 有効期限月
        expiryYear:
          type: integer
          minimum: 2024
          maximum: 2040
          description: 有効期限年
        cvv:
          type: string
          pattern: '^[0-9]{3,4}$'
          description: セキュリティコード
```
