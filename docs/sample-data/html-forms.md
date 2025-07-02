# HTML Forms Sample Data

## 1. ユーザー登録フォーム

```html
<form action="/register" method="POST">
    <h2>新規会員登録</h2>
    
    <!-- 個人情報 -->
    <fieldset>
        <legend>個人情報</legend>
        <input name="first_name" type="text" placeholder="太郎" required>
        <input name="last_name" type="text" placeholder="山田" required>
        <input name="email" type="email" placeholder="taro@example.com" required>
        <input name="phone" type="tel" placeholder="090-1234-5678">
        <input name="birth_date" type="date" required>
        <select name="gender">
            <option value="">選択してください</option>
            <option value="male">男性</option>
            <option value="female">女性</option>
            <option value="other">その他</option>
        </select>
    </fieldset>
    
    <!-- アカウント情報 -->
    <fieldset>
        <legend>アカウント情報</legend>
        <input name="username" type="text" placeholder="yamada_taro" required>
        <input name="password" type="password" minlength="8" required>
        <input name="password_confirm" type="password" required>
        <select name="preferred_language">
            <option value="ja">日本語</option>
            <option value="en">English</option>
            <option value="zh">中文</option>
        </select>
    </fieldset>
    
    <!-- 住所 -->
    <fieldset>
        <legend>住所</legend>
        <input name="postal_code" type="text" placeholder="100-0001" required>
        <select name="prefecture" required>
            <option value="">都道府県を選択</option>
            <option value="tokyo">東京都</option>
            <option value="osaka">大阪府</option>
            <option value="kanagawa">神奈川県</option>
        </select>
        <input name="city" type="text" placeholder="千代田区" required>
        <input name="street_address" type="text" placeholder="千代田1-1-1" required>
        <input name="building" type="text" placeholder="千代田ビル101号">
    </fieldset>
    
    <!-- 設定 -->
    <fieldset>
        <legend>設定</legend>
        <input name="newsletter_email" type="checkbox" checked>
        <label>メールマガジンを受け取る</label>
        
        <input name="newsletter_sms" type="checkbox">
        <label>SMS通知を受け取る</label>
        
        <input name="marketing_consent" type="checkbox">
        <label>マーケティング情報の受信に同意する</label>
        
        <input name="terms_agree" type="checkbox" required>
        <label>利用規約に同意する</label>
    </fieldset>
    
    <button type="submit">登録する</button>
</form>
```

## 2. ECサイト商品注文フォーム

```html
<form action="/orders" method="POST">
    <h2>ご注文手続き</h2>
    
    <!-- 注文者情報 -->
    <fieldset>
        <legend>注文者情報</legend>
        <input name="customer_name" type="text" placeholder="山田太郎" required>
        <input name="customer_email" type="email" placeholder="customer@example.com" required>
        <input name="customer_phone" type="tel" placeholder="03-1234-5678" required>
    </fieldset>
    
    <!-- 配送先住所 -->
    <fieldset>
        <legend>配送先住所</legend>
        <input name="shipping_postal_code" type="text" placeholder="150-0001" required>
        <input name="shipping_prefecture" type="text" placeholder="東京都" required>
        <input name="shipping_city" type="text" placeholder="渋谷区" required>
        <input name="shipping_street" type="text" placeholder="神宮前1-1-1" required>
        <input name="shipping_building" type="text" placeholder="表参道ヒルズ">
        <textarea name="shipping_notes" placeholder="配送時の注意事項があればご記入ください"></textarea>
    </fieldset>
    
    <!-- 請求先住所 -->
    <input name="billing_same_as_shipping" type="checkbox" checked>
    <label>請求先住所は配送先と同じ</label>
    
    <fieldset id="billing-address" style="display: none;">
        <legend>請求先住所</legend>
        <input name="billing_postal_code" type="text" placeholder="150-0001">
        <input name="billing_prefecture" type="text" placeholder="東京都">
        <input name="billing_city" type="text" placeholder="渋谷区">
        <input name="billing_street" type="text" placeholder="神宮前1-1-1">
        <input name="billing_building" type="text" placeholder="表参道ヒルズ">
    </fieldset>
    
    <!-- 支払い方法 -->
    <fieldset>
        <legend>支払い方法</legend>
        <input name="payment_method" type="radio" value="credit_card" checked>
        <label>クレジットカード</label>
        
        <input name="payment_method" type="radio" value="bank_transfer">
        <label>銀行振込</label>
        
        <input name="payment_method" type="radio" value="convenience_store">
        <label>コンビニ支払い</label>
        
        <!-- クレジットカード情報 -->
        <div id="credit-card-info">
            <input name="card_number" type="text" placeholder="1234 5678 9012 3456">
            <input name="card_holder" type="text" placeholder="YAMADA TARO">
            <select name="card_expiry_month">
                <option value="">月</option>
                <option value="01">01</option>
                <option value="02">02</option>
                <!-- ... -->
                <option value="12">12</option>
            </select>
            <select name="card_expiry_year">
                <option value="">年</option>
                <option value="2024">2024</option>
                <option value="2025">2025</option>
                <!-- ... -->
            </select>
            <input name="card_cvv" type="text" placeholder="123" maxlength="4">
        </div>
    </fieldset>
    
    <!-- オプション -->
    <fieldset>
        <legend>配送オプション</legend>
        <select name="delivery_time">
            <option value="">指定なし</option>
            <option value="morning">午前中</option>
            <option value="afternoon">14-16時</option>
            <option value="evening">18-20時</option>
        </select>
        
        <input name="gift_wrap" type="checkbox">
        <label>ギフト包装（+500円）</label>
        
        <input name="express_delivery" type="checkbox">
        <label>お急ぎ便（+800円）</label>
    </fieldset>
    
    <!-- クーポン -->
    <fieldset>
        <legend>クーポン・ポイント</legend>
        <input name="coupon_code" type="text" placeholder="クーポンコードを入力">
        <input name="use_points" type="number" min="0" max="1000" placeholder="使用ポイント">
    </fieldset>
    
    <button type="submit">注文を確定する</button>
</form>
```

## 3. イベント参加申し込みフォーム

```html
<form action="/events/123/apply" method="POST" enctype="multipart/form-data">
    <h2>技術カンファレンス 2024 参加申し込み</h2>
    
    <!-- 参加者情報 -->
    <fieldset>
        <legend>参加者情報</legend>
        <input name="participant_name" type="text" placeholder="山田太郎" required>
        <input name="participant_email" type="email" placeholder="yamada@company.com" required>
        <input name="participant_phone" type="tel" placeholder="090-1234-5678">
        
        <select name="job_title" required>
            <option value="">職種を選択</option>
            <option value="engineer">エンジニア</option>
            <option value="designer">デザイナー</option>
            <option value="manager">マネージャー</option>
            <option value="director">ディレクター</option>
            <option value="student">学生</option>
            <option value="other">その他</option>
        </select>
        
        <input name="experience_years" type="number" min="0" max="50" placeholder="経験年数">
        
        <select name="company_size">
            <option value="">会社規模</option>
            <option value="startup">スタートアップ（〜50名）</option>
            <option value="medium">中規模（51〜500名）</option>
            <option value="large">大企業（501名〜）</option>
            <option value="freelance">フリーランス</option>
        </select>
    </fieldset>
    
    <!-- 会社情報 -->
    <fieldset>
        <legend>会社情報</legend>
        <input name="company_name" type="text" placeholder="株式会社テック">
        <input name="company_url" type="url" placeholder="https://company.com">
        <textarea name="company_description" placeholder="会社の事業内容を簡単にご記入ください"></textarea>
    </fieldset>
    
    <!-- 参加セッション -->
    <fieldset>
        <legend>参加希望セッション（複数選択可）</legend>
        <input name="sessions[]" type="checkbox" value="keynote">
        <label>基調講演</label>
        
        <input name="sessions[]" type="checkbox" value="ai_ml">
        <label>AI/機械学習トラック</label>
        
        <input name="sessions[]" type="checkbox" value="web_frontend">
        <label>Web フロントエンド</label>
        
        <input name="sessions[]" type="checkbox" value="backend_api">
        <label>バックエンド/API</label>
        
        <input name="sessions[]" type="checkbox" value="devops">
        <label>DevOps/インフラ</label>
        
        <input name="sessions[]" type="checkbox" value="mobile">
        <label>モバイル開発</label>
    </fieldset>
    
    <!-- 食事・アレルギー -->
    <fieldset>
        <legend>食事・アレルギー情報</legend>
        <select name="lunch_preference">
            <option value="regular">通常食</option>
            <option value="vegetarian">ベジタリアン</option>
            <option value="vegan">ヴィーガン</option>
            <option value="halal">ハラル</option>
        </select>
        
        <textarea name="dietary_restrictions" placeholder="アレルギーや食事制限があればご記入ください"></textarea>
    </fieldset>
    
    <!-- 交流会 -->
    <fieldset>
        <legend>懇親会</legend>
        <input name="networking_attend" type="radio" value="yes" required>
        <label>参加する（+3,000円）</label>
        
        <input name="networking_attend" type="radio" value="no" required>
        <label>参加しない</label>
    </fieldset>
    
    <!-- 質問・コメント -->
    <fieldset>
        <legend>質問・コメント</legend>
        <textarea name="questions" placeholder="講演者への質問や、期待することなどがあればご記入ください"></textarea>
        
        <input name="first_time_attendee" type="checkbox">
        <label>このカンファレンスへの参加は初回です</label>
        
        <input name="speaker_interest" type="checkbox">
        <label>将来的に登壇に興味があります</label>
    </fieldset>
    
    <!-- ファイルアップロード -->
    <fieldset>
        <legend>名刺・プロフィール（任意）</legend>
        <input name="business_card" type="file" accept="image/*,.pdf">
        <label>名刺画像またはPDF</label>
        
        <input name="profile_photo" type="file" accept="image/*">
        <label>プロフィール写真</label>
    </fieldset>
    
    <button type="submit">申し込みを完了する</button>
</form>
```

## 4. 求人応募フォーム

```html
<form action="/jobs/apply" method="POST" enctype="multipart/form-data">
    <h2>エンジニア職 応募フォーム</h2>
    
    <!-- 基本情報 -->
    <fieldset>
        <legend>基本情報</legend>
        <input name="applicant_name" type="text" placeholder="山田太郎" required>
        <input name="applicant_email" type="email" placeholder="yamada@example.com" required>
        <input name="applicant_phone" type="tel" placeholder="090-1234-5678" required>
        <input name="applicant_age" type="number" min="18" max="100" placeholder="年齢">
        
        <select name="current_status" required>
            <option value="">現在の状況</option>
            <option value="employed">在職中</option>
            <option value="unemployed">離職中</option>
            <option value="student">学生</option>
            <option value="freelance">フリーランス</option>
        </select>
    </fieldset>
    
    <!-- 希望条件 -->
    <fieldset>
        <legend>希望条件</legend>
        <select name="desired_position" required>
            <option value="">希望職種</option>
            <option value="frontend">フロントエンドエンジニア</option>
            <option value="backend">バックエンドエンジニア</option>
            <option value="fullstack">フルスタックエンジニア</option>
            <option value="mobile">モバイルエンジニア</option>
            <option value="devops">DevOpsエンジニア</option>
        </select>
        
        <input name="desired_salary_min" type="number" min="0" placeholder="希望年収（最低）">
        <input name="desired_salary_max" type="number" min="0" placeholder="希望年収（最高）">
        
        <select name="work_style_preference">
            <option value="">勤務形態の希望</option>
            <option value="office">オフィス勤務</option>
            <option value="remote">完全リモート</option>
            <option value="hybrid">ハイブリッド</option>
            <option value="flexible">柔軟対応</option>
        </select>
        
        <input name="available_start_date" type="date">
    </fieldset>
    
    <!-- スキル -->
    <fieldset>
        <legend>技術スキル</legend>
        <div>
            <h4>プログラミング言語（複数選択可）</h4>
            <input name="programming_languages[]" type="checkbox" value="javascript">
            <label>JavaScript</label>
            
            <input name="programming_languages[]" type="checkbox" value="typescript">
            <label>TypeScript</label>
            
            <input name="programming_languages[]" type="checkbox" value="python">
            <label>Python</label>
            
            <input name="programming_languages[]" type="checkbox" value="java">
            <label>Java</label>
            
            <input name="programming_languages[]" type="checkbox" value="php">
            <label>PHP</label>
            
            <input name="programming_languages[]" type="checkbox" value="golang">
            <label>Go</label>
            
            <input name="programming_languages[]" type="checkbox" value="rust">
            <label>Rust</label>
        </div>
        
        <div>
            <h4>フレームワーク・ライブラリ</h4>
            <input name="frameworks[]" type="checkbox" value="react">
            <label>React</label>
            
            <input name="frameworks[]" type="checkbox" value="vue">
            <label>Vue.js</label>
            
            <input name="frameworks[]" type="checkbox" value="angular">
            <label>Angular</label>
            
            <input name="frameworks[]" type="checkbox" value="nodejs">
            <label>Node.js</label>
            
            <input name="frameworks[]" type="checkbox" value="django">
            <label>Django</label>
            
            <input name="frameworks[]" type="checkbox" value="laravel">
            <label>Laravel</label>
        </div>
        
        <textarea name="technical_experience" placeholder="これまでの技術的な経験や実績について詳しく教えてください" required></textarea>
    </fieldset>
    
    <!-- 経歴 -->
    <fieldset>
        <legend>職歴</legend>
        <textarea name="work_experience" placeholder="これまでの職歴を時系列で記載してください" required></textarea>
        <input name="total_experience_years" type="number" min="0" placeholder="総職務経験年数">
        <input name="programming_experience_years" type="number" min="0" placeholder="プログラミング経験年数">
    </fieldset>
    
    <!-- 学歴 -->
    <fieldset>
        <legend>学歴</legend>
        <select name="education_level">
            <option value="">最終学歴</option>
            <option value="high_school">高等学校</option>
            <option value="vocational">専門学校</option>
            <option value="university">大学</option>
            <option value="graduate">大学院</option>
        </select>
        
        <input name="school_name" type="text" placeholder="学校名">
        <input name="major" type="text" placeholder="専攻分野">
        <input name="graduation_year" type="number" min="1950" max="2030" placeholder="卒業年">
    </fieldset>
    
    <!-- 応募動機 -->
    <fieldset>
        <legend>応募動機</legend>
        <textarea name="motivation" placeholder="当社への応募動機を教えてください" required></textarea>
        <textarea name="career_goals" placeholder="今後のキャリアゴールについて教えてください"></textarea>
    </fieldset>
    
    <!-- 添付ファイル -->
    <fieldset>
        <legend>添付ファイル</legend>
        <input name="resume" type="file" accept=".pdf,.doc,.docx" required>
        <label>履歴書（PDF, Word形式）</label>
        
        <input name="portfolio" type="file" accept=".pdf,.zip">
        <label>ポートフォリオ（任意）</label>
        
        <input name="cover_letter" type="file" accept=".pdf,.doc,.docx">
        <label>職務経歴書（任意）</label>
    </fieldset>
    
    <!-- 質問 -->
    <fieldset>
        <legend>質問・その他</legend>
        <textarea name="questions_for_company" placeholder="弊社に対する質問があればご記入ください"></textarea>
        
        <input name="referral_source" type="text" placeholder="求人をどちらでお知りになりましたか？">
        
        <input name="contact_permission" type="checkbox" required>
        <label>選考に関する連絡を受け取ることに同意します</label>
    </fieldset>
    
    <button type="submit">応募する</button>
</form>
```
