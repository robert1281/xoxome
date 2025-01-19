<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'post.php';
require_once 'comment.php';
require_once 'settings.php';

$auth = new Auth();
$post = new Post();
$comment = new Comment();
$settings = new Settings();

// 检查是否是管理员
if(!$auth->isAdmin()) {
    $_SESSION['error'] = '您没有权限访问管理后台';
    header('Location: index.php');
    exit;
}

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .nav-pills .nav-link {
            color: #00ff00;
        }
        .nav-pills .nav-link.active {
            background-color: #00ff00;
            color: #1a1a1a;
        }
        .table {
            color: #00ff00;
        }
        .table th, .table td {
            border-color: #00ff00;
        }
        .form-control {
            background-color: #1a1a1a;
            border-color: #00ff00;
            color: #00ff00;
        }
        .form-control:focus {
            background-color: #1a1a1a;
            border-color: #00ff00;
            color: #00ff00;
            box-shadow: 0 0 0 0.25rem rgba(0, 255, 0, 0.25);
        }
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
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-house-fill"></i> 返回首页</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">
                        管理员: <span class="user-name"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    </span>
                    <button class="btn btn-hack btn-sm" onclick="logout()">
                        <i class="bi bi-box-arrow-right"></i> 登出
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主要内容 -->
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <a class="nav-link active" href="#posts" data-bs-toggle="tab">
                                    <i class="bi bi-file-text-fill"></i> 帖子管理
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#comments" data-bs-toggle="tab">
                                    <i class="bi bi-chat-fill"></i> 评论管理
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#settings" data-bs-toggle="tab">
                                    <i class="bi bi-gear-fill"></i> 系统设置
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- 帖子管理 -->
                    <div class="tab-pane fade show active" id="posts">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">帖子管理</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>标题</th>
                                                <th>作者</th>
                                                <th>状态</th>
                                                <th>发布时间</th>
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody id="posts-table"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 评论管理 -->
                    <div class="tab-pane fade" id="comments">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">评论管理</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>内容</th>
                                                <th>作者</th>
                                                <th>帖子</th>
                                                <th>状态</th>
                                                <th>发布时间</th>
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody id="comments-table"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 系统设置 -->
                    <div class="tab-pane fade" id="settings">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">系统设置</h5>
                                <form id="settingsForm">
                                    <div class="mb-3">
                                        <label for="siteName" class="form-label">网站标题</label>
                                        <input type="text" class="form-control" id="siteName" name="site_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="siteDescription" class="form-label">网站描述</label>
                                        <textarea class="form-control" id="siteDescription" name="site_description" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="postReview" name="post_need_review">
                                        <label class="form-check-label" for="postReview">帖子需要审核</label>
                                    </div>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="commentReview" name="comment_need_review">
                                        <label class="form-check-label" for="commentReview">评论需要审核</label>
                                    </div>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="guestPost" name="allow_guest_post">
                                        <label class="form-check-label" for="guestPost">允许游客发帖</label>
                                    </div>
                                    <button type="button" class="btn btn-hack" onclick="saveSettings()">
                                        保存设置
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 在 body 末尾添加编辑帖子的模态框 -->
    <div class="modal fade" id="editPostModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
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

    <script>
        let editPostEditor = null;
        let editPostModal = null;

        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', () => {
            // 初始化模态框
            editPostModal = new bootstrap.Modal(document.getElementById('editPostModal'));
            
            // 监听模态框显示事件
            document.getElementById('editPostModal').addEventListener('show.bs.modal', () => {
                // 确保编辑器只初始化一次
                if (!editPostEditor) {
                    const editorElement = document.getElementById('edit-editor');
                    if (editorElement) {
                        editPostEditor = new Quill(editorElement, {
                            theme: 'snow',
                            modules: {
                                toolbar: [
                                    ['bold', 'italic', 'underline', 'strike'],
                                    ['blockquote', 'code-block'],
                                    [{ 'header': 1 }, { 'header': 2 }],
                                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                    [{ 'color': [] }, { 'background': [] }],
                                    ['link', 'image']
                                ]
                            },
                            placeholder: '请输入内容...'
                        });
                    }
                }
            });

            // 监听模态框隐藏事件
            document.getElementById('editPostModal').addEventListener('hidden.bs.modal', () => {
                // 清空编辑器内容
                if (editPostEditor) {
                    editPostEditor.setText('');
                }
            });
            
            // 加载数据
            loadPosts();
            loadComments();
            loadSettings();
        });

        // 编辑帖子
        async function editPost(postId) {
            try {
                const response = await fetch(`/api?path=posts/detail&id=${postId}`);
                const data = await response.json();
                
                if(data.success) {
                    const post = data.data;
                    document.getElementById('edit-post-id').value = post.id;
                    document.getElementById('edit-post-title').value = post.title;
                    
                    // 显示模态框
                    editPostModal.show();
                    
                    // 等待编辑器初始化完成后设置内容
                    setTimeout(() => {
                        if (editPostEditor) {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = post.content;
                            editPostEditor.root.innerHTML = tempDiv.innerHTML;
                        }
                    }, 100);
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('获取帖子详情错误:', error);
                showError('获取帖子详情失败');
            }
        }

        // 提交编辑后的帖子
        async function submitEditPost() {
            if (!editPostEditor) {
                showError('编辑器未正确初始化');
                return;
            }

            const postId = document.getElementById('edit-post-id').value;
            const title = document.getElementById('edit-post-title').value.trim();
            const content = editPostEditor.root.innerHTML.trim();
            
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
                    showSuccess('更新成功');
                    editPostModal.hide();
                    loadPosts();
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('更新帖子错误:', error);
                showError('更新帖子失败');
            } finally {
                hideLoading();
            }
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

        // 页面加载完成后加载数据
        document.addEventListener('DOMContentLoaded', () => {
            loadPosts();
            loadComments();
            loadSettings();
        });

        // 加载帖子列表
        function loadPosts() {
            fetch('/api?path=posts')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const tbody = document.getElementById('posts-table');
                        tbody.innerHTML = '';
                        
                        data.data.posts.forEach(post => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${post.id}</td>
                                <td>${post.title}</td>
                                <td>${post.username || '游客'}</td>
                                <td>${post.status === 1 ? '已审核' : '待审核'}</td>
                                <td>${formatTime(post.created_at)}</td>
                                <td>
                                    <button class="btn btn-hack btn-sm" onclick="editPost(${post.id})">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn btn-hack btn-sm" onclick="togglePostStatus(${post.id}, ${post.status})">
                                        ${post.status === 1 ? '<i class="bi bi-x-circle-fill"></i>' : '<i class="bi bi-check-circle-fill"></i>'}
                                    </button>
                                    <button class="btn btn-hack btn-sm" onclick="togglePostTop(${post.id}, ${post.is_top})">
                                        ${post.is_top ? '<i class="bi bi-arrow-down-circle-fill"></i>' : '<i class="bi bi-arrow-up-circle-fill"></i>'}
                                    </button>
                                    <button class="btn btn-hack btn-sm" onclick="deletePost(${post.id})">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });
                    }
                });
        }

        // 加载评论列表
        async function loadComments() {
            try {
                showLoading();
                const response = await fetch('/api?path=comments');
                const data = await response.json();
                
                if(data.success) {
                    displayComments(data.data);
                } else {
                    showError(data.message || '加载评论失败');
                }
            } catch(error) {
                console.error('加载评论错误:', error);
                showError('加载评论失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 显示评论列表
        function displayComments(comments) {
            const container = document.getElementById('comments-table');
            container.innerHTML = '';

            if(!comments || comments.length === 0) {
                container.innerHTML = '<tr><td colspan="7" class="text-center">暂无评论</td></tr>';
                return;
            }

            comments.forEach(comment => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${comment.id}</td>
                    <td>${escapeHtml(comment.content)}</td>
                    <td class="user-name">${escapeHtml(comment.username)}</td>
                    <td>${escapeHtml(comment.post_title)}</td>
                    <td>${getCommentStatus(comment.status)}</td>
                    <td>${formatDate(comment.created_at)}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            ${comment.status === 0 ? 
                                `<button class="btn btn-hack" onclick="approveComment(${comment.id}, 1)" title="通过">
                                    <i class="bi bi-check-circle"></i>
                                </button>` : 
                                `<button class="btn btn-hack" onclick="approveComment(${comment.id}, 0)" title="驳回">
                                    <i class="bi bi-x-circle"></i>
                                </button>`
                            }
                            <button class="btn btn-hack" onclick="deleteComment(${comment.id})" title="删除">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                container.appendChild(tr);
            });
        }

        // 加载设置
        async function loadSettings() {
            try {
                showLoading();
                const response = await fetch('/api?path=settings');
                const data = await response.json();
                
                if(data.success) {
                    displaySettings(data.data);
                } else {
                    showError(data.message || '加载设置失败');
                }
            } catch(error) {
                console.error('加载设置错误:', error);
                showError('加载设置失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 保存设置
        async function saveSettings() {
            const settings = {
                site_name: document.querySelector('input[name="site_name"]').value,
                site_description: document.querySelector('textarea[name="site_description"]').value,
                post_need_review: document.querySelector('input[name="post_need_review"]').checked ? 1 : 0,
                comment_need_review: document.querySelector('input[name="comment_need_review"]').checked ? 1 : 0,
                allow_guest_post: document.querySelector('input[name="allow_guest_post"]').checked ? 1 : 0
            };
            
            try {
                showLoading();
                const response = await fetch('/api?path=settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({settings: settings})
                });
                const data = await response.json();
                
                if(data.success) {
                    showSuccess('设置保存成功');
                } else {
                    showError(data.message || '保存设置失败');
                }
            } catch(error) {
                console.error('保存设置错误:', error);
                showError('保存设置失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 审核帖子
        async function approvePost(id, status) {
            try {
                showLoading();
                const response = await fetch('/api?path=posts/approve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({id: id, status: status})
                });
                const data = await response.json();
                
                if(data.success) {
                    showSuccess(data.message);
                    loadPosts();
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('审核帖子错误:', error);
                showError('操作失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 置顶/取消置顶帖子
        async function toggleTop(id) {
            try {
                showLoading();
                const response = await fetch('/api?path=posts/top', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({id: id})
                });
                const data = await response.json();
                
                if(data.success) {
                    showSuccess(data.message);
                    loadPosts();
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('置顶帖子错误:', error);
                showError('操作失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 删除帖子
        async function deletePost(id) {
            const result = await Swal.fire({
                title: '确定要删除这篇帖子吗？',
                text: "删除后将无法恢复！",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '确定删除',
                cancelButtonText: '取消',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            });

            if (!result.isConfirmed) {
                return;
            }
            
            try {
                showLoading();
                const response = await fetch('/api?path=posts/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({id: id})
                });
                const data = await response.json();
                
                if(data.success) {
                    showSuccess(data.message);
                    loadPosts();
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('删除帖子错误:', error);
                showError('删除失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 审核评论
        async function approveComment(id, status) {
            try {
                showLoading();
                const response = await fetch('/api?path=comments/approve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({id: id, status: status})
                });
                const data = await response.json();
                
                if(data.success) {
                    showSuccess(data.message);
                    loadComments();
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('审核评论错误:', error);
                showError('操作失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 删除评论
        async function deleteComment(id) {
            const result = await Swal.fire({
                title: '确定要删除这条评论吗？',
                text: "删除后将无法恢复！",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '确定删除',
                cancelButtonText: '取消',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            });

            if (!result.isConfirmed) {
                return;
            }
            
            try {
                showLoading();
                const response = await fetch('/api?path=comments/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({id: id})
                });
                const data = await response.json();
                
                if(data.success) {
                    showSuccess(data.message);
                    loadComments();
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('删除评论错误:', error);
                showError('删除失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 登出
        async function logout() {
            try {
                showLoading();
                const response = await fetch('/api?path=logout', {
                    method: 'POST'
                });
                const data = await response.json();
                
                if(data.success) {
                    window.location.href = 'index.php';
                } else {
                    showError(data.message || '登出失败');
                }
            } catch(error) {
                console.error('登出错误:', error);
                showError('登出失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 工具函数
        function showLoading() {
            const loading = document.createElement('div');
            loading.id = 'loading';
            loading.className = 'loading';
            document.body.appendChild(loading);
        }

        function hideLoading() {
            const loading = document.getElementById('loading');
            if (loading) {
                loading.remove();
            }
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

        function showConfirm(title, text) {
            return Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                confirmButtonColor: '#00ff00',
                cancelButtonColor: '#d33',
                background: '#1a1a1a',
                color: '#00ff00'
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

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('zh-CN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function getPostStatus(status) {
            switch(status) {
                case 0: return '<span class="badge bg-warning">待审核</span>';
                case 1: return '<span class="badge bg-success">已通过</span>';
                case 2: return '<span class="badge bg-danger">已拒绝</span>';
                default: return '<span class="badge bg-secondary">未知</span>';
            }
        }

        function getCommentStatus(status) {
            switch(status) {
                case 0: return '<span class="badge bg-warning">待审核</span>';
                case 1: return '<span class="badge bg-success">已通过</span>';
                case 2: return '<span class="badge bg-danger">已拒绝</span>';
                default: return '<span class="badge bg-secondary">未知</span>';
            }
        }

        // 显示设置
        function displaySettings(settings) {
            const form = document.getElementById('settingsForm');
            if(!form) return;

            // 设置网站标题
            form.elements['site_name'].value = settings.site_name || '';
            
            // 设置网站描述
            form.elements['site_description'].value = settings.site_description || '';
            
            // 设置帖子审核开关
            form.elements['post_need_review'].checked = settings.post_need_review === '1';
            
            // 设置评论审核开关
            form.elements['comment_need_review'].checked = settings.comment_need_review === '1';
            
            // 设置游客发帖开关
            form.elements['allow_guest_post'].checked = settings.allow_guest_post === '1';
        }

        // 切换帖子状态（审核/驳回）
        async function togglePostStatus(postId, currentStatus) {
            try {
                const action = currentStatus === 1 ? '驳回' : '通过审核';
                const result = await showConfirm('确认操作', `确定要${action}这篇帖子吗？`);
                
                if (result.isConfirmed) {
                    showLoading();
                    const response = await fetch('/api?path=posts/approve', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: postId,
                            status: currentStatus === 1 ? 0 : 1
                        })
                    });
                    
                    const data = await response.json();
                    if(data.success) {
                        showSuccess(data.message);
                        loadPosts();
                    } else {
                        showError(data.message);
                    }
                }
            } catch(error) {
                console.error('更新帖子状态错误:', error);
                showError('操作失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 切换帖子置顶状态
        async function togglePostTop(postId, isTop) {
            try {
                const action = isTop ? '取消置顶' : '置顶';
                const result = await showConfirm('确认操作', `确定要${action}这篇帖子吗？`);
                
                if (result.isConfirmed) {
                    showLoading();
                    const response = await fetch('/api?path=posts/top', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: postId
                        })
                    });
                    
                    const data = await response.json();
                    if(data.success) {
                        showSuccess(data.message);
                        loadPosts();
                    } else {
                        showError(data.message);
                    }
                }
            } catch(error) {
                console.error('更新置顶状态错误:', error);
                showError('操作失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }

        // 删除帖子
        async function deletePost(postId) {
            try {
                const result = await showConfirm('确认删除', '确定要删除这篇帖子吗？此操作不可恢复！');
                
                if (result.isConfirmed) {
                    showLoading();
                    const response = await fetch('/api?path=posts/delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: postId
                        })
                    });
                    
                    const data = await response.json();
                    if(data.success) {
                        showSuccess(data.message);
                        loadPosts();
                    } else {
                        showError(data.message);
                    }
                }
            } catch(error) {
                console.error('删除帖子错误:', error);
                showError('删除失败，请稍后重试');
            } finally {
                hideLoading();
            }
        }
    </script>
</body>
</html> 