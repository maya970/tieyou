<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网页游戏UI</title>
    <style>
        /* 你的CSS样式 */
    </style>
</head>
<body>

<h1>网页游戏UI</h1>

<div id="container">
    <div id="coordinates">
        <?php for ($i = 0; $i < 10; $i++) {
            echo "<div class='coordinate'>$i</div>";
        } ?>
    </div>
    <div id="indices">
        <?php for ($i = 0; $i < 20; $i++) {
            echo "<div class='coordinate'>$i</div>";
        } ?>
    </div>
    <div id="map-wrapper">
        <div id="map">
            <?php
            include 'data.php'; // 包含数据文件

            for ($row = 0; $row < 20; $row++) {
                for ($col = 0; $col < 10; $col++) {
                    $key = $row . "_" . $col;
                    $info = isset($data[$key]['description']) ? $data[$key]['description'] : "无信息";
                    $word = isset($data[$key]['word']) ? $data[$key]['word'] : "";
                    echo "<div class='map-section' onclick='showStatus(\"" . $key . "\")'>
                            <div class='map-word'>" . $word . "</div>
                          </div>";
                }
            }
            ?>
        </div>
    </div>
</div>

<div id="status">
    请点击地图上的区域来查看详细信息。
</div>

<div id="interaction">
    <form method="post">
        <textarea name="user_input" placeholder="输入你的信息..." required></textarea>
        <input type="submit" value="提交">
    </form>
</div>

<div class="tab active" onclick="showSection('view')">查看信息</div>
<div class="tab" onclick="showSection('history')">历史信息</div>

<div id="view">
    <h2>查看信息</h2>
    <?php
    $view_file = 'view.txt';

    if (file_exists($view_file)) {
        $view_messages = file($view_file, FILE_IGNORE_NEW_LINES);
        foreach ($view_messages as $view_message) {
            echo "<p>" . htmlspecialchars($view_message) . "</p>";
        }
    }
    ?>
</div>

<div id="history">
    <h2>历史信息</h2>
    <?php
    $history_file = 'history.txt';

    if (file_exists($history_file)) {
        $history_messages = file($history_file, FILE_IGNORE_NEW_LINES);
        foreach ($history_messages as $history_message) {
            echo "<p>" . htmlspecialchars($history_message) . "</p>";
        }
    }
    ?>
</div>

<script>
    const data = <?php echo json_encode($data); ?>;

    function showStatus(key) {
        const statusElement = document.getElementById('status');
        if (data[key] && data[key]['description']) {
            statusElement.innerText = data[key]['description'];
        } else {
            statusElement.innerText = "没有相关信息";
        }
    }

    function showSection(sectionId) {
        document.getElementById('view').style.display = 'none';
        document.getElementById('history').style.display = 'none';
        document.getElementById(sectionId).style.display = 'block';
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`.tab[onclick="showSection('${sectionId}')"]`).classList.add('active');
    }

    document.getElementById('view').style.display = 'block';
</script>

</body>
</html>
