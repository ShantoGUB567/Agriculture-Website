<?php
session_start();
require_once '../config/db_config.php';

// Get user data if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}


$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build the search query
$params = [];
$where_clauses = [];

if ($search_query) {
    $where_clauses[] = "(a.title LIKE :search OR a.content LIKE :search)";
    $params[':search'] = "%{$search_query}%";
}

if ($category) {
    $where_clauses[] = "c.slug = :category";
    $params[':category'] = $category;
}

if ($tag) {
    $where_clauses[] = "a.tags LIKE :tag";
    $params[':tag'] = "%{$tag}%";
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Fetch articles
$articles_query = "SELECT a.*, c.name as category_name, c.slug as category_slug, u.username as author_name 
                  FROM learning_articles a 
                  JOIN learning_categories c ON a.category_id = c.id 
                  JOIN users u ON a.author_id = u.id 
                  {$where_sql}
                  ORDER BY a.created_at DESC 
                  LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($articles_query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

// Get total count for pagination
$count_query = "SELECT COUNT(*) 
                FROM learning_articles a 
                JOIN learning_categories c ON a.category_id = c.id 
                {$where_sql}";
$stmt = $pdo->prepare($count_query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_articles = $stmt->fetchColumn();
$total_pages = ceil($total_articles / $per_page);

// Fetch all categories for filter
$categories = $pdo->query("SELECT name, slug FROM learning_categories ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - FarmKnowledge Learning Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/learning-center.css">
    <script src="js/learning-center.js" defer></script>

    <style>
        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Navigation */
        header {
            background: white;
            padding: 1rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2e7d32;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .logo:hover {
            color: #1b5e20;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 2rem;
        }

        .nav-links li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-links li a:hover {
            color: #2e7d32;
        }

        .nav-links li a.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #2e7d32;
        }

        /* Search Filters Section */
        .search-filters {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .filter-group {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-item label {
            font-weight: 500;
            color: #555;
        }

        .filter-item input,
        .filter-item select {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .filter-item input:focus,
        .filter-item select:focus {
            border-color: #2e7d32;
            outline: none;
        }

        .search-button {
            background: #2e7d32;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
        }

        .search-button:hover {
            background: #1b5e20;
        }

        /* Search Results Section */
        .search-results-header {
            margin-bottom: 2rem;
        }

        .search-results-header h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .search-results-count {
            color: #666;
            font-size: 1.1rem;
        }

        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .article-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .article-image {
            height: 200px;
            overflow: hidden;
        }

        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .article-card:hover .article-image img {
            transform: scale(1.05);
        }

        .article-content {
            padding: 1.5rem;
        }

        .article-category {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .article-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #333;
            line-height: 1.4;
        }

        .article-excerpt {
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #888;
            font-size: 0.9rem;
        }

        .article-author, .article-date {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Pagination */
        .pagination {
            margin-top: 3rem;
            display: flex;
            justify-content: center;
        }

        .pagination-list {
            display: flex;
            gap: 0.5rem;
            list-style: none;
        }

        .pagination-link {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            background: white;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .pagination-link.active {
            background: #2e7d32;
            color: white;
            border-color: #2e7d32;
        }

        .pagination-link:hover:not(.active) {
            background: #f5f5f5;
            border-color: #2e7d32;
        }

        /* No Results State */
        .no-results {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .no-results h2 {
            color: #333;
            margin-bottom: 1rem;
        }

        .no-results p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .article-category-link {
            display: inline-block;
            padding: 12px 24px;
            background: #2e7d32;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .article-category-link:hover {
            background: #1b5e20;
        }

        /* User Dropdown Styles */
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            cursor: pointer;
        }

        .user-avatar {
            font-size: 1.5rem;
            color: #f1c40f;
        }

        .username {
            font-weight: 500;
        }

        .user-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            background: white;
            min-width: 200px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 100;
            border-radius: 4px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .user-info:hover .user-dropdown,
        .user-dropdown:hover {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            transition-delay: 0s;
        }

        /* Create a transparent gap to prevent accidental mouseout */
        .user-info::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 20px;
            background: transparent;
        }

        /* Keep dropdown interactive */
        .user-dropdown {
            pointer-events: auto;
        }

        .user-dropdown li {
            transition: background-color 0.2s ease;
        }

        .user-dropdown li:hover {
            background-color: #f8f9fa;
        }

        /* Add padding to dropdown items */
        .user-dropdown li {
            padding: 12px 20px;
            transition: background 0.2s ease;
        }

        .user-dropdown li:hover {
            background: #f5f5f5;
        }

        /* Style for user info area */
        .user-info {
            position: relative;
            padding: 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-info:hover {
            cursor: pointer;
        }

        .user-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .user-dropdown li {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }

        .user-dropdown li:last-child {
            border-bottom: none;
        }

        .user-dropdown a {
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }

        .user-dropdown a:hover {
            color: #2e7d32;
        }

    </style>
</head>
<body>
<header>
    <div class="container">
        <nav>
            <div class="nav-left">
                <a href="../farm-website-homepage.php" class="logo">FarmKnowledge</a>
            </div>
            <div class="nav-middle">
            <ul class="nav-links">
                    <li><a href="../farm-website-homepage.php">Home</a></li>
                    <li><a href="../about.php">About</a></li>
                    <li><a href="index.php" class="active">Learning Center</a></li>
                    <li><a href="..\marketplace\marketplace.php">Marketplace</a></li>
                    <li><a href="#">Community</a></li>
                    <li><a href="../news/index.php">News</a></li>
                </ul>
            </div>
            <div class="nav-right">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                        <div class="user-dropdown">
                            <ul>
                                <li><a href="../User/user-profile.php"><i class="fas fa-user"></i> My Account</a></li>
                                <li><a href="../User/sse_messaging.php"><i class="fas fa-envelope"></i> Messages</a></li>
                                <li><a href="../User/wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="../login.php" class="login">Login</a>
                        <a href="../signup.php" class="register">Sign Up</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

    <main style="margin-top: 80px;">
        <div class="container">
            <section class="search-filters">
                <form action="" method="GET">
                    <div class="filter-group">
                        <div class="filter-item">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="q" value="<?= htmlspecialchars($search_query) ?>" 
                                   placeholder="Search articles...">
                        </div>
                        <div class="filter-item">
                            <label for="category">Category</label>
                            <select name="category" id="category">
                                <option value="">All Categories</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['slug']) ?>" 
                                            <?= $category === $cat['slug'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </section>

            <section class="search-results">
                <div class="search-results-header">
                    <h2>Search Results</h2>
                    <p class="search-results-count">
                        Found <?= $total_articles ?> article<?= $total_articles !== 1 ? 's' : '' ?>
                        <?php if ($search_query || $category || $tag): ?>
                            <?php 
                            $filters = [];
                            if ($search_query) $filters[] = "matching \"" . htmlspecialchars($search_query) . "\"";
                            if ($category) {
                                foreach($categories as $cat) {
                                    if ($cat['slug'] === $category) {
                                        $filters[] = "in " . htmlspecialchars($cat['name']);
                                        break;
                                    }
                                }
                            }
                            if ($tag) $filters[] = "tagged with \"" . htmlspecialchars($tag) . "\"";
                            echo " " . implode(", ", $filters);
                            ?>
                        <?php endif; ?>
                    </p>
                </div>

                <?php if (empty($articles)): ?>
                    <div class="no-results">
                        <h2>No articles found</h2>
                        <p>Try adjusting your search terms or filters to find what you're looking for.</p>
                        <a href="index.php" class="article-category-link">Browse all articles</a>
                    </div>
                <?php else: ?>
                    <div class="articles-grid">
                        <?php foreach($articles as $article): ?>
                        <a href="article.php?id=<?= $article['id'] ?>" class="article-card">
                            <div class="article-image">
                                <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                            </div>
                            <div class="article-content">
                                <span class="article-category"><?= htmlspecialchars($article['category_name']) ?></span>
                                <h3 class="article-title"><?= htmlspecialchars($article['title']) ?></h3>
                                <p class="article-excerpt"><?= substr(htmlspecialchars($article['excerpt'] ?? $article['content']), 0, 150) ?>...</p>
                                <div class="article-meta">
                                    <div class="article-author">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($article['author_name']) ?>
                                    </div>
                                    <div class="article-date">
                                        <i class="far fa-calendar"></i>
                                        <?= date('M d, Y', strtotime($article['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <ul class="pagination-list">
                            <?php if ($page > 1): ?>
                                <li>
                                    <a href="?q=<?= urlencode($search_query) ?>&category=<?= urlencode($category) ?>&page=<?= $page - 1 ?>" 
                                       class="pagination-link">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li>
                                    <a href="?q=<?= urlencode($search_query) ?>&category=<?= urlencode($category) ?>&page=<?= $i ?>" 
                                       class="pagination-link <?= $i === $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li>
                                    <a href="?q=<?= urlencode($search_query) ?>&category=<?= urlencode($category) ?>&page=<?= $page + 1 ?>" 
                                       class="pagination-link">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>