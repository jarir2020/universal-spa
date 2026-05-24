<?php
require_once __DIR__ . '/../src/SpaEngine.php';
\JarirAhmed\UniversalSpa\SpaEngine::start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - Universal SPA Test</title>
    <style>
        body { font-family: sans-serif; background: #f0f4f8; padding: 20px; }
        nav a { margin-right: 15px; text-decoration: none; color: #0056b3; font-weight: bold; }
        nav a.active { color: #d9534f; text-decoration: underline; }
        main { background: white; padding: 20px; margin-top: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <h2>Universal SPA Testing Sandbox</h2>
    <nav>
        <a href="index.php" data-spa>Home</a>
        <a href="about.php" data-spa>About Us</a>
    </nav>
    
    <main data-spa-content>
        <h1>Welcome to the Homepage</h1>
        <p>Notice that clicking the links updates the time below without refreshing the whole page.</p>
        <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    </main>

    <script src="../resources/js/jarir-spa.js"></script>
    <script>
        // Try changing this to 'html' to see it work without the PHP backend assistance!
        window.spaApp = new JarirSpa({ mode: 'json' }); 
    </script>
</body>
</html>
