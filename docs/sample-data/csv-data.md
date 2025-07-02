# CSV/TSV Data Sample

## 1. ユーザーデータ（CSV形式）

```csv
first_name,last_name,email,phone,birth_date,gender,username,preferred_language,postal_code,prefecture,city,street_address,building,newsletter_email,newsletter_sms,marketing_consent,terms_agree
太郎,山田,taro@example.com,090-1234-5678,1990-05-15,male,yamada_taro,ja,100-0001,tokyo,千代田区,千代田1-1-1,千代田ビル101号,true,false,true,true
花子,佐藤,hanako@example.com,080-9876-5432,1985-12-20,female,sato_hanako,ja,150-0001,tokyo,渋谷区,神宮前2-2-2,,true,true,false,true
次郎,田中,jiro@example.com,070-5555-1111,1992-03-10,male,tanaka_jiro,en,530-0001,osaka,大阪市北区,梅田3-3-3,梅田タワー5F,false,false,true,true
美咲,鈴木,misaki@example.com,,1988-08-25,female,suzuki_misaki,ja,231-0023,kanagawa,横浜市中区,元町4-4-4,,true,false,false,true
```

## 2. 商品データ（TSV形式）

```tsv
product_name	product_description	product_sku	brand	base_price	sale_price	currency	stock_quantity	track_inventory	category_id	category_name	weight	length	width	height	color	size	material	image_url	image_alt_text	is_primary_image	requires_shipping	shipping_class	meta_title	meta_description	status
ワイヤレスイヤホン	高音質Bluetooth5.0対応ワイヤレスイヤホン	WE-001	TechSound	12800	9800	JPY	50	true	1	オーディオ機器	0.05	5.2	3.1	2.8	black	M	プラスチック	https://example.com/images/we001.jpg	ワイヤレスイヤホン ブラック	true	true	standard	高音質ワイヤレスイヤホン - TechSound	Bluetooth5.0対応の高音質ワイヤレスイヤホン。ノイズキャンセリング機能付き	active
スマートウォッチ	健康管理機能付きスマートウォッチ	SW-002	FitTech	25000	22000	JPY	30	true	2	ウェアラブル	0.08	4.5	4.0	1.2	silver	L	アルミニウム	https://example.com/images/sw002.jpg	スマートウォッチ シルバー	true	true	standard	スマートウォッチ 健康管理 - FitTech	心拍数や歩数を測定できるスマートウォッチ	active
プログラミング入門書	初心者向けPython入門書	BOOK-003	TechBooks	2800		JPY	100	true	3	書籍	0.3	21.0	14.8	1.5	blue		紙	https://example.com/images/book003.jpg	Python入門書	true	true	standard	Python入門書 - プログラミング学習	初心者でもわかりやすいPython入門書	active
```

## 3. 注文データ（pipe区切り）

```
customer_name|customer_email|customer_phone|shipping_street|shipping_city|shipping_postal_code|shipping_country|billing_same_as_shipping|billing_street|billing_city|billing_postal_code|billing_country|payment_method|card_number|card_holder|card_expiry_month|card_expiry_year|card_cvv|delivery_time|gift_wrap|express_delivery|coupon_code|use_points
山田太郎|yamada@example.com|03-1234-5678|神宮前1-1-1|渋谷区|150-0001|JP|true||||JP|credit_card|1234567890123456|YAMADA TARO|12|2025|123|morning|true|false|WELCOME10|500
佐藤花子|sato@example.com|06-9876-5432|梅田2-2-2|大阪市北区|530-0001|JP|false|天神3-3-3|福岡市中央区|810-0001|JP|bank_transfer||||||afternoon|false|true||0
田中次郎|tanaka@example.com|052-5555-1111|栄4-4-4|名古屋市中区|460-0008|JP|true||||JP|convenience_store||||||||false|false|SPRING20|1000
```

## 4. イベント参加者データ（セミコロン区切り）

```
participant_name;participant_email;participant_phone;job_title;experience_years;company_size;company_name;company_url;sessions;lunch_preference;dietary_restrictions;networking_attend;questions;first_time_attendee;speaker_interest
山田太郎;yamada@techcorp.com;090-1234-5678;engineer;5;medium;株式会社テックコープ;https://techcorp.com;keynote,ai_ml,backend_api;regular;;yes;AIの最新動向について知りたいです;true;false
佐藤花子;sato@startup.jp;080-9876-5432;designer;3;startup;スタートアップ株式会社;https://startup.jp;keynote,web_frontend,mobile;vegetarian;小麦アレルギーがあります;yes;デザインシステムについて;false;true
田中次郎;tanaka@bigcorp.co.jp;070-5555-1111;manager;8;large;大手商事株式会社;https://bigcorp.co.jp;keynote,devops;regular;;no;チーム管理のベストプラクティス;true;false
鈴木美咲;suzuki@freelance.com;060-1111-2222;freelance;2;freelance;フリーランス;;keynote,web_frontend,ai_ml;vegan;完全菜食主義者です;yes;フリーランスのキャリア戦略;true;true
```

## 5. 求人応募データ（カンマ区切り、複雑な配列データ含む）

```csv
applicant_name,applicant_email,applicant_phone,applicant_age,current_status,desired_position,desired_salary_min,desired_salary_max,work_style_preference,available_start_date,programming_languages,frameworks,technical_experience,work_experience,total_experience_years,programming_experience_years,education_level,school_name,major,graduation_year,motivation,career_goals,referral_source
山田太郎,yamada@example.com,090-1234-5678,28,employed,fullstack,6000000,8000000,hybrid,2024-04-01,"javascript,typescript,python","react,nodejs,django","5年間のWebアプリケーション開発経験。React/Node.jsでのSPA開発が得意","2019-2024: 株式会社ABC システムエンジニア, 2017-2019: フリーランス",7,6,university,東京大学,情報科学,2017,新しい技術に挑戦できる環境で成長したい,フルスタックエンジニアとしてのスキルを深め将来的にはテックリードを目指したい,転職サイト
佐藤花子,sato@example.com,080-9876-5432,25,student,frontend,4000000,6000000,remote,2024-03-15,"javascript,typescript","react,vue,angular","大学でのプロジェクトとインターンシップでフロントエンド開発を経験","2023-2024: 株式会社XYZ インターン, 大学でのチーム開発プロジェクト多数",1,3,university,早稲田大学,コンピュータサイエンス,2024,ユーザー体験を重視した開発に興味がある,フロントエンドエキスパートとして美しく使いやすいUIを作り続けたい,大学のキャリアセンター
田中次郎,tanaka@example.com,070-5555-1111,32,unemployed,backend,7000000,10000000,office,2024-02-01,"python,golang,java","django,gin,spring","8年間のバックエンド開発。マイクロサービス設計とAPI開発が専門","2016-2023: 株式会社DEF シニアエンジニア, 2014-2016: 株式会社GHI エンジニア",9,9,graduate,京都大学大学院,情報工学,2014,スケーラブルなシステム設計に携わりたい,アーキテクトとして大規模システムの設計を主導したい,知人の紹介
```

## 6. ネストしたデータのフラット表現（pipe区切り）

```
blog_title|blog_content|blog_excerpt|blog_status|author_id|author_name|author_email|author_bio|category_ids|category_names|category_slugs|tag_list|featured_image_url|featured_image_alt|featured_image_caption|meta_title|meta_description|meta_keywords|publish_at|allow_comments|is_featured
AI時代のプログラミング|人工知能技術の発展により、プログラミングの世界も大きく変わろうとしています...|AIとプログラミングの未来について考察します|published|1|山田太郎|yamada@example.com|10年のエンジニア経験を持つテックライター|1,3|テクノロジー,プログラミング|technology,programming|AI,機械学習,プログラミング,未来|https://example.com/ai-programming.jpg|AI時代のプログラミングのイメージ|プログラマーとAIが協働する未来|AI時代のプログラミング - 技術ブログ|人工知能がプログラミングにもたらす変化と可能性について|AI,プログラミング,機械学習,技術,未来|2024-01-15 10:00:00|true|true
Webフレームワーク比較|React、Vue、Angularの特徴と選び方について詳しく解説します...|主要なJavaScriptフレームワークを比較|draft|2|佐藤花子|sato@example.com|フロントエンドエンジニア、UI/UXデザイナー|2,3|フロントエンド,プログラミング|frontend,programming|JavaScript,React,Vue,Angular,フレームワーク|https://example.com/frameworks.jpg|JavaScriptフレームワーク比較|React、Vue、Angularのロゴ|Webフレームワーク徹底比較 2024|React、Vue、Angularの特徴と選び方ガイド|JavaScript,React,Vue,Angular,Webフレームワーク|2024-02-01 09:00:00|true|false
```

## 7. マルチレベルネストデータ（タブ区切り）

```tsv
order_id	customer_first_name	customer_last_name	customer_email	shipping_street	shipping_city	shipping_postal_code	billing_street	billing_city	billing_postal_code	payment_type	card_number_masked	card_holder	item_ids	item_names	item_quantities	item_prices	item_skus	options_gift_wrap	options_express	options_delivery_time	coupon_code	total_amount
ORD-001	太郎	山田	yamada@example.com	神宮前1-1-1	渋谷区	150-0001	神宮前1-1-1	渋谷区	150-0001	credit_card	****-****-****-1234	YAMADA TARO	PROD-001,PROD-002	ワイヤレスイヤホン,スマートウォッチ	1,1	9800,22000	WE-001,SW-002	true	false	morning	WELCOME10	28620
ORD-002	花子	佐藤	sato@example.com	梅田2-2-2	大阪市北区	530-0001	天神3-3-3	福岡市中央区	810-0001	bank_transfer			PROD-003	プログラミング入門書	2	2800	BOOK-003	false	true	afternoon		6400
ORD-003	次郎	田中	tanaka@example.com	栄4-4-4	名古屋市中区	460-0008	栄4-4-4	名古屋市中区	460-0008	convenience_store			PROD-001,PROD-003	ワイヤレスイヤホン,プログラミング入門書	1,1	9800,2800	WE-001,BOOK-003	false	false		SPRING20	10080
```

## 8. 投稿・コメントデータ（カンマ区切り、エスケープ処理）

```csv
post_title,post_content,post_slug,author_name,author_email,comment_ids,comment_contents,comment_author_names,comment_author_emails,comment_created_ats,comment_ratings
"Ray.InputQueryの紹介","\"Ray.InputQuery\"は、型安全なInput処理を実現するライブラリです。従来の$_POSTや$requestから直接値を取得する方法と比較して、以下のメリットがあります:\n\n1. 型安全性\n2. 構造の明確化\n3. テストのしやすさ","ray-input-query-introduction","山田太郎","yamada@example.com","1,2,3","素晴らしいライブラリですね！,早速使ってみます。,ドキュメントがわかりやすいです。","鈴木一郎,田中花子,佐藤次郎","suzuki@example.com,tanaka@example.com,sato@example.com","2024-01-15 14:30:00,2024-01-15 15:45:00,2024-01-16 09:00:00","5,4,5"
"PHPの新機能","PHP 8.3で追加された新機能について解説します。特にreadonly classesとtyped propertiesの組み合わせは、Immutableなオブジェクト設計において非常に有用です。","php-new-features","佐藤花子","sato@example.com","4,5","参考になりました！,実際のプロジェクトで使ってみたいと思います。","山田太郎,鈴木一郎","yamada@example.com,suzuki@example.com","2024-01-20 10:15:00,2024-01-20 16:20:00","4,5"
```

## 9. 複雑な設定データ（JSON-like構造をCSVで表現）

```csv
user_id,personal_first_name,personal_last_name,personal_email,personal_phone,account_username,account_language,address_postal_code,address_prefecture,address_city,address_street,notifications_email_newsletter,notifications_email_promotions,notifications_sms_orders,notifications_push_enabled,privacy_profile_visibility,privacy_data_collection,privacy_third_party_sharing,social_twitter,social_linkedin,social_github,social_website
1,太郎,山田,yamada@example.com,090-1234-5678,yamada_taro,ja,100-0001,tokyo,千代田区,千代田1-1-1,true,false,true,true,friends,false,false,@yamada_taro,https://linkedin.com/in/yamada,yamada-taro,https://yamada.dev
2,花子,佐藤,sato@example.com,080-9876-5432,sato_hanako,en,150-0001,tokyo,渋谷区,神宮前2-2-2,false,true,false,false,public,true,false,@sato_hanako,,sato-hanako,
3,次郎,田中,tanaka@example.com,070-5555-1111,tanaka_jiro,ja,530-0001,osaka,大阪市北区,梅田3-3-3,true,true,true,true,private,false,true,,,tanaka-jiro,https://tanaka-blog.com
```

## 10. APIログデータ（タブ区切り、高頻度データ）

```tsv
timestamp	endpoint	method	user_id	request_ip	user_agent	query_params	request_body_summary	response_status	response_time_ms	error_message
2024-01-15T10:30:00Z	/api/users	POST	null	192.168.1.100	Mozilla/5.0 (Windows NT 10.0; Win64; x64)	register=true	{firstName:太郎,lastName:山田,email:yamada@example.com}	201	245	
2024-01-15T10:31:00Z	/api/auth/login	POST	null	192.168.1.100	Mozilla/5.0 (Windows NT 10.0; Win64; x64)	remember=true	{username:yamada_taro,password:***}	200	180	
2024-01-15T10:32:00Z	/api/products	GET	1	192.168.1.100	Mozilla/5.0 (Windows NT 10.0; Win64; x64)	page=1&limit=20&category=electronics	{}	200	95	
2024-01-15T10:33:00Z	/api/cart	POST	1	192.168.1.100	Mozilla/5.0 (Windows NT 10.0; Win64; x64)		{productId:PROD-001,quantity:2}	400	50	Insufficient stock
2024-01-15T10:34:00Z	/api/orders	POST	1	192.168.1.100	Mozilla/5.0 (Windows NT 10.0; Win64; x64)		{customer:{},shipping:{},payment:{}}	201	1200	
```
