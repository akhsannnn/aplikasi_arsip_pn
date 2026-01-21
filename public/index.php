<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Arsip Pengadilan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script>
        tailwind.config = {
            theme: { extend: { colors: { court: { green: '#1b5e20', dark: '#003300', gold: '#c6a700', light: '#f1f8e9' } } } }
        }
    </script>
</head>
<body class="bg-gray-100 font-sans h-screen flex flex-col overflow-hidden text-sm">

    <div id="loginScreen" class="fixed inset-0 z-[100] bg-court-dark hidden flex items-center justify-center">
        <?php include '../views/login.php'; ?>
    </div>

    <div id="appScreen" class="flex flex-col h-full hidden">
        <?php include '../views/header.php'; ?>
        
        <div class="flex flex-1 overflow-hidden">
            <?php include '../views/sidebar.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-6 relative bg-gray-50" id="mainArea">
                <div id="loading" class="hidden absolute inset-0 bg-white/80 z-40 flex flex-col items-center justify-center">
                    <div class="loader mb-2"></div>
                    <p class="font-bold text-gray-600">Memuat...</p>
                </div>

                <div id="viewDashboard" class="hidden"><?php include '../views/dashboard.php'; ?></div>
                <div id="viewContent" class="hidden"><?php include '../views/content.php'; ?></div>
                <div id="viewTemplates" class="hidden"><?php include '../views/templates.php'; ?></div>
                <div id="viewTrash" class="hidden"><?php include '../views/trash.php'; ?></div>
                <div id="viewUsers" class="hidden h-full flex flex-col min-h-0"><?php include '../views/users.php'; ?></div>
            </main>
        </div>
    </div>

    <?php include '../views/modals.php'; ?>

    <script src="js/app.js"></script>
</body>
</html>