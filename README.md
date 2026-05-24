# Universal SPA Engine (jarir-ahmed/universal-spa)

A lightning-fast, zero-reload Single Page Application (SPA) engine for **any** PHP Framework (Laravel, CodeIgniter, Symfony, Slim, or even raw PHP).

## Two Modes

This package empowers you with two distinct approaches:

### 1. HTML Mode (Zero Backend Required)
In this mode, the frontend JavaScript fetches the full HTML page, parses it in the browser using `DOMParser`, and swaps out the body. 
**Pros:** Requires ZERO PHP changes. Works with static sites or any backend.

### 2. JSON Mode (Bandwidth Optimized)
In this mode, the PHP backend intercepts the response buffer, parses the HTML, and sends only the required parts (Title, Body, Scripts) as a tiny JSON object.
**Pros:** Faster network transfer.

## Installation

```bash
composer require jarir-ahmed/universal-spa
```

## Setup (JSON Mode)

1. Put this at the very top of your PHP application (e.g., `public/index.php` or as a middleware):
```php
\JarirAhmed\UniversalSpa\SpaEngine::start();
```

2. Add `data-spa-content` to your main wrapper in your HTML layout:
```html
<main data-spa-content>
    <!-- Your page content goes here -->
</main>
```

3. Add the `data-spa` attribute to any links you want to feel instant:
```html
<a href="/about" data-spa>About Us</a>
```

4. Include the JS script and initialize it:
```html
<script src="path/to/resources/js/jarir-spa.js"></script>
<script>
    new JarirSpa({
        mode: 'json' // or 'html'
    });
</script>
```

## License
MIT
