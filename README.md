# 🍳 TubeChef (チューブシェフ)

**動画の「おいしそう」を、文字の「見やすさ」に。**

🌍 **アプリURL**: [https://app.cosmethod.com](https://app.cosmethod.com)

**フロントエンド**: [https://github.com/m0xyu/tubechef-frontend](https://github.com/m0xyu/tubechef-frontend)

動画を何度も止めたり、巻き戻したりする必要はありません。
TubeChefは、YouTubeの料理動画URLを入力するだけで、AIが動画から「材料」と「手順」を自動で書き起こすWebアプリケーションです。
動画を見る時間を最小限にショートカットし、あなたの料理体験をもっとスムーズにします。

---

## 🛠️ Tech Stack

| Category           | Technology                                              |
| :----------------- | :------------------------------------------------------ |
| **Frontend**       | React, TanStack Router, Tailwind CSS, Vite              |
| **Backend**        | Laravel 11, PHP 8.4, Laravel Sanctum (SPA Auth)         |
| **AI Microservice**| Go, chi v5                                              |
| **Infrastructure** | Docker, MySQL 8.0, Redis, Cloudflare (DNS / Pages), VPS |
| **Testing**        | Pest (PHP), Go testing                                  |
| **AI / API**       | YouTube Data API v3, Gemini API                         |

---

## 🏗️ Architecture & Design

「保守性・拡張性・テスト容易性」を重視したモダンなバックエンド設計を取り入れています。

- **Fat Controllerの排除 (Action & DTO)**
  複雑なビジネスロジックは単一責任の `Action` クラスに分離し、データのやり取りには配列ではなく型安全な **DTO (Data Transfer Object)** を採用しています。
- **LLM連携のマイクロサービス化 + インターフェース抽象化**
  Gemini APIとの通信は **Go製のAIマイクロサービス** (`ai-recipe-service`) に分離。Laravel側は `LLMServiceInterface` を介してHTTP呼び出しするだけで、AIプロバイダーの実装詳細に依存しない設計にしています。将来的なモデル変更（OpenAI等）もインターフェースの差し替えのみで対応可能です。
- **堅牢なエラーハンドリング**
  PHP 8.1の Enum と独自例外クラスを組み合わせ、マジックナンバーを排除した厳密なステータス管理を行っています。Go側も `domain` パッケージのセンチネルエラーで統一しています。

---

## 🔥 Technical Highlights

バックエンドのパフォーマンスと拡張性を意識し、以下の設計・実装を行っています。

### 非同期処理によるUX向上とスケーラビリティ

LLM（Gemini）によるレシピ生成処理は数秒〜数十秒の待機時間が発生するため、同期処理ではUXを著しく損なう懸念がありました。
これを解決するため、**Redis + Laravel Worker** を用いた非同期処理（Job）アーキテクチャを導入。即時にレスポンスを返しつつ、裏側で安全にタスクを処理するスケーラブルな構成を実現しています。

### Go製AIマイクロサービスによる責務分離

Gemini APIとのやり取りは **`ai-recipe-service`（Go / chi v5）** として独立したマイクロサービスに切り出しています。Laravel Workerがジョブを処理する際、`GoLLMService` 経由でこのサービスにHTTP POSTし、構造化されたレシピデータを受け取ります。

- **Laravel側**: ビジネスロジック・DB永続化・認証・キュー管理に専念
- **Go側**: Gemini APIとのプロトコル処理・プロンプト構築・レスポンスパースに専念

`domain` パッケージにインターフェースとセンチネルエラーを集約し、外部依存ゼロで純粋なGoコードとして維持しています。

---

## 🗄️ データベース設計（ER図）

本プロジェクトでは、データの整合性とクエリパフォーマンスを両立させるため、適切な正規化とインデックス設計を行っています。YouTubeからのメタデータ（`videos`）と、AIによって生成された構造化データ（`recipes`）を分離し、拡張性の高い構造にしています。

### ER図

```mermaid
erDiagram
    users ||--o{ video_user : "動画の要約をリクエストしたユーザー"
    videos ||--o{ video_user : ""
    channels ||--o{ videos : "Youtubeチャンネル"
    videos ||--o| recipes : "動画のSource"
    dishes ||--o{ recipes : "動画の料理名(カテゴリ)"
    recipes ||--o{ recipe_ingredients : "材料"
    recipes ||--o{ recipe_steps : "手順"
    recipes ||--o{ recipe_tips : "コツ"
    recipe_steps ||--o{ recipe_tips : "手順に関連するコツ"

    users {
        bigint id PK
        string name
        string email "UK"
        timestamp email_verified_at
        string password
    }

    channels {
        bigint id PK
        string channel_id "UK: YouTube ID"
        string name
        text description
        string thumbnail_url
        string custom_url
        bigint view_count
        bigint video_count
        bigint subscriber_count
    }

    videos {
        bigint id PK
        string video_id "UK: YouTube ID"
        bigint channel_id FK
        string title
        text description
        string thumbnail_url
        bigint view_count "Indexed"
        json topic_categories
        int duration "Seconds"
        string recipe_generation_status "pending/processing/etc"
        text recipe_generation_error_message
        int generation_retry_count
    }

    video_user {
        bigint user_id FK
        bigint video_id FK
    }

    dishes {
        bigint id PK
        bigint parent_id FK "Self-reference (Parent Category)"
        string name
        string slug "UK"
        text description
    }

    recipes {
        bigint id PK
        bigint video_id FK "UK"
        bigint dish_id FK
        string slug
        string title
        text summary
        string serving_size
        int cooking_time "Minutes"
    }

    recipe_ingredients {
        bigint id PK
        bigint recipe_id FK
        string group "調味料、食材、その他"
        string name
        string quantity
        smallint order "Sort Order"
    }

    recipe_steps {
        bigint id PK
        bigint recipe_id FK
        smallint step_number
        text description
        int start_time_in_seconds "Video timestamp (start)"
        int end_time_in_seconds "Video timestamp (end)"
    }

    recipe_tips {
        bigint id PK
        bigint recipe_id FK
        bigint recipe_step_id FK "Optional"
        text description
        int start_time_in_seconds
    }
```

## 💻 Local Setup

Docker (Laravel Sail ベース) を使用して簡単に立ち上げ可能です。

```
git clone [https://github.com/yourusername/tubechef.git](https://github.com/yourusername/tubechef.git)
cd tubechef
cp .env.example .env

# Composer 依存関係のインストール (ローカルに PHP がない場合、Sail の一時コンテナを利用)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# コンテナのビルドと起動（バックグラウンド）
./vendor/bin/sail up -d

# アプリケーションキーの生成
./vendor/bin/sail artisan key:generate

# データベースのマイグレーション
./vendor/bin/sail artisan migrate
```
