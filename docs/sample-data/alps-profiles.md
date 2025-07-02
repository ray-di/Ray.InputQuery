# ALPS (Application-Level Profile Semantics) Sample Data

## 1. ブログシステム ALPS プロファイル

```json
{
  "alps": {
    "version": "1.0",
    "doc": {
      "value": "Blog Management System API Profile"
    },
    "descriptor": [
      {
        "id": "blog-post",
        "type": "semantic",
        "doc": {
          "value": "ブログ記事のセマンティック記述"
        },
        "descriptor": [
          {
            "id": "title",
            "type": "semantic",
            "doc": {
              "value": "記事タイトル"
            }
          },
          {
            "id": "content",
            "type": "semantic",
            "doc": {
              "value": "記事本文"
            }
          },
          {
            "id": "excerpt",
            "type": "semantic",
            "doc": {
              "value": "記事の抜粋"
            }
          },
          {
            "id": "slug",
            "type": "semantic",
            "doc": {
              "value": "URLスラッグ"
            }
          },
          {
            "id": "published-at",
            "type": "semantic",
            "doc": {
              "value": "公開日時"
            }
          },
          {
            "id": "status",
            "type": "semantic",
            "doc": {
              "value": "公開ステータス（draft, published, archived）"
            }
          },
          {
            "id": "author",
            "type": "semantic",
            "doc": {
              "value": "記事作成者情報"
            },
            "descriptor": [
              {
                "id": "author-id",
                "type": "semantic",
                "doc": {
                  "value": "著者ID"
                }
              },
              {
                "id": "author-name",
                "type": "semantic",
                "doc": {
                  "value": "著者名"
                }
              },
              {
                "id": "author-email",
                "type": "semantic",
                "doc": {
                  "value": "著者メールアドレス"
                }
              },
              {
                "id": "author-bio",
                "type": "semantic",
                "doc": {
                  "value": "著者経歴"
                }
              }
            ]
          },
          {
            "id": "categories",
            "type": "semantic",
            "doc": {
              "value": "記事カテゴリ"
            },
            "descriptor": [
              {
                "id": "category-id",
                "type": "semantic",
                "doc": {
                  "value": "カテゴリID"
                }
              },
              {
                "id": "category-name",
                "type": "semantic",
                "doc": {
                  "value": "カテゴリ名"
                }
              },
              {
                "id": "category-slug",
                "type": "semantic",
                "doc": {
                  "value": "カテゴリスラッグ"
                }
              }
            ]
          },
          {
            "id": "tags",
            "type": "semantic",
            "doc": {
              "value": "記事タグ（配列）"
            }
          },
          {
            "id": "featured-image",
            "type": "semantic",
            "doc": {
              "value": "アイキャッチ画像"
            },
            "descriptor": [
              {
                "id": "image-url",
                "type": "semantic",
                "doc": {
                  "value": "画像URL"
                }
              },
              {
                "id": "image-alt",
                "type": "semantic",
                "doc": {
                  "value": "画像の代替テキスト"
                }
              },
              {
                "id": "image-caption",
                "type": "semantic",
                "doc": {
                  "value": "画像キャプション"
                }
              }
            ]
          },
          {
            "id": "seo-meta",
            "type": "semantic",
            "doc": {
              "value": "SEO メタデータ"
            },
            "descriptor": [
              {
                "id": "meta-title",
                "type": "semantic",
                "doc": {
                  "value": "SEO タイトル"
                }
              },
              {
                "id": "meta-description",
                "type": "semantic",
                "doc": {
                  "value": "SEO 説明文"
                }
              },
              {
                "id": "meta-keywords",
                "type": "semantic",
                "doc": {
                  "value": "SEO キーワード"
                }
              }
            ]
          }
        ]
      },
      {
        "id": "create-post",
        "type": "unsafe",
        "rt": "blog-post",
        "doc": {
          "value": "新しいブログ記事を作成"
        }
      },
      {
        "id": "update-post",
        "type": "idempotent",
        "rt": "blog-post",
        "doc": {
          "value": "既存のブログ記事を更新"
        }
      },
      {
        "id": "delete-post",
        "type": "unsafe",
        "doc": {
          "value": "ブログ記事を削除"
        }
      },
      {
        "id": "list-posts",
        "type": "safe",
        "rt": "blog-post",
        "doc": {
          "value": "ブログ記事一覧を取得"
        }
      }
    ]
  }
}
```

## 2. Eコマース商品管理 ALPS プロファイル

```json
{
  "alps": {
    "version": "1.0",
    "doc": {
      "value": "E-commerce Product Management API Profile"
    },
    "descriptor": [
      {
        "id": "product",
        "type": "semantic",
        "doc": {
          "value": "商品情報のセマンティック記述"
        },
        "descriptor": [
          {
            "id": "basic-info",
            "type": "semantic",
            "doc": {
              "value": "基本商品情報"
            },
            "descriptor": [
              {
                "id": "product-name",
                "type": "semantic",
                "doc": {
                  "value": "商品名"
                }
              },
              {
                "id": "product-description",
                "type": "semantic",
                "doc": {
                  "value": "商品説明"
                }
              },
              {
                "id": "product-sku",
                "type": "semantic",
                "doc": {
                  "value": "商品コード（SKU）"
                }
              },
              {
                "id": "brand",
                "type": "semantic",
                "doc": {
                  "value": "ブランド名"
                }
              },
              {
                "id": "model",
                "type": "semantic",
                "doc": {
                  "value": "モデル名"
                }
              }
            ]
          },
          {
            "id": "pricing",
            "type": "semantic",
            "doc": {
              "value": "価格情報"
            },
            "descriptor": [
              {
                "id": "base-price",
                "type": "semantic",
                "doc": {
                  "value": "基本価格"
                }
              },
              {
                "id": "sale-price",
                "type": "semantic",
                "doc": {
                  "value": "セール価格"
                }
              },
              {
                "id": "currency",
                "type": "semantic",
                "doc": {
                  "value": "通貨コード"
                }
              },
              {
                "id": "tax-rate",
                "type": "semantic",
                "doc": {
                  "value": "税率"
                }
              }
            ]
          },
          {
            "id": "inventory",
            "type": "semantic",
            "doc": {
              "value": "在庫情報"
            },
            "descriptor": [
              {
                "id": "stock-quantity",
                "type": "semantic",
                "doc": {
                  "value": "在庫数量"
                }
              },
              {
                "id": "track-inventory",
                "type": "semantic",
                "doc": {
                  "value": "在庫管理フラグ"
                }
              },
              {
                "id": "low-stock-threshold",
                "type": "semantic",
                "doc": {
                  "value": "低在庫アラート閾値"
                }
              },
              {
                "id": "allow-backorder",
                "type": "semantic",
                "doc": {
                  "value": "バックオーダー許可フラグ"
                }
              }
            ]
          },
          {
            "id": "category",
            "type": "semantic",
            "doc": {
              "value": "商品カテゴリ"
            },
            "descriptor": [
              {
                "id": "category-id",
                "type": "semantic",
                "doc": {
                  "value": "カテゴリID"
                }
              },
              {
                "id": "category-name",
                "type": "semantic",
                "doc": {
                  "value": "カテゴリ名"
                }
              },
              {
                "id": "is-primary-category",
                "type": "semantic",
                "doc": {
                  "value": "メインカテゴリフラグ"
                }
              }
            ]
          },
          {
            "id": "product-attributes",
            "type": "semantic",
            "doc": {
              "value": "商品属性"
            },
            "descriptor": [
              {
                "id": "weight",
                "type": "semantic",
                "doc": {
                  "value": "重量（kg）"
                }
              },
              {
                "id": "dimensions",
                "type": "semantic",
                "doc": {
                  "value": "寸法"
                },
                "descriptor": [
                  {
                    "id": "length",
                    "type": "semantic",
                    "doc": {
                      "value": "長さ（cm）"
                    }
                  },
                  {
                    "id": "width",
                    "type": "semantic",
                    "doc": {
                      "value": "幅（cm）"
                    }
                  },
                  {
                    "id": "height",
                    "type": "semantic",
                    "doc": {
                      "value": "高さ（cm）"
                    }
                  }
                ]
              },
              {
                "id": "color",
                "type": "semantic",
                "doc": {
                  "value": "色"
                }
              },
              {
                "id": "size",
                "type": "semantic",
                "doc": {
                  "value": "サイズ"
                }
              },
              {
                "id": "material",
                "type": "semantic",
                "doc": {
                  "value": "素材"
                }
              }
            ]
          },
          {
            "id": "product-images",
            "type": "semantic",
            "doc": {
              "value": "商品画像"
            },
            "descriptor": [
              {
                "id": "image-url",
                "type": "semantic",
                "doc": {
                  "value": "画像URL"
                }
              },
              {
                "id": "image-alt-text",
                "type": "semantic",
                "doc": {
                  "value": "画像代替テキスト"
                }
              },
              {
                "id": "is-primary-image",
                "type": "semantic",
                "doc": {
                  "value": "メイン画像フラグ"
                }
              },
              {
                "id": "sort-order",
                "type": "semantic",
                "doc": {
                  "value": "表示順序"
                }
              }
            ]
          },
          {
            "id": "shipping-info",
            "type": "semantic",
            "doc": {
              "value": "配送情報"
            },
            "descriptor": [
              {
                "id": "requires-shipping",
                "type": "semantic",
                "doc": {
                  "value": "配送必要フラグ"
                }
              },
              {
                "id": "shipping-class",
                "type": "semantic",
                "doc": {
                  "value": "配送クラス"
                }
              },
              {
                "id": "handling-time",
                "type": "semantic",
                "doc": {
                  "value": "発送準備期間（日数）"
                }
              },
              {
                "id": "free-shipping-threshold",
                "type": "semantic",
                "doc": {
                  "value": "送料無料閾値"
                }
              }
            ]
          }
        ]
      },
      {
        "id": "create-product",
        "type": "unsafe",
        "rt": "product",
        "doc": {
          "value": "新しい商品を作成"
        }
      },
      {
        "id": "update-product",
        "type": "idempotent",
        "rt": "product",
        "doc": {
          "value": "既存商品を更新"
        }
      },
      {
        "id": "delete-product",
        "type": "unsafe",
        "doc": {
          "value": "商品を削除"
        }
      },
      {
        "id": "search-products",
        "type": "safe",
        "rt": "product",
        "doc": {
          "value": "商品を検索"
        }
      }
    ]
  }
}
```

## 3. ユーザー管理システム ALPS プロファイル

```json
{
  "alps": {
    "version": "1.0",
    "doc": {
      "value": "User Management System API Profile"
    },
    "descriptor": [
      {
        "id": "user",
        "type": "semantic",
        "doc": {
          "value": "ユーザー情報のセマンティック記述"
        },
        "descriptor": [
          {
            "id": "personal-info",
            "type": "semantic",
            "doc": {
              "value": "個人情報"
            },
            "descriptor": [
              {
                "id": "first-name",
                "type": "semantic",
                "doc": {
                  "value": "名前"
                }
              },
              {
                "id": "last-name",
                "type": "semantic",
                "doc": {
                  "value": "姓"
                }
              },
              {
                "id": "email",
                "type": "semantic",
                "doc": {
                  "value": "メールアドレス"
                }
              },
              {
                "id": "phone-number",
                "type": "semantic",
                "doc": {
                  "value": "電話番号"
                }
              },
              {
                "id": "birth-date",
                "type": "semantic",
                "doc": {
                  "value": "生年月日"
                }
              },
              {
                "id": "gender",
                "type": "semantic",
                "doc": {
                  "value": "性別"
                }
              }
            ]
          },
          {
            "id": "account-info",
            "type": "semantic",
            "doc": {
              "value": "アカウント情報"
            },
            "descriptor": [
              {
                "id": "username",
                "type": "semantic",
                "doc": {
                  "value": "ユーザー名"
                }
              },
              {
                "id": "password",
                "type": "semantic",
                "doc": {
                  "value": "パスワード"
                }
              },
              {
                "id": "password-confirm",
                "type": "semantic",
                "doc": {
                  "value": "パスワード確認"
                }
              },
              {
                "id": "preferred-language",
                "type": "semantic",
                "doc": {
                  "value": "優先言語"
                }
              }
            ]
          },
          {
            "id": "address",
            "type": "semantic",
            "doc": {
              "value": "住所情報"
            },
            "descriptor": [
              {
                "id": "postal-code",
                "type": "semantic",
                "doc": {
                  "value": "郵便番号"
                }
              },
              {
                "id": "prefecture",
                "type": "semantic",
                "doc": {
                  "value": "都道府県"
                }
              },
              {
                "id": "city",
                "type": "semantic",
                "doc": {
                  "value": "市区町村"
                }
              },
              {
                "id": "street-address",
                "type": "semantic",
                "doc": {
                  "value": "番地・町名"
                }
              },
              {
                "id": "building",
                "type": "semantic",
                "doc": {
                  "value": "建物名・部屋番号"
                }
              }
            ]
          },
          {
            "id": "preferences",
            "type": "semantic",
            "doc": {
              "value": "ユーザー設定"
            },
            "descriptor": [
              {
                "id": "notification-settings",
                "type": "semantic",
                "doc": {
                  "value": "通知設定"
                },
                "descriptor": [
                  {
                    "id": "email-newsletter",
                    "type": "semantic",
                    "doc": {
                      "value": "メールマガジン購読"
                    }
                  },
                  {
                    "id": "sms-notification",
                    "type": "semantic",
                    "doc": {
                      "value": "SMS通知"
                    }
                  },
                  {
                    "id": "marketing-consent",
                    "type": "semantic",
                    "doc": {
                      "value": "マーケティング情報受信同意"
                    }
                  }
                ]
              },
              {
                "id": "privacy-settings",
                "type": "semantic",
                "doc": {
                  "value": "プライバシー設定"
                },
                "descriptor": [
                  {
                    "id": "profile-visibility",
                    "type": "semantic",
                    "doc": {
                      "value": "プロフィール公開設定"
                    }
                  },
                  {
                    "id": "data-collection-consent",
                    "type": "semantic",
                    "doc": {
                      "value": "データ収集同意"
                    }
                  }
                ]
              }
            ]
          },
          {
            "id": "terms-agreement",
            "type": "semantic",
            "doc": {
              "value": "利用規約同意"
            }
          }
        ]
      },
      {
        "id": "register-user",
        "type": "unsafe",
        "rt": "user",
        "doc": {
          "value": "新規ユーザー登録"
        }
      },
      {
        "id": "update-user",
        "type": "idempotent",
        "rt": "user",
        "doc": {
          "value": "ユーザー情報更新"
        }
      },
      {
        "id": "delete-user",
        "type": "unsafe",
        "doc": {
          "value": "ユーザー削除"
        }
      },
      {
        "id": "authenticate-user",
        "type": "unsafe",
        "doc": {
          "value": "ユーザー認証"
        }
      }
    ]
  }
}
```
