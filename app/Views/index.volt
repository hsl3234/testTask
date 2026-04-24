<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Админка магазина</title>
    <link rel="stylesheet" href="/assets/app.css" />
    <script>window.__API_BASE__ = "{{ apiBase }}";</script>
</head>
<body>
    <div id="app"></div>
    <script type="importmap">
    {
        "imports": {
            "vue": "https://unpkg.com/vue@3/dist/vue.esm-browser.prod.js"
        }
    }
    </script>
    <script type="module" src="/assets/app.js"></script>
</body>
</html>
