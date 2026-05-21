<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>郵件寄送系統</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: auto; }
        .box { border: 1px solid #ccc; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        .progress-bar { width: 100%; background-color: #f3f3f3; border-radius: 4px; margin-top: 10px; }
        .progress-fill { height: 20px; background-color: #4caf50; width: 0%; border-radius: 4px; text-align: center; color: white; font-size: 14px; line-height: 20px;}
        .hidden { display: none; }
        input, select, textarea { margin-bottom: 10px; width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px;}
        button:hover { background-color: #0056b3; }
        #log { font-size: 12px; color: #555; height: 100px; overflow-y: auto; background: #eee; padding: 10px; margin-top:10px; }
    </style>
</head>
<body>

    <!-- A. 建構資料庫區塊 -->
    <div class="box">
        <h3>A. 新增收件者至資料庫</h3>
        <form id="addEmailForm">
            <input type="email" id="new_email" placeholder="輸入 Email 位址" required>
            <button type="submit">新增 Email</button>
        </form>
    </div>

    <!-- B. 寄信介面 -->
    <div class="box">
        <h3>B. 寄送郵件</h3>
        <label>寄送對象模式：</label>
        <select id="send_mode" onchange="toggleInputs()">
            <option value="all">全部寄送</option>
            <option value="random">隨機寄送幾筆</option>
        </select>
        <div id="random_count_div" class="hidden">
            <input type="number" id="random_count" placeholder="請輸入要隨機寄送的筆數" min="1" value="5">
        </div>

        <label>寄送時間間隔：</label>
        <select id="delay_mode" onchange="toggleInputs()">
            <option value="fixed">固定秒數</option>
            <option value="random">隨機秒數</option>
        </select>
        <div id="fixed_delay_div">
            <input type="number" id="fixed_sec" placeholder="設定間隔秒數" min="0" value="2">
        </div>
        <div id="random_delay_div" class="hidden">
            <input type="number" id="min_sec" placeholder="最小秒數" min="0" value="1" style="width:48%;"> - 
            <input type="number" id="max_sec" placeholder="最大秒數" min="1" value="5" style="width:48%;">
        </div>

        <label>信件主旨：</label>
        <input type="text" id="subject" placeholder="請輸入主旨" required>

        <label>信件內容：</label>
        <textarea id="body" rows="5" placeholder="請輸入信件內容" required></textarea>

        <button onclick="startSending()" id="sendBtn">開始寄送</button>

        <!-- 進度條與狀態列 -->
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill">0%</div>
        </div>
        <div id="log">系統準備就緒...</div>
    </div>

    <script>
        // 處理介面顯示隱藏
        function toggleInputs() {
            document.getElementById('random_count_div').className = document.getElementById('send_mode').value === 'random' ? '' : 'hidden';
            document.getElementById('fixed_delay_div').className = document.getElementById('delay_mode').value === 'fixed' ? '' : 'hidden';
            document.getElementById('random_delay_div').className = document.getElementById('delay_mode').value === 'random' ? '' : 'hidden';
        }

        // 新增 Email AJAX
        document.getElementById('addEmailForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            let formData = new FormData();
            formData.append('email', document.getElementById('new_email').value);
            
            let res = await fetch('add_email.php', { method: 'POST', body: formData });
            let text = await res.text();
            alert(text);
            document.getElementById('new_email').value = '';
        });

        // 紀錄與進度條更新函數
        function log(msg) {
            let logDiv = document.getElementById('log');
            logDiv.innerHTML += `<div>${msg}</div>`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        function updateProgress(current, total) {
            let percent = total === 0 ? 0 : Math.round((current / total) * 100);
            let bar = document.getElementById('progressFill');
            bar.style.width = percent + '%';
            bar.innerText = percent + '% (' + current + '/' + total + ')';
        }

        // 核心寄送邏輯
        async function startSending() {
            let sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;
            document.getElementById('log').innerHTML = ''; // 清空 Log

            let mode = document.getElementById('send_mode').value;
            let count = document.getElementById('random_count').value;
            let subject = document.getElementById('subject').value;
            let body = document.getElementById('body').value;

            if (!subject || !body) { alert("主旨與內容不能為空！"); sendBtn.disabled = false; return; }

            log("正在取得收件者名單...");
            let response = await fetch(`get_emails.php?mode=${mode}&count=${count}`);
            let emails = await response.json();

            if(emails.length === 0) {
                log("錯誤：資料庫中沒有可用的 Email。");
                sendBtn.disabled = false; return;
            }

            let total = emails.length;
            let current = 0;
            updateProgress(0, total);

            // 開始迴圈寄送
            for(let i = 0; i < emails.length; i++) {
                let email = emails[i];
                
                // 處理延遲時間 (第一封信不延遲)
                if (i > 0) {
                    let waitTime = 0;
                    if(document.getElementById('delay_mode').value === 'fixed') {
                        waitTime = parseInt(document.getElementById('fixed_sec').value) * 1000;
                    } else {
                        let min = parseInt(document.getElementById('min_sec').value) * 1000;
                        let max = parseInt(document.getElementById('max_sec').value) * 1000;
                        waitTime = Math.floor(Math.random() * (max - min + 1)) + min;
                    }
                    log(`等待 ${waitTime/1000} 秒...`);
                    await new Promise(r => setTimeout(r, waitTime)); // JS 暫停功能
                }

                log(`正在寄送給: ${email} ...`);
                
                // 呼叫 PHP 寄送信件
                let formData = new FormData();
                formData.append('email', email);
                formData.append('subject', subject);
                formData.append('body', body);

                let res = await fetch('send_single.php', { method: 'POST', body: formData });
                let resultText = await res.text();
                if(resultText.trim() === 'success') {
                log(`<span style="color:green;">完成寄送給: ${email}</span>`);
                } else {
                log(`<span style="color:red;">寄送失敗 (${email}): ${resultText}</span>`);
                }
                
                current++;
                updateProgress(current, total);
                log(`完成寄送給: ${email}`);
            }

            log(`<b>寄送任務全部完成！</b>`);
            sendBtn.disabled = false;
        }
    </script>
</body>
</html>