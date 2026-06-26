# 智慧手機型號推薦網站

這是一個 PHP + SQLite 的手機推薦系統範例，包含前台需求問卷、七維度推薦、雷達圖、收藏、規格對比，以及管理者控制台與爬蟲。

## 功能

- 一般使用者：註冊登入、填答需求問卷、取得推薦結果、收藏手機、比較多款手機規格。
- 管理者：新增、編輯、刪除、查詢手機資料。
- 權重管理：可調整七維度底下各規格的評分權重。
- 精準度設定：可調整推薦匹配的最大容忍誤差。
- 爬蟲：可從後台貼上手機規格頁網址，或用命令列批次爬取。
- 資料庫：使用 SQLite，第一次執行 `install.php` 會建立資料表與範例資料。

## 主要檔案

- `install.php`：初始化資料庫、建立範例帳號與手機資料。
- `index.php`：首頁。
- `questionnaire.php`：需求問卷。
- `recommend.php`：推薦結果。
- `favorites.php`：收藏手機。
- `compare.php`：多款手機規格與七維度對比。
- `admin/`：管理者控制台。
- `crawler/PhoneCrawler.php`：爬蟲與規格解析核心。
- `crawler/run_crawler.php`：命令列爬蟲入口。
- `data/database.sqlite`：初始化後產生的資料庫。

## 初始帳號

先執行 `install.php` 後會建立：

- 管理者：admin@example.com / admin123
- 一般使用者：user@example.com / user123

## 使用方式

1. 將整個資料夾放到支援 PHP 8 與 SQLite PDO 的環境。
2. 先開啟或執行 `install.php`。
3. 回到 `index.php` 開始使用。

命令列爬蟲範例：

```bash
php crawler/run_crawler.php https://example.com/phone-spec-page
```

也可以把網址陣列放進 `crawler/sources.json` 後執行：

```bash
php crawler/run_crawler.php
```

## 七維度評分

- 螢幕：面板種類、解析度、PPI、更新率、觸控採樣率、亮度
- 性能：CPU 型號、安兔兔跑分
- 儲存：RAM、ROM 容量
- 相機：主鏡頭、超廣角、長焦、微距、前鏡頭、錄影規格
- 續航：電池容量、有線充電瓦數、無線充電瓦數
- 通訊：5G 頻段、Wi-Fi、藍牙、eSIM
- 功能：指紋辨識、臉部辨識、防水、散熱

