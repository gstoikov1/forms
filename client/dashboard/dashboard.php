<?php
require_once __DIR__ . '/../../session.php';
require_login();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/forms/client/index.css">
    <link rel="stylesheet" href="/forms/client/button.css">
    <link rel="stylesheet" href="/forms/client/dashboard/dashboard.css">
</head>
<body>

<div class="dashboard-wrapper">
    <header class="main-header">
        <div class="header-left">
            <h1 class="project-title">PuffinForms</h1>
            <a href="/forms/client/createForm/create-form.php" class="btn btn-secondary">Create Form</a>
        </div>
        
        <div class="header-right">
            <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong></span>
            <a href="/forms/logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </header>

    <main class="dashboard-content">
        <h2>Your Dashboard</h2>
        <section id="Forms">
            </section>
    </main>
</div>

<script>
    async function fetchForms() {
        const res = await fetch('/forms/api/forms.php');

        if (res.status === 401) {
            window.location.href = '/forms/login.php';
            return;
        }

        const data = await res.json();
        console.log(data.forms);
        console.log(data);
        return data.forms;
    }

    (async () => {
        let forms = await fetchForms();
        const formsSection = document.querySelector('#Forms');

        for (const form of forms) {
            const element = document.createElement('div');

            const link = document.createElement('a');
            link.href = `/forms/form.php?id=${form.id}`;
            link.textContent = 'Open form';

            const title = document.createElement('h2');
            title.textContent = form.name;

            element.append(title, link);
            formsSection.appendChild(element);
        }
    })();
</script>
</body>
</html>
