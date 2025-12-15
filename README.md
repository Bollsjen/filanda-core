# PHP API Framework

A lightweight PHP framework inspired by ASP.NET Core Web API, featuring attribute-based routing, dependency injection patterns, and modern PHP 8+ features.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation & Setup](#installation--setup)
- [Core Components](#core-components)
  - [Autoloader](#autoloader)
  - [index.php](#indexphp)
  - [.htaccess](#htaccess)
  - [Program.php](#programphp)
- [How It Works](#how-it-works)
- [Creating Controllers](#creating-controllers)
- [HTTP Method Attributes](#http-method-attributes)
- [Parameter Binding](#parameter-binding)
- [Response Types](#response-types)
- [Authentication & Authorization](#authentication--authorization)
- [CORS Configuration](#cors-configuration)
- [Advanced Features](#advanced-features)

---

## Overview

This framework provides a modern, attribute-driven approach to building RESTful APIs in PHP, similar to ASP.NET Core Web API. It uses PHP 8's attributes (annotations) for routing, parameter binding, and authorization, making your API code clean, declarative, and easy to understand.

**Key Philosophy:**
- Convention over configuration
- Attribute-based routing and binding
- Type safety and modern PHP features
- Separation of concerns

---

## Features

✅ **Attribute-Based Routing** - Define routes using PHP 8 attributes  
✅ **HTTP Method Support** - GET, POST, PUT, DELETE, PATCH  
✅ **Automatic Parameter Binding** - From route, query, body, and form data  
✅ **Built-in Authentication** - Session-based auth with remember-me tokens  
✅ **Authorization Attributes** - Protect routes with `#[Authorize]`  
✅ **Action Results** - Structured responses (Ok, NotFound, NoContent, etc.)  
✅ **CORS Support** - Configurable cross-origin resource sharing  
✅ **Type-Safe Collections** - Generic `ListOf<T>` class similar to C# List  
✅ **PSR-4 Autoloading** - Custom autoloader with PSR-4 support  

---

## Requirements

- PHP 8.0 or higher (requires attributes support)
- Apache web server (or any server with URL rewriting)
- `mod_rewrite` enabled (for .htaccess)

---

## Installation & Setup

### 1. Clone or Download

```bash
git clone <your-repo-url>
cd your-project
```

### 2. Configure Web Server

#### Apache
The framework includes an `.htaccess` file for Apache. Ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx
If using Nginx, add this to your server block:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 3. Set Document Root

Point your web server's document root to the project root directory (where `index.php` is located).

### 4. Configure autoload.json

The `autoload.json` file defines namespace-to-directory mappings for the PSR-4 autoloader:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Core\\": "core/"
        }
    }
}
```

Add your own namespaces as needed.

### 5. Start Development Server (Optional)

For quick testing, use PHP's built-in server:

```bash
php -S localhost:8000
```

---

## Core Components

### Autoloader

**File:** `autoload.php`

The framework includes a custom PSR-4 compliant autoloader that eliminates the need for Composer in simple projects.

**How it works:**
1. Reads `autoload.json` to get namespace-to-directory mappings
2. Registers a PSR-4 autoloader with `spl_autoload_register()`
3. Automatically loads classes based on their namespace and file structure

**Example Structure:**
```
app/
  controllers/
    UserController.php    // Class: App\controllers\UserController
core/
  Routes.php             // Class: Core\Routes
```

**Namespace Resolution:**
- `App\controllers\UserController` → `app/controllers/UserController.php`
- `Core\responses\Ok` → `core/responses/Ok.php`

The autoloader handles all class loading automatically - you never need to manually `require` files (except for `autoload.php` itself).

---

### index.php

**File:** `index.php`

The entry point for all requests. This file is called for every HTTP request via the `.htaccess` rewrite rules.

**What it does:**

1. **Loads the Autoloader**
   ```php
   require_once __DIR__ . '/autoload.php';
   require_once __DIR__ . '/core/Routes.php';
   ```

2. **Initializes the Application**
   ```php
   \App\Program::Main();
   ```
   Calls your `Program::Main()` method for application startup (CORS, database, etc.)

3. **Parses the Request**
   ```php
   $requestUri = $_SERVER['REQUEST_URI'];
   $requestPath = parse_url($requestUri, PHP_URL_PATH);
   $requestMethod = $_SERVER['REQUEST_METHOD'];
   ```

4. **Routes API Requests**
   ```php
   if (str_starts_with($requestPath, '/api')) {
       \Core\Routes::init($requestPath, $requestMethod);
   }
   ```
   All requests starting with `/api` are routed through the framework's routing system

5. **Handles Non-API Requests** (Optional)
   You can add custom logic for serving web pages or other content for non-`/api` routes

**Customization:**
- Modify the `/api` prefix check to match your API route structure
- Add middleware, logging, or error handling before routing
- Implement web page routing in `handleWebRequest()`

---

### .htaccess

**File:** `.htaccess`

Apache configuration file that enables clean URLs by routing all requests to `index.php`.

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**What each line does:**

1. **`RewriteEngine On`**  
   Enables Apache's URL rewriting module

2. **`RewriteCond %{REQUEST_FILENAME} !-f`**  
   Condition: Don't rewrite if the request is for an actual file (e.g., images, CSS)

3. **`RewriteCond %{REQUEST_FILENAME} !-d`**  
   Condition: Don't rewrite if the request is for an actual directory

4. **`RewriteRule ^(.*)$ index.php [QSA,L]`**  
   Rule: Route everything else to `index.php`
   - `QSA` - Query String Append: preserves query parameters
   - `L` - Last: stop processing further rules

**Result:**  
Requests like `/api/users/123` are internally routed to `index.php` while preserving the original path in `$_SERVER['REQUEST_URI']`, enabling clean URLs without `.php` extensions.

---

### Program.php

**File:** `app/Program.php`

Your application's entry point class. This is where you configure application-level services and settings.

**Purpose:**
- Configure CORS
- Initialize database connections
- Register services
- Set up middleware
- Configure authentication

**Example:**

```php
<?php

namespace App;

use Core\cors\Cors;
use Core\cors\CorsOptions;

class Program {
    public static function Main(): void {
        // Configure CORS
        $corsOptions = new CorsOptions();
        $corsOptions
            ->allowOrigin(['http://localhost:3000', 'https://myapp.com'])
            ->allowMethods(['GET', 'POST', 'PUT', 'DELETE'])
            ->allowHeaders(['Content-Type', 'Authorization'])
            ->withCredentials(true)
            ->withMaxAge(600);

        new Cors($corsOptions);

        // Initialize other services
        // Database::connect();
        // AuthService::init();
    }
}
```

**Called By:** `index.php` before any routing occurs

---

## How It Works

### Request Lifecycle

1. **Request arrives** → Apache rewrites to `index.php` (via `.htaccess`)
2. **Autoloader loads** → Classes are loaded on-demand
3. **Program::Main() executes** → CORS and services initialize
4. **Routes::init() called** → Framework begins routing
5. **Controller discovery** → Scans for controllers extending `BaseController`
6. **Route matching** → Finds controller and method matching the request
7. **Authorization check** → Verifies `#[Authorize]` if present
8. **Parameter binding** → Extracts parameters from route, query, body, or form
9. **Method invocation** → Calls the matched controller method
10. **Response sent** → Returns `ActionResult` or auto-serializes to JSON

### Routing System

The framework uses reflection to discover routes from controller attributes:

1. **Scan for Controllers**
   - Finds all classes extending `Core\controller\BaseController`
   - Reads their `#[ApiController('/path')]` attributes

2. **Match Controller**
   - Compares request path with controller paths (longest match first)
   - Example: `/api/users/123` matches `#[ApiController('/api/users')]`

3. **Match Method**
   - Looks for methods with matching HTTP attributes (e.g., `#[HttpGet('{id}')]`)
   - Extracts route parameters from URL segments

4. **Parameter Binding**
   - Binds URL parameters, query strings, request body, or form data to method parameters
   - Uses attributes like `#[FromBody]`, `#[FromQuery]`, `#[FromRoute]`

---

## Creating Controllers

### Basic Controller Structure

```php
<?php

namespace App\controllers;

use Core\controller\BaseController;
use Core\attributes\ApiController;
use Core\attributes\HttpGet;
use Core\attributes\HttpPost;
use Core\attributes\HttpPut;
use Core\attributes\HttpDelete;
use Core\responses\Ok;
use Core\responses\NotFound;
use Core\responses\NoContent;

#[ApiController('/api/users')]
class UserController extends BaseController {
    
    #[HttpGet('')]
    public function getAll() {
        $users = ['John', 'Jane', 'Bob'];
        return new Ok($users);
    }
    
    #[HttpGet('{id}')]
    public function getById($id) {
        // Logic to fetch user by ID
        if ($user = $this->findUser($id)) {
            return new Ok($user);
        }
        return new NotFound();
    }
    
    #[HttpPost('')]
    public function create(#[FromBody] $userData) {
        // Create user from $userData array
        return new Ok(['id' => 123, 'message' => 'User created']);
    }
    
    #[HttpPut('{id}')]
    public function update($id, #[FromBody] $userData) {
        // Update user
        return new NoContent();
    }
    
    #[HttpDelete('{id}')]
    public function delete($id) {
        // Delete user
        return new NoContent();
    }
}
```

### Controller Requirements

1. **Extend BaseController**
   ```php
   class UserController extends BaseController
   ```

2. **Add ApiController Attribute**
   ```php
   #[ApiController('/api/users')]
   ```
   Defines the base route for all methods in this controller

3. **Use HTTP Method Attributes**
   Each public method should have an HTTP method attribute

---

## HTTP Method Attributes

### Available Attributes

- `#[HttpGet('path')]` - GET requests
- `#[HttpPost('path')]` - POST requests
- `#[HttpPut('path')]` - PUT requests
- `#[HttpPatch('path')]` - PATCH requests
- `#[HttpDelete('path')]` - DELETE requests

### Route Parameters

Use `{paramName}` syntax in the path:

```php
#[HttpGet('{id}')]
public function getUser($id) {
    // $id will be automatically bound from URL
    return new Ok(['userId' => $id]);
}

#[HttpGet('{userId}/posts/{postId}')]
public function getUserPost($userId, $postId) {
    // Both parameters extracted from URL
    return new Ok(['user' => $userId, 'post' => $postId]);
}
```

**Example URLs:**
- `/api/users/123` → `$id = '123'`
- `/api/users/456/posts/789` → `$userId = '456'`, `$postId = '789'`

---

## Parameter Binding

Parameters can be bound from different sources using attributes or by name matching.

### From Route (Default)

Parameters without attributes are automatically bound from route parameters:

```php
#[HttpGet('{id}')]
public function get($id) {
    // $id comes from URL: /api/users/123
}
```

### From Query String

```php
use Core\attributes\FromQuery;

#[HttpGet('search')]
public function search(#[FromQuery] $term, #[FromQuery] $page) {
    // URL: /api/users/search?term=john&page=2
    // $term = 'john', $page = '2'
}
```

### From Request Body (JSON)

```php
use Core\attributes\FromBody;

#[HttpPost('')]
public function create(#[FromBody] $userData) {
    // $userData is the decoded JSON body as an array
    $name = $userData['name'];
    $email = $userData['email'];
}
```

**Client sends:**
```json
{
    "name": "John Doe",
    "email": "john@example.com"
}
```

### From Form Data

```php
use Core\attributes\FromForm;

#[HttpPost('upload')]
public function upload(#[FromForm] $formData) {
    // $formData contains $_POST data
    $file = $_FILES['avatar'] ?? null;
}
```

### Mixed Parameters

```php
#[HttpPut('{id}')]
public function update(
    $id,                    // From route
    #[FromBody] $data,      // From JSON body
    #[FromQuery] $notify    // From query string
) {
    // PUT /api/users/123?notify=true
    // Body: {"name": "Jane"}
}
```

---

## Response Types

The framework provides structured response classes in `Core\responses\`.

### Ok (200)

```php
use Core\responses\Ok;

return new Ok(['message' => 'Success', 'data' => $users]);
```

Returns 200 status with JSON-encoded data.

### NoContent (204)

```php
use Core\responses\NoContent;

return new NoContent();
```

Returns 204 status with no body (for successful DELETE/UPDATE operations).

### NotFound (404)

```php
use Core\responses\NotFound;

return new NotFound(['error' => 'User not found']);
// or
return new NotFound(); // Default error message
```

### Unauthorized (401)

```php
use Core\responses\Unauthorized;

return new Unauthorized();
```

### File Response

```php
use Core\responses\File;

$imageData = file_get_contents('path/to/image.jpg');
return new File($imageData, 'image/jpeg', 3600);
```

### Auto-Serialization

If you return an array or object without wrapping it in an `ActionResult`, it's automatically JSON-encoded:

```php
#[HttpGet('')]
public function getAll() {
    return ['users' => $users]; // Automatically JSON-encoded
}
```

---

## Authentication & Authorization

### AuthenticationService

The framework includes a session-based authentication service in `Core\services\AuthenticationService`.

**Key Methods:**

```php
use Core\services\AuthenticationService;
use Core\models\AuthUser;

// Login
$user = new AuthUser();
$user->id = 1;
$user->email = 'user@example.com';
$user->roles = ['admin'];
AuthenticationService::login($user, $rememberMe = false);

// Check if authenticated
if (AuthenticationService::isAuthenticated()) {
    // User is logged in
}

// Get current user
$currentUser = AuthenticationService::getCurrentUser();

// Check role
if (AuthenticationService::hasRole('admin')) {
    // User has admin role
}

// Logout
AuthenticationService::logout();
```

### Protecting Routes with #[Authorize]

```php
use Core\attributes\Authorize;

#[ApiController('/api/admin')]
class AdminController extends BaseController {
    
    #[HttpGet('users')]
    #[Authorize]
    public function getUsers() {
        // Only authenticated users can access
        return new Ok($users);
    }
    
    #[HttpDelete('users/{id}')]
    #[Authorize(roles: ['admin'])]
    public function deleteUser($id) {
        // Only admin role can access (Note: role checking needs implementation)
        return new NoContent();
    }
}
```

**Behavior:**
- If `#[Authorize]` is present and user is not authenticated, returns `401 Unauthorized`
- User is redirected before the method executes

---

## CORS Configuration

Configure Cross-Origin Resource Sharing in `Program.php`:

```php
use Core\cors\Cors;
use Core\cors\CorsOptions;

$corsOptions = new CorsOptions();
$corsOptions
    ->allowOrigin(['http://localhost:3000', 'https://app.com'])
    ->allowMethods(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])
    ->allowHeaders(['Content-Type', 'Authorization', 'X-Custom-Header'])
    ->withCredentials(true)  // Allow cookies
    ->withMaxAge(600);       // Preflight cache duration in seconds

new Cors($corsOptions);
```

### CORS Methods

- `allowOrigin(string|array)` - Allowed origins (use `['*']` for all)
- `allowMethods(array)` - Allowed HTTP methods
- `allowHeaders(array)` - Allowed request headers
- `withCredentials(bool)` - Allow credentials (cookies, auth headers)
- `withMaxAge(int)` - Preflight cache duration

**Important:** When using `withCredentials(true)`, you cannot use `allowOrigin(['*'])`. You must specify exact origins.

---

## Advanced Features

### ListOf<T> - Type-Safe Collections

A C#-style generic list implementation:

```php
use Core\types\Collections\ListOf;

// Create a typed list
$users = new ListOf(User::class);
$users->add(new User('John'));
$users->add(new User('Jane'));

// Type safety - this would throw TypeError
// $users->add("string"); // Error!

// Iterate
foreach ($users as $user) {
    echo $user->name;
}

// Array-like access
$firstUser = $users[0];
$users[1] = new User('Bob');

// Common operations
$count = $users->count();
$users->remove($firstUser);
$users->clear();
$index = $users->indexOf($someUser);
$found = $users->find(fn($u) => $u->age > 18);
$filtered = $users->findAll(fn($u) => $u->isActive);

// JSON serialization
echo json_encode($users); // Automatically serializes
```

**Supported Types:**
- Scalar types: `'int'`, `'string'`, `'float'`, `'bool'`, `'array'`
- Object types: Any class name or interface
- Example: `new ListOf(Product::class)`

---

## Project Structure

```
your-project/
│
├── .htaccess              # Apache URL rewriting
├── index.php              # Application entry point
├── autoload.php           # PSR-4 autoloader
├── autoload.json          # Namespace mappings
│
├── app/                   # Your application code
│   ├── Program.php        # Application configuration
│   ├── controllers/       # API controllers
│   ├── models/            # Data models
│   ├── repositories/      # Data access layer
│   ├── managers/          # Business logic
│   └── utils/             # Helper utilities
│
└── core/                  # Framework core (don't modify)
    ├── Routes.php         # Routing engine
    ├── attributes/        # Route & binding attributes
    ├── controller/        # Base controller
    ├── responses/         # Response types
    ├── services/          # Auth service
    ├── cors/              # CORS handling
    ├── models/            # Framework models
    └── types/             # Type utilities (ListOf)
```

---

## Example: Complete CRUD API

```php
<?php

namespace App\controllers;

use Core\controller\BaseController;
use Core\attributes\ApiController;
use Core\attributes\HttpGet;
use Core\attributes\HttpPost;
use Core\attributes\HttpPut;
use Core\attributes\HttpDelete;
use Core\attributes\FromBody;
use Core\attributes\FromQuery;
use Core\attributes\Authorize;
use Core\responses\Ok;
use Core\responses\NotFound;
use Core\responses\NoContent;

#[ApiController('/api/products')]
class ProductController extends BaseController {
    
    // GET /api/products
    #[HttpGet('')]
    public function list(#[FromQuery] $page = 1, #[FromQuery] $limit = 10) {
        $products = $this->repository->getAll($page, $limit);
        return new Ok($products);
    }
    
    // GET /api/products/123
    #[HttpGet('{id}')]
    public function get($id) {
        $product = $this->repository->findById($id);
        
        if (!$product) {
            return new NotFound(['error' => 'Product not found']);
        }
        
        return new Ok($product);
    }
    
    // POST /api/products
    #[HttpPost('')]
    #[Authorize]
    public function create(#[FromBody] $productData) {
        $product = $this->repository->create($productData);
        return new Ok($product);
    }
    
    // PUT /api/products/123
    #[HttpPut('{id}')]
    #[Authorize]
    public function update($id, #[FromBody] $productData) {
        $this->repository->update($id, $productData);
        return new NoContent();
    }
    
    // DELETE /api/products/123
    #[HttpDelete('{id}')]
    #[Authorize]
    public function delete($id) {
        $this->repository->delete($id);
        return new NoContent();
    }
    
    // GET /api/products/search?term=laptop
    #[HttpGet('search')]
    public function search(#[FromQuery] $term) {
        $results = $this->repository->search($term);
        return new Ok($results);
    }
}
```

**Resulting Endpoints:**
- `GET /api/products` - List all products
- `GET /api/products/123` - Get product by ID
- `GET /api/products/search?term=laptop` - Search products
- `POST /api/products` - Create product (requires auth)
- `PUT /api/products/123` - Update product (requires auth)
- `DELETE /api/products/123` - Delete product (requires auth)

---

## Tips & Best Practices

### 1. Controller Organization
- One controller per resource (e.g., `UserController`, `ProductController`)
- Keep controllers thin - delegate to services/managers
- Use consistent naming: `list()`, `get($id)`, `create()`, `update()`, `delete()`

### 2. Response Consistency
- Always use `ActionResult` types for explicit status codes
- Return consistent JSON structures (e.g., `{data: ..., error: ...}`)

### 3. Validation
- Validate input in controllers before passing to services
- Return appropriate error responses (400 Bad Request, 422 Unprocessable Entity)

### 4. Error Handling
- Add try-catch blocks in controllers
- Log errors appropriately
- Return user-friendly error messages

### 5. Security
- Always use `#[Authorize]` on protected routes
- Sanitize and validate all user input
- Use parameterized queries to prevent SQL injection
- Enable HTTPS in production
- Configure CORS properly for production

---

## Troubleshooting

### 404 Errors on All Routes
- Check if `mod_rewrite` is enabled
- Verify `.htaccess` file exists and is readable
- Ensure `AllowOverride All` in Apache config

### Class Not Found Errors
- Check namespace matches file structure
- Verify `autoload.json` mappings
- Ensure class name matches filename

### CORS Errors
- Verify allowed origins include your frontend URL
- Check that preflight OPTIONS requests are working
- Enable error logging in `Cors.php` to debug

### Authentication Not Working
- Ensure `session_start()` is called before using auth
- Check that cookies are enabled
- Verify session configuration (domain, secure flags)

---

## License

[Your License Here]

---

## Contributing

Contributions are welcome! Please submit pull requests or open issues for bugs and feature requests.

---

## Credits

Inspired by ASP.NET Core Web API and modern PHP frameworks.

