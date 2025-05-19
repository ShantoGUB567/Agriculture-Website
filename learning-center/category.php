<?php
session_start();
require_once '../config/db_config.php';
require_once '../config/translate_config.php';

// Get category from URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Get user data if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Fetch category details
$category_query = "SELECT * FROM learning_categories WHERE slug = :slug";
$stmt = $pdo->prepare($category_query);
$stmt->execute(['slug' => $slug]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: index.php');
    exit;
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Fetch articles for this category with pagination
$articles_query = "SELECT a.*, u.username as author_name 
                  FROM learning_articles a 
                  JOIN users u ON a.author_id = u.id 
                  WHERE a.category_id = :category_id 
                  AND a.status = 'published'
                  ORDER BY a.created_at DESC 
                  LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($articles_query);
$stmt->bindValue(':category_id', $category['id'], PDO::PARAM_INT);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

// Get total articles count for pagination
$count_query = "SELECT COUNT(*) FROM learning_articles WHERE category_id = :category_id";
$stmt = $pdo->prepare($count_query);
$stmt->execute(['category_id' => $category['id']]);
$total_articles = $stmt->fetchColumn();
$total_pages = ceil($total_articles / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($category['name']) ?> - FarmKnowledge Learning Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/learning-center.css">
    <script src="js/learning-center.js" defer></script>
    <style>
        /* Enhanced Category Page Styles */
        body {
            background-color: #f9faf7;
            font-family: 'Poppins', sans-serif;
            color: #2c3e50;
            line-height: 1.6;
        }
        
        /* Navigation styles from article page */
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
        }

        .nav-left {
            flex: 0 0 20%;
        }

        .nav-middle {
            flex: 0 0 60%;
            display: flex;
            justify-content: center;
        }

        .nav-right {
            flex: 0 0 20%;
            display: flex;
            justify-content: flex-end;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2e7d32;
            text-decoration: none;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 1rem;
            margin: 0;
            padding: 0;
        }

        .nav-links li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 0.5rem;
        }

        .nav-links li a:hover {
            color: #2e7d32;
        }

        .nav-links li a.active {
            color: #2e7d32;
            font-weight: 600;
        }

        .auth-buttons a {
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .auth-buttons .login {
            background: #fff;
            color: #2e7d32;
            border: 2px solid #2e7d32;
        }

        .auth-buttons .login:hover {
            background: #2e7d32;
            color: #fff;
        }

        .auth-buttons .register {
            background: #2e7d32;
            color: #fff;
            border: none;
        }

        .auth-buttons .register:hover {
            background: #1b5e20;
        }

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
        
        .user-info::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 20px;
            background: transparent;
        }
        
        .user-dropdown {
            pointer-events: auto;
        }
        
        .user-dropdown li {
            transition: background-color 0.2s ease;
        }
        
        .user-dropdown li:hover {
            background-color: #f8f9fa;
        }
        
        .user-dropdown li {
            padding: 12px 20px;
            transition: background 0.2s ease;
        }
        
        .user-dropdown li:hover {
            background: #f5f5f5;
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
        
        /* Hero section styles (adjusted to account for fixed header) */
        .hero-section {
            background: linear-gradient(rgba(44, 85, 48, 0.85), rgba(44, 85, 48, 0.9)), 
                        url('../Image/<?= strtolower(str_replace(' ', '_', $category['name'])) ?>.jpg');
            background-size: cover;
            background-position: center;
            padding: 9rem 0 5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            margin-top: 0;
        }
        
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(to top, #f9faf7, transparent);
        }
        
        .hero-section .container {
            position: relative;
            z-index: 2;
        }
        
        .hero-section h1 {
            font-size: 3rem;
            margin-bottom: 1.2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            letter-spacing: -0.5px;
        }
        
        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.95;
            max-width: 700px;
            margin: 0 auto 2rem;
            line-height: 1.8;
        }
        
        .category-stats-banner {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 1.5rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .category-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .category-stat-value {
            font-size: 2rem;
            font-weight: 600;
        }
        
        .category-stat-label {
            font-size: 0.9rem;
            margin-top: 0.2rem;
            text-transform: uppercase;
            opacity: 0.8;
        }
        
        .search-bar {
            max-width: 650px;
        }
        
        .search-input {
            padding: 1.2rem 2rem;
            font-size: 1.05rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .articles-section {
            padding: 5rem 0;
            background-color: transparent;
        }
        
        .articles-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .articles-header h2 {
            color: #2c5530;
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
        }
        
        .articles-header p {
            color: #666;
            max-width: 700px;
            margin: 0 auto;
            font-size: 1.05rem;
        }
        
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2.5rem;
        }
        
        .article-card {
            border-radius: 16px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            border: 1px solid rgba(44, 85, 48, 0.08);
        }
        
        .article-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(44, 85, 48, 0.15);
            border-color: rgba(44, 85, 48, 0.15);
        }
        
        .article-image {
            height: 220px;
            border-radius: 16px 16px 0 0;
            position: relative;
        }
        
        .article-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.5), transparent);
            border-radius: 16px 16px 0 0;
        }
        
        .article-content {
            padding: 1.8rem;
            position: relative;
        }
        
        .article-title {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            line-height: 1.4;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .article-excerpt {
            font-size: 0.95rem;
            line-height: 1.7;
            color: #555;
            margin-bottom: 1.5rem;
        }
        
        .article-meta {
            display: flex;
            justify-content: space-between;
            padding-top: 1.2rem;
            border-top: 1px solid #eef2ee;
            color: #777;
        }
        
        .article-author {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
        }
        
        .article-author i {
            color: #2c5530;
        }
        
        .article-date {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
        }
        
        .article-date i {
            color: #2c5530;
        }
        
        .article-tag {
            position: absolute;
            top: -12px;
            left: 1.8rem;
            background: #2c5530;
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: 0 3px 10px rgba(44, 85, 48, 0.2);
        }
        
        .pagination {
            margin-top: 5rem;
        }
        
        .pagination-list {
            display: flex;
            justify-content: center;
            gap: 0.7rem;
            list-style: none;
            padding: 0;
        }
        
        .pagination-link {
            padding: 0.7rem 1.2rem;
            font-weight: 500;
            border-radius: 8px;
            min-width: 45px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            text-decoration: none;
            color: #333;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .pagination-link.active {
            box-shadow: 0 4px 12px rgba(44, 85, 48, 0.2);
        }
        
        .pagination-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(44, 85, 48, 0.15);
        }
        
        .no-articles {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }
        
        .no-articles h2 {
            color: #2c5530;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        
        .no-articles p {
            color: #666;
            font-size: 1.1rem;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.7;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 6rem 0 4rem;
            }
            
            .hero-section h1 {
                font-size: 2.2rem;
            }
            
            .hero-section p {
                font-size: 1rem;
            }
            
            .category-stats-banner {
                gap: 1.5rem;
            }
            
            .category-stat-value {
                font-size: 1.5rem;
            }
            
            .articles-grid {
                grid-template-columns: 1fr;
            }
            
            .articles-header h2 {
                font-size: 1.8rem;
            }
        }

        /* Ensure translation container is visible above everything */
        .translate-container {
            position: fixed;
            top: 20px;
            right: 100px;
            z-index: 9999 !important; /* Higher z-index to ensure visibility */
        }
    </style>
</head>
<body>
    <?php echo get_language_selector(); ?>
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

    <section class="hero-section">
        <div class="container">
            <h1><?= htmlspecialchars($category['name']) ?></h1>
            <p><?= htmlspecialchars($category['description']) ?></p>
            
            <div class="category-stats-banner">
                <div class="category-stat">
                    <span class="category-stat-value"><?= $total_articles ?></span>
                    <span class="category-stat-label">Articles</span>
                </div>
                <div class="category-stat">
                    <span class="category-stat-value"><?= rand(50, 300) ?></span>
                    <span class="category-stat-label">Views Today</span>
                </div>
            </div>
            
            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search in <?= htmlspecialchars($category['name']) ?>...">
            </div>
        </div>
    </section>

    <section class="articles-section">
        <div class="container">
            <div class="articles-header">
                <h2><?= htmlspecialchars($category['name']) ?> Articles</h2>
                <p>Explore our collection of articles covering various aspects of <?= strtolower(htmlspecialchars($category['name'])) ?> to enhance your farming knowledge.</p>
            </div>
            
            <div class="articles-grid">
                <?php if (empty($articles)): ?>
                    <div class="no-articles">
                        <h2>No articles found</h2>
                        <p>Check back later for new content in this category.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($articles as $article): ?>
                    <a href="article.php?id=<?= $article['id'] ?>" class="article-card">
                        <div class="article-image">
                            <img src="<?= !empty($article['image']) ? htmlspecialchars($article['image']) : '../Image/article/default-article.jpg' ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                        </div>
                        <div class="article-content">
                            <span class="article-tag">
                                <?= htmlspecialchars($category['name']) ?>
                            </span>
                            <h3 class="article-title"><?= htmlspecialchars($article['title']) ?></h3>
                            <p class="article-excerpt"><?= substr(strip_tags($article['content']), 0, 120) ?>...</p>
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
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <ul class="pagination-list">
                    <?php if ($page > 1): ?>
                        <li>
                            <a href="?slug=<?= $slug ?>&page=<?= $page - 1 ?>" class="pagination-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li>
                            <a href="?slug=<?= $slug ?>&page=<?= $i ?>" 
                               class="pagination-link <?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li>
                            <a href="?slug=<?= $slug ?>&page=<?= $page + 1 ?>" class="pagination-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
        // Search functionality
        const searchInput = document.querySelector('.search-input');
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                window.location.href = `search.php?q=${encodeURIComponent(this.value)}&category=<?= $category['slug'] ?>`;
            }
        });
    </script>
    
    <?php echo get_translate_javascript(); ?>
</body>
</html>