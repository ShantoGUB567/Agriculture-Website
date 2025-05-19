<?php
session_start();
require_once '../config/db_config.php';
require_once '../config/translate_config.php';

// Get user data if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Fetch categories with article counts
$category_query = "SELECT c.*, COUNT(a.id) as article_count 
                  FROM learning_categories c 
                  LEFT JOIN learning_articles a ON c.id = a.category_id AND a.status = 'published'
                  GROUP BY c.id";
$categories = $pdo->query($category_query)->fetchAll();

// Fetch latest articles
$article_query = "SELECT a.*, c.name as category_name, c.slug as category_slug, u.username as author_name 
                 FROM learning_articles a 
                 JOIN learning_categories c ON a.category_id = c.id 
                 JOIN users u ON a.author_id = u.id 
                 WHERE a.status = 'published'
                 ORDER BY a.created_at DESC 
                 LIMIT 6";
$articles = $pdo->query($article_query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Center - FarmKnowledge</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/learning-center.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
   
   <style>

/* Navigation */
header {
    background: white;
    padding: 1rem 0;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
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
}

.nav-links li a {
    text-decoration: none;
    color: #333;
    font-weight: 500;
    transition: color 0.3s ease;
}

.nav-links li a:hover {
    color: #2e7d32;
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
       
    <script src="js/learning-center.js" defer></script>
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
                    <!-- Language Selector -->
                    <?php echo get_language_selector(); ?>
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
                    <!-- Language Selector -->
                    <?php echo get_language_selector(); ?>
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
            <h1>Agricultural Learning Center</h1>
            <p>Explore our comprehensive collection of farming knowledge, techniques, and best practices</p>
            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search for articles, topics, or farming techniques...">
            </div>
        </div>
    </section>

    <section class="categories-section container">
        <div class="section-header">
            <h2>Learning Categories</h2>
            <p>Browse through our diverse range of agricultural topics</p>
        </div>
        <div class="categories-wrapper">
            <?php foreach($categories as $category): ?>
            <a href="category.php?slug=<?= htmlspecialchars($category['slug']) ?>" class="category-card">
                <div class="category-image">
                    <img src="../Image/<?= htmlspecialchars($category['name']) ?>.jpg" alt="<?= htmlspecialchars($category['name']) ?>">
                </div>
                <div class="category-content">
                    <h3 class="category-title"><?= htmlspecialchars($category['name']) ?></h3>
                    <p class="category-description"><?= htmlspecialchars($category['description']) ?></p>
                    <div class="category-stats">
                        <span><i class="fas fa-book-open"></i> <?= $category['article_count'] ?> Articles</span>
                        <span><i class="fas fa-arrow-right"></i></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="section-divider"></div>

    <section class="articles-section">
        <div class="container">
            <div class="section-header">
                <h2>Latest Articles</h2>
                <p>Stay updated with our newest farming insights and guides</p>
            </div>
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
        </div>
    </section>

    <script>
        // Add scroll behavior for categories wrapper
        const categoriesWrapper = document.querySelector('.categories-wrapper');
        let isDown = false;
        let startX;
        let scrollLeft;

        categoriesWrapper.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - categoriesWrapper.offsetLeft;
            scrollLeft = categoriesWrapper.scrollLeft;
        });

        categoriesWrapper.addEventListener('mouseleave', () => {
            isDown = false;
        });

        categoriesWrapper.addEventListener('mouseup', () => {
            isDown = false;
        });

        categoriesWrapper.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - categoriesWrapper.offsetLeft;
            const walk = (x - startX) * 2;
            categoriesWrapper.scrollLeft = scrollLeft - walk;
        });

        // Search functionality
        const searchInput = document.querySelector('.search-input');
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                window.location.href = `search.php?q=${encodeURIComponent(this.value)}`;
            }
        });
    </script>

    <!-- Include Google Translate Script -->
    <?php echo get_translate_javascript(); ?>
</body>
</html>