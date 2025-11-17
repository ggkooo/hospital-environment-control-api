<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $translations['title'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            overflow-x: hidden;
        }

        html {
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Responsividade para container */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 4px solid #84cc16;
            width: 100%;
            box-sizing: border-box;
        }

        /* Responsividade para header */
        @media (max-width: 768px) {
            .header {
                padding: 20px;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 15px;
                margin-bottom: 15px;
                border-radius: 6px;
            }
        }

        .header h1 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 2rem;
            font-weight: bold;
        }

        /* Responsividade para título */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.5rem;
                line-height: 1.3;
            }
        }

        .header p {
            color: #6b7280;
            margin: 0 0 20px 0;
            font-size: 1.1rem;
        }

        /* Responsividade para descrição */
        @media (max-width: 768px) {
            .header p {
                font-size: 1rem;
                margin-bottom: 15px;
            }
        }

        @media (max-width: 480px) {
            .header p {
                font-size: 0.9rem;
                margin-bottom: 12px;
            }
        }

        .header-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        /* Responsividade para header-bottom */
        @media (max-width: 768px) {
            .header-bottom {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                margin-top: 15px;
            }
        }

        @media (max-width: 480px) {
            .header-bottom {
                gap: 10px;
                margin-top: 12px;
            }
        }

        .auth-info {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Responsividade para auth-info */
        @media (max-width: 480px) {
            .auth-info {
                gap: 8px;
                flex-direction: column;
            }
        }

        .badge {
            background: #e5e7eb;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Responsividade para badges */
        @media (max-width: 480px) {
            .badge {
                padding: 6px 10px;
                font-size: 0.8rem;
                text-align: center;
            }
        }

        .badge.api {
            background: #ecfccb;
            color: #365314;
        }

        .search-container {
            width: 250px;
            margin-left: 2rem;
            margin-right: 2rem;
            box-sizing: border-box;
        }

        /* Responsividade para search-container */
        @media (max-width: 768px) {
            .search-container {
                width: 200px;
                margin-left: 1rem;
                margin-right: 1rem;
            }
        }

        @media (max-width: 480px) {
            .search-container {
                width: 100%;
                margin-left: 0;
                margin-right: 0;
            }
        }

        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            background-color: #ffffff;
            color: #374151;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .search-input:focus {
            outline: 3px solid rgba(132, 204, 22, 0.3);
            outline-offset: -3px;
            border-color: #84cc16;
        }

        .section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }

        /* Responsividade para sections */
        @media (max-width: 480px) {
            .section {
                border-radius: 6px;
                margin-bottom: 15px;
            }
        }

        .section-header {
            background: #f7fee7;
            border-left: 4px solid #84cc16;
            padding: 20px;
        }

        /* Responsividade para section-header */
        @media (max-width: 768px) {
            .section-header {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .section-header {
                padding: 12px;
            }
        }

        .section-header h2 {
            color: #365314;
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        /* Responsividade para títulos das seções */
        @media (max-width: 768px) {
            .section-header h2 {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .section-header h2 {
                font-size: 1.1rem;
            }
        }

        .route-item {
            border-bottom: 1px solid #e5e7eb;
            margin: 0;
        }
        .route-item:last-child {
            border-bottom: none;
        }
        .route-button {
            width: 100%;
            padding: 20px;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        /* Responsividade para route-button */
        @media (max-width: 768px) {
            .route-button {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .route-button {
                padding: 12px;
            }
        }

        .route-button:hover {
            background-color: #f9fafb;
        }
        .route-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        /* Responsividade para route-header */
        @media (max-width: 768px) {
            .route-header {
                margin-bottom: 8px;
            }
        }

        @media (max-width: 480px) {
            .route-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                margin-bottom: 6px;
            }
        }

        .route-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Responsividade para route-info */
        @media (max-width: 768px) {
            .route-info {
                gap: 10px;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
            .route-info {
                gap: 8px;
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .method-badge {
            min-width: 70px;
            text-align: center;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.8rem;
            font-family: monospace;
        }

        /* Responsividade para method-badge */
        @media (max-width: 480px) {
            .method-badge {
                min-width: 60px;
                padding: 4px 8px;
                font-size: 0.75rem;
            }
        }

        .method-get {
            background: #dcfce7;
            color: #166534;
        }
        .method-post {
            background: #ecfccb;
            color: #365314;
        }
        .endpoint {
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
            color: #374151;
            word-break: break-all;
        }

        /* Responsividade para endpoint */
        @media (max-width: 768px) {
            .endpoint {
                font-size: 0.8rem;
                padding: 3px 6px;
            }
        }

        @media (max-width: 480px) {
            .endpoint {
                font-size: 0.75rem;
                padding: 3px 6px;
                width: 100%;
                box-sizing: border-box;
                word-break: break-all;
                overflow-wrap: break-word;
            }
        }

        .description {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 0;
        }

        /* Responsividade para description */
        @media (max-width: 768px) {
            .description {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .description {
                font-size: 0.8rem;
                line-height: 1.4;
            }
        }

        .arrow {
            width: 20px;
            height: 20px;
            transition: transform 0.2s;
            flex-shrink: 0;
        }

        /* Responsividade para arrow */
        @media (max-width: 480px) {
            .arrow {
                width: 18px;
                height: 18px;
                align-self: flex-end;
            }
        }

        .route-details {
            display: none;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 25px;
            width: 100%;
            box-sizing: border-box;
            overflow-x: hidden;
        }

        /* Responsividade para route-details */
        @media (max-width: 768px) {
            .route-details {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .route-details {
                padding: 15px;
            }
        }

        .route-details.show {
            display: block;
        }
        .detail-section {
            margin-bottom: 25px;
        }

        /* Responsividade para detail-section */
        @media (max-width: 768px) {
            .detail-section {
                margin-bottom: 20px;
            }
        }

        @media (max-width: 480px) {
            .detail-section {
                margin-bottom: 15px;
            }
        }

        .detail-section:last-child {
            margin-bottom: 0;
        }
        .detail-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        /* Responsividade para detail-title */
        @media (max-width: 480px) {
            .detail-title {
                font-size: 0.85rem;
                margin-bottom: 8px;
            }
        }

        .code-block {
            background: #1f2937;
            color: #10b981;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.8rem;
            overflow-x: auto;
            white-space: pre;
            width: 100%;
            box-sizing: border-box;
            max-width: 100%;
        }

        /* Responsividade para code-block */
        @media (max-width: 768px) {
            .code-block {
                padding: 12px;
                font-size: 0.75rem;
                overflow-x: auto;
                max-width: calc(100vw - 60px);
            }
        }

        @media (max-width: 480px) {
            .code-block {
                padding: 10px;
                font-size: 0.7rem;
                border-radius: 4px;
                word-break: break-all;
                white-space: pre-wrap;
                overflow-x: hidden;
                max-width: calc(100vw - 40px);
            }
        }

        .param-item {
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        /* Responsividade para param-item */
        @media (max-width: 480px) {
            .param-item {
                margin-bottom: 6px;
                font-size: 0.8rem;
                word-break: break-word;
            }
        }

        .param-name {
            background: #fef3c7;
            color: #92400e;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 0.8rem;
            word-break: break-all;
        }

        /* Responsividade para param-name */
        @media (max-width: 480px) {
            .param-name {
                font-size: 0.75rem;
                padding: 2px 4px;
            }
        }

        .param-name.query {
            background: #ecfccb;
            color: #365314;
        }

        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
            font-size: 1.1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        /* Responsividade para no-results */
        @media (max-width: 768px) {
            .no-results {
                padding: 30px 15px;
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .no-results {
                padding: 20px 10px;
                font-size: 0.9rem;
            }
        }

        .footer {
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            margin-top: 40px;
            padding: 20px;
            border-top: 1px solid #e5e7eb;
        }

        /* Responsividade para footer */
        @media (max-width: 768px) {
            .footer {
                margin-top: 30px;
                padding: 15px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .footer {
                margin-top: 20px;
                padding: 12px;
                font-size: 12px;
                line-height: 1.5;
            }
        }

        .footer a {
            color: #365314;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }

        /* Melhorias gerais para dispositivos touch */
        @media (hover: none) and (pointer: coarse) {
            .route-button:hover {
                background-color: transparent;
            }

            .route-button:active {
                background-color: #f3f4f6;
            }
        }

        /* Ajustes para orientação paisagem em dispositivos móveis */
        @media (max-width: 768px) and (orientation: landscape) {
            .header {
                padding: 15px 20px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .header p {
                font-size: 0.9rem;
            }
        }

        /* Ajustes para telas muito pequenas */
        @media (max-width: 320px) {
            .container {
                padding: 8px;
            }

            .header {
                padding: 12px;
            }

            .header h1 {
                font-size: 1.3rem;
            }

            .route-button {
                padding: 10px;
            }

            .route-details {
                padding: 12px;
            }
        }

        /* Ajuste no container para acomodar o seletor */
        .container {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $translations['title'] }}</h1>
            <p>{{ $translations['description'] }}</p>
            <div class="header-bottom">
                <div class="auth-info">
                    <span class="badge">{{ $translations['authentication'] }}</span>
                    <span class="badge api">{{ $translations['base_url'] }}</span>
                </div>
                <div class="search-container">
                    <input type="text" id="search-input" placeholder="Search sections..." class="search-input">
                </div>
            </div>
        </div>

        <!-- API Endpoints -->
        @foreach($apiRoutes as $groupIndex => $group)
        <div class="section">
            <div class="section-header">
                <h2>{{ $group['group'] }}</h2>
            </div>

            <div>
                @foreach($group['routes'] as $routeIndex => $route)
                <div class="route-item">
                    <button class="route-button" onclick="toggleRoute({{ $groupIndex }}, {{ $routeIndex }})">
                        <div class="route-header">
                            <div class="route-info">
                                <span class="method-badge {{ $route['method'] === 'GET' ? 'method-get' : 'method-post' }}">
                                    {{ $route['method'] }}
                                </span>
                                <code class="endpoint">{{ $route['endpoint'] }}</code>
                            </div>
                            <svg class="arrow" id="arrow-{{ $groupIndex }}-{{ $routeIndex }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <p class="description">{{ $route['description'] }}</p>
                    </button>

                    <div class="route-details" id="details-{{ $groupIndex }}-{{ $routeIndex }}">
                        @if(isset($route['headers']))
                        <div class="detail-section">
                            <div class="detail-title">{{ $translations['headers'] }}</div>
                            <div class="code-block">@foreach($route['headers'] as $header => $value){{ $header }}: {{ $value }}
@endforeach</div>
                        </div>
                        @endif

                        @if(isset($route['parameters']))
                        <div class="detail-section">
                            <div class="detail-title">{{ $translations['parameters'] }}</div>
                            @foreach($route['parameters'] as $param => $desc)
                            <div class="param-item">
                                <code class="param-name">{{ $param }}</code> - {{ $desc }}
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @if(isset($route['query_params']))
                        <div class="detail-section">
                            <div class="detail-title">{{ $translations['query_params'] }}</div>
                            @foreach($route['query_params'] as $param => $desc)
                            <div class="param-item">
                                <code class="param-name query">{{ $param }}</code> - {{ $desc }}
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @if(isset($route['example_request']))
                        <div class="detail-section">
                            <div class="detail-title">{{ $translations['request_example'] }}</div>
                            <div class="code-block">GET {{ $route['example_request']['url'] }}</div>
                            <div style="margin-top: 8px; font-size: 0.85rem; color: #6b7280;">
                                {{ $route['example_request']['description'] }}
                            </div>
                        </div>
                        @endif

                        @if(isset($route['body']))
                        <div class="detail-section">
                            <div class="detail-title">{{ $translations['request_body'] }}</div>
                            <div class="code-block">{{ json_encode($route['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</div>
                        </div>
                        @endif

                        @if(isset($route['response']))
                        <div class="detail-section">
                            <div class="detail-title">{{ $translations['response_example'] }}</div>
                            <div class="code-block">{{ json_encode($route['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <!-- No Results Message -->
        <div id="no-results" class="no-results" style="display: none;">
            <p>No results found for your search. Try a different term.</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><a href="https://github.com/ggkooo" target="_blank" rel="external">Giordano Bruno Biasi Berwig</a> | <strong>{{ $translations['footer_text'] }}</strong> | {{ date('Y') }}</p>
        </div>
    </div>

    <script>
        function toggleRoute(groupIndex, routeIndex) {
            const details = document.getElementById(`details-${groupIndex}-${routeIndex}`);
            const arrow = document.getElementById(`arrow-${groupIndex}-${routeIndex}`);

            if (details.classList.contains('show')) {
                details.classList.remove('show');
                arrow.style.transform = 'rotate(0deg)';
            } else {
                details.classList.add('show');
                arrow.style.transform = 'rotate(180deg)';
            }
        }


        // Improved accessibility for mobile devices
        document.addEventListener('DOMContentLoaded', function() {
            // Adds touch support for better mobile experience
            const routeButtons = document.querySelectorAll('.route-button');

            routeButtons.forEach(button => {
                button.addEventListener('touchstart', function() {
                    this.style.backgroundColor = '#f3f4f6';
                });

                button.addEventListener('touchend', function() {
                    setTimeout(() => {
                        this.style.backgroundColor = '';
                    }, 150);
                });
            });

            // Search functionality
            const searchInput = document.getElementById('search-input');
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const sections = document.querySelectorAll('.section');
                let resultsFound = false;

                sections.forEach(section => {
                    const header = section.querySelector('.section-header h2');
                    const headerText = header.textContent.toLowerCase();

                    if (headerText.includes(searchTerm)) {
                        section.style.display = 'block';
                        resultsFound = true;
                    } else {
                        section.style.display = 'none';
                    }
                });

                // Show or hide the no results message
                const noResultsMessage = document.getElementById('no-results');
                if (resultsFound) {
                    noResultsMessage.style.display = 'none';
                } else {
                    noResultsMessage.style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>
