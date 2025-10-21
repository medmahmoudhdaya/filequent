# 📂 Filequent

**A lightweight, file-based ORM-like system for PHP**  
Developed by **Zidbih**

---

**Filequent** allows you to build and interact with data models in a clean, Eloquent-style syntax — without using a traditional database. Data is saved to structured JSON files, making this ideal for local development, prototypes, lightweight apps, or embedded systems.

---

## 🚀 Features

- Eloquent-style model structure
- Stores data as JSON files (no database required)
- Simple CRUD support: `create`, `find`, `all`, `update`, `delete`, `where`, etc.
- Relationship support: `hasMany`, `hasOne`, `belongsTo`
- Default and custom foreign key handling
- Extensible and easy to test
- Fully tested with PHPUnit

---

## 📦 Installation

Install via Composer:

```bash
composer require zidbih/filequent
```

---

## 🧱 Defining Models

Create model classes by extending `Zidbih\Filequent\Filequent` and define:

- `$collection` — JSON file name (without `.json`)
- `$basePath` (optional) — directory where the file is saved

```php
use Zidbih\Filequent\Filequent;

class User extends Filequent
{
    protected static string $collection = 'users';
    protected static ?string $basePath = __DIR__ . '/data';

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function latestPost()
    {
        return $this->hasOne(Post::class);
    }
}

class Post extends Filequent
{
    protected static string $collection = 'posts';
    protected static ?string $basePath = __DIR__ . '/data';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## ✨ CRUD Operations

### Create

```php
$user = User::create(['name' => 'Alice']);
```

### Read

```php
$found = User::find($user->id);// by ID
$allUsers = User::all();// all records
```

### Update

```php
$user->update(['name' => 'Updated']);
```

### Delete

```php
$user->delete();
```

---

## 🔍 Query Builder

Filequent supports method chaining similar to Eloquent:

```php
$results = User::where('name', '=', 'Alice')->get();

$firstMatch = User::where('age', '>', 25)->first();

$likeMatches = User::where('name', 'LIKE', 'Ah%')->get();
```

Supported operators: `=`, `!=`, `>`, `<`, `LIKE`

---

## 🔗 Relationships

### hasMany

```php
$user = User::find(1);
$posts = $user->posts(); // Returns all posts with user_id = $user->id
```

### hasOne

```php
$post = $user->latestPost(); // Returns one related Post
```

### belongsTo

```php
$post = Post::find(1);
$user = $post->user(); // Returns the User who owns the post
```

### Custom Foreign Key

You can specify a custom foreign key:

```php
return $this->belongsTo(User::class, 'custom_user_id');
```

---

## 💾 Where Is Data Stored?

By default, JSON files are saved in a `/data` folder under the project root.

```bash
your-project/
├── data/
│   ├── users.json
│   └── posts.json
```

You can override this using the static `$basePath` property on each model.

---

## 🧪 Testing

Filequent includes a fully-featured test suite using **PHPUnit**.

### Setup

```bash
vendor/bin/phpunit
```

### Structure

```
tests/
├── FilequentTest.php
├── testData/
│   ├── users.json
│   └── posts.json
```

The tests cover:

- File-level operations (insert/read)
- All CRUD methods
- Advanced query logic (chaining, LIKE)
- Relationship resolution
- Custom foreign keys
- Error cases and edge handling

---

## 📂 Example Usage

```php
$user = User::create(['name' => 'Ahmed']);

Post::create([
    'title' => 'Hello World',
    'body' => 'Welcome to Filequent!',
    'user_id' => $user->id,
]);

// Retrieve all posts by this user
$posts = $user->posts();

// Get the user for a post
$post = Post::find(1);
$owner = $post->user();
```

---

## 🧑‍💻 Author

**Zidbih**  
GitHub: [https://github.com/medmahmoudhdaya](https://github.com/medmahmoudhdaya)

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

## 🤝 Contributing

Pull requests, bug reports, and suggestions are welcome!

If you like the package, consider ⭐ starring it on GitHub and sharing it with others.

---

*Filequent — Simplify data storage, without a database.*
