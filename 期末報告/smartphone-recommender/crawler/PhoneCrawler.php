<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

class PhoneCrawler
{
    public function crawlUrl(string $url): array
    {
        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'message' => '網址格式不正確：' . $url];
        }

        $html = $this->download($url);
        if ($html === '') {
            $this->log($url, 'failed', '下載失敗');
            return ['ok' => false, 'message' => '下載失敗：' . $url];
        }

        $cacheName = CRAWLER_CACHE_DIR . '/' . sha1($url) . '.html';
        if (!is_dir(CRAWLER_CACHE_DIR)) {
            mkdir(CRAWLER_CACHE_DIR, 0775, true);
        }
        file_put_contents($cacheName, $html);

        $parsed = $this->parseHtml($html, $url);
        $phoneId = $this->savePhone($parsed);

        $message = '已擷取並儲存：' . $parsed['brand'] . ' ' . $parsed['model'];
        $this->log($url, 'success', $message);

        return [
            'ok' => true,
            'message' => $message,
            'phone_id' => $phoneId,
            'phone' => $parsed,
        ];
    }

    private function download(string $url, int $timeout = 20): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_USERAGENT => 'Mozilla/5.0 SmartphoneRecommenderCrawler/1.0',
            ]);
            $html = curl_exec($ch);
            curl_close($ch);
            return is_string($html) ? $html : '';
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'header' => "User-Agent: Mozilla/5.0 SmartphoneRecommenderCrawler/1.0\r\n",
            ],
        ]);
        $html = @file_get_contents($url, false, $context);
        return is_string($html) ? $html : '';
    }

    public function parseHtml(string $html, string $url = ''): array
    {
        $title = $this->extractTitle($html);
        $pairs = $this->extractSpecPairs($html);
        $text = $this->htmlToText($html);

        $isAppleSpec = $this->isAppleSpecPage($url, $title, $text);
        $appleDisplayText = $isAppleSpec ? $this->extractSection($text, '顯示器', ['防潑', 'Apple Intelligence', '晶片']) : '';
        $appleCapacityText = $isAppleSpec ? $this->extractSection($text, '容量', ['尺寸與重量', '顯示器']) : '';
        $appleChipText = $isAppleSpec ? $this->extractSection($text, '晶片', ['相機', '錄影']) : '';
        $appleCameraText = $isAppleSpec ? $this->extractFirstSection($text, ['相機'], ['錄影', '影片拍攝', '前置相機', 'TrueDepth 相機', '電源與電池', 'Face ID']) : '';
        $appleVideoText = $isAppleSpec ? $this->extractFirstSection($text, ['錄影', '影片拍攝'], ['前置相機', 'TrueDepth 相機', '電源與電池', 'Face ID']) : '';
        $appleFrontCameraText = $isAppleSpec ? $this->extractFirstSection($text, ['前置相機', '原深感測相機', 'TrueDepth 相機'], ['電源與電池', 'Face ID', 'Apple Pay']) : '';
        $appleBatteryText = $isAppleSpec ? $this->extractSection($text, '電源與電池', ['感測器', '作業系統', 'Face ID', '安全功能', '行動網路', '充電與擴充']) : '';
        $appleNetworkText = $isAppleSpec ? $this->extractFirstSection($text, ['行動網路與無線技術', '行動網路與無線連線', '行動網路'], ['定位功能', '視訊通話', '音訊通話', '外部按鈕', '電源與電池']) : '';
        $appleChargingText = $isAppleSpec ? $this->extractSection($text, '充電與擴充', ['感測器', 'SIM 卡']) : '';
        $appleSimText = $isAppleSpec ? $this->extractSection($text, 'SIM 卡', ['進一步了解出國', '環境需求']) : '';

        [$brand, $model] = $this->guessBrandModel($title, $pairs, $url, $text);

        $screenText = $this->collectValues($pairs, ['display', 'screen', '螢幕', '屏幕', '解析度', '更新率'])
            ?: $appleDisplayText;
        $ramText = $this->findExactValue($pairs, ['RAM記憶體', 'RAM', '記憶體', '內存'])
            ?: $this->collectValues($pairs, ['ram', '記憶體', '內存']);
        $romText = $this->findExactValue($pairs, ['ROM儲存空間', 'ROM', '儲存空間', '儲存容量'])
            ?: $this->collectValues($pairs, ['rom', 'storage', '儲存', '容量'])
            ?: $appleCapacityText;
        $storageText = $this->collectValues($pairs, ['ram', 'rom', 'storage', 'memory', '記憶體', '內存', '儲存', '容量']);
        $batteryText = $this->collectValues($pairs, ['battery', '電池', '續航', '充電'])
            ?: trim($appleBatteryText . ' ' . $appleChargingText);
        $cameraText = $this->collectValues($pairs, ['camera', '相機', '鏡頭', '攝影', '拍照'])
            ?: trim($appleCameraText . ' ' . $appleFrontCameraText);
        $mainCameraText = $this->findExactValue($pairs, ['主相機畫素', '主鏡頭畫素'])
            ?: $this->findValue($pairs, ['main camera', '主相機', '主鏡頭'])
            ?: $appleCameraText;
        $ultrawideCameraText = $this->findExactValue($pairs, ['第二主相機畫素'])
            ?: $this->findValue($pairs, ['超廣角', 'ultrawide', 'ultra-wide'])
            ?: $appleCameraText;
        $telephotoCameraText = $this->findExactValue($pairs, ['第三主相機畫素'])
            ?: $this->findValue($pairs, ['長焦', '望遠', 'telephoto', 'periscope'])
            ?: $appleCameraText;
        $frontCameraText = $this->findExactValue($pairs, ['前相機畫素', '前鏡頭畫素'])
            ?: $this->findValue($pairs, ['selfie', 'front camera', '前鏡頭', '前置', '自拍'])
            ?: $appleFrontCameraText;
        $screenSize = $this->findExactValue($pairs, ['主螢幕尺寸', '螢幕尺寸', '主屏幕尺寸', 'screen size'])
            ?: $this->parseScreenSize($screenText)
            ?: $this->parseScreenSize($appleDisplayText);
        $releaseDate = $this->parseReleaseDate(
            $this->findValue($pairs, ['release', '上市', '發表', '推出', 'release date']) ?: $text
        );
        $ramGb = $this->parseRamGb($ramText);
        if ($ramGb <= 0 && $isAppleSpec) {
            $ramGb = $this->guessAppleRamGb($model);
        }
        if ($ramGb <= 0 && !$isAppleSpec) {
            $ramGb = $this->parseRamGb($storageText);
        }

        $data = [
            'brand' => $brand,
            'model' => $model,
            'price' => $this->parsePrice($this->collectValues($pairs, ['price', '價格', '建議售價', '售價', '空機價']) ?: $text),
            'image_url' => $this->extractImage($html, $url, $brand . ' ' . $model, $isAppleSpec),
            'release_date' => $releaseDate,
            'panel_type' => $screenSize,
            'resolution' => $this->findExactValue($pairs, ['主螢幕解析度', '螢幕解析度'])
                ?: $this->findValue($pairs, ['resolution', '解析度'])
                ?: $this->parseResolution($screenText),
            'ppi' => $this->parseUnitNumber(
                $this->findExactValue($pairs, ['主螢幕像素密度', '螢幕像素密度'])
                    ?: $this->findValue($pairs, ['ppi', 'pixels per inch', '像素密度'])
                    ?: $appleDisplayText,
                ['ppi'],
                700
            ),
            'refresh_rate' => $this->parseUnitNumber($this->collectValues($pairs, ['refresh', '更新率', '螢幕更新率']) ?: $screenText, ['hz', '赫茲'], 360),
            'touch_sampling_rate' => $this->parseUnitNumber($this->collectValues($pairs, ['touch sampling', '觸控採樣率']), ['hz', '赫茲'], 1000),
            'brightness' => $this->parseMaxUnitNumber($this->collectValues($pairs, ['brightness', '亮度', '峰值亮度']) ?: $appleDisplayText, ['nits', 'nit', '尼特'], 6000),
            'cpu' => $this->extractCpu($pairs, $appleChipText ?: $text),
            'antutu_score' => $this->parseAntutu($this->collectValues($pairs, ['antutu', '安兔兔', '跑分', 'benchmark'])),
            'ram_gb' => $ramGb,
            'rom_gb' => $this->parseStorageGb($romText ?: $storageText),
            'battery_mah' => $this->parseUnitNumber($batteryText, ['mah', '毫安時', '毫安'], 12000, 1000),
            'wired_charging_w' => $isAppleSpec
                ? $this->parseAppleWiredChargingWatts($appleBatteryText ?: $batteryText)
                : $this->parseChargingWatts($this->collectValues($pairs, ['wired', '有線充電', '快充', '充電']) ?: $batteryText, false),
            'wireless_charging_w' => $isAppleSpec
                ? $this->parseAppleWirelessChargingWatts(trim($appleChargingText . ' ' . $appleBatteryText))
                : $this->parseChargingWatts($this->collectValues($pairs, ['wireless charging', '無線充電', '無線']) ?: $appleChargingText, true),
            'main_camera_mp' => $isAppleSpec ? $this->parseAppleCameraMp($appleCameraText, $appleFrontCameraText, 'main') : $this->parseCameraMp($mainCameraText ?: $cameraText, 'main'),
            'ultrawide_camera_mp' => $isAppleSpec ? $this->parseAppleCameraMp($appleCameraText, $appleFrontCameraText, 'ultrawide') : $this->parseCameraMp($ultrawideCameraText ?: $text, 'ultrawide'),
            'telephoto_camera_mp' => $isAppleSpec ? $this->parseAppleCameraMp($appleCameraText, $appleFrontCameraText, 'telephoto') : $this->parseCameraMp($telephotoCameraText ?: $cameraText, 'telephoto'),
            'macro_camera_mp' => $isAppleSpec ? $this->parseAppleCameraMp($appleCameraText, $appleFrontCameraText, 'macro') : $this->parseCameraMp($cameraText, 'macro'),
            'front_camera_mp' => $isAppleSpec ? $this->parseAppleCameraMp($appleCameraText, $appleFrontCameraText, 'front') : $this->parseCameraMp($frontCameraText ?: $text, 'front'),
            'video_spec' => $this->summarizeVideoSpec($this->findValue($pairs, ['video', '錄影', '錄影規格']) ?: ($appleVideoText ?: $text)),
            'fiveg_bands' => $this->findValue($pairs, ['5g bands', '5g頻段', '網路頻段']) ?: $this->guess5gBands($text),
            'wifi' => $isAppleSpec
                ? $this->summarizeWifiSpec($appleNetworkText ?: $text)
                : $this->summarizeWifiSpec($this->findValue($pairs, ['wi-fi', 'wifi', '無線網路']) ?: $text),
            'bluetooth' => $this->findExactValue($pairs, ['藍牙版本', 'Bluetooth版本', 'Bluetooth Version'])
                ?: $this->guessBluetooth($this->collectValues($pairs, ['bluetooth', '藍牙']) ?: ($appleNetworkText ?: $text)),
            'esim' => ($this->hasEsim($pairs) || ($isAppleSpec && $this->containsAny($appleSimText ?: $text, ['eSIM', 'esim']))) ? 1 : 0,
            'fingerprint' => (
                $this->hasTruthySpec($pairs, ['fingerprint', '指紋'])
                || $this->containsAny($text, ['fingerprint', '指紋'])
            ) ? 1 : 0,
            'face_unlock' => (
                $this->hasTruthySpec($pairs, ['face unlock', 'Face ID', '臉部辨識', '人臉辨識', '臉部解鎖'])
                || $this->containsAny($text, ['face unlock', 'Face ID', '臉部辨識', '人臉辨識', '臉部解鎖'])
            ) ? 1 : 0,
            'waterproof_rating' => $this->guessWaterproof($text),
            'cooling' => (
                $this->containsAny($text, ['cooling', 'vapor chamber', '散熱', '均熱板'])
                || $this->isAppleProModel($brand, $model)
            ) ? 1 : 0,
            'specs_json' => json_encode($pairs, JSON_UNESCAPED_UNICODE),
            'source_url' => $url,
        ];

        return $this->sanitizeData($data);
    }

    private function savePhone(array $phone): int
    {
        execute_sql(
            'INSERT INTO phones (
                brand, model, price, image_url, release_date, panel_type, resolution, ppi, refresh_rate,
                touch_sampling_rate, brightness, cpu, antutu_score, ram_gb, rom_gb, battery_mah,
                wired_charging_w, wireless_charging_w, main_camera_mp, ultrawide_camera_mp,
                telephoto_camera_mp, macro_camera_mp, front_camera_mp, video_spec, fiveg_bands,
                wifi, bluetooth, esim, fingerprint, face_unlock, waterproof_rating, cooling,
                specs_json, source_url, created_at, updated_at
            ) VALUES (
                :brand, :model, :price, :image_url, :release_date, :panel_type, :resolution, :ppi, :refresh_rate,
                :touch_sampling_rate, :brightness, :cpu, :antutu_score, :ram_gb, :rom_gb, :battery_mah,
                :wired_charging_w, :wireless_charging_w, :main_camera_mp, :ultrawide_camera_mp,
                :telephoto_camera_mp, :macro_camera_mp, :front_camera_mp, :video_spec, :fiveg_bands,
                :wifi, :bluetooth, :esim, :fingerprint, :face_unlock, :waterproof_rating, :cooling,
                :specs_json, :source_url, NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                price = VALUES(price),
                image_url = VALUES(image_url),
                release_date = VALUES(release_date),
                panel_type = VALUES(panel_type),
                resolution = VALUES(resolution),
                ppi = VALUES(ppi),
                refresh_rate = VALUES(refresh_rate),
                touch_sampling_rate = VALUES(touch_sampling_rate),
                brightness = VALUES(brightness),
                cpu = VALUES(cpu),
                antutu_score = VALUES(antutu_score),
                ram_gb = VALUES(ram_gb),
                rom_gb = VALUES(rom_gb),
                battery_mah = VALUES(battery_mah),
                wired_charging_w = VALUES(wired_charging_w),
                wireless_charging_w = VALUES(wireless_charging_w),
                main_camera_mp = VALUES(main_camera_mp),
                ultrawide_camera_mp = VALUES(ultrawide_camera_mp),
                telephoto_camera_mp = VALUES(telephoto_camera_mp),
                macro_camera_mp = VALUES(macro_camera_mp),
                front_camera_mp = VALUES(front_camera_mp),
                video_spec = VALUES(video_spec),
                fiveg_bands = VALUES(fiveg_bands),
                wifi = VALUES(wifi),
                bluetooth = VALUES(bluetooth),
                esim = VALUES(esim),
                fingerprint = VALUES(fingerprint),
                face_unlock = VALUES(face_unlock),
                waterproof_rating = VALUES(waterproof_rating),
                cooling = VALUES(cooling),
                specs_json = VALUES(specs_json),
                source_url = VALUES(source_url),
                updated_at = NOW()',
            $phone
        );

        $row = fetch_one('SELECT id FROM phones WHERE brand = ? AND model = ?', [$phone['brand'], $phone['model']]);
        return (int)($row['id'] ?? 0);
    }

    private function extractTitle(string $html): string
    {
        $metaTitle = $this->extractMeta($html, 'og:title')
            ?: $this->extractMeta($html, 'twitter:title');
        if ($metaTitle !== '') {
            return $metaTitle;
        }

        $dom = $this->loadDom($html);
        if ($dom instanceof DOMDocument) {
            $xpath = new DOMXPath($dom);
            foreach (['//h1', '//title'] as $query) {
                $nodes = $xpath->query($query);
                if ($nodes !== false && $nodes->length > 0) {
                    $title = $this->cleanText($nodes->item(0)->textContent ?? '');
                    if ($title !== '') {
                        return $title;
                    }
                }
            }
        }

        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return $this->cleanText($matches[1]);
        }

        return '未命名手機';
    }

    private function extractImage(string $html, string $url = '', string $model = '', bool $preferAppleTopImage = false): string
    {
        if ($preferAppleTopImage) {
            $appleImage = $this->extractAppleTopImage($html, $url, $model);
            if ($appleImage !== '') {
                return $appleImage;
            }
        }

        $metaImage = $this->extractMeta($html, 'og:image')
            ?: $this->extractMeta($html, 'twitter:image');

        return $this->absoluteUrl($metaImage, $url);
    }

    private function extractMeta(string $html, string $name): string
    {
        $dom = $this->loadDom($html);
        if (!$dom instanceof DOMDocument) {
            return '';
        }

        $xpath = new DOMXPath($dom);
        $escaped = $this->xpathLiteral($name);
        $nodes = $xpath->query("//meta[translate(@property, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = {$escaped} or translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = {$escaped}]/@content");
        if ($nodes !== false && $nodes->length > 0) {
            return $this->cleanText($nodes->item(0)->nodeValue ?? '');
        }

        return '';
    }

    private function extractAppleTopImage(string $html, string $url, string $model): string
    {
        $dom = $this->loadDom($html);
        if (!$dom instanceof DOMDocument) {
            return '';
        }

        $xpath = new DOMXPath($dom);
        $slugs = $this->modelImageSlugs($model);

        $finishImage = $this->extractAppleFinishImage($html, $xpath, $url, $slugs);
        if ($finishImage !== '') {
            return $finishImage;
        }

        $localnavImage = $this->extractAppleLocalnavImage($xpath, $url, $slugs);
        if ($localnavImage !== '') {
            return $localnavImage;
        }

        return $this->extractFirstUsablePageImage($xpath, $url, $slugs);
    }

    private function extractAppleFinishImage(string $html, DOMXPath $xpath, string $url, array $slugs): string
    {
        $queries = [
            '//figure[contains(@class, "image-finish") or contains(@class, "image-hero") or @data-anim-lazy-image]',
            '//*[contains(@class, "hero") or contains(@class, "finish")]//picture',
        ];
        $classes = [];

        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes === false) {
                continue;
            }

            foreach ($nodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }

                $image = $this->extractImageFromElement($node, $url);
                if ($image !== '') {
                    return $image;
                }

                $classAttribute = $node->getAttribute('class');
                foreach (preg_split('/\s+/', $classAttribute) ?: [] as $className) {
                    $className = trim($className);
                    if ($className !== '' && preg_match('/(?:^image-|finish|hero|iphone)/i', $className)) {
                        $classes[] = $className;
                    }
                }

                if (count($classes) >= 8) {
                    break 2;
                }
            }
        }

        $classes = array_values(array_unique($classes));
        if ($classes === []) {
            return '';
        }

        return $this->extractAppleImageFromStylesheets($html, $url, $classes, $slugs);
    }

    private function extractAppleLocalnavImage(DOMXPath $xpath, string $url, array $slugs): string
    {
        $nodes = $xpath->query('//picture[contains(concat(" ", normalize-space(@class), " "), " product-image ")]');
        if ($nodes === false) {
            return '';
        }

        $fallback = '';
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $image = $this->extractImageFromElement($node, $url);
            if ($image === '') {
                continue;
            }

            $fingerprint = $this->imageFingerprint(
                $node->getAttribute('id') . ' ' . $node->getAttribute('class') . ' ' . $this->imageFileToken($image)
            );
            foreach ($slugs as $slug) {
                if ($this->fingerprintHasSlug($fingerprint, $slug)) {
                    return $image;
                }
            }

            if ($fallback === '' && str_contains($fingerprint, 'iphone')) {
                $fallback = $image;
            }
        }

        return $fallback;
    }

    private function extractFirstUsablePageImage(DOMXPath $xpath, string $url, array $slugs): string
    {
        $nodes = $xpath->query('(//main//*[self::picture or self::img] | //body//*[self::picture or self::img])[position() <= 40]');
        if ($nodes === false) {
            return '';
        }

        $fallback = '';
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $image = $this->extractImageFromElement($node, $url);
            if ($image === '') {
                continue;
            }

            $fingerprint = $this->imageFingerprint(
                $node->getAttribute('id') . ' ' . $node->getAttribute('class') . ' ' . $this->imageFileToken($image)
            );
            foreach ($slugs as $slug) {
                if ($this->fingerprintHasSlug($fingerprint, $slug)) {
                    return $image;
                }
            }

            if ($fallback === '' && str_contains($fingerprint, 'iphone')) {
                $fallback = $image;
            }
        }

        return $fallback;
    }

    private function extractImageFromElement(DOMElement $element, string $baseUrl): string
    {
        foreach (['srcset', 'data-srcset'] as $attribute) {
            $image = $this->imageFromSrcset($element->getAttribute($attribute), $baseUrl);
            if ($image !== '') {
                return $image;
            }
        }

        foreach (['src', 'data-src', 'data-original', 'href'] as $attribute) {
            $image = $this->resolveImageCandidate($element->getAttribute($attribute), $baseUrl);
            if ($image !== '') {
                return $image;
            }
        }

        $image = $this->imageFromCssValue($element->getAttribute('style'), $baseUrl);
        if ($image !== '') {
            return $image;
        }

        $document = $element->ownerDocument;
        if (!$document instanceof DOMDocument) {
            return '';
        }

        $xpath = new DOMXPath($document);
        $children = $xpath->query('.//*[self::source or self::img]', $element);
        if ($children === false) {
            return '';
        }

        foreach ($children as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            foreach (['srcset', 'data-srcset'] as $attribute) {
                $image = $this->imageFromSrcset($child->getAttribute($attribute), $baseUrl);
                if ($image !== '') {
                    return $image;
                }
            }

            foreach (['src', 'data-src', 'data-original'] as $attribute) {
                $image = $this->resolveImageCandidate($child->getAttribute($attribute), $baseUrl);
                if ($image !== '') {
                    return $image;
                }
            }
        }

        return '';
    }

    private function extractAppleImageFromStylesheets(string $html, string $url, array $classNames, array $slugs): string
    {
        $stylesheets = $this->extractStylesheetUrls($html, $url);
        foreach ($stylesheets as $stylesheetUrl) {
            $css = $this->download($stylesheetUrl, 8);
            if ($css === '') {
                continue;
            }

            $image = $this->extractImageFromCss($css, $stylesheetUrl, $classNames, $slugs);
            if ($image !== '') {
                return $image;
            }
        }

        return '';
    }

    private function extractStylesheetUrls(string $html, string $url): array
    {
        $dom = $this->loadDom($html);
        if (!$dom instanceof DOMDocument) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//link[contains(translate(@rel, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "stylesheet")]/@href');
        if ($nodes === false) {
            return [];
        }

        $urls = [];
        foreach ($nodes as $node) {
            $href = $this->absoluteUrl($node->nodeValue ?? '', $url);
            if ($href === '' || !str_contains($this->lower($href), 'apple.com')) {
                continue;
            }
            if (!preg_match('/(?:iphone|specs|overview|built)\S*\.css/i', $href)) {
                continue;
            }
            $urls[] = $href;
        }

        $urls = array_values(array_unique($urls));
        usort($urls, static function (string $a, string $b): int {
            $score = static function (string $value): int {
                $value = strtolower($value);
                return (str_contains($value, 'specs') ? 2 : 0)
                    + (str_contains($value, 'built') ? 1 : 0);
            };
            return $score($b) <=> $score($a);
        });

        return array_slice($urls, 0, 3);
    }

    private function extractImageFromCss(string $css, string $baseUrl, array $classNames, array $slugs): string
    {
        $css = preg_replace('/\/\*.*?\*\//s', '', $css) ?? $css;
        $bestImage = '';
        $bestScore = PHP_INT_MIN;

        foreach ($classNames as $className) {
            $className = trim($className);
            if ($className === '') {
                continue;
            }

            $classPattern = preg_quote('.' . $className, '/');
            if (!preg_match_all('/[^{}]*' . $classPattern . '[^{]*\{([^{}]*)\}/i', $css, $blocks)) {
                continue;
            }

            foreach ($blocks[1] as $block) {
                if (!preg_match_all('/url\((["\']?)(.*?)\1\)/i', $block, $matches)) {
                    continue;
                }

                foreach ($matches[2] as $candidate) {
                    $image = $this->resolveImageCandidate($candidate, $baseUrl);
                    if ($image === '') {
                        continue;
                    }

                    $score = $this->scoreAppleImageUrl($image, $slugs);
                    if ($score > $bestScore) {
                        $bestImage = $image;
                        $bestScore = $score;
                    }
                }
            }
        }

        return $bestImage;
    }

    private function imageFromSrcset(string $srcset, string $baseUrl): string
    {
        $srcset = trim($srcset);
        if ($srcset === '') {
            return '';
        }

        $bestCandidate = '';
        $bestScore = PHP_INT_MIN;
        foreach (explode(',', $srcset) as $candidate) {
            $parts = preg_split('/\s+/', trim($candidate)) ?: [];
            $candidateUrl = $parts[0] ?? '';
            if ($candidateUrl === '') {
                continue;
            }

            $score = 1;
            foreach (array_slice($parts, 1) as $descriptor) {
                if (preg_match('/^(\d+(?:\.\d+)?)x$/i', $descriptor, $matches)) {
                    $score += (int)round((float)$matches[1] * 1000);
                } elseif (preg_match('/^(\d+)w$/i', $descriptor, $matches)) {
                    $score += (int)$matches[1];
                }
            }
            if (preg_match('/(?:_2x|@2x|xlarge|large)/i', $candidateUrl)) {
                $score += 200;
            }

            if ($score > $bestScore) {
                $bestCandidate = $candidateUrl;
                $bestScore = $score;
            }
        }

        return $this->resolveImageCandidate($bestCandidate, $baseUrl);
    }

    private function imageFromCssValue(string $value, string $baseUrl): string
    {
        if (!preg_match_all('/url\((["\']?)(.*?)\1\)/i', $value, $matches)) {
            return '';
        }

        foreach ($matches[2] as $candidate) {
            $image = $this->resolveImageCandidate($candidate, $baseUrl);
            if ($image !== '') {
                return $image;
            }
        }

        return '';
    }

    private function resolveImageCandidate(string $candidate, string $baseUrl): string
    {
        $candidate = html_entity_decode(trim($candidate, " \t\n\r\0\x0B\"'"), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($candidate === '' || preg_match('/^data:/i', $candidate)) {
            return '';
        }

        $url = $this->absoluteUrl($candidate, $baseUrl);
        return $this->isUsableProductImageUrl($url) ? $url : '';
    }

    private function absoluteUrl(string $assetUrl, string $baseUrl): string
    {
        $assetUrl = html_entity_decode(trim($assetUrl, " \t\n\r\0\x0B\"'"), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($assetUrl === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $assetUrl)) {
            return $assetUrl;
        }
        if (str_starts_with($assetUrl, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $assetUrl;
        }

        $base = parse_url($baseUrl);
        $host = $base['host'] ?? '';
        if ($host === '') {
            return $assetUrl;
        }

        $scheme = $base['scheme'] ?? 'https';
        $port = isset($base['port']) ? ':' . $base['port'] : '';
        if (str_starts_with($assetUrl, '/')) {
            return $scheme . '://' . $host . $port . $assetUrl;
        }

        $path = $base['path'] ?? '/';
        $directory = preg_replace('#/[^/]*$#', '/', $path) ?: '/';
        return $scheme . '://' . $host . $port . $this->normalizeUrlPath($directory . $assetUrl);
    }

    private function normalizeUrlPath(string $path): string
    {
        $segments = explode('/', $path);
        $normalized = [];
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($normalized);
                continue;
            }
            $normalized[] = $segment;
        }

        return '/' . implode('/', $normalized);
    }

    private function isUsableProductImageUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $lower = $this->lower($url);
        if (preg_match('/\.(?:svg|ico)(?:[?#]|$)/i', $lower)) {
            return false;
        }

        foreach (['favicon', 'globalnav', 'apple-logo', 'bag', 'search', 'compare', 'accessories', 'nav_shop', 'nav_ios', 'router', 'spinner'] as $blocked) {
            if (str_contains($lower, $blocked)) {
                return false;
            }
        }

        return preg_match('/\.(?:png|jpe?g|webp|avif)(?:[?#]|$)/i', $lower) === 1;
    }

    private function modelImageSlugs(string $model): array
    {
        $model = preg_replace('/\bapple\b/i', '', $model) ?? $model;
        $slug = $this->imageFingerprint($model);
        $slugs = [$slug];

        if (preg_match('/^iphone_(\d{1,2})_pro_max$/', $slug, $matches)) {
            $slugs[] = 'iphone_' . $matches[1] . '_pro';
            $slugs[] = 'iphone_' . $matches[1] . 'pro';
        } elseif (preg_match('/^iphone_(\d{1,2})_pro$/', $slug, $matches)) {
            $slugs[] = 'iphone_' . $matches[1] . 'pro';
        }

        return array_values(array_filter(array_unique($slugs)));
    }

    private function imageFingerprint(string $value): string
    {
        $value = $this->lower($value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;
        return trim($value, '_');
    }

    private function imageFileToken(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        return basename($path);
    }

    private function scoreAppleImageUrl(string $url, array $slugs): int
    {
        if (!$this->isUsableProductImageUrl($url)) {
            return PHP_INT_MIN;
        }

        $fingerprint = $this->imageFingerprint($url);
        $score = 0;
        foreach ($slugs as $slug) {
            if ($this->fingerprintHasSlug($fingerprint, $slug)) {
                $score += 120;
            }
        }
        foreach (['specs' => 80, 'finish' => 70, 'hero' => 40, 'iphone' => 20, 'large_2x' => 25, '_2x' => 20, 'large' => 10] as $needle => $points) {
            if (str_contains($fingerprint, $needle)) {
                $score += $points;
            }
        }

        return $score;
    }

    private function fingerprintHasSlug(string $fingerprint, string $slug): bool
    {
        if ($slug === '') {
            return false;
        }

        return preg_match('/(?:^|_)' . preg_quote($slug, '/') . '(?:_|$)/', $fingerprint) === 1;
    }

    private function extractSpecPairs(string $html): array
    {
        $pairs = [];
        $dom = $this->loadDom($html);
        if (!$dom instanceof DOMDocument) {
            return $pairs;
        }

        $xpath = new DOMXPath($dom);

        $rows = $xpath->query('//tr');
        if ($rows !== false) {
            foreach ($rows as $row) {
                $cells = $xpath->query('./th|./td', $row);
                if ($cells === false || $cells->length < 2) {
                    continue;
                }

                $key = $this->cleanText($cells->item(0)->textContent ?? '');
                $values = [];
                for ($i = 1; $i < $cells->length; $i++) {
                    $value = $this->cleanText($cells->item($i)->textContent ?? '');
                    if ($value !== '') {
                        $values[] = $value;
                    }
                }
                $this->addSpecPair($pairs, $key, implode(' / ', $values));
            }
        }

        $definitions = $xpath->query('//dt');
        if ($definitions !== false) {
            foreach ($definitions as $dt) {
                $next = $dt->nextSibling;
                while ($next !== null && !($next instanceof DOMElement)) {
                    $next = $next->nextSibling;
                }
                if ($next instanceof DOMElement && strtolower($next->tagName) === 'dd') {
                    $this->addSpecPair($pairs, $dt->textContent ?? '', $next->textContent ?? '');
                }
            }
        }

        $blocks = $xpath->query('//*[self::li or self::div or self::section]');
        if ($blocks !== false) {
            foreach ($blocks as $block) {
                $children = [];
                foreach ($block->childNodes as $child) {
                    if ($child instanceof DOMElement) {
                        $children[] = $child;
                    }
                }

                if (count($children) >= 2 && count($children) <= 6) {
                    $key = $this->cleanText($children[0]->textContent ?? '');
                    $values = [];
                    for ($i = 1; $i < count($children); $i++) {
                        $value = $this->cleanText($children[$i]->textContent ?? '');
                        if ($value !== '') {
                            $values[] = $value;
                        }
                    }
                    $this->addSpecPair($pairs, $key, implode(' / ', $values));
                }

                $text = $this->cleanText($block->textContent ?? '');
                if (preg_match('/^([^：:]{2,40})[：:]\s*(.{1,300})$/u', $text, $matches)) {
                    $this->addSpecPair($pairs, $matches[1], $matches[2]);
                }
            }
        }

        return $pairs;
    }

    private function addSpecPair(array &$pairs, string $key, string $value): void
    {
        $key = preg_replace('/[：:\s]+$/u', '', $this->cleanText($key)) ?? '';
        $value = $this->cleanText($value);

        if ($key === '' || $value === '' || $key === $value) {
            return;
        }

        if ($this->textLength($key) > 40 || $this->textLength($value) > 800) {
            return;
        }

        if (isset($pairs[$key])) {
            if (!str_contains($pairs[$key], $value)) {
                $pairs[$key] .= ' / ' . $value;
            }
            return;
        }

        $pairs[$key] = $value;
    }

    private function findValue(array $pairs, array $keywords): string
    {
        foreach ($pairs as $key => $value) {
            $lowerKey = $this->lower($key);
            foreach ($keywords as $keyword) {
                if (str_contains($lowerKey, $this->lower($keyword))) {
                    return trim((string)$value);
                }
            }
        }
        return '';
    }

    private function findExactValue(array $pairs, array $keys): string
    {
        $wanted = array_map(fn (string $key): string => $this->normalizeKey($key), $keys);
        foreach ($pairs as $key => $value) {
            if (in_array($this->normalizeKey($key), $wanted, true)) {
                return trim((string)$value);
            }
        }

        return '';
    }

    private function collectValues(array $pairs, array $keywords): string
    {
        $values = [];
        foreach ($pairs as $key => $value) {
            $lowerKey = $this->lower($key);
            foreach ($keywords as $keyword) {
                if (str_contains($lowerKey, $this->lower($keyword))) {
                    $cleanValue = trim((string)$value);
                    if ($cleanValue !== '' && !in_array($cleanValue, $values, true)) {
                        $values[] = $cleanValue;
                    }
                    break;
                }
            }
        }

        return implode(' / ', $values);
    }

    private function guessBrandModel(string $title, array $pairs, string $url = '', string $text = ''): array
    {
        $name = $this->findExactValue($pairs, ['產品名稱', '商品名稱', '手機名稱', '產品型號', '型號', 'model', 'name']);
        if (!$this->looksLikePhoneName($name)) {
            $name = $this->extractNameFromTitle($title);
        }
        if (!$this->looksLikePhoneName($name)) {
            $name = $this->extractNameFromText($text);
        }
        if (!$this->looksLikePhoneName($name)) {
            $name = $this->extractNameFromUrl($url);
        }

        $name = $this->cleanProductName($name ?: '未命名手機');

        $knownBrands = [
            'Apple', 'Samsung', 'Google', 'ASUS', 'Sony', 'Xiaomi', 'Redmi', 'POCO',
            'OPPO', 'vivo', 'realme', 'OnePlus', 'Nothing', 'Motorola', 'Nokia',
            'HTC', 'Honor', 'Huawei', 'Sharp', 'Infinix', 'Tecno',
        ];

        if (preg_match('/^iPhone\b/i', $name)) {
            return ['Apple', $name];
        }

        foreach ($knownBrands as $brand) {
            if (stripos($name, $brand) !== false) {
                $model = trim(preg_replace('/' . preg_quote($brand, '/') . '/i', '', $name, 1) ?? '');
                return [$brand, $model !== '' ? $model : $name];
            }
        }

        $parts = preg_split('/\s+/', $name, 2);
        return [$parts[0] ?: 'Unknown', $parts[1] ?? $name];
    }

    private function extractNameFromTitle(string $title): string
    {
        return $this->cleanProductName($title);
    }

    private function extractNameFromText(string $text): string
    {
        if (preg_match('/\b(iPhone\s+\d{1,2}(?:\s*(?:Pro Max|Pro|Plus|Air|mini))?)\b/iu', $text, $matches)) {
            return $this->cleanProductName($matches[1]);
        }

        if (preg_match('/\b((?:POCO|Redmi|Xiaomi|Samsung|ASUS|Sony|OPPO|vivo|realme|OnePlus|Nothing|Google)\s+[A-Za-z0-9][A-Za-z0-9 +.-]{1,40})\b/iu', $text, $matches)) {
            return $this->cleanProductName($matches[1]);
        }

        return '';
    }

    private function extractNameFromUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $path)));
        if (!$segments) {
            return '';
        }

        $slug = end($segments);
        if (is_numeric($slug) && count($segments) >= 2) {
            $slug = $segments[count($segments) - 2];
        }

        $slug = preg_replace('/-\d+$/', '', (string)$slug) ?? (string)$slug;
        $name = str_replace(['-', '_'], ' ', $slug);
        $name = preg_replace_callback('/\b[a-z]/', static fn (array $matches): string => strtoupper($matches[0]), $name) ?? $name;
        return $this->cleanProductName($name);
    }

    private function cleanProductName(string $name): string
    {
        $name = $this->cleanText($name);
        $name = preg_replace('/\s*[-|｜].*$/u', '', $name) ?? $name;
        $name = preg_replace('/\s*(技術規格|規格|價格|評測|開箱|手機介紹|產品介紹|上市日期).*$/u', '', $name) ?? $name;
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        return trim($name);
    }

    private function looksLikePhoneName(string $name): bool
    {
        $name = $this->cleanProductName($name);
        if ($name === '' || $this->textLength($name) > 80) {
            return false;
        }

        if (preg_match('/(Dimensity|Snapdragon|Helio|Exynos|Tensor|Kirin|MediaTek|Qualcomm|處理器|晶片|CPU|GPU)/iu', $name)) {
            return false;
        }

        return preg_match('/(iPhone|POCO|Redmi|Xiaomi|Samsung|Galaxy|Pixel|ASUS|Zenfone|ROG|Sony|Xperia|OPPO|vivo|realme|OnePlus|Nothing|Moto|Motorola|Nokia|HTC|Honor|Huawei|Sharp)/iu', $name) === 1
            || preg_match('/[A-Za-z]+\s*\d/u', $name) === 1;
    }

    private function parsePrice(string $text): float
    {
        $text = str_replace([',', '，'], '', $text);
        $patterns = [
            '/原廠售價\s*[：:]\s*[$＄]\s*(\d{3,6})/iu',
            '/(?:門市空機價|空機價|價格|售價|建議售價|原廠售價|最低價|最低)[\s\S]{0,180}?[$＄]\s*(\d{3,6})/iu',
            '/(?:NT\$|NTD|TWD|新台幣|台幣)\s*[$＄]?\s*(\d{3,6})/iu',
            '/(?:價格|售價|建議售價|原廠售價|空機價)[^\d$＄]{0,30}(\d{3,6})\s*(?:元|新台幣|台幣|NTD|TWD)/iu',
            '/[$＄]\s*(\d{3,6})/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return (float)$matches[1];
            }
        }

        return 0.0;
    }

    private function parseReleaseDate(string $text): string
    {
        $text = $this->cleanText($text);
        if ($text === '') {
            return '';
        }

        if ($this->textLength($text) <= 80 && preg_match('/^(\d{4})\s*年?$/u', $text, $matches)) {
            return $matches[1];
        }

        if ($this->textLength($text) <= 80
            && preg_match('/^(\d{4})(?:\s*(?:年|[-\/.])\s*(\d{1,2})(?:\s*(?:月|[-\/.])\s*(\d{1,2})\s*日?)?)?\s*$/u', $text, $matches)) {
            return $this->formatReleaseDateParts($matches[1], $matches[2] ?? '', $matches[3] ?? '');
        }

        if (preg_match('/(?:推出年份|推出日期|上市日期|發表日期|發售日期|release date|released)[^\d]{0,30}(\d{4})(?:\s*(?:年|[-\/.])\s*(\d{1,2}))?(?:\s*(?:月|[-\/.])\s*(\d{1,2}))?/iu', $text, $matches)) {
            return $this->formatReleaseDateParts($matches[1], $matches[2] ?? '', $matches[3] ?? '');
        }

        if (preg_match('/(\d{4})\s*年?\s*(?:推出|上市|發表|發售)/u', $text, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function formatReleaseDateParts(string $year, string $month = '', string $day = ''): string
    {
        if ($month === '') {
            return $year;
        }

        $date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
        if ($day !== '') {
            $date .= '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
        }

        return $date;
    }

    private function parseResolution(string $text): string
    {
        if (preg_match('/(\d{3,4})\s*[x×*]\s*(\d{3,4})/u', $text, $matches)) {
            $suffix = preg_match('/(pixel|像素)/iu', $text) ? ' pixels' : '';
            return $matches[1] . 'x' . $matches[2] . $suffix;
        }

        return '';
    }

    private function parseScreenSize(string $text): string
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:吋|inch|inches|")/iu', $text, $matches)) {
            return $matches[1] . ' inch';
        }

        return '';
    }

    private function parsePanelType(string $text): string
    {
        if (preg_match('/(OLED|AMOLED|LCD|Mini-LED|Retina XDR|Super Retina XDR|超 Retina XDR)/iu', $text, $matches)) {
            return $this->cleanText($matches[1]);
        }

        return '';
    }

    private function parseUnitNumber(string $text, array $units, float $maxReasonable, float $minReasonable = 0): float
    {
        if ($text === '') {
            return 0.0;
        }

        $unitPattern = implode('|', array_map(static fn (string $unit): string => preg_quote($unit, '/'), $units));
        $normalized = str_replace([',', '，'], '', $text);
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:' . $unitPattern . ')/iu', $normalized, $matches)) {
            $number = (float)$matches[1];
            if ($number >= $minReasonable && $number <= $maxReasonable) {
                return $number;
            }
        }

        return 0.0;
    }

    private function parseMaxUnitNumber(string $text, array $units, float $maxReasonable, float $minReasonable = 0): float
    {
        if ($text === '') {
            return 0.0;
        }

        $unitPattern = implode('|', array_map(static fn (string $unit): string => preg_quote($unit, '/'), $units));
        $normalized = str_replace([',', '，'], '', $text);
        preg_match_all('/(\d+(?:\.\d+)?)\s*(?:' . $unitPattern . ')/iu', $normalized, $matches, PREG_SET_ORDER);

        $numbers = [];
        foreach ($matches as $match) {
            $number = (float)$match[1];
            if ($number >= $minReasonable && $number <= $maxReasonable) {
                $numbers[] = $number;
            }
        }

        return $numbers ? max($numbers) : 0.0;
    }

    private function parseRamGb(string $text): float
    {
        if ($text === '') {
            return 0.0;
        }

        if (preg_match('/(?:RAM|記憶體|內存)[^\d]{0,20}(\d+(?:\.\d+)?)\s*(GB|TB)/iu', $text, $matches)) {
            return $this->memoryToGb((float)$matches[1], $matches[2]);
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*(GB|TB)\s*(?:RAM|記憶體|內存)/iu', $text, $matches)) {
            return $this->memoryToGb((float)$matches[1], $matches[2]);
        }

        $values = $this->extractMemoryValuesGb($text);
        $ramValues = array_values(array_filter($values, static fn (float $value): bool => $value > 0 && $value <= 64));
        if ($ramValues) {
            return max($ramValues);
        }

        return 0.0;
    }

    private function parseStorageGb(string $text): float
    {
        if ($text === '') {
            return 0.0;
        }

        if (preg_match('/(?:ROM|儲存|容量|storage)[^\d]{0,20}(\d+(?:\.\d+)?)\s*(GB|TB)/iu', $text, $matches)) {
            return $this->memoryToGb((float)$matches[1], $matches[2]);
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*(GB|TB)\s*(?:ROM|儲存|容量|storage)/iu', $text, $matches)) {
            return $this->memoryToGb((float)$matches[1], $matches[2]);
        }

        $values = $this->extractMemoryValuesGb($text);
        if ($values) {
            return max($values);
        }

        return 0.0;
    }

    private function extractMemoryValuesGb(string $text): array
    {
        preg_match_all('/(\d+(?:\.\d+)?)\s*(GB|TB)/iu', $text, $matches, PREG_SET_ORDER);
        $values = [];
        foreach ($matches as $match) {
            $values[] = $this->memoryToGb((float)$match[1], $match[2]);
        }

        return $values;
    }

    private function memoryToGb(float $number, string $unit): float
    {
        return strtoupper($unit) === 'TB' ? $number * 1024 : $number;
    }

    private function guessAppleRamGb(string $model): float
    {
        $model = $this->normalizeAppleModelName($model);
        $ramByModel = [
            'iphone x' => 3.0,
            'iphone xr' => 3.0,
            'iphone xs' => 4.0,
            'iphone xs max' => 4.0,
            'iphone 11' => 4.0,
            'iphone 11 pro' => 4.0,
            'iphone 11 pro max' => 4.0,
            'iphone se 2' => 3.0,
            'iphone se 3' => 4.0,
            'iphone 12 mini' => 4.0,
            'iphone 12' => 4.0,
            'iphone 12 pro' => 6.0,
            'iphone 12 pro max' => 6.0,
            'iphone 13 mini' => 4.0,
            'iphone 13' => 4.0,
            'iphone 13 pro' => 6.0,
            'iphone 13 pro max' => 6.0,
            'iphone 14' => 6.0,
            'iphone 14 plus' => 6.0,
            'iphone 14 pro' => 6.0,
            'iphone 14 pro max' => 6.0,
            'iphone 15' => 6.0,
            'iphone 15 plus' => 6.0,
            'iphone 15 pro' => 8.0,
            'iphone 15 pro max' => 8.0,
            'iphone 16' => 8.0,
            'iphone 16 plus' => 8.0,
            'iphone 16 pro' => 8.0,
            'iphone 16 pro max' => 8.0,
            'iphone 16e' => 8.0,
        ];

        return $ramByModel[$model] ?? 0.0;
    }

    private function normalizeAppleModelName(string $model): string
    {
        $model = $this->lower($this->cleanProductName($model));
        $model = preg_replace('/iphone\s+se\s*\(第\s*(\d+)\s*代\)/u', 'iphone se $1', $model) ?? $model;
        $model = preg_replace('/iphone\s+se\s+第\s*(\d+)\s*代/u', 'iphone se $1', $model) ?? $model;
        $model = preg_replace('/\s+/u', ' ', $model) ?? $model;
        return trim($model);
    }

    private function extractCpu(array $pairs, string $text): string
    {
        $specific = $this->collectValues($pairs, ['chipset', 'processor', 'cpu', '處理器', '晶片', 'soc', '平台']);
        $chip = $this->extractChipName($specific);
        if ($chip !== '') {
            return $chip;
        }

        $generic = $this->collectValues($pairs, ['規格', '性能']);
        $chip = $this->extractChipName($generic);
        if ($chip !== '') {
            return $chip;
        }

        $chip = $this->extractChipName($text);
        return $chip !== '' ? $chip : '未辨識 CPU';
    }

    private function extractChipName(string $text): string
    {
        $patterns = [
            '/(?:Qualcomm\s*)?Snapdragon\s*[A-Za-z0-9+ -]{2,30}/iu',
            '/(?:MediaTek\s*)?Dimensity\s*[A-Za-z0-9+ -]{2,30}/iu',
            '/(?:MediaTek\s*)?Helio\s*[A-Za-z0-9+ -]{2,20}/iu',
            '/Apple\s*A\d{1,2}\s*(?:Pro|Bionic)?/iu',
            '/\bA\d{1,2}\s*(?:Pro|Bionic|仿生)?\s*晶片/iu',
            '/Exynos\s*\d{3,4}/iu',
            '/Tensor\s*G\d/iu',
            '/Kirin\s*\d{3,4}/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return preg_replace('/\s*晶片$/u', '', $this->cleanText($matches[0])) ?? $this->cleanText($matches[0]);
            }
        }

        return '';
    }

    private function parseAntutu(string $text): float
    {
        if ($text === '') {
            return 0.0;
        }

        $text = str_replace([',', '，'], '', $text);
        if (preg_match('/(\d+(?:\.\d+)?)\s*萬/u', $text, $matches)) {
            $score = (float)$matches[1] * 10000;
            return $score >= 100000 ? $score : 0.0;
        }

        if (preg_match('/(\d{5,8})/u', $text, $matches)) {
            return (float)$matches[1];
        }

        return 0.0;
    }

    private function parseChargingWatts(string $text, bool $wirelessOnly): float
    {
        if ($text === '') {
            return 0.0;
        }

        $normalized = str_replace([',', '，'], '', $text);
        if ($wirelessOnly) {
            if (!preg_match('/(?:wireless|無線)[^\/，。,;；]{0,30}?(\d+(?:\.\d+)?)\s*w/iu', $normalized, $matches)
                && !preg_match('/(\d+(?:\.\d+)?)\s*w[^\/，。,;；]{0,30}?(?:wireless|無線)/iu', $normalized, $matches)) {
                return 0.0;
            }
            $watts = (float)$matches[1];
            return $watts >= 1 && $watts <= 150 ? $watts : 0.0;
        }

        if (!$this->containsAny($normalized, ['wired', '有線', '快充', '快速充電', 'USB', '電源轉接器'])
            && preg_match('/(?:wireless|無線)[^\/，。,;；]{0,30}?(\d+(?:\.\d+)?)\s*w/iu', $normalized)) {
            return 0.0;
        }

        if (preg_match('/(?:wired|有線|快充|快速充電|USB|電源轉接器)[^\/，。,;；]{0,40}?(\d+(?:\.\d+)?)\s*w/iu', $normalized, $matches)
            || preg_match('/(\d+(?:\.\d+)?)\s*w[^\/，。,;；]{0,40}?(?:wired|有線|快充|快速充電|USB|電源轉接器)/iu', $normalized, $matches)
            || preg_match('/(?<!無線)充電[^\/，。,;；]{0,30}?(\d+(?:\.\d+)?)\s*w/iu', $normalized, $matches)
            || preg_match('/(\d+(?:\.\d+)?)\s*w[^\/，。,;；]{0,30}?(?<!無線)充電/iu', $normalized, $matches)
            || preg_match('/(\d+(?:\.\d+)?)\s*w/iu', $normalized, $matches)) {
            $watts = (float)$matches[1];
            return $watts >= 1 && $watts <= 300 ? $watts : 0.0;
        }

        return 0.0;
    }

    private function parseAppleWiredChargingWatts(string $text): float
    {
        if ($text === '') {
            return 0.0;
        }

        $normalized = str_replace([',', '，'], '', $text);
        preg_match_all(
            '/須使用\s*(\d+(?:\.\d+)?)\s*W\s*或更高功率(?:(?!或使用|MagSafe|無線|Qi2|Qi 認證).){0,120}/iu',
            $normalized,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        $watts = [];
        foreach ($matches as $match) {
            $offset = (int)$match[0][1];
            $context = substr($normalized, max(0, $offset - 60), 60) . $match[0][0];
            if (!$this->containsAny($context, ['MagSafe', '無線', 'Qi2', 'Qi 認證'])) {
                $watts[] = (float)$match[1][0];
            }
        }

        if ($watts) {
            return max($watts);
        }

        if (preg_match('/USB[\s\-\x{2011}]*C[^。；;]{0,80}?(\d+(?:\.\d+)?)\s*W/iu', $normalized, $match)
            || preg_match('/(\d+(?:\.\d+)?)\s*W[^。；;]{0,80}?USB[\s\-\x{2011}]*C/iu', $normalized, $match)) {
            return (float)$match[1];
        }

        if ($this->containsAny($normalized, ['快速充電', 'fast charge'])
            && (preg_match('/30\s*分鐘/u', $normalized) || preg_match('/50\s*%/u', $normalized))) {
            return 18.0;
        }

        return 0.0;
    }

    private function parseAppleWirelessChargingWatts(string $text): float
    {
        if ($text === '') {
            return 0.0;
        }

        $normalized = str_replace([',', '，'], '', $text);
        preg_match_all('/(?:MagSafe|Qi2|Qi)[^。；;]{0,80}?無線充電[^。；;]{0,80}?(\d+(?:\.\d+)?)\s*W/iu', $normalized, $matches, PREG_SET_ORDER);

        $watts = [];
        foreach ($matches as $match) {
            $watts[] = (float)$match[1];
        }

        if ($watts) {
            return max($watts);
        }

        if (preg_match('/(?:MagSafe[^。；;]{0,80}?無線充電|無線充電[^。；;]{0,80}?MagSafe)/iu', $normalized)) {
            return 15.0;
        }
        if (preg_match('/(?:Qi2[^。；;]{0,80}?無線充電|無線充電[^。；;]{0,80}?Qi2)/iu', $normalized)) {
            return 15.0;
        }
        if (preg_match('/(?:Qi(?:\s*認證)?[^。；;]{0,80}?無線充電|無線充電[^。；;]{0,80}?Qi(?:\s*認證)?)/iu', $normalized)) {
            return 7.5;
        }

        return $this->parseChargingWatts($text, true);
    }

    private function parseCameraMp(string $text, string $kind): float
    {
        if ($text === '') {
            return 0.0;
        }

        $text = str_replace([',', '，'], '', $text);
        if ($kind === 'macro') {
            return $this->parseMacroCameraMp($text);
        }

        $keywordPatterns = [
            'main' => '(?:主(?:鏡頭|相機|攝)?|main)',
            'ultrawide' => '(?:超廣角|ultra[\s-]?wide)',
            'telephoto' => '(?:長焦|望遠|telephoto|periscope)',
            'macro' => '(?:微距|macro)',
            'front' => '(?:前鏡頭|前置|自拍|front|selfie)',
        ];

        $numberUnit = '(\d+(?:\.\d+)?)\s*(MP|百萬畫素|百萬像素|萬畫素|萬像素)';
        $keyword = $keywordPatterns[$kind] ?? '';
        if ($keyword !== '' && preg_match('/' . $keyword . '[^\/，。,;；]{0,40}?' . $numberUnit . '/iu', $text, $matches)) {
            return $this->cameraNumberToMp((float)$matches[1], $matches[2]);
        }
        if ($keyword !== '' && preg_match('/' . $numberUnit . '[^\/，。,;；]{0,40}?' . $keyword . '/iu', $text, $matches)) {
            return $this->cameraNumberToMp((float)$matches[1], $matches[2]);
        }

        if ($kind === 'main' && preg_match('/' . $numberUnit . '/iu', $text, $matches)) {
            return $this->cameraNumberToMp((float)$matches[1], $matches[2]);
        }
        if ($this->textLength($text) <= 40 && preg_match('/' . $numberUnit . '/iu', $text, $matches)) {
            return $this->cameraNumberToMp((float)$matches[1], $matches[2]);
        }

        return 0.0;
    }

    private function parseAppleCameraMp(string $cameraText, string $frontCameraText, string $kind): float
    {
        if ($kind === 'front') {
            return $this->extractFirstCameraMp($frontCameraText)
                ?: $this->parseCameraMp($frontCameraText, 'front');
        }

        $patterns = match ($kind) {
            'main' => ['融合主相機', '主相機', '(?<!超)廣角'],
            'ultrawide' => ['超廣角'],
            'telephoto' => ['長焦', '望遠', '光學品質\s*\d+\s*倍'],
            'macro' => ['微距攝影', '微距'],
            default => [],
        };

        if ($kind === 'macro') {
            return $this->parseMacroCameraMp($cameraText);
        }

        foreach ($patterns as $pattern) {
            $value = $this->extractCameraMpNearPattern($cameraText, $pattern);
            if ($value > 0) {
                return $value;
            }
        }

        return $this->parseCameraMp($cameraText, $kind);
    }

    private function parseMacroCameraMp(string $text): float
    {
        if ($text === '') {
            return 0.0;
        }

        $text = str_replace([',', '，'], '', $text);
        preg_match_all('/(?:微距|macro)/iu', $text, $keywordMatches, PREG_OFFSET_CAPTURE);
        if (empty($keywordMatches[0])) {
            return 0.0;
        }

        preg_match_all('/(\d+(?:\.\d+)?)\s*(MP|百萬畫素|百萬像素|萬畫素|萬像素)/iu', $text, $numberMatches, PREG_OFFSET_CAPTURE);
        if (empty($numberMatches[0])) {
            return 0.0;
        }

        $bestValue = 0.0;
        $bestDistance = PHP_INT_MAX;
        foreach ($keywordMatches[0] as $keywordMatch) {
            $keywordOffset = (int)$keywordMatch[1];
            foreach ($numberMatches[0] as $index => $numberMatch) {
                $numberOffset = (int)$numberMatch[1];
                $distance = abs($keywordOffset - $numberOffset);
                if ($distance > 90 || $distance >= $bestDistance) {
                    continue;
                }

                $contextStart = max(0, min($keywordOffset, $numberOffset) - 30);
                $context = substr($text, $contextStart, $distance + 90);
                if ($this->containsAny($context, ['全景模式', 'panorama'])) {
                    continue;
                }

                $between = substr($text, min($keywordOffset, $numberOffset), $distance + strlen($numberMatch[0][0]));
                preg_match_all('/\d+(?:\.\d+)?\s*(?:MP|百萬畫素|百萬像素|萬畫素|萬像素)/iu', $between, $betweenNumbers);
                if (count($betweenNumbers[0] ?? []) > 1) {
                    continue;
                }

                $unit = $numberMatches[2][$index][0];
                $value = $this->cameraNumberToMp((float)$numberMatches[1][$index][0], $unit);
                if ($value > 0) {
                    $bestValue = $value;
                    $bestDistance = $distance;
                }
            }
        }

        return $bestValue;
    }

    private function extractCameraMpNearPattern(string $text, string $pattern): float
    {
        if ($text === '') {
            return 0.0;
        }

        preg_match_all('/' . $pattern . '/iu', $text, $keywordMatches, PREG_OFFSET_CAPTURE);
        if (empty($keywordMatches[0])) {
            return 0.0;
        }

        preg_match_all('/(\d+(?:\.\d+)?)\s*(MP|百萬畫素|百萬像素|萬畫素|萬像素)/iu', $text, $numberMatches, PREG_OFFSET_CAPTURE);
        if (empty($numberMatches[0])) {
            return 0.0;
        }

        $bestValue = 0.0;
        $bestDistance = PHP_INT_MAX;
        foreach ($keywordMatches[0] as $keywordMatch) {
            $keywordOffset = (int)$keywordMatch[1];
            foreach ($numberMatches[0] as $index => $numberMatch) {
                $numberOffset = (int)$numberMatch[1];
                $distance = abs($numberOffset - $keywordOffset);
                if ($distance > 180 || $distance >= $bestDistance) {
                    continue;
                }

                $unit = $numberMatches[2][$index][0];
                $bestValue = $this->cameraNumberToMp((float)$numberMatches[1][$index][0], $unit);
                $bestDistance = $distance;
            }
        }

        return $bestValue;
    }

    private function extractFirstCameraMp(string $text): float
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*(MP|百萬畫素|百萬像素|萬畫素|萬像素)/iu', $text, $matches)) {
            return $this->cameraNumberToMp((float)$matches[1], $matches[2]);
        }

        return 0.0;
    }

    private function cameraNumberToMp(float $number, string $unit): float
    {
        if (str_contains($unit, '萬') && $number >= 1000) {
            return $number / 100;
        }

        return $number;
    }

    private function guessVideo(string $text): string
    {
        return $this->summarizeVideoSpec($text);
    }

    private function summarizeVideoSpec(string $text): string
    {
        if ($text === '') {
            return '';
        }

        preg_match_all('/(8K|4K|2(?:\.\d)?K|1080p|720p)/iu', $text, $matches, PREG_OFFSET_CAPTURE);
        if (empty($matches[0])) {
            return '';
        }

        $items = [];
        foreach ($matches[0] as $match) {
            $resolution = $this->normalizeVideoResolution($match[0]);
            $items[$resolution] = true;
        }

        $order = ['8K', '4K', '2.8K', '2K', '1080p', '720p'];
        foreach ($order as $resolution) {
            if (array_key_exists($resolution, $items)) {
                return $resolution;
            }
        }

        return '';
    }

    private function normalizeVideoResolution(string $resolution): string
    {
        $resolution = strtoupper($resolution);
        if (str_ends_with($resolution, 'P')) {
            return strtolower($resolution);
        }

        return $resolution;
    }

    private function guess5gBands(string $text): string
    {
        preg_match_all('/n\d{1,3}/i', $text, $matches);
        return implode('/', array_unique($matches[0] ?? []));
    }

    private function guessWifi(string $text): string
    {
        $text = $this->cleanText($text);
        $standards = [
            ['label' => 'Wi-Fi 7', 'rank' => 7, 'patterns' => ['/Wi[\s-]*Fi\s*7\b/i', '/802\.11[^。；;,]{0,50}be/i']],
            ['label' => 'Wi-Fi 6E', 'rank' => 6.5, 'patterns' => ['/Wi[\s-]*Fi\s*6E\b/i']],
            ['label' => 'Wi-Fi 6', 'rank' => 6, 'patterns' => ['/Wi[\s-]*Fi\s*6\b/i', '/802\.11[^。；;,]{0,50}ax/i']],
            ['label' => 'Wi-Fi 5', 'rank' => 5, 'patterns' => ['/Wi[\s-]*Fi\s*5\b/i', '/802\.11[^。；;,]{0,50}ac/i']],
            ['label' => 'Wi-Fi 4', 'rank' => 4, 'patterns' => ['/Wi[\s-]*Fi\s*4\b/i', '/802\.11[^。；;,]{0,50}n/i']],
        ];

        $matchesByPosition = [];
        foreach ($standards as $standard) {
            foreach ($standard['patterns'] as $pattern) {
                if (!preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                foreach ($matches[0] as $match) {
                    $matchesByPosition[] = [
                        'label' => $standard['label'],
                        'rank' => $standard['rank'],
                        'position' => (int)$match[1],
                    ];
                }
            }
        }

        if ($matchesByPosition) {
            usort($matchesByPosition, static function (array $a, array $b): int {
                if ($a['position'] === $b['position']) {
                    return $b['rank'] <=> $a['rank'];
                }
                return $a['position'] <=> $b['position'];
            });

            return $matchesByPosition[0]['label'];
        }

        return '';
    }

    private function summarizeWifiSpec(string $text): string
    {
        $wifi = $this->guessWifi($text);
        if ($wifi !== '') {
            return $wifi;
        }

        $text = $this->cleanText($text);
        if ($text !== '' && $this->textLength($text) <= 60 && preg_match('/(?:wi[\s-]*fi|802\.11)/i', $text)) {
            return $text;
        }

        return '';
    }

    private function guessBluetooth(string $text): string
    {
        if (preg_match('/Bluetooth\s*(\d+(?:\.\d+)?)/i', $text, $matches)
            || preg_match('/藍牙\s*(\d+(?:\.\d+)?)/u', $text, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function guessWaterproof(string $text): string
    {
        if (preg_match('/IP\d{2}/i', $text, $matches)) {
            return strtoupper($matches[0]);
        }
        return '';
    }

    private function htmlToText(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<(br|\/p|\/div|\/li|\/tr|\/section)\b[^>]*>/i', "\n", $html) ?? $html;
        return $this->cleanText($html);
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\xc2\xa0", '&nbsp;', '　'], ' ', $text);
        $text = str_replace(['‑', '‐', '–', '—', '－'], '-', $text);
        $text = strip_tags($text);
        $text = preg_replace('/[\h\v]+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    private function isAppleSpecPage(string $url, string $title, string $text): bool
    {
        return stripos($url, 'apple.com') !== false
            || (
                preg_match('/\biPhone\s+(?:\d{1,2}|X(?:R|S(?:\s*Max)?)?|SE)(?:\s*(?:Pro Max|Pro|Plus|Air|mini))?\b/iu', $title . ' ' . $text) === 1
                && str_contains($text, '技術規格')
                && (str_contains($text, 'Apple Intelligence') || str_contains($text, 'Face ID'))
            );
    }

    private function extractSection(string $text, string $start, array $endMarkers): string
    {
        $startPosition = $this->striposUtf8($text, $start);
        if ($startPosition === false) {
            return '';
        }

        $endPosition = false;
        foreach ($endMarkers as $marker) {
            $position = $this->striposUtf8($text, $marker, $startPosition + $this->textLength($start));
            if ($position !== false && ($endPosition === false || $position < $endPosition)) {
                $endPosition = $position;
            }
        }

        $length = $endPosition === false ? null : $endPosition - $startPosition;
        if (function_exists('mb_substr')) {
            return trim(mb_substr($text, $startPosition, $length, 'UTF-8'));
        }

        return trim(substr($text, $startPosition, $length));
    }

    private function extractFirstSection(string $text, array $starts, array $endMarkers): string
    {
        $bestStart = '';
        $bestPosition = false;
        foreach ($starts as $start) {
            $position = $this->striposUtf8($text, $start);
            if ($position !== false && ($bestPosition === false || $position < $bestPosition)) {
                $bestStart = $start;
                $bestPosition = $position;
            }
        }

        return $bestStart !== '' ? $this->extractSection($text, $bestStart, $endMarkers) : '';
    }

    private function striposUtf8(string $haystack, string $needle, int $offset = 0): int|false
    {
        if (function_exists('mb_stripos')) {
            return mb_stripos($haystack, $needle, $offset, 'UTF-8');
        }

        return stripos($haystack, $needle, $offset);
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (stripos($text, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isAppleProModel(string $brand, string $model): bool
    {
        if (strcasecmp(trim($brand), 'Apple') !== 0) {
            return false;
        }

        $model = $this->cleanProductName($model);
        return preg_match('/\biPhone\b.*\bPro(?:\s+Max)?\b/iu', $model) === 1;
    }

    private function hasEsim(array $pairs): bool
    {
        foreach ($pairs as $key => $value) {
            $combined = $key . ' ' . $value;
            if (preg_match('/\beSIM\b/iu', $combined) || preg_match('/支援\s*eSIM/iu', $combined)) {
                return !$this->isNegativeSpecValue((string)$value);
            }
        }

        return false;
    }

    private function hasTruthySpec(array $pairs, array $keys): bool
    {
        $value = $this->collectValues($pairs, $keys);
        if ($value === '') {
            return false;
        }

        return !$this->isNegativeSpecValue($value);
    }

    private function isNegativeSpecValue(string $value): bool
    {
        $value = $this->lower($this->cleanText($value));
        return $value === ''
            || preg_match('/^(no|none|false|n\/a|na|0|無|否|不支援|-)+$/u', $value) === 1;
    }

    private function sanitizeData(array $data): array
    {
        $numericKeys = [
            'price', 'ppi', 'refresh_rate', 'touch_sampling_rate', 'brightness',
            'antutu_score', 'ram_gb', 'rom_gb', 'battery_mah', 'wired_charging_w',
            'wireless_charging_w', 'main_camera_mp', 'ultrawide_camera_mp',
            'telephoto_camera_mp', 'macro_camera_mp', 'front_camera_mp',
            'esim', 'fingerprint', 'face_unlock', 'cooling',
        ];

        foreach ($numericKeys as $key) {
            $data[$key] = $data[$key] ?? 0;
        }

        foreach ($data as $key => $value) {
            if (!in_array($key, $numericKeys, true)) {
                $data[$key] = trim((string)$value);
            }
        }

        if ($data['brand'] === '') {
            $data['brand'] = 'Unknown';
        }
        if ($data['model'] === '') {
            $data['model'] = '未命名手機 ' . date('YmdHis');
        }

        return $data;
    }

    private function loadDom(string $html): ?DOMDocument
    {
        if (trim($html) === '' || !class_exists('DOMDocument')) {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $flags = LIBXML_NOWARNING | LIBXML_NOERROR;
        if (defined('LIBXML_NONET')) {
            $flags |= LIBXML_NONET;
        }

        $loaded = @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, $flags);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $dom : null;
    }

    private function xpathLiteral(string $value): string
    {
        $value = $this->lower($value);
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }
        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }

        $parts = explode("'", $value);
        return "concat('" . implode("', \"'\", '", $parts) . "')";
    }

    private function lower($text): string
    {
        $text = (string)$text;
        if (function_exists('text_lower')) {
            return text_lower($text);
        }

        return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    }

    private function normalizeKey($key): string
    {
        $key = $this->lower($this->cleanText((string)$key));
        return preg_replace('/[\s　:：_\-]+/u', '', $key) ?? $key;
    }

    private function textLength(string $text): int
    {
        return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    }

    private function log(string $url, string $status, string $message): void
    {
        execute_sql(
            'INSERT INTO crawler_logs (url, status, message, created_at) VALUES (?, ?, ?, NOW())',
            [$url, $status, $message]
        );
    }
}
