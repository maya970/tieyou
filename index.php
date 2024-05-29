<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网页游戏UI</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
        }
        #container {
            display: grid;
            grid-template-areas: 
                "header header"
                "index map-wrapper";
            grid-template-columns: auto 1fr;
            width: 90vw;
            max-width: 800px;
            margin-top: 20px;
            position: relative;
        }
        #coordinates {
            grid-area: header;
            display: grid;
            grid-template-columns: repeat(10, 1fr);
        }
        #indices {
            grid-area: index;
            display: grid;
            grid-template-rows: repeat(20, 1fr);
        }
        #map-wrapper {
            grid-area: map-wrapper;
            position: relative;
            overflow: auto;
            width: 100%;
        }
        #map {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            grid-template-rows: repeat(20, 1fr);
            gap: 2px;
            background-image: url('不列颠2.png');
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            width: 100%;
            aspect-ratio: 10 / 20;
        }
        .coordinate {
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.8em;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid #333;
        }
        .map-section {
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            background-color: rgba(255, 255, 255, 0.5);
            border: 1px solid #333;
            aspect-ratio: 1 / 1;
        }
        .map-word {
            font-size: 1em;
            text-align: center;
        }
        #status {
            width: 90vw;
            max-width: 800px;
            margin-top: 20px;
            padding: 10px;
            background-color: #fff;
            border: 1px solid #333;
            box-sizing: border-box;
        }
        #interaction {
            width: 90vw;
            max-width: 800px;
            margin-top: 20px;
        }
        #interaction textarea {
            width: calc(100% - 20px);
            height: 80px;
            margin-bottom: 10px;
            resize: none;
        }
        #view, #history {
            width: 90vw;
            max-width: 800px;
            margin-top: 20px;
            padding: 10px;
            background-color: #fff;
            border: 1px solid #333;
            max-height: 200px;
            overflow-y: scroll;
            display: none;
        }
        .tab {
            margin-top: 10px;
            cursor: pointer;
            font-weight: bold;
            padding: 5px 10px;
            background-color: #ddd;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .tab:hover {
            background-color: #ccc;
        }
        .tab.active {
            background-color: #bbb;
        }

        /* Default show view section */
        #view {
            display: block;
        }

        /* Beautify view section */
        #view h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #333;
        }
        #view p {
            margin: 5px 0;
            padding: 5px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        /* 媒体查询：针对手机屏幕 */
        @media only screen and (max-width: 600px) {
            #container {
                grid-template-areas:
                    "map-wrapper";
                grid-template-columns: 1fr;
                grid-template-rows: auto;
            }
            #map-wrapper {
                overflow: scroll;
            }
            #map {
                width: 200%;
                height: auto;
                grid-template-columns: repeat(10, 1fr);
                grid-template-rows: repeat(20, 1fr);
            }
            #coordinates, #indices {
                display: none;
            }
        }
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
        // 隐藏所有部分
        document.getElementById('view').style.display = 'none';
        document.getElementById('history').style.display = 'none';

        // 显示所选部分
        document.getElementById(sectionId).style.display = 'block';

        // 移除所有标签的active类
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });

        // 添加active类到点击的标签
        document.querySelector(`.tab[onclick="showSection('${sectionId}')"]`).classList.add('active');
    }

    // 页面加载时显示view部分
    document.getElementById('view').style.display = 'block';
</script>

</body>
</html>
