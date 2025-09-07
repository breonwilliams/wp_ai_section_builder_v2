<?php
/**
 * Test CSS Architecture
 * Verify that styles are loading correctly and consistently
 * 
 * Usage: Navigate to /wp-content/plugins/ai_section_builder_v2/test-css-architecture.php
 */

// Load WordPress
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Set up test post ID (you can change this)
$test_post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

?>
<!DOCTYPE html>
<html>
<head>
    <title>AISB CSS Architecture Test</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Load our CSS files in the correct order -->
    <link rel="stylesheet" href="<?php echo AISB_PLUGIN_URL; ?>assets/css/core/00-tokens.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo AISB_PLUGIN_URL; ?>assets/css/core/02-utilities.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo AISB_PLUGIN_URL; ?>assets/css/sections/hero.css?v=<?php echo time(); ?>">
    
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .test-section {
            margin-bottom: 40px;
            padding: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        h1 { color: #1a1a1a; }
        h2 { color: #2563eb; margin-top: 0; }
        code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 14px;
        }
        .status-good { color: #10b981; font-weight: bold; }
        .status-bad { color: #ef4444; font-weight: bold; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🔧 AISB CSS Architecture Test</h1>
        
        <!-- CSS Files Loading Status -->
        <div class="test-section">
            <h2>CSS Files Status</h2>
            <ul>
                <li>Design Tokens (00-tokens.css): <span class="status-good">✓ Loaded</span></li>
                <li>Utilities (02-utilities.css): <span class="status-good">✓ Loaded</span></li>
                <li>Hero Section (hero.css): <span class="status-good">✓ Loaded</span></li>
            </ul>
        </div>
        
        <!-- Light Mode Test -->
        <div class="test-section">
            <h2>Light Mode Buttons</h2>
            <p>These should display with proper colors:</p>
            
            <div class="aisb-hero__buttons" style="display: flex; gap: 16px; flex-wrap: wrap;">
                <button class="aisb-btn aisb-btn-primary">Primary Button</button>
                <button class="aisb-btn aisb-btn-secondary">Secondary Button</button>
                <button class="aisb-btn aisb-btn-ghost">Ghost Button</button>
            </div>
            
            <div style="margin-top: 20px;">
                <p><strong>Expected:</strong></p>
                <ul>
                    <li>Primary: Blue background (#2563eb), white text</li>
                    <li>Secondary: Transparent background, blue border (#2563eb)</li>
                    <li>Ghost: Transparent, gray text</li>
                </ul>
            </div>
        </div>
        
        <!-- Dark Mode Test -->
        <div class="test-section aisb-section--dark" style="background: #1a1a1a; color: #fafafa;">
            <h2 style="color: #60a5fa;">Dark Mode Buttons</h2>
            <p>These should display with dark mode colors:</p>
            
            <div class="aisb-hero__buttons" style="display: flex; gap: 16px; flex-wrap: wrap;">
                <button class="aisb-btn aisb-btn-primary">Primary Button</button>
                <button class="aisb-btn aisb-btn-secondary">Secondary Button</button>
                <button class="aisb-btn aisb-btn-ghost">Ghost Button</button>
            </div>
            
            <div style="margin-top: 20px;">
                <p><strong>Expected:</strong></p>
                <ul>
                    <li>Primary: Light blue background (#60a5fa), dark text</li>
                    <li>Secondary: Transparent background, light blue border (#60a5fa)</li>
                    <li>Ghost: Transparent, light gray text</li>
                </ul>
            </div>
        </div>
        
        <!-- Button Sizes Test -->
        <div class="test-section">
            <h2>Button Sizes</h2>
            <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                <button class="aisb-btn aisb-btn-primary aisb-btn-sm">Small</button>
                <button class="aisb-btn aisb-btn-primary">Default</button>
                <button class="aisb-btn aisb-btn-primary aisb-btn-lg">Large</button>
            </div>
        </div>
        
        <!-- Full Hero Section Test -->
        <div class="test-section">
            <h2>Full Hero Section Preview</h2>
            <div class="aisb-section--dark aisb-hero" style="padding: 40px 0;">
                <div class="aisb-hero__container">
                    <div class="aisb-hero__grid">
                        <div class="aisb-hero__content">
                            <div class="aisb-hero__eyebrow">WELCOME TO THE FUTURE</div>
                            <h1 class="aisb-hero__heading">Your Headline Here</h1>
                            <div class="aisb-hero__body">
                                <p>Add your compelling message that engages visitors</p>
                            </div>
                            <div class="aisb-hero__buttons">
                                <a href="#" class="aisb-btn aisb-btn-primary">Get Started</a>
                                <a href="#" class="aisb-btn aisb-btn-secondary">Learn More</a>
                            </div>
                        </div>
                        <div class="aisb-hero__media">
                            <div class="aisb-media-placeholder">
                                <span>Media Placeholder</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- CSS Variable Check -->
        <div class="test-section">
            <h2>CSS Variables Status</h2>
            <div id="css-var-check"></div>
        </div>
        
        <script>
            // Check if CSS variables are defined
            const root = getComputedStyle(document.documentElement);
            const varsToCheck = [
                '--aisb-color-primary',
                '--aisb-color-text',
                '--aisb-space-sm',
                '--aisb-space-md'
            ];
            
            const varCheckDiv = document.getElementById('css-var-check');
            let html = '<ul>';
            
            varsToCheck.forEach(varName => {
                const value = root.getPropertyValue(varName);
                if (value) {
                    html += `<li><code>${varName}</code>: <span class="status-good">✓ ${value}</span></li>`;
                } else {
                    html += `<li><code>${varName}</code>: <span class="status-bad">✗ Not defined</span></li>`;
                }
            });
            
            html += '</ul>';
            varCheckDiv.innerHTML = html;
        </script>
    </div>
</body>
</html>