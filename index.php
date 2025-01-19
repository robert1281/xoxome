<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'settings.php';

$auth = new Auth();
$settings = new Settings();
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="site-title"><?php echo $settings->getSiteName(); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <style>
        body {
            background-color: #0a0a0a;
            color: #00ff00;
            font-family: 'Courier New', Courier, monospace;
        }
        .navbar {
            background-color: #1a1a1a !important;
            border-bottom: 1px solid #00ff00;
        }
        .card {
            background-color: #1a1a1a;
            border: 1px solid #00ff00;
            margin-bottom: 1rem;
        }
        .card:hover {
            box-shadow: 0 0 10px #00ff00;
            transition: all 0.3s ease;
        }
        .btn-hack {
            background-color: #1a1a1a;
            color: #00ff00;
            border: 1px solid #00ff00;
        }
        .btn-hack:hover {
            background-color: #00ff00;
            color: #1a1a1a;
        }
        .user-name {
            color: #ff69b4 !important;
        }
        .loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
        }
        .loading:after {
            content: '';
            display: block;
            width: 40px;
            height: 40px;
            border: 4px solid #00ff00;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .pagination .page-link {
            background-color: #1a1a1a;
            color: #00ff00;
            border-color: #00ff00;
        }
        .pagination .page-link:hover {
            background-color: #00ff00;
            color: #1a1a1a;
        }
        .top-post {
            border: 2px solid #ff0000 !important;
        }
        .modal-dialog {
            max-width: 800px;
        }
        .modal-body textarea {
            min-height: 300px;
            width: 100%;
            padding: 10px;
        }
        .modal-body input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }
        .tag-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 0;
        }
        .tag {
            display: inline-block;
            padding: 4px 12px;
            border: 1px solid #00ff00;
            border-radius: 15px;
            color: #00ff00;
            cursor: pointer;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            font-size: 14px;
        }
        .tag:hover {
            background-color: #00ff00;
            color: #1a1a1a;
            transform: scale(1.1);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        .tag:nth-child(2n) {
            animation-delay: 0.5s;
        }
        .tag:nth-child(3n) {
            animation-delay: 1s;
        }
        .tag:nth-child(4n) {
            animation-delay: 1.5s;
        }
        /* 编辑器样式 */
        .ql-container {
            font-size: 16px;
            height: 300px;
            margin-bottom: 15px;
            background: #1a1a1a;
            border-color: #00ff00 !important;
            color: #00ff00;
        }
        .ql-toolbar {
            background: #1a1a1a;
            border-color: #00ff00 !important;
        }
        .ql-toolbar button {
            color: #00ff00 !important;
        }
        .ql-toolbar button:hover {
            color: #1a1a1a !important;
            background: #00ff00;
        }
        .ql-toolbar .ql-stroke {
            stroke: #00ff00 !important;
        }
        .ql-toolbar .ql-fill {
            fill: #00ff00 !important;
        }
        .ql-toolbar button:hover .ql-stroke {
            stroke: #1a1a1a !important;
        }
        .ql-toolbar button:hover .ql-fill {
            fill: #1a1a1a !important;
        }
        .ql-snow.ql-toolbar button.ql-active,
        .ql-snow .ql-toolbar button.ql-active {
            background: #00ff00;
        }
        .ql-snow.ql-toolbar button.ql-active .ql-stroke,
        .ql-snow .ql-toolbar button.ql-active .ql-stroke {
            stroke: #1a1a1a !important;
        }
        .ql-snow.ql-toolbar button.ql-active .ql-fill,
        .ql-snow .ql-toolbar button.ql-active .ql-fill {
            fill: #1a1a1a !important;
        }
        .ql-editor.ql-blank::before {
            color: rgba(0, 255, 0, 0.6);
        }
        .ql-editor {
            color: #00ff00;
        }
        .ql-editor img {
            max-width: 100%;
            height: auto;
        }
        /* 拖拽提示样式 */
        .ql-editor.drag-over {
            border: 2px dashed #00ff00;
        }
        /* 附件样式 */
        .attachment {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            margin: 10px 0;
            background: #2a2a2a;
            border: 1px solid #00ff00;
            border-radius: 4px;
        }
        .attachment i {
            font-size: 20px;
            color: #00ff00;
        }
        .attachment a {
            color: #00ff00;
            text-decoration: none;
        }
        .attachment a:hover {
            text-decoration: underline;
        }
        .attachment .file-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .attachment .file-size {
            font-size: 12px;
            color: #666;
        }
        .post-content {
            padding: 15px;
            line-height: 1.6;
            word-break: break-word;
        }
        .post-content img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #00ff00;
        }
        .post-content p {
            margin-bottom: 1rem;
        }
        .post-content a {
            color: #00ff00;
            text-decoration: none;
        }
        .post-content a:hover {
            text-decoration: underline;
        }
        .post-content pre, .post-content code {
            background: #2a2a2a;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .post-content blockquote {
            border-left: 3px solid #00ff00;
            padding-left: 1rem;
            margin-left: 0;
            color: #00cc00;
        }
        /* 添加帖子标题样式 */
        .post-title {
            font-weight: bold;
            color: #00ff00;
            text-shadow: 0 0 5px rgba(0, 255, 0, 0.5);
            transition: all 0.3s ease;
            text-decoration: none !important;
        }
        .post-title:hover {
            color: #fff;
            text-shadow: 0 0 10px #00ff00;
            text-decoration: none !important;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php" id="site-name"><?php echo $settings->getSiteName(); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-house-fill"></i> 首页</a>
                    </li>
                    <?php if($auth->isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php"><i class="bi bi-gear-fill"></i> 管理后台</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex">
                    <?php if($currentUser): ?>
                        <?php if($currentUser['username'] !== 'guest'): ?>
                        <span class="navbar-text me-3">
                            欢迎, <span class="user-name"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                        </span>
                        <button class="btn btn-hack btn-sm" onclick="logout()">
                            <i class="bi bi-box-arrow-right"></i> 登出
                        </button>
                        <?php else: ?>
                        <span class="navbar-text me-3">
                            游客身份
                        </span>
                        <button class="btn btn-hack btn-sm me-2" onclick="showLoginModal()">
                            <i class="bi bi-person-fill"></i> 登录
                        </button>
                        <button class="btn btn-hack btn-sm" onclick="showRegisterModal()">
                            <i class="bi bi-person-plus-fill"></i> 注册
                        </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-hack btn-sm me-2" onclick="showLoginModal()">
                            <i class="bi bi-person-fill"></i> 登录
                        </button>
                        <button class="btn btn-hack btn-sm" onclick="showRegisterModal()">
                            <i class="bi bi-person-plus-fill"></i> 注册
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主要内容 -->
    <div class="container">
        <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars($_SESSION['error']); 
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <!-- 发帖按钮 -->
                <?php 
                $canPost = $currentUser || $settings->isGuestPostAllowed();
                if($canPost): 
                ?>
                <button class="btn btn-hack mb-4" onclick="showPostModal()">
                    <i class="bi bi-plus-circle-fill"></i> 发布新帖子
                </button>
                <?php endif; ?>
                
                <div id="posts-container"></div>
                <div class="loading" id="loading"></div>
                <nav aria-label="分页" class="mt-4">
                    <ul class="pagination justify-content-center" id="pagination"></ul>
                </nav>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-info-circle-fill"></i> 关于论坛</h5>
                        <p class="card-text" id="site-description"><?php echo $settings->getSiteDescription(); ?></p>
                    </div>
                </div>
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-tags-fill"></i> 热门标签</h5>
                        <div class="tag-cloud" id="tagCloud">
                            <!-- 标签将通过 JavaScript 动态加载 -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 登录模态框 -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header border-success">
                    <h5 class="modal-title">用户登录</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm" method="post" onsubmit="event.preventDefault(); login();">
                        <div class="mb-3">
                            <label class="form-label">用户名</label>
                            <input type="text" class="form-control bg-dark text-light" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">密码</label>
                            <input type="password" class="form-control bg-dark text-light" name="password" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-hack">登录</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 注册模态框 -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header border-success">
                    <h5 class="modal-title">用户注册</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="registerForm" method="post" onsubmit="event.preventDefault(); register();">
                        <div class="mb-3">
                            <label class="form-label">用户名</label>
                            <input type="text" class="form-control bg-dark text-light" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">邮箱</label>
                            <input type="email" class="form-control bg-dark text-light" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">密码</label>
                            <input type="password" class="form-control bg-dark text-light" name="password" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-hack">注册</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 发帖模态框 -->
    <div class="modal fade" id="postModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header border-success">
                    <h5 class="modal-title">发布新帖子</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="postForm">
                        <div class="mb-3">
                            <label for="post-title" class="form-label">标题</label>
                            <input type="text" class="form-control bg-dark text-success border-success" id="post-title" required>
                        </div>
                        <div class="mb-3">
                            <label for="editor" class="form-label">内容</label>
                            <div id="editor"></div>
                            <input type="hidden" id="post-content">
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-success">
                    <button type="button" class="btn btn-hack" onclick="submitPost()">
                        <i class="bi bi-send-fill"></i> 发布
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 帖子详情模态框 -->
    <div class="modal fade" id="postDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark">
                <div class="modal-header border-success">
                    <h5 class="modal-title" id="postDetailTitle"></h5>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-hack btn-sm me-2" id="editPostBtn" onclick="showEditPostModal()">
                            <i class="bi bi-pencil-fill"></i> 编辑
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <small class="text-muted">
                            作者: <span id="postDetailAuthor" class="user-name"></span>
                            发布于 <span id="postDetailTime"></span>
                            <span class="ms-2">
                                <i class="bi bi-eye-fill"></i> <span id="postDetailViews"></span>
                                <i class="bi bi-chat-fill ms-2"></i> <span id="postDetailComments"></span>
                            </span>
                        </small>
                    </div>
                    <div id="postDetailContent" class="post-content mb-4"></div>
                    
                    <!-- 评论列表 -->
                    <div class="comments-section mt-4">
                        <h5 class="mb-3"><i class="bi bi-chat-dots-fill"></i> 评论</h5>
                        <div id="commentsList"></div>
                    </div>
                    
                    <!-- 评论表单 -->
                    <?php 
                    $canComment = $currentUser || $settings->isGuestPostAllowed();
                    if($canComment): 
                    ?>
                    <div class="comment-form mt-4">
                        <form id="commentForm" onsubmit="event.preventDefault(); submitComment();">
                            <div class="mb-3">
                                <textarea class="form-control bg-dark text-light" id="commentContent" rows="3" placeholder="写下你的评论..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-hack">发表评论</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning mt-4">
                        请<a href="#" onclick="showLoginModal()" class="alert-link">登录</a>后发表评论
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 编辑帖子模态框 -->
    <div class="modal fade" id="editPostModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header border-success">
                    <h5 class="modal-title">编辑帖子</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPostForm">
                        <input type="hidden" id="edit-post-id">
                        <div class="mb-3">
                            <label for="edit-post-title" class="form-label">标题</label>
                            <input type="text" class="form-control bg-dark text-success border-success" id="edit-post-title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-editor" class="form-label">内容</label>
                            <div id="edit-editor"></div>
                            <input type="hidden" id="edit-post-content">
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-success">
                    <button type="button" class="btn btn-hack" onclick="submitEditPost()">
                        <i class="bi bi-save"></i> 保存
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 版权信息 -->
    <footer class="py-3 mt-4" style="background-color: #1a1a1a; border-top: 1px solid #00ff00;">
        <div class="container text-center">
            <p class="mb-0" style="color: #00ff00;">微信公众号 xc201905 网站https://xoxome.online</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // 全局变量
        let currentPage = 1;
        const postsPerPage = 10;

        // 页面加载完成后获取帖子列表
        document.addEventListener('DOMContentLoaded', () => {
            loadPosts(1);
            loadSettings();
            loadTags();
        });

        // 加载帖子列表
        function loadPosts(page) {
            showLoading();
            console.log('开始加载帖子, 页码:', page);
            currentPage = page;
            
            const url = '/api?path=posts&page=' + page;
            console.log('请求URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('响应状态:', response.status);
                    console.log('响应头:', response.headers);
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.text().then(text => {
                        console.log('原始响应:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON解析错误:', e);
                            throw new Error('响应格式错误');
                        }
                    });
                })
                .then(data => {
                    console.log('解析后的数据:', data);
                    if (data.success) {
                        if (!data.data || !Array.isArray(data.data.posts)) {
                            console.error('帖子数据格式错误:', data);
                            displayPosts([]);
                            return;
                        }
                        displayPosts(data.data.posts);
                        updatePagination(Math.ceil(data.data.total / 10));
                    } else {
                        console.error('加载帖子失败:', data.message);
                        displayPosts([]);
                    }
                })
                .catch(error => {
                    console.error('加载帖子错误:', error);
                    displayPosts([]);
                })
                .finally(() => {
                    hideLoading();
                });
        }

        // 显示帖子列表
        function displayPosts(posts) {
            const container = document.getElementById('posts-container');
            container.innerHTML = '';
            
            posts.forEach(post => {
                const card = document.createElement('div');
                card.className = `card mb-3 ${post.is_top ? 'top-post' : ''}`;
                card.innerHTML = `
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="#" class="post-title" onclick="event.preventDefault(); showPostDetail(${post.id})">
                                ${post.title}
                            </a>
                            ${post.is_top ? '<span class="badge bg-danger ms-2">置顶</span>' : ''}
                        </h5>
                        <div class="card-text small text-muted">
                            <span class="user-name">${post.username || '游客'}</span> · 
                            ${formatTime(post.created_at)} · 
                            <i class="bi bi-eye-fill"></i> ${post.views} · 
                            <i class="bi bi-chat-fill"></i> ${post.comment_count}
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
            
            if(posts.length === 0) {
                container.innerHTML = '<div class="alert alert-warning">暂无帖子</div>';
            }
        }

        // 更新分页
        function updatePagination(totalPages) {
            const pagination = document.getElementById('pagination');
            if(totalPages <= 1) {
                pagination.innerHTML = '';
                return;
            }

            let html = '';
            
            // 上一页按钮
            html += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="event.preventDefault(); ${currentPage > 1 ? 'loadPosts(' + (currentPage - 1) + ')' : ''}">上一页</a>
                </li>
            `;

            // 页码按钮
            for(let i = 1; i <= totalPages; i++) {
                if(i === currentPage) {
                    html += `
                        <li class="page-item active">
                            <span class="page-link">${i}</span>
                        </li>
                    `;
                } else if(i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += `
                        <li class="page-item">
                            <a class="page-link" href="#" onclick="event.preventDefault(); loadPosts(${i})">${i}</a>
                        </li>
                    `;
                } else if(i === currentPage - 3 || i === currentPage + 3) {
                    html += `
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    `;
                }
            }

            // 下一页按钮
            html += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="event.preventDefault(); ${currentPage < totalPages ? 'loadPosts(' + (currentPage + 1) + ')' : ''}">下一页</a>
                </li>
            `;

            pagination.innerHTML = html;
        }

        // 显示登录模态框
        function showLoginModal() {
            new bootstrap.Modal(document.getElementById('loginModal')).show();
        }

        // 显示注册模态框
        function showRegisterModal() {
            new bootstrap.Modal(document.getElementById('registerModal')).show();
        }

        // 显示发帖模态框
        function showPostModal() {
            const modal = new bootstrap.Modal(document.getElementById('postModal'));
            modal.show();
            // 初始化编辑器
            if (!window.postEditor) {
                window.postEditor = initEditor();
            }
        }

        // 用户登录
        async function login() {
            const form = document.getElementById('loginForm');
            
            // 表单验证
            const username = form.elements['username'].value.trim();
            const password = form.elements['password'].value;
            
            if(!username || !password) {
                showError('用户名和密码不能为空');
                return;
            }
            
            try {
                showLoading();
                const response = await fetch('/api?path=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
                });
                
                const data = await response.json();
                console.log('登录响应:', data);
                
                if(data.success) {
                    showSuccess('登录成功');
                    location.reload();
                } else {
                    showError(data.message || '登录失败，请稍后重试');
                }
            } catch(error) {
                console.error('登录错误:', error);
                showError('登录失败，请检查网络连接');
            } finally {
                hideLoading();
            }
        }

        // 用户注册
        async function register() {
            const form = document.getElementById('registerForm');
            
            // 表单验证
            const username = form.elements['username'].value.trim();
            const email = form.elements['email'].value.trim();
            const password = form.elements['password'].value;
            
            if(!username || !email || !password) {
                showError('所有字段都是必填的');
                return;
            }
            
            if(username.length < 3 || username.length > 20) {
                showError('用户名长度必须在3-20个字符之间');
                return;
            }
            
            if(password.length < 6) {
                showError('密码长度必须大于6个字符');
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if(!emailRegex.test(email)) {
                showError('邮箱格式不正确');
                return;
            }
            
            try {
                showLoading();
                const response = await fetch('/api?path=register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
                });
                
                const data = await response.json();
                console.log('注册响应:', data);
                
                if(data.success) {
                    showSuccess('注册成功，请登录');
                    form.reset();
                    bootstrap.Modal.getInstance(document.getElementById('registerModal')).hide();
                    showLoginModal();
                } else {
                    showError(data.message || '注册失败，请稍后重试');
                }
            } catch(error) {
                console.error('注册错误:', error);
                showError('注册失败，请检查网络连接');
            } finally {
                hideLoading();
            }
        }

        // 用户登出
        async function logout() {
            try {
                showLoading();
                const response = await fetch('/api?path=logout', {
                    method: 'POST'
                });
                const data = await response.json();
                console.log('登出响应:', data);
                
                if(data.success) {
                    location.reload();
                } else {
                    showError(data.message || '登出失败，请稍后重试');
                }
            } catch(error) {
                console.error('登出错误:', error);
                showError('登出失败，请检查网络连接');
            } finally {
                hideLoading();
            }
        }

        // 发布帖子
        async function submitPost() {
            const title = document.getElementById('post-title').value.trim();
            const content = window.postEditor.root.innerHTML.trim();
            
            if(!title || !content) {
                showError('标题和内容不能为空');
                return;
            }
            
            try {
                showLoading();
                const response = await fetch('/api?path=posts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        title: title,
                        content: content
                    })
                });
                
                const data = await response.json();
                if(data.success) {
                    showSuccess(data.message);
                    document.getElementById('post-title').value = '';
                    window.postEditor.setText('');
                    bootstrap.Modal.getInstance(document.getElementById('postModal')).hide();
                    loadPosts(1); // 重新加载帖子列表
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('发布帖子错误:', error);
                showError('发布帖子失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 提交评论
        async function submitComment() {
            const content = document.getElementById('commentContent').value.trim();
            if(!content) {
                showError('评论内容不能为空');
                return;
            }
            
            try {
                showLoading();
                const response = await fetch('/api?path=comments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        post_id: window.currentPostId,
                        content: content
                    })
                });
                
                const data = await response.json();
                if(data.success) {
                    showSuccess(data.message);
                    document.getElementById('commentContent').value = '';
                    loadComments(window.currentPostId);
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('提交评论错误:', error);
                showError('提交评论失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 工具函数
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: message,
                showConfirmButton: false,
                timer: 1500
            });
        }

        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: '错误',
                text: message
            });
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleString('zh-CN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // 格式化时间函数
        function formatTime(timestamp) {
            if (!timestamp) return '';
            
            const date = new Date(timestamp);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000); // 计算时间差（秒）
            
            if (diff < 60) {
                return '刚刚';
            } else if (diff < 3600) {
                return Math.floor(diff / 60) + '分钟前';
            } else if (diff < 86400) {
                return Math.floor(diff / 3600) + '小时前';
            } else if (diff < 2592000) { // 30天内
                return Math.floor(diff / 86400) + '天前';
            } else {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${year}-${month}-${day} ${hours}:${minutes}`;
            }
        }

        // 显示帖子详情
        async function showPostDetail(postId) {
            try {
                showLoading();
                const response = await fetch(`/api?path=posts/detail&id=${postId}`);
                const data = await response.json();
                
                if(data.success) {
                    const post = data.data;
                    document.getElementById('postDetailTitle').textContent = post.title;
                    document.getElementById('postDetailAuthor').textContent = post.username || '游客';
                    document.getElementById('postDetailTime').textContent = formatTime(post.created_at);
                    document.getElementById('postDetailViews').textContent = post.views;
                    document.getElementById('postDetailComments').textContent = post.comment_count;
                    
                    // 处理帖子内容
                    const contentDiv = document.getElementById('postDetailContent');
                    contentDiv.innerHTML = post.content;
                    
                    // 处理图片
                    contentDiv.querySelectorAll('img').forEach(img => {
                        img.onerror = function() {
                            this.style.display = 'none';
                        };
                        img.onload = function() {
                            this.style.display = 'block';
                        };
                    });
                    
                    // 显示/隐藏编辑按钮
                    const editBtn = document.getElementById('editPostBtn');
                    if (editBtn) {
                        editBtn.style.display = post.can_edit ? 'inline-block' : 'none';
                        console.log('编辑按钮权限:', post.can_edit); // 添加调试日志
                    } else {
                        console.log('未找到编辑按钮元素'); // 添加调试日志
                    }
                    
                    // 保存当前帖子数据用于编辑
                    window.currentPost = post;
                    window.currentPostId = postId;
                    
                    new bootstrap.Modal(document.getElementById('postDetailModal')).show();
                    loadComments(postId);
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('获取帖子详情错误:', error);
                showError('获取帖子详情失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 加载评论列表
        async function loadComments(postId) {
            try {
                const response = await fetch(`/api?path=comments&post_id=${postId}`);
                const data = await response.json();
                
                const commentsList = document.getElementById('commentsList');
                commentsList.innerHTML = '';
                
                if(data.success && data.data.length > 0) {
                    data.data.forEach(comment => {
                        const commentElement = document.createElement('div');
                        commentElement.className = 'card bg-dark mb-3';
                        commentElement.innerHTML = `
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="user-name">${escapeHtml(comment.username)}</span>
                                        <small class="text-muted ms-2">${formatDate(comment.created_at)}</small>
                                    </div>
                                    ${comment.status === 0 ? '<span class="badge bg-warning">待审核</span>' : ''}
                                </div>
                                <div class="mt-2">${nl2br(escapeHtml(comment.content))}</div>
                            </div>
                        `;
                        commentsList.appendChild(commentElement);
                    });
                } else {
                    commentsList.innerHTML = '<div class="text-muted">暂无评论</div>';
                }
            } catch(error) {
                console.error('加载评论错误:', error);
                showError('加载评论失败，请稍后重试');
            }
        }

        // 将换行符转换为<br>标签
        function nl2br(str) {
            return str.replace(/\n/g, '<br>');
        }

        // 添加获取设置的函数
        async function loadSettings() {
            try {
                const response = await fetch('/api?path=settings');
                const data = await response.json();
                if(data.success) {
                    // 更新网站标题
                    document.title = data.data.site_name;
                    document.getElementById('site-name').textContent = data.data.site_name;
                    document.getElementById('site-title').textContent = data.data.site_name;
                    
                    // 更新网站描述
                    document.getElementById('site-description').textContent = data.data.site_description;
                }
            } catch(error) {
                console.error('加载设置失败:', error);
            }
        }

        // 加载标签
        async function loadTags() {
            try {
                const response = await fetch('/api?path=posts/tags');
                const data = await response.json();
                
                if(data.success) {
                    const tagCloud = document.getElementById('tagCloud');
                    tagCloud.innerHTML = '';
                    
                    data.data.forEach(tag => {
                        const tagElement = document.createElement('span');
                        tagElement.className = 'tag';
                        tagElement.textContent = tag.text;
                        tagElement.title = `出现 ${tag.count} 次`;
                        tagElement.onclick = () => filterPostsByTag(tag.text);
                        tagCloud.appendChild(tagElement);
                    });
                } else {
                    console.error('加载标签失败:', data.message);
                }
            } catch(error) {
                console.error('加载标签错误:', error);
            }
        }

        // 根据标签筛选帖子
        async function filterPostsByTag(tag) {
            try {
                showLoading();
                const response = await fetch(`/api?path=posts&tag=${encodeURIComponent(tag)}`);
                const data = await response.json();
                
                if(data.success) {
                    displayPosts(data.data.posts);
                    updatePagination(Math.ceil(data.data.total / 10));
                } else {
                    showError(data.message || '筛选帖子失败');
                }
            } catch(error) {
                console.error('筛选帖子错误:', error);
                showError('筛选帖子失败，请检查网络连接');
            } finally {
                hideLoading();
            }
        }

        // 显示编辑帖子模态框
        function showEditPostModal() {
            if (!window.currentPost) {
                showError('无法获取帖子信息');
                return;
            }
            
            // 初始化编辑器（如果还没有初始化）
            if (!window.editPostEditor) {
                window.editPostEditor = new Quill('#edit-editor', {
                    theme: 'snow',
                    placeholder: '在这里输入内容...',
                    modules: {
                        toolbar: {
                            container: [
                                ['bold', 'italic', 'underline', 'strike'],
                                ['blockquote', 'code-block'],
                                [{ 'header': 1 }, { 'header': 2 }],
                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                [{ 'color': [] }, { 'background': [] }],
                                ['link', 'image'],
                                ['attachment']
                            ],
                            handlers: {
                                'attachment': function() {
                                    const input = document.createElement('input');
                                    input.setAttribute('type', 'file');
                                    input.click();

                                    input.onchange = async () => {
                                        const file = input.files[0];
                                        if (file) {
                                            await uploadFileForEdit(file, file.type.startsWith('image/'));
                                        }
                                    };
                                },
                                'image': function() {
                                    const input = document.createElement('input');
                                    input.setAttribute('type', 'file');
                                    input.setAttribute('accept', 'image/*');
                                    input.click();

                                    input.onchange = async () => {
                                        const file = input.files[0];
                                        if (file) {
                                            await uploadFileForEdit(file, true);
                                        }
                                    };
                                }
                            }
                        }
                    }
                });

                // 添加附件按钮的图标
                const attachmentButton = document.querySelector('#edit-editor .ql-attachment');
                if (attachmentButton) {
                    attachmentButton.innerHTML = '<i class="bi bi-paperclip"></i>';
                }

                // 处理拖拽上传
                const editor = document.querySelector('#edit-editor .ql-editor');
                editor.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('drag-over');
                });

                editor.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('drag-over');
                });

                editor.addEventListener('drop', async function(e) {
                    e.preventDefault();
                    this.classList.remove('drag-over');

                    const files = e.dataTransfer.files;
                    if (files && files.length > 0) {
                        for (let i = 0; i < files.length; i++) {
                            const file = files[i];
                            await uploadFileForEdit(file, file.type.startsWith('image/'));
                        }
                    }
                });
            }
            
            // 设置编辑器内容
            document.getElementById('edit-post-id').value = window.currentPost.id;
            document.getElementById('edit-post-title').value = window.currentPost.title;
            
            // 确保编辑器已经初始化并且内容区域存在
            if (window.editPostEditor) {
                window.editPostEditor.root.innerHTML = window.currentPost.content;
            }
            
            // 隐藏详情模态框，显示编辑模态框
            bootstrap.Modal.getInstance(document.getElementById('postDetailModal')).hide();
            new bootstrap.Modal(document.getElementById('editPostModal')).show();
        }

        // 编辑模式的文件上传函数
        async function uploadFileForEdit(file, isImage) {
            try {
                showLoading();
                const formData = new FormData();
                formData.append('file', file);

                const response = await fetch('/api?path=upload', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    if (isImage) {
                        window.editPostEditor.insertEmbed(window.editPostEditor.getSelection(true).index, 'image', data.file_url);
                    } else {
                        const attachmentHtml = `
                            <div class="attachment">
                                <i class="bi bi-file-earmark"></i>
                                <div class="file-info">
                                    <a href="${data.file_url}" target="_blank">${data.file_name}</a>
                                    <span class="file-size">${data.file_size}</span>
                                </div>
                            </div>
                        `;
                        window.editPostEditor.clipboard.dangerouslyPasteHTML(
                            window.editPostEditor.getSelection(true).index,
                            attachmentHtml
                        );
                    }
                } else {
                    showError(data.message || '文件上传失败');
                }
            } catch (error) {
                console.error('上传文件错误:', error);
                showError('文件上传失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 提交编辑后的帖子
        async function submitEditPost() {
            const postId = document.getElementById('edit-post-id').value;
            const title = document.getElementById('edit-post-title').value.trim();
            const content = window.editPostEditor.root.innerHTML.trim();
            
            if(!title || !content) {
                showError('标题和内容不能为空');
                return;
            }
            
            try {
                showLoading();
                const response = await fetch('/api?path=posts/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: postId,
                        title: title,
                        content: content
                    })
                });
                
                const data = await response.json();
                if(data.success) {
                    showSuccess(data.message);
                    bootstrap.Modal.getInstance(document.getElementById('editPostModal')).hide();
                    // 重新加载帖子列表和详情
                    loadPosts(currentPage);
                    showPostDetail(postId);
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('编辑帖子错误:', error);
                showError('编辑帖子失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 初始化编辑器
        function initEditor() {
            const quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: '在这里输入内容...',
                modules: {
                    toolbar: {
                        container: [
                            ['bold', 'italic', 'underline', 'strike'],
                            ['blockquote', 'code-block'],
                            [{ 'header': 1 }, { 'header': 2 }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'color': [] }, { 'background': [] }],
                            ['link', 'image'],
                            ['attachment']  // 添加附件按钮
                        ],
                        handlers: {
                            'attachment': function() {
                                const input = document.createElement('input');
                                input.setAttribute('type', 'file');
                                input.click();

                                input.onchange = async () => {
                                    const file = input.files[0];
                                    if (file) {
                                        await uploadFile(file, file.type.startsWith('image/'));
                                    }
                                };
                            },
                            'image': function() {
                                const input = document.createElement('input');
                                input.setAttribute('type', 'file');
                                input.setAttribute('accept', 'image/*');
                                input.click();

                                input.onchange = async () => {
                                    const file = input.files[0];
                                    if (file) {
                                        await uploadFile(file, true);
                                    }
                                };
                            }
                        }
                    }
                }
            });

            // 添加自定义图标到工具栏
            const toolbar = quill.getModule('toolbar');
            toolbar.addHandler('attachment', function() {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.click();

                input.onchange = async () => {
                    const file = input.files[0];
                    if (file) {
                        await uploadFile(file, file.type.startsWith('image/'));
                    }
                };
            });

            // 添加附件按钮的图标
            const attachmentButton = document.querySelector('.ql-attachment');
            if (attachmentButton) {
                attachmentButton.innerHTML = '<i class="bi bi-paperclip"></i>';
            }

            // 保存编辑器内容到隐藏输入框
            quill.on('text-change', function() {
                document.getElementById('post-content').value = quill.root.innerHTML;
            });

            // 处理拖拽上传
            const editor = document.querySelector('.ql-editor');
            
            editor.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });

            editor.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
            });

            editor.addEventListener('drop', async function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                const files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        await uploadFile(file, file.type.startsWith('image/'));
                    }
                }
            });

            // 处理粘贴上传
            quill.clipboard.addMatcher(Node.ELEMENT_NODE, function(node, delta) {
                let ops = [];
                delta.ops.forEach(op => {
                    if (op.insert && typeof op.insert === 'object' && op.insert.image) {
                        // 如果是Base64图片，转换为文件并上传
                        if (op.insert.image.startsWith('data:')) {
                            const file = dataURLtoFile(op.insert.image, 'pasted-image.png');
                            uploadFile(file, true);
                            return;
                        }
                    }
                    ops.push(op);
                });
                delta.ops = ops;
                return delta;
            });

            return quill;
        }

        // 文件上传函数
        async function uploadFile(file, isImage) {
            try {
                showLoading();
                const formData = new FormData();
                formData.append('file', file);

                const response = await fetch('/api?path=upload', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    const quill = Quill.find(document.getElementById('editor'));
                    const range = quill.getSelection(true);

                    if (isImage) {
                        quill.insertEmbed(range.index, 'image', data.file_url);
                    } else {
                        const attachmentHtml = `
                            <div class="attachment">
                                <i class="bi bi-file-earmark"></i>
                                <div class="file-info">
                                    <a href="${data.file_url}" target="_blank">${data.file_name}</a>
                                    <span class="file-size">${data.file_size}</span>
                                </div>
                            </div>
                        `;
                        quill.clipboard.dangerouslyPasteHTML(range.index, attachmentHtml);
                    }
                    quill.setSelection(range.index + 1);
                } else {
                    showError(data.message || '文件上传失败');
                }
            } catch (error) {
                console.error('上传文件错误:', error);
                showError('文件上传失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // Base64转File函数
        function dataURLtoFile(dataurl, filename) {
            const arr = dataurl.split(',');
            const mime = arr[0].match(/:(.*?);/)[1];
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while(n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new File([u8arr], filename, {type: mime});
        }
    </script>
</body>
</html> 