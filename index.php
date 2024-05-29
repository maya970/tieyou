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
