<?php
session_start();
require_once '../config/db_config.php';
require_once '../config/translate_config.php'; // Include translation functionality

// Get article ID from URL
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch article details with category and author information
$article_query = "SELECT a.*, c.name as category_name, c.slug as category_slug, u.username as author_name 
                 FROM learning_articles a 
                 JOIN learning_categories c ON a.category_id = c.id 
                 JOIN users u ON a.author_id = u.id 
                 WHERE a.id = :id";
$stmt = $pdo->prepare($article_query);
$stmt->execute(['id' => $article_id]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: index.php');
    exit;
}

// Fetch related articles from the same category
$related_query = "SELECT a.*, u.username as author_name 
                 FROM learning_articles a 
                 JOIN users u ON a.author_id = u.id 
                 WHERE a.category_id = :category_id 
                 AND a.id != :article_id 
                 AND a.status = 'published'
                 ORDER BY a.created_at DESC 
                 LIMIT 3";
$stmt = $pdo->prepare($related_query);
$stmt->execute([
    'category_id' => $article['category_id'],
    'article_id' => $article_id
]);
$related_articles = $stmt->fetchAll();

// Fetch related products based on category
$products_query = "SELECT p.* FROM products p 
                  WHERE p.category LIKE :category 
                  AND p.status = 'available'
                  ORDER BY RAND() 
                  LIMIT 3";
$stmt = $pdo->prepare($products_query);
$stmt->execute(['category' => '%' . $article['category_name'] . '%']);
$related_products = $stmt->fetchAll();

// Get user data if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> - FarmKnowledge</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/learning-center.css">
    <style>
        /* General Layout */
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            color: #2c3e50;
            line-height: 1.6;
        }
        
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

.logo {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2e7d32;
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
    

        /* Update Article Layout Grid */
        .article-layout {
            display: grid;
            grid-template-columns: minmax(0, 1000px) 250px;
            gap: 2rem;
            max-width: 1300px;
            margin: 0 auto;
            padding: 2rem 20px;
        }

        /* Article Content Container */
        .article-content-wrapper {
            position: relative;
            display: grid;
            grid-template-columns: 250px minmax(0, 750px);
            gap: 2rem;
        }


        /* Article Header */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .article-header {
            animation: fadeIn 0.8s ease-out;
        }

        .article-category-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background-color: rgba(46, 125, 50, 0.1);
            color: #2e7d32;
            border-radius: 25px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .article-category-badge:hover {
            background-color: #2e7d32;
            color: white;
            transform: translateY(-2px);
        }

        .article-title {
            font-size: 2.8rem;
            color: #2c3e50;
            line-height: 1.3;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .article-meta {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            color: #666;
            font-size: 0.95rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Featured Image */
        .article-featured-image {
            width: 100%;
            height: 500px;
            border-radius: 12px;
            overflow: hidden;
            margin: 2rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease-out 0.2s backwards;
        }

        .article-featured-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .article-featured-image:hover img {
            transform: scale(1.02);
        }

        /* Table of Contents */
        .table-of-contents {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 100px;
            height: fit-content;
            max-height: calc(100vh - 150px);
            overflow-y: auto;
        }

        .table-of-contents h2 {
            color: #2e7d32;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #e8f5e9;
        }

        .toc-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .toc-list li {
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }

        .toc-list a {
            color: #2c3e50;
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            border-left: 3px solid transparent;
        }

        .toc-list a:hover,
        .toc-list a.active {
            background: #e8f5e9;
            color: #2e7d32;
            border-left-color: #2e7d32;
        }

        /* Article Content */
        .article-content {
            font-size: 1.2rem;
            line-height: 1.8;
            color: #2c3e50;
            margin-bottom: 3rem;
            animation: fadeIn 1s ease-out 0.4s backwards;
        }

        .article-content h2 {
            font-size: 2rem;
            color: #2c3e50;
            margin: 2.5rem 0 1.5rem;
            padding-top: 2rem;
            border-top: 2px solid #f0f0f0;
        }

        .article-content p {
            margin-bottom: 1.5rem;
        }

        .article-content ul,
        .article-content ol {
            margin: 1.5rem 0;
            padding-left: 1.5rem;
        }

        .article-content li {
            margin-bottom: 0.8rem;
        }

        /* Share Buttons */
        .share-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 3rem 0;
        }

        .share-button {
            display: inline-flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .share-button i {
            margin-right: 0.5rem;
        }

        .share-button.facebook {
            background: #1877f2;
        }

        .share-button.twitter {
            background: #1da1f2;
        }

        .share-button.linkedin {
            background: #0a66c2;
        }

        .share-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Author Box */
        .author-box {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin: 3rem 0;
            display: flex;
            align-items: center;
            gap: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .author-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #e8f5e9;
        }

        .author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .author-info h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .author-info p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 0;
        }

        /* Marketplace Sidebar */
        .marketplace-sidebar {
            background: linear-gradient(to bottom, #ffffff, #f9f9f9);
            border-radius: 12px;
            padding: 1.2rem;
            position: sticky;
            top: 100px;
            height: fit-content;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            scrollbar-width: thin;
            scrollbar-color: #2e7d32 #f0f0f0;
            border: 1px solid rgba(46, 125, 50, 0.1);
            width: 220px;
        }

        .marketplace-sidebar-header {
            text-align: center;
            margin-bottom: 1.2rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid rgba(46, 125, 50, 0.15);
            position: sticky;
            top: 0;
            background: linear-gradient(to bottom, #ffffff, #f9f9f9);
            z-index: 1;
        }

        .marketplace-sidebar-header h3 {
            color: #2e7d32;
            font-size: 1rem;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-weight: 600;
        }

        .marketplace-sidebar-header p {
            color: #666;
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .products-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: block;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.12);
            border-color: rgba(46, 125, 50, 0.2);
        }

        .product-image {
            position: relative;
            width: 100%;
            height: 100px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-info {
            padding: 0.6rem 0.8rem;
        }

        .product-title {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: #2c3e50;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.5rem;
        }

        .product-category {
            font-size: 0.65rem;
            color: #2e7d32;
            background: rgba(46, 125, 50, 0.1);
            padding: 0.15rem 0.4rem;
            border-radius: 20px;
            display: inline-block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            font-weight: 500;
        }

        .product-price {
            font-weight: 700;
            color: #2e7d32;
            font-size: 0.8rem;
            background: rgba(46, 125, 50, 0.05);
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
        }

        .product-price::before {
            content: '\f155';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 0.2rem;
            font-size: 0.7rem;
        }

        .view-marketplace {
            display: block;
            text-align: center;
            background: #2e7d32;
            color: white;
            padding: 0.6rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 0.8rem;
            box-shadow: 0 3px 10px rgba(46, 125, 50, 0.15);
        }

        .view-marketplace:hover {
            background: #1b5e20;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.2);
        }

        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .marketplace-sidebar {
                position: static;
                max-height: none;
                overflow: visible;
            }

            .products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Related Articles - Bigger Cards */
        .related-articles {
            padding: 2rem 0;
            background-color: #f8f9fa;
        }
        
        .related-articles .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.8rem;
        }
        
        .related-articles .article-card {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            max-height: 430px;
        }
        
        .related-articles .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(46, 125, 50, 0.12);
        }
        
        .related-articles .article-image {
            height: 180px;
        }
        
        .related-articles .article-content {
            padding: 1.4rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .related-articles .article-title {
            font-size: 1.2rem;
            line-height: 1.4;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .related-articles .article-excerpt {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 1.2rem;
            line-height: 1.6;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .related-articles .article-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 1rem;
            margin-top: auto;
        }
        
        .related-articles .article-author,
        .related-articles .article-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="reading-progress-bar"></div>
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
                    <li><a href="../marketplace.php">Marketplace</a></li>
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

    <main style="margin-top: 80px;">
        <div class="article-layout">
            <!-- Main Article Content -->
            <div class="article-container">
                <article>
                    <div class="article-header">
                        <a href="category.php?slug=<?= htmlspecialchars($article['category_slug']) ?>" 
                           class="article-category-badge">
                            <?= htmlspecialchars($article['category_name']) ?>
                        </a>
                        <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
                        <div class="article-meta">
                            <div class="meta-item">
                                <i class="fas fa-user"></i>
                                <span><?= htmlspecialchars($article['author_name']) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="far fa-calendar"></i>
                                <span><?= date('F j, Y', strtotime($article['created_at'])) ?></span>
                            </div>
                            <div class="meta-item reading-time">
                                <i class="far fa-clock"></i>
                                <span><?= ceil(str_word_count(strip_tags($article['content'])) / 200) ?> min read</span>
                            </div>
                        </div>
                    </div>

                    <?php if ($article['image']): ?>
                    <div class="article-featured-image">
                        <img src="<?= htmlspecialchars($article['image']) ?>" 
                             alt="<?= htmlspecialchars($article['title']) ?>">
                    </div>
                    <?php endif; ?>

                    <div class="article-content-wrapper">
                        <!-- Table of Contents -->
                        <div class="table-of-contents">
                            <h2>Table of Contents</h2>
                            <ul class="toc-list">
                                <?php
                                preg_match_all('/<h2>(.*?)<\/h2>/i', $article['content'], $matches);
                                foreach ($matches[1] as $index => $heading) {
                                    // Clean the heading text of any HTML tags or entities
                                    $clean_heading = htmlspecialchars(strip_tags(html_entity_decode($heading)));
                                    $anchor = 'section-' . ($index + 1);
                                    echo '<li><a href="#' . $anchor . '">' . $clean_heading . '</a></li>';
                                }
                                ?>
                            </ul>
                        </div>

                        <div class="article-content">
                            <?= $article['content'] ?>
                        </div>
                    </div>

                    <?php if (!empty($article['tags'])): ?>
                    <div class="article-tags">
                        <?php foreach(explode(',', $article['tags']) as $tag): ?>
                            <a href="search.php?tag=<?= urlencode(trim($tag)) ?>" class="tag">
                                #<?= htmlspecialchars(trim($tag)) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                           class="share-button facebook" target="_blank">
                            <i class="fab fa-facebook-f"></i> Share
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($article['title']) ?>" 
                           class="share-button twitter" target="_blank">
                            <i class="fab fa-twitter"></i> Tweet
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                           class="share-button linkedin" target="_blank">
                            <i class="fab fa-linkedin-in"></i> Share
                        </a>
                    </div>

                    <div class="author-box">
                        <div class="author-avatar">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($article['author_name']) ?>&background=2e7d32&color=fff" 
                                 alt="<?= htmlspecialchars($article['author_name']) ?>">
                        </div>
                        <div class="author-info">
                            <h3><?= htmlspecialchars($article['author_name']) ?></h3>
                            <p>Expert agricultural content creator and farming enthusiast. Sharing knowledge to help farmers succeed.</p>
                        </div>
                    </div>
                </article>

             

               

            <!-- Marketplace Sidebar -->
            <aside class="marketplace-sidebar">
                <div class="marketplace-sidebar-header">
                    <h3><i class="fas fa-shopping-basket"></i> Recommended Products</h3>
                    <p>Tools and supplies related to this topic</p>
                </div>
                <div class="products-grid">
                    <?php foreach($related_products as $product): ?>
                    <a href="../marketplace/product_details.php?id=<?= $product['id'] ?>" class="product-card">
                        <div class="product-image">
                            <img src="../<?= htmlspecialchars($product['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="product-info">
                            <h4 class="product-title"><?= htmlspecialchars($product['name']) ?></h4>
                            <?php if(isset($product['description'])): ?>
                            <p class="product-description">
                                <?php 
                                $plain_desc = html_entity_decode($product['description']); 
                                $plain_desc = preg_replace('/<[^>]*>/', ' ', $plain_desc);
                                $plain_desc = preg_replace('/\s+/', ' ', $plain_desc);
                                echo htmlspecialchars(substr(trim($plain_desc), 0, 80)) . (strlen($plain_desc) > 80 ? '...' : ''); 
                                ?>
                            </p>
                            <?php endif; ?>
                            <div class="product-meta">
                                <span class="product-category"><?= htmlspecialchars($product['category']) ?></span>
                                <span class="product-price">$<?= number_format($product['price'], 2) ?></span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($related_products)): ?>
                <div class="no-products">
                    <p>No related products found.</p>
                </div>
                <?php endif; ?>
                <a href="../marketplace/marketplace.php?category=<?= urlencode($article['category_name']) ?>" class="view-marketplace">
                    <i class="fas fa-store"></i> View More Products
                </a>
            </aside>
        </div>

        <?php if (!empty($related_articles)): ?>
        <section class="related-articles">
            <div class="container">
                <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem;">Related Articles</h2>
                <div class="articles-grid">
                    <?php foreach($related_articles as $related): 
                        // Process content to completely remove HTML tags
                        $excerpt = strip_tags($related['content']); 
                        $excerpt = preg_replace('/\s+/', ' ', $excerpt);
                        $excerpt = trim($excerpt);
                        $excerpt = substr($excerpt, 0, 150) . '...';
                    ?>
                    <a href="article.php?id=<?= $related['id'] ?>" class="article-card">
                        <div class="article-image">
                            <img src="<?= htmlspecialchars($related['image']) ?>" 
                                 alt="<?= htmlspecialchars($related['title']) ?>">
                        </div>
                        <div class="article-content">
                            <h3 class="article-title"><?= htmlspecialchars($related['title']) ?></h3>
                            <p class="article-excerpt"><?= htmlspecialchars($excerpt) ?></p>
                            <div class="article-meta">
                                <div class="article-author">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($related['author_name']) ?>
                                </div>
                                <div class="article-date">
                                    <i class="far fa-calendar"></i>
                                    <?= date('M d, Y', strtotime($related['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Existing progress bar and TOC code...

            // Animate sections on scroll
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.quick-facts, .expert-tips, .additional-resources, .next-steps').forEach(el => {
                observer.observe(el);
            });

            // Estimated reading time calculation
            const content = document.querySelector('.article-content');
            const wordsPerMinute = 200;
            const numberOfWords = content.innerText.split(/\s+/).length;
            const readingTime = Math.ceil(numberOfWords / wordsPerMinute);
            document.querySelector('.reading-time').textContent = `${readingTime} min read`;

            // Highlight current section in table of contents
            const headings = document.querySelectorAll('.article-content h2');
            const tocLinks = document.querySelectorAll('.toc-list a');
            
            const highlightTocLink = () => {
                let currentSectionId = null;
                
                headings.forEach(heading => {
                    const rect = heading.getBoundingClientRect();
                    if (rect.top <= 100) {
                        currentSectionId = heading.id;
                    }
                });

                tocLinks.forEach(link => {
                    const href = link.getAttribute('href').slice(1);
                    if (href === currentSectionId) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
            };

            window.addEventListener('scroll', highlightTocLink);
        });
    </script>

    <!-- Include Google Translate Script -->
    <?php echo get_translate_javascript(); ?>
</body>
</html>