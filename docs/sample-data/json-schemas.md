# JSON Schema Sample Data

## 1. ユーザープロフィール管理API

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "User Profile Update",
  "type": "object",
  "properties": {
    "personalInfo": {
      "type": "object",
      "properties": {
        "firstName": {
          "type": "string",
          "minLength": 1,
          "maxLength": 50,
          "description": "ユーザーの名前"
        },
        "lastName": {
          "type": "string",
          "minLength": 1,
          "maxLength": 50,
          "description": "ユーザーの姓"
        },
        "email": {
          "type": "string",
          "format": "email",
          "description": "メールアドレス"
        },
        "phoneNumber": {
          "type": "string",
          "pattern": "^[0-9-]+$",
          "description": "電話番号"
        },
        "birthDate": {
          "type": "string",
          "format": "date",
          "description": "生年月日"
        },
        "gender": {
          "type": "string",
          "enum": ["male", "female", "other", "prefer_not_to_say"],
          "description": "性別"
        }
      },
      "required": ["firstName", "lastName", "email"],
      "additionalProperties": false
    },
    "address": {
      "type": "object",
      "properties": {
        "postalCode": {
          "type": "string",
          "pattern": "^[0-9]{3}-[0-9]{4}$",
          "description": "郵便番号"
        },
        "prefecture": {
          "type": "string",
          "enum": [
            "hokkaido", "aomori", "iwate", "miyagi", "akita", "yamagata", "fukushima",
            "ibaraki", "tochigi", "gunma", "saitama", "chiba", "tokyo", "kanagawa",
            "niigata", "toyama", "ishikawa", "fukui", "yamanashi", "nagano", "gifu",
            "shizuoka", "aichi", "mie", "shiga", "kyoto", "osaka", "hyogo", "nara",
            "wakayama", "tottori", "shimane", "okayama", "hiroshima", "yamaguchi",
            "tokushima", "kagawa", "ehime", "kochi", "fukuoka", "saga", "nagasaki",
            "kumamoto", "oita", "miyazaki", "kagoshima", "okinawa"
          ],
          "description": "都道府県"
        },
        "city": {
          "type": "string",
          "minLength": 1,
          "maxLength": 100,
          "description": "市区町村"
        },
        "streetAddress": {
          "type": "string",
          "minLength": 1,
          "maxLength": 200,
          "description": "番地・町名"
        },
        "building": {
          "type": "string",
          "maxLength": 100,
          "description": "建物名・部屋番号"
        }
      },
      "required": ["postalCode", "prefecture", "city", "streetAddress"],
      "additionalProperties": false
    },
    "preferences": {
      "type": "object",
      "properties": {
        "language": {
          "type": "string",
          "enum": ["ja", "en", "zh", "ko"],
          "default": "ja",
          "description": "表示言語"
        },
        "timezone": {
          "type": "string",
          "default": "Asia/Tokyo",
          "description": "タイムゾーン"
        },
        "theme": {
          "type": "string",
          "enum": ["light", "dark", "auto"],
          "default": "auto",
          "description": "テーマ設定"
        },
        "emailNotifications": {
          "type": "object",
          "properties": {
            "newsletter": {
              "type": "boolean",
              "default": false,
              "description": "ニュースレター"
            },
            "promotions": {
              "type": "boolean",
              "default": false,
              "description": "プロモーション情報"
            },
            "securityAlerts": {
              "type": "boolean",
              "default": true,
              "description": "セキュリティアラート"
            },
            "orderUpdates": {
              "type": "boolean",
              "default": true,
              "description": "注文更新通知"
            }
          },
          "additionalProperties": false
        },
        "privacySettings": {
          "type": "object",
          "properties": {
            "profileVisibility": {
              "type": "string",
              "enum": ["public", "friends", "private"],
              "default": "friends",
              "description": "プロフィール公開設定"
            },
            "allowDataCollection": {
              "type": "boolean",
              "default": false,
              "description": "データ収集許可"
            },
            "allowThirdPartySharing": {
              "type": "boolean",
              "default": false,
              "description": "第三者データ共有許可"
            }
          },
          "additionalProperties": false
        }
      },
      "additionalProperties": false
    },
    "socialMedia": {
      "type": "object",
      "properties": {
        "twitter": {
          "type": "string",
          "pattern": "^@[A-Za-z0-9_]+$",
          "description": "Twitterハンドル"
        },
        "linkedin": {
          "type": "string",
          "format": "uri",
          "description": "LinkedIn URL"
        },
        "github": {
          "type": "string",
          "pattern": "^[A-Za-z0-9_-]+$",
          "description": "GitHubユーザー名"
        },
        "website": {
          "type": "string",
          "format": "uri",
          "description": "個人ウェブサイト"
        }
      },
      "additionalProperties": false
    }
  },
  "required": ["personalInfo"],
  "additionalProperties": false
}
```

## 2. Eコマース商品投稿API

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "Product Creation",
  "type": "object",
  "properties": {
    "basicInfo": {
      "type": "object",
      "properties": {
        "name": {
          "type": "string",
          "minLength": 1,
          "maxLength": 200,
          "description": "商品名"
        },
        "description": {
          "type": "string",
          "minLength": 10,
          "maxLength": 5000,
          "description": "商品説明"
        },
        "shortDescription": {
          "type": "string",
          "maxLength": 500,
          "description": "短い説明"
        },
        "sku": {
          "type": "string",
          "pattern": "^[A-Z0-9-]+$",
          "minLength": 5,
          "maxLength": 20,
          "description": "商品コード"
        },
        "brand": {
          "type": "string",
          "maxLength": 100,
          "description": "ブランド名"
        },
        "model": {
          "type": "string",
          "maxLength": 100,
          "description": "モデル名"
        }
      },
      "required": ["name", "description", "sku"],
      "additionalProperties": false
    },
    "pricing": {
      "type": "object",
      "properties": {
        "basePrice": {
          "type": "number",
          "minimum": 0,
          "multipleOf": 0.01,
          "description": "基本価格"
        },
        "salePrice": {
          "type": "number",
          "minimum": 0,
          "multipleOf": 0.01,
          "description": "セール価格"
        },
        "currency": {
          "type": "string",
          "enum": ["JPY", "USD", "EUR", "GBP"],
          "default": "JPY",
          "description": "通貨"
        },
        "taxRate": {
          "type": "number",
          "minimum": 0,
          "maximum": 1,
          "multipleOf": 0.001,
          "description": "税率"
        },
        "discountRules": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "type": {
                "type": "string",
                "enum": ["percentage", "fixed_amount", "buy_x_get_y"]
              },
              "value": {
                "type": "number",
                "minimum": 0
              },
              "minQuantity": {
                "type": "integer",
                "minimum": 1
              },
              "startDate": {
                "type": "string",
                "format": "date-time"
              },
              "endDate": {
                "type": "string",
                "format": "date-time"
              }
            },
            "required": ["type", "value"],
            "additionalProperties": false
          }
        }
      },
      "required": ["basePrice", "currency"],
      "additionalProperties": false
    },
    "inventory": {
      "type": "object",
      "properties": {
        "trackInventory": {
          "type": "boolean",
          "default": true,
          "description": "在庫管理するかどうか"
        },
        "stockQuantity": {
          "type": "integer",
          "minimum": 0,
          "description": "在庫数量"
        },
        "lowStockThreshold": {
          "type": "integer",
          "minimum": 0,
          "description": "低在庫アラート閾値"
        },
        "allowBackorder": {
          "type": "boolean",
          "default": false,
          "description": "バックオーダー許可"
        },
        "maxOrderQuantity": {
          "type": "integer",
          "minimum": 1,
          "description": "最大注文数量"
        },
        "restockDate": {
          "type": "string",
          "format": "date",
          "description": "入荷予定日"
        }
      },
      "additionalProperties": false
    },
    "categories": {
      "type": "array",
      "minItems": 1,
      "items": {
        "type": "object",
        "properties": {
          "id": {
            "type": "integer",
            "minimum": 1,
            "description": "カテゴリID"
          },
          "name": {
            "type": "string",
            "description": "カテゴリ名"
          },
          "isPrimary": {
            "type": "boolean",
            "default": false,
            "description": "メインカテゴリかどうか"
          }
        },
        "required": ["id", "name"],
        "additionalProperties": false
      }
    },
    "attributes": {
      "type": "object",
      "properties": {
        "weight": {
          "type": "number",
          "minimum": 0,
          "description": "重量（kg）"
        },
        "dimensions": {
          "type": "object",
          "properties": {
            "length": {
              "type": "number",
              "minimum": 0,
              "description": "長さ（cm）"
            },
            "width": {
              "type": "number",
              "minimum": 0,
              "description": "幅（cm）"
            },
            "height": {
              "type": "number",
              "minimum": 0,
              "description": "高さ（cm）"
            }
          },
          "additionalProperties": false
        },
        "color": {
          "type": "string",
          "description": "色"
        },
        "size": {
          "type": "string",
          "enum": ["XS", "S", "M", "L", "XL", "XXL", "XXXL"],
          "description": "サイズ"
        },
        "material": {
          "type": "string",
          "description": "素材"
        },
        "country": {
          "type": "string",
          "description": "製造国"
        }
      },
      "additionalProperties": true
    },
    "images": {
      "type": "array",
      "minItems": 1,
      "maxItems": 10,
      "items": {
        "type": "object",
        "properties": {
          "url": {
            "type": "string",
            "format": "uri",
            "description": "画像URL"
          },
          "altText": {
            "type": "string",
            "maxLength": 200,
            "description": "画像の代替テキスト"
          },
          "isPrimary": {
            "type": "boolean",
            "default": false,
            "description": "メイン画像かどうか"
          },
          "sortOrder": {
            "type": "integer",
            "minimum": 0,
            "description": "表示順序"
          }
        },
        "required": ["url", "altText"],
        "additionalProperties": false
      }
    },
    "shipping": {
      "type": "object",
      "properties": {
        "requiresShipping": {
          "type": "boolean",
          "default": true,
          "description": "配送が必要かどうか"
        },
        "shippingClass": {
          "type": "string",
          "enum": ["standard", "heavy", "fragile", "hazardous"],
          "default": "standard",
          "description": "配送クラス"
        },
        "handlingTime": {
          "type": "integer",
          "minimum": 0,
          "maximum": 30,
          "description": "発送までの日数"
        },
        "freeShippingThreshold": {
          "type": "number",
          "minimum": 0,
          "description": "送料無料の閾値"
        }
      },
      "additionalProperties": false
    },
    "seo": {
      "type": "object",
      "properties": {
        "metaTitle": {
          "type": "string",
          "maxLength": 60,
          "description": "メタタイトル"
        },
        "metaDescription": {
          "type": "string",
          "maxLength": 160,
          "description": "メタディスクリプション"
        },
        "slug": {
          "type": "string",
          "pattern": "^[a-z0-9-]+$",
          "maxLength": 100,
          "description": "URL スラッグ"
        },
        "keywords": {
          "type": "array",
          "items": {
            "type": "string",
            "minLength": 1,
            "maxLength": 50
          },
          "maxItems": 20,
          "description": "キーワード"
        }
      },
      "additionalProperties": false
    },
    "status": {
      "type": "string",
      "enum": ["draft", "pending", "active", "inactive", "discontinued"],
      "default": "draft",
      "description": "商品ステータス"
    },
    "publishDate": {
      "type": "string",
      "format": "date-time",
      "description": "公開日時"
    }
  },
  "required": ["basicInfo", "pricing", "categories"],
  "additionalProperties": false
}
```

## 3. ブログ記事投稿API

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "Blog Post Creation",
  "type": "object",
  "properties": {
    "content": {
      "type": "object",
      "properties": {
        "title": {
          "type": "string",
          "minLength": 5,
          "maxLength": 200,
          "description": "記事タイトル"
        },
        "subtitle": {
          "type": "string",
          "maxLength": 300,
          "description": "サブタイトル"
        },
        "body": {
          "type": "string",
          "minLength": 100,
          "description": "記事本文（Markdown形式）"
        },
        "excerpt": {
          "type": "string",
          "maxLength": 500,
          "description": "記事の抜粋"
        },
        "language": {
          "type": "string",
          "enum": ["ja", "en", "zh", "ko"],
          "default": "ja",
          "description": "記事の言語"
        }
      },
      "required": ["title", "body"],
      "additionalProperties": false
    },
    "author": {
      "type": "object",
      "properties": {
        "id": {
          "type": "integer",
          "minimum": 1,
          "description": "著者ID"
        },
        "name": {
          "type": "string",
          "description": "著者名"
        },
        "email": {
          "type": "string",
          "format": "email",
          "description": "著者メール"
        },
        "bio": {
          "type": "string",
          "maxLength": 1000,
          "description": "著者紹介"
        },
        "avatar": {
          "type": "string",
          "format": "uri",
          "description": "著者アバター画像URL"
        }
      },
      "required": ["id"],
      "additionalProperties": false
    },
    "categories": {
      "type": "array",
      "minItems": 1,
      "maxItems": 5,
      "items": {
        "type": "object",
        "properties": {
          "id": {
            "type": "integer",
            "minimum": 1
          },
          "name": {
            "type": "string",
            "description": "カテゴリ名"
          },
          "slug": {
            "type": "string",
            "pattern": "^[a-z0-9-]+$",
            "description": "カテゴリスラッグ"
          }
        },
        "required": ["id", "name", "slug"],
        "additionalProperties": false
      }
    },
    "tags": {
      "type": "array",
      "maxItems": 20,
      "items": {
        "type": "string",
        "minLength": 1,
        "maxLength": 50,
        "pattern": "^[\\p{L}\\p{N}_-]+$"
      },
      "uniqueItems": true,
      "description": "タグリスト"
    },
    "media": {
      "type": "object",
      "properties": {
        "featuredImage": {
          "type": "object",
          "properties": {
            "url": {
              "type": "string",
              "format": "uri",
              "description": "アイキャッチ画像URL"
            },
            "altText": {
              "type": "string",
              "maxLength": 200,
              "description": "代替テキスト"
            },
            "caption": {
              "type": "string",
              "maxLength": 500,
              "description": "キャプション"
            }
          },
          "required": ["url"],
          "additionalProperties": false
        },
        "gallery": {
          "type": "array",
          "maxItems": 10,
          "items": {
            "type": "object",
            "properties": {
              "url": {
                "type": "string",
                "format": "uri"
              },
              "altText": {
                "type": "string",
                "maxLength": 200
              },
              "caption": {
                "type": "string",
                "maxLength": 500
              },
              "order": {
                "type": "integer",
                "minimum": 0
              }
            },
            "required": ["url"],
            "additionalProperties": false
          }
        }
      },
      "additionalProperties": false
    },
    "seo": {
      "type": "object",
      "properties": {
        "metaTitle": {
          "type": "string",
          "maxLength": 60,
          "description": "SEOタイトル"
        },
        "metaDescription": {
          "type": "string",
          "maxLength": 160,
          "description": "SEO説明文"
        },
        "slug": {
          "type": "string",
          "pattern": "^[a-z0-9-]+$",
          "maxLength": 100,
          "description": "URLスラッグ"
        },
        "canonicalUrl": {
          "type": "string",
          "format": "uri",
          "description": "正規URL"
        },
        "noindex": {
          "type": "boolean",
          "default": false,
          "description": "検索エンジンにインデックスさせない"
        }
      },
      "additionalProperties": false
    },
    "publishing": {
      "type": "object",
      "properties": {
        "status": {
          "type": "string",
          "enum": ["draft", "pending", "published", "scheduled", "archived"],
          "default": "draft",
          "description": "公開ステータス"
        },
        "publishAt": {
          "type": "string",
          "format": "date-time",
          "description": "公開日時"
        },
        "allowComments": {
          "type": "boolean",
          "default": true,
          "description": "コメント許可"
        },
        "isFeatured": {
          "type": "boolean",
          "default": false,
          "description": "注目記事フラグ"
        },
        "isSticky": {
          "type": "boolean",
          "default": false,
          "description": "トップ固定"
        }
      },
      "additionalProperties": false
    },
    "social": {
      "type": "object",
      "properties": {
        "shareTitle": {
          "type": "string",
          "maxLength": 100,
          "description": "SNSシェア用タイトル"
        },
        "shareDescription": {
          "type": "string",
          "maxLength": 200,
          "description": "SNSシェア用説明"
        },
        "shareImage": {
          "type": "string",
          "format": "uri",
          "description": "SNSシェア用画像"
        },
        "twitterCard": {
          "type": "string",
          "enum": ["summary", "summary_large_image", "app", "player"],
          "default": "summary_large_image",
          "description": "Twitterカードタイプ"
        }
      },
      "additionalProperties": false
    }
  },
  "required": ["content", "author", "categories"],
  "additionalProperties": false
}
```
