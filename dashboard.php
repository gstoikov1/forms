<?php
require_once __DIR__ . '/session.php';
require_login();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard</title></head>
<body>
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


<h1>Dashboard</h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? '') ?>!</p>
<p><a href="/forms/logout.php">Logout</a></p>
<section id="Forms"></section>
</body>
</html>
