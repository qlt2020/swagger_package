<!-- HTML for static distribution bundle build -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('swagger.title') }}</title>
    <link rel="stylesheet" type="text/css" href="/swagger/swagger-ui.css" >
    <link rel="icon" type="image/png" href="/swagger/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="/swagger/favicon-16x16.png" sizes="16x16" />
    <style>
        html
        {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *,
        *:before,
        *:after
        {
            box-sizing: inherit;
        }

        body
        {
            margin:0;
            background: #fafafa;
        }
    </style>
</head>

<body>
<div id="swagger-ui"></div>

<script src="/swagger/swagger-ui-bundle.js" charset="UTF-8"> </script>
<script src="/swagger/swagger-ui-standalone-preset.js" charset="UTF-8"> </script>
<script>
    window.onload = function() {
        // Begin swagger UI call region
        const ui = SwaggerUIBundle({
            url: "{{ config('swagger.url_to_openapi') }}",
            dom_id: '#swagger-ui',
            validatorUrl: null,
            deepLinking: true,
            docExpansion: 'none',
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout"
        })

        window.ui = ui
    }
</script>
</body>
</html>
