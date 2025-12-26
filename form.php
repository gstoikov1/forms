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
<div id="actions" style="margin-top:16px;">
    <button id="submitBtn" type="button">Generate Answers JSON</button>
    <p id="submitMsg" class="error"></p>
    <pre id="answersPreview" class="hidden"></pre>
</div>
<script>
    const formId = <?= $formId ?>;

    const codeGateEl = document.getElementById('codeGate');
    const codeInputEl = document.getElementById('codeInput');
    const codeBtnEl = document.getElementById('codeBtn');
    const codeMsgEl = document.getElementById('codeMsg');
    const statusEl = document.getElementById('status');
    const debugEl = document.getElementById('debug');

    let currentFormData = null; // will hold data.data from the API
    const submitBtnEl = document.getElementById('submitBtn');
    const submitMsgEl = document.getElementById('submitMsg');
    const answersPreviewEl = document.getElementById('answersPreview');

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
        currentFormData = data.data;
        statusEl.textContent = 'Loaded.';
        debugEl.classList.remove('hidden');
        debugEl.textContent = JSON.stringify(data, null, 2);

        //------------------FORM----------------
        const payload = data.data;
        const formMeta = payload.form;
        const questions = payload.questions ?? [];
        const questionOptionsMap = payload.questionOptions ?? {};

        const titleEl = document.querySelector('#title');
        const questionsEl = document.querySelector('#questions');

        titleEl.textContent = '';
        questionsEl.replaceChildren();

        titleEl.textContent = formMeta.name;

        questions.sort((a, b) => (a.question_order ?? 0) - (b.question_order ?? 0));

        for (const q of questions) {
            const card = document.createElement('div');
            card.className = 'question-card';
            card.style.margin = '12px 0';
            card.style.padding = '12px';
            card.style.border = '1px solid #ddd';
            card.style.borderRadius = '8px';

            const qTitle = document.createElement('h3');
            qTitle.style.margin = '0 0 8px 0';
            qTitle.textContent = `${q.question_order}. ${q.question_text}`;

            const qType = document.createElement('small');
            qType.style.display = 'block';
            qType.style.marginBottom = '10px';
            qType.textContent = `Type: ${q.question_type}`;

            card.append(qTitle, qType);

            const options = questionOptionsMap[String(q.id)] ?? [];

            if (q.question_type === 'OPEN') {
                const input = document.createElement('input');
                input.type = 'text';
                input.name = `q_${q.id}`;
                input.placeholder = 'Your answer...';
                input.style.width = '100%';
                card.appendChild(input);
            }
            else if (q.question_type === 'SINGLE_CHOICE') {
                if (!options.length) {
                    const msg = document.createElement('p');
                    msg.className = 'error';
                    msg.textContent = 'No options available for this question.';
                    card.appendChild(msg);
                } else {
                    const fieldset = document.createElement('fieldset');
                    fieldset.style.border = '0';
                    fieldset.style.padding = '0';
                    fieldset.style.margin = '0';

                    // sort options by option_order
                    options.sort((a, b) => (a.option_order ?? 0) - (b.option_order ?? 0));

                    for (const opt of options) {
                        const label = document.createElement('label');
                        label.style.display = 'block';
                        label.style.margin = '6px 0';

                        const radio = document.createElement('input');
                        radio.type = 'radio';
                        radio.name = `q_${q.id}`;             // same name for single choice
                        radio.value = String(opt.id);

                        label.append(radio, document.createTextNode(' ' + opt.option_text));
                        fieldset.appendChild(label);
                    }

                    card.appendChild(fieldset);
                }
            }
            else if (q.question_type === 'MULTI_CHOICE') {
                if (!options.length) {
                    const msg = document.createElement('p');
                    msg.className = 'error';
                    msg.textContent = 'No options available for this question.';
                    card.appendChild(msg);
                } else {
                    const fieldset = document.createElement('fieldset');
                    fieldset.style.border = '0';
                    fieldset.style.padding = '0';
                    fieldset.style.margin = '0';

                    options.sort((a, b) => (a.option_order ?? 0) - (b.option_order ?? 0));

                    for (const opt of options) {
                        const label = document.createElement('label');
                        label.style.display = 'block';
                        label.style.margin = '6px 0';

                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.name = `q_${q.id}[]`;
                        checkbox.value = String(opt.id);

                        label.append(checkbox, document.createTextNode(' ' + opt.option_text));
                        fieldset.appendChild(label);
                    }

                    card.appendChild(fieldset);
                }
            }
            else {
                const msg = document.createElement('p');
                msg.className = 'error';
                msg.textContent = `Unsupported question type: ${q.question_type}`;
                card.appendChild(msg);
            }

            questionsEl.appendChild(card);
        }

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
            currentFormData = data.data;


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
    function buildAnswersJsonOrThrow() {
        if (!currentFormData) throw new Error('Form is not loaded yet.');

        const formIdOut = currentFormData.form.id;
        const questions = (currentFormData.questions ?? []).slice()
            .sort((a, b) => (a.question_order ?? 0) - (b.question_order ?? 0));

        const answers = [];

        for (const q of questions) {
            const qid = q.id;
            const type = q.question_type;

            if (type === 'OPEN') {
                const input = document.querySelector(`input[name="q_${qid}"]`);
                const value = (input?.value ?? '').trim();
                if (!value) throw new Error(`Question ${q.question_order}: please fill the text answer.`);
                answers.push({ question_id: qid, type: 'OPEN', value });
            }
            else if (type === 'SINGLE_CHOICE') {
                const checked = document.querySelector(`input[type="radio"][name="q_${qid}"]:checked`);
                if (!checked) throw new Error(`Question ${q.question_order}: please select one option.`);
                answers.push({ question_id: qid, type: 'SINGLE_CHOICE', option_id: Number(checked.value) });
            }
            else if (type === 'MULTI_CHOICE') {
                const checked = Array.from(document.querySelectorAll(`input[type="checkbox"][name="q_${qid}[]"]:checked`));
                if (checked.length === 0) throw new Error(`Question ${q.question_order}: please select at least one option.`);
                answers.push({ question_id: qid, type: 'MULTI_CHOICE', option_ids: checked.map(x => Number(x.value)) });
            }
            else {
                throw new Error(`Unsupported question type: ${type}`);
            }
        }

        return { form_id: formIdOut, answers };
    }

    submitBtnEl.addEventListener('click', () => {
        submitMsgEl.textContent = '';
        answersPreviewEl.classList.add('hidden');
        answersPreviewEl.textContent = '';

        try {
            const payload = buildAnswersJsonOrThrow();
            answersPreviewEl.classList.remove('hidden');
            answersPreviewEl.textContent = JSON.stringify(payload, null, 2);
        } catch (err) {
            submitMsgEl.textContent = err?.message || 'Validation error.';
        }
    });

</script>

</body>
</html>
