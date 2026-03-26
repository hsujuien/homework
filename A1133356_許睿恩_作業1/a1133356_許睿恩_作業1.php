<html>

<head>
    <title>棒球夏令營報名表</title>
</head>
<body bgcolor="#f0f8ff">

    <center>
        <h1><font color="navy">2026 青春揮棒：棒球夏令營報名表</font></h1>
        <p>歡迎熱愛棒球的學員加入！請填妥以下資訊。如有疑問，請<a href="">寄信給我</a></p>
        <IMG SRC="baseball.png" height="400"width="800" ></IMG>
    </center>

    <hr>

    <center>
        <h3>【營隊行程表】</h3>
            <table border="1" cellpadding="10" width="600" bgcolor="white">
                <tr bgcolor="#003366">
                    <th><font color="white">天數</font></th>
                    <th><font color="white">訓練主題</font></th>
                    <th><font color="white">課程重點</font></th>
                </tr>
                <tr>
                    <td align="center">第一天</td>
                    <td align="center"><b>投球練習</b></td>
                    <td>基礎投球姿勢、控球技巧與熱身方法。</td>
                </tr>
                <tr>
                    <td align="center">第二天</td>
                    <td align="center"><b>打擊練習</b></td>
                    <td>打擊姿勢修正、選球眼力訓練及揮棒力量。</td>
                </tr>
                <tr>
                    <td align="center">第三天</td>
                    <td align="center"><b>守備練習</b></td>
                    <td>內外野接球、傳球默契與各位置站位教學。</td>
                </tr>
            </table>
        
        <p><font size="4" COLOR="red"><b>報名費用：新台幣 3,000 元整</b></font></p>
    </center>

    <hr>

    <form action="" method="">
        <table border="1" align="center" cellpadding="10">
            <tr bgcolor="#dcdcdc">
                <th colspan="2"><font size="4">基本資料與課程選擇</font></th>
            </tr>

            <tr>
                <td><font color="red">*</font>學員姓名：</td>
                <td><input type="text" placeholder="請輸入中文姓名" name="nName" value="" required></td>
            </tr>
            <tr>
                <td><font color="red">*</font>設定查詢密碼：</td>
                <td><input type="password" name="nPass" required> <i>(查詢報名結果用)</i></td>
            </tr>

            <tr>
                <td>性別：</td>
                <td>
                    男<input type="radio" name="mGender" value="m" checked>
                    女<input type="radio" name="mGender" value="f">
                </td>
            </tr>

            <tr>
                <td>出生日期：</td>
                <td><input type="date" name="mDate"><br></td>
            </tr>

            <tr>
                <td>聯絡方式：</td>
                <td>
                電話：<input type="number" name="mNumber" placeholder="09XXXXXXXX"><br>
                信箱：<input type="email" name="mEmail" placeholder="example@mail.com">
                </td>
            </tr>

            <tr>
                <td>守備位置：</td>
                <td>
                    投手<input type="checkbox" name="mPosition" value="Pitcher">
                    捕手<input type="checkbox" name="mPosition" value="Catcher">
                    內野手<input type="checkbox" name="mPosition" value="Infielders">
                    外野手<input type="checkbox" name="mPosition" value="Outfielders">
                </td>
            </tr>

            <tr>
                <td>報名內容：</td>
                <td>
                    <font color="red">*</font>報名場次：<select name="nCity" required>
                        <option value="Taipei">台北</option>
                        <option value="Hsinchu">新竹</option>
                        <option value="Kaohsiung">高雄</option>
                    </select><br>
                    報到時間：<input type="time" name="mTime"><br>
                    球衣顏色：<input type="color" name="mColor"><br>
                    體能狀況自評 (1-100)：<input type="range" name="mRange"><br>
                </td>
            </tr>

            <tr>
                <td>備註事項：</td>
                <td>
                    <textarea name="comment" rows="5" cols="40">請輸入特殊飲食習慣或疾病史。</textarea>
                </td>
            </tr>

            <tr bgcolor="#f9f9f9">
                <td colspan="2" align="center">
                    <input type="submit" value="確認報名">
                    <input type="reset" value="重新填寫">
                </td>
            </tr>

        </table>
    </form>

    <hr>

</body>

</html>