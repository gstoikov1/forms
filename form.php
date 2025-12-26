<?php
require_once __DIR__ . '/session.php';

$formId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($formId <= 0) {
    http_response_code(404);
    exit('Form not found');
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Form</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 24px auto; }
        .hidden { display:none; }
        .error { color: #b00020; }
        input { padding: 8px; }
        button { padding: 8px 10px; }
        pre { background:#111; color:#eee; padding:12px; border-radius:8px; overflow:auto; }
    </style>
</head>
<body>

<h1>Form</h1>

<div id="welcome">
    <p>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Anonymous') ?>!</p>
</div>

<div id="codeGate" class="hidden">
    <p>This form requires a code.</p>
    <input id="codeInput" maxlength="5" placeholder="Enter 5-char code">
    <button id="codeBtn" type="button">Unlock</button>
    <p id="codeMsg" class="error"></p>
</div>

<div id="content">
    <p id="status">Loading…</p>
    <pre id="debug" class="hidden"></pre>
</div>
<div id = "form">
    <div id="title">

    </div>
    <div id="questions">

    </div>
</div>
<script>
    const formId = <?= $formId ?>;

    const codeGateEl = document.getElementById('codeGate');
    const codeInputEl = document.getElementById('codeInput');
    const codeBtnEl = document.getElementById('codeBtn');
    const codeMsgEl = document.getElementById('codeMsg');
    const statusEl = document.getElementById('status');
    const debugEl = document.getElementById('debug');

    async function fetchForm() {
        statusEl.textContent = 'Loading…';
        codeGateEl.classList.add('hidden');
        codeMsgEl.textContent = '';

        const res = await fetch(`/forms/api/form.php?id=${formId}`);

        if (res.status === 401) {
            window.location.href = '/forms/login.php';
            return;
        }

        // If your API uses 403 for "code required", handle it here:
        if (res.status === 403) {
            statusEl.textContent = '';
            codeGateEl.classList.remove('hidden');
            return;
        }

        if (!res.ok) {
            statusEl.textContent = 'Could not load form.';
            return;
        }

        const data = await res.json();
        statusEl.textContent = 'Loaded.';
        debugEl.classList.remove('hidden');
        debugEl.textContent = JSON.stringify(data, null, 2);

        //------------------FORM----------------
        const form = data.data;
        //console.log(form);

        const title = document.querySelector('#title');
        //console.log(form.form.name)
        title.textContent = form.form.name;

        const questionsEl = document.querySelector('#questions')
        const questionOptions = form.questionOptions;
        console.log(questionOptions);
        form.questions.forEach(question => {
            const div = document.createElement('div');
            div.innerHTML = question.question_text;
            questionsEl.appendChild(div);
            console.log (question.id, questionOptions[question.id]);
        })
    }

    codeBtnEl.addEventListener('click', async () => {
        const code = codeInputEl.value.trim();

        if (code.length !== 5) {
            codeMsgEl.textContent = 'Code must be exactly 5 characters.';
            return;
        }

        codeBtnEl.disabled = true;
        codeMsgEl.textContent = 'Checking…';

        try {
            const res = await fetch('/forms/api/verify_form_code.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ form_id: formId, code })
            });

            const data = await res.json();

            if (!res.ok) {
                codeMsgEl.textContent = data.error || 'Wrong code.';
                codeBtnEl.disabled = false;
                return;
            }

            // Success → fetch again (now session has access)
            codeMsgEl.textContent = '';
            await fetchForm();

        } catch (e) {
            codeMsgEl.textContent = 'Network error.';
            console.error(e);
        } finally {
            codeBtnEl.disabled = false;
        }
    });

    // initial load
    fetchForm();
</script>

</body>
</html>
