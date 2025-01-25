<?php
define('INCLUDED_PATH', dirname(__FILE__));
require_once INCLUDED_PATH . '/data-provider.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $posts = getPostsData();
    
    $platforms = array_unique(array_filter(array_column($posts, 'PlatformName')));
    $formats = array_unique(array_filter(array_column($posts, 'Format')));
    $clients = array_unique(array_filter(array_column($posts, 'ClientName')));

    sort($platforms);
    sort($formats);
    sort($clients);
} catch (Exception $e) {
    die("Error loading data: " . $e->getMessage());
}

function getContainerClass($format) {
    if ($format === 'Site') {
        return 'desktop';
    } elseif ($format === 'Long-Form Video') {
        return 'video';
    }
    return 'mobile';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Posts Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .iframe-container {
            position: relative;
            margin: 20px auto;
            background: #000;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        /* Mobile device styling */
        /* Mobile device styling */
        .iframe-container.mobile {
            width: 375px;
            height: 812px;
            border-radius: 40px;
        }
        .iframe-container.mobile::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 20px;
            background: #000;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }
        .iframe-container.mobile iframe {
            border-radius: 20px;
        }

        /* Desktop viewport styling */
        .iframe-container.desktop {
            width: 1200px;
            height: 800px;
            border-radius: 10px;
        }
        .iframe-container.desktop::before {
            content: '';
            position: absolute;
            top: -30px;
            left: 0;
            right: 0;
            height: 30px;
            background: #f0f0f0;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        /* Video 16:9 styling */
        .iframe-container.video {
            width: 100%;
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            background: none;
            box-shadow: none;
        }
        .iframe-container.video iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .iframe-container iframe {
            width: 100%;
            height: 100%;
            border: none;
            background: #fff;
        }

        @media (max-width: 1400px) {
            .iframe-container.desktop {
                width: 100%;
                height: 600px;
            }
        }

        @media (max-width: 768px) {
            .iframe-container.mobile {
                width: 100%;
                max-width: 375px;
                height: 812px;
            }
        }

        /* Card styling */
        .card {
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #f8f9fa;
            padding: 1rem;
            min-height: 120px; /* Accommodate larger QR code */
        }
        .card-header h5 {
            font-size: 1.1rem;
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }
        .card-header small {
            font-size: 0.85rem;
            display: block;
            margin-bottom: 0.5rem;
        }
        .card-header .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .card-header .qr-code {
            width: 80px !important;
            height: 80px !important;
            flex-shrink: 0;
            object-fit: contain;
        }
        .card-body.p-0 {
            overflow: hidden;
        }
        .card-footer {
            background-color: #f8f9fa;
            padding: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <h1 class="mb-4">Posts Dashboard</h1>
        
        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-md-4">
                <label for="platformFilter" class="form-label">Platform</label>
                <select class="form-select" id="platformFilter">
                    <option value="">All Platforms</option>
                    <?php foreach($platforms as $platform): ?>
                        <option value="<?php echo htmlspecialchars($platform); ?>">
                            <?php echo htmlspecialchars($platform); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="formatFilter" class="form-label">Format</label>
                <select class="form-select" id="formatFilter">
                    <option value="">All Formats</option>
                    <?php foreach($formats as $format): ?>
                        <option value="<?php echo htmlspecialchars($format); ?>">
                            <?php echo htmlspecialchars($format); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="clientFilter" class="form-label">Client</label>
                <select class="form-select" id="clientFilter">
                    <option value="">All Clients</option>
                    <?php foreach($clients as $client): ?>
                        <option value="<?php echo htmlspecialchars($client); ?>">
                            <?php echo htmlspecialchars($client); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Posts Grid -->
        <div class="row" id="postsGrid">
            <?php foreach($posts as $post): 
                $containerClass = getContainerClass($post['Format']);
            ?>
            <div class="col-12 <?php echo ($containerClass === 'desktop') ? 'col-xl-12' : 'col-xl-4'; ?> post-item" 
                 data-platform="<?php echo htmlspecialchars($post['PlatformName'] ?? ''); ?>"
                 data-format="<?php echo htmlspecialchars($post['Format'] ?? ''); ?>"
                 data-client="<?php echo htmlspecialchars($post['ClientName'] ?? ''); ?>">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><?php echo htmlspecialchars($post['ClientName'] ?? 'Unknown Client'); ?></h5>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($post['PlatformName'] ?? 'Unknown Platform'); ?> | 
                                    <?php echo htmlspecialchars($post['Format'] ?? 'Unknown'); ?>
                                </small>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <?php if(!empty($post['QR Code URL'])): ?>
                                    <img src="<?php echo htmlspecialchars($post['QR Code URL']); ?>" 
                                         alt="QR Code"
                                         class="qr-code">
                                <?php endif; ?>
                                <?php if(isset($post['URL'])): ?>
                                    <a href="<?php echo htmlspecialchars($post['URL']); ?>" 
                                       class="btn btn-primary btn-sm" 
                                       target="_blank">View Original</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if($post['Format'] === 'Long-Form Video' && isset($post['Embed Code'])): ?>
                            <div class="iframe-container video">
                                <?php echo $post['Embed Code']; ?>
                            </div>
                        <?php elseif(isset($post['URL'])): ?>
                            <div class="iframe-container <?php echo htmlspecialchars($containerClass); ?>">
                                <iframe src="<?php echo htmlspecialchars($post['URL']); ?>" 
                                        allowfullscreen></iframe>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if(!empty($post['Description'])): ?>
                    <div class="card-footer">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($post['Description'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const filters = {
            platform: '',
            format: '',
            client: ''
        };

        function filterPosts() {
            const posts = document.querySelectorAll('.post-item');
            let visibleCount = 0;
            
            posts.forEach(post => {
                const matchesPlatform = !filters.platform || post.dataset.platform === filters.platform;
                const matchesFormat = !filters.format || post.dataset.format === filters.format;
                const matchesClient = !filters.client || post.dataset.client === filters.client;
                
                if (matchesPlatform && matchesFormat && matchesClient) {
                    post.style.display = '';
                    visibleCount++;
                } else {
                    post.style.display = 'none';
                }
            });
        }

        document.getElementById('platformFilter').addEventListener('change', function(e) {
            filters.platform = e.target.value;
            filterPosts();
        });

        document.getElementById('formatFilter').addEventListener('change', function(e) {
            filters.format = e.target.value;
            filterPosts();
        });

        document.getElementById('clientFilter').addEventListener('change', function(e) {
            filters.client = e.target.value;
            filterPosts();
        });
    });
    </script>
</body>
</html>