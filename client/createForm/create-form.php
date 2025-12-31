<?php
require_once __DIR__ . '/../../session.php';
require_login();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Form</title>
    
    <link rel="stylesheet" href="/forms/client/index.css">
    <link rel="stylesheet" href="/forms/client/button.css">
    <link rel="stylesheet" href="/forms/client/dashboard/dashboard.css">
    <link rel="stylesheet" href="/forms/client/createForm/create-form.css">
</head>
<body>

<div class="dashboard-wrapper">
    <header class="main-header">
        <div class="header-left">
            <h1 class="project-title">PuffinForms</h1>
        </div>
        <div class="header-right">
            <a href="/forms/client/dashboard/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </header>

    <main class="dashboard-content">
        <div class="builder-container">
            
            <header class="builder-header">
                <h1>Create New Form</h1>
                <p>Fill in the details below to build your custom form.</p>
            </header>

            <section class="builder-section">
                <h2>Form Settings</h2>
                <div class="input-stack">
                    <div>
                        <label>Title</label>
                        <input id="formName" type="text" class="input-div fullWidth" placeholder="Enter title" />
                    </div>
                    <div class="settings-row">
                        <div class="flex-1">
                            <label>Requires Access Code?</label>
                            <select id="requiresCode" class="fullWidth">
                                <option value="0">No (Public)</option>
                                <option value="1">Yes (Private)</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label>5-Char Code</label>
                            <input id="formCode" type="text" maxlength="5" class="input-div fullWidth" placeholder="Enter code" disabled />
                        </div>
                    </div>
                </div>
            </section>

            <div id="questions"></div>

            <button id="addQuestionBtn" type="button" class="btn-dashed">
                + Add New Question
            </button>

            <section class="builder-section">
                <h2>Finalize & Save</h2>
                <p class="builder-footer-info">
                    Review your JSON payload below before saving to the database.
                </p>
                
                <div class="builder-footer-actions">
                    <button id="buildJsonBtn" type="button" class="btn btn-primary flex-2">Verify Form (Build JSON)</button>
                    <button id="clearBtn" type="button" class="btn btn-secondary flex-1">Clear All</button>
                </div>

                <pre id="output">{}</pre>

                <button id="saveFormBtn" type="button" class="btn btn-primary" style="width: 100%; margin-top: var(--space-24); height: 50px; font-size: var(--font-size-lg);">
                    Save Form to Database
                </button>
                <p id="saveStatus"></p>
            </section>
        </div>
    </main>
</div>

<script>
    const questionsEl = document.getElementById('questions');
    const formNameEl = document.getElementById('formName');
    const requiresCodeEl = document.getElementById('requiresCode');
    const formCodeEl = document.getElementById('formCode');
    const outputEl = document.getElementById('output');
    
    let qCounter = 0;

    requiresCodeEl.addEventListener('change', () => {
        const needs = requiresCodeEl.value === '1';
        formCodeEl.disabled = !needs;
        if (!needs) formCodeEl.value = '';
    });

    document.getElementById('addQuestionBtn').addEventListener('click', () => addQuestionCard());
    document.getElementById('buildJsonBtn').addEventListener('click', () => buildJson());
    document.getElementById('saveFormBtn').addEventListener('click', () => {
        const payload = buildJson();
        if (payload) saveFormToBackend(payload);
    });

    document.getElementById('clearBtn').addEventListener('click', () => {
        if(confirm("Are you sure you want to clear the entire form?")) {
            formNameEl.value = '';
            requiresCodeEl.value = '0';
            formCodeEl.value = '';
            formCodeEl.disabled = true;
            questionsEl.innerHTML = '';
            outputEl.textContent = '{}';
            qCounter = 0;
            addQuestionCard();
        }
    });



    function addQuestionCard() {
        qCounter += 1;
        const qId = `q_${Date.now()}_${qCounter}`;
        const card = document.createElement('div');
        card.className = 'builder-section';
        card.dataset.qid = qId;

        card.innerHTML = `
            <div class="question-header">
                <h2 style="margin:0;">Question #<span class="q-num-display">${questionsEl.children.length + 1}</span></h2>
                <button type="button" class="btn remove-q-btn">Remove Question</button>
            </div>
            
            <div class="input-stack">
                <div class="input-div">
                    <label>Title</label>
                    <input type="text" class="q-text" placeholder="Enter title" />
                </div>
                <div class="settings-row">
                  <div class="input-div">
                    <label>Type</label>
                    <select class="q-type">
                        <option value="OPEN">Text Input</option>
                        <option value="SINGLE_CHOICE">Multiple Choice (Radio)</option>
                        <option value="MULTI_CHOICE">Checkboxes</option>
                    </select>
                  </div>
                  <div class="input-div q-order-box">
                    <label>Order</label>
                    <input type="number" min=1 class="q-order input-div" value="${questionsEl.children.length + 1}" />
                  </div>
                </div>
            </div>

            <div class="options" style="display:none;">
                <div class="options-container">
                    <div class="opt-list"></div>
                    <button type="button" class="add-opt btn-dashed" style="padding: var(--space-8); margin-top: var(--space-12); font-size: var(--font-size-sm);">
                        + Add Option
                    </button>
                </div>
            </div>
        `;

        const typeEl = card.querySelector('.q-type');
        const optionsBox = card.querySelector('.options');
        const addOptBtn = card.querySelector('.add-opt');
        const optList = card.querySelector('.opt-list');

        const orderInput = card.querySelector('.q-order');
        const numDisplay = card.querySelector('.q-num-display');

        orderInput.addEventListener('input', () => {
            numDisplay.textContent = orderInput.value;
        });

        typeEl.addEventListener('change', () => {
            const t = typeEl.value;
            const needsOptions = (t === 'SINGLE_CHOICE' || t === 'MULTI_CHOICE');
            optionsBox.style.display = needsOptions ? 'block' : 'none';
                
            if (needsOptions) {
                if (optList.children.length === 0) {
                    addOptionRow(optList);
                    addOptionRow(optList);
                } else {
                    const inputType = (t === 'SINGLE_CHOICE') ? 'checkbox' : 'radio';
                    const qUid = card.dataset.qid;
                    const existingInputs = optList.querySelectorAll('.opt-is-correct');
                    
                    existingInputs.forEach(input => {
                        input.type = inputType;
                        if (inputType === 'radio') {
                            input.name = `correct_${qUid}`;
                        } else {
                            input.removeAttribute('name');
                        }
                    });
                }
            }
        });

        addOptBtn.addEventListener('click', () => addOptionRow(optList));
        card.querySelector('.remove-q-btn').addEventListener('click', () => card.remove());

        questionsEl.appendChild(card);
    }

   function addOptionRow(optList) {
    const card = optList.closest('.builder-section');
    const questionType = card.querySelector('.q-type').value;
    const qUid = card.dataset.qid;
    
    const row = document.createElement('div');
    row.className = 'opt-row';

    const inputType = (questionType === 'SINGLE_CHOICE') ? 'checkbox' : 'radio';
    const inputName = `correct_${qUid}`;

    row.innerHTML = `
        <div class="opt-field flex-1">
            <label>Option Text</label>
            <input type="text" class="opt-text fullWidth" placeholder="Enter text" />
          </div>
        <div class="opt-check-field">
            <label>Correct</label>
            <div class="check-field-container">
              <input type="${inputType}" name="${inputName}" class="opt-is-correct"/>
            </div>
        </div>
        <div class="opt-field q-order-box">
            <label>Order</label>
            <input type="number" class="q-order" min="1" value="${optList.children.length + 1}" />
        </div>
        <button type="button" class="btn remove-q-btn" style="margin-bottom: 2px;">&times;</button>
    `;

    row.querySelector('button').addEventListener('click', () => {
        row.remove();
    });
    
    optList.appendChild(row);
}

    function buildJson() {
        const name = formNameEl.value.trim();
        const requiresCode = requiresCodeEl.value === '1';
        const code = formCodeEl.value.trim();
        const errors = [];

        if (!name) errors.push("Form name is missing.");
        if (requiresCode && code.length !== 5) errors.push("Private forms require a 5-character code.");

        const qCards = [...questionsEl.querySelectorAll('.builder-section')];
        if (qCards.length === 0) errors.push("Please add at least one question.");

        const questions = qCards.map((card) => {
            const question_text = card.querySelector('.q-text').value.trim();
            const question_type = card.querySelector('.q-type').value;
            const question_order = parseInt(card.querySelector('.q-order').value);

            if (!question_text) errors.push("A question is missing its title.");

            let options = [];
            if (question_type !== 'OPEN') {
                const optRows = [...card.querySelectorAll('.opt-row')];
                options = optRows.map((r) => ({
                    option_text: r.querySelector('.opt-text').value.trim(),
                    option_order: parseInt(r.querySelector('.q-order').value),
                    is_correct: r.querySelector('.opt-is-correct').checked ? 1 : 0
                }));
              
                if (options.length < 2) errors.push("Choice questions need at least 2 options.");

                const hasCorrect = options.some(opt => opt.is_correct);
                if (!hasCorrect) errors.push(`Question "${question_text}" needs at least one correct answer.`);
            }
            return { question_text, question_type, question_order, options };
        });

        if (errors.length) {
            outputEl.textContent = "VALIDATION ERRORS:\n" + errors.join("\n");
            outputEl.style.borderColor = "var(--color-error)";
            return null;
        }

        const payload = {
            form: { name, requires_code: requiresCode ? 1 : 0, code: requiresCode ? code : null },
            questions: questions.sort((a,b) => a.question_order - b.question_order)
        };

        outputEl.textContent = JSON.stringify(payload, null, 2);
        outputEl.style.borderColor = "#ccc";
        return payload;
    }

    async function saveFormToBackend(payload) {
        const statusEl = document.getElementById('saveStatus');
        statusEl.textContent = 'Uploading to PuffinForms...';
        statusEl.style.color = "var(--color-main)";

        try {
            const res = await fetch('/forms/api/create_form.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (res.status === 401) { window.location.href = '/forms/client/loginPage/login.php'; return; }

            const data = await res.json();
            if (!res.ok) {
                statusEl.textContent = 'Error: ' + (data.error || 'Server error');
                statusEl.style.color = "var(--color-error)";
            } else {
                statusEl.textContent = 'Success! Form Created ID: ' + data.form_id;
                statusEl.style.color = "var(--color-success)";
                setTimeout(() => window.location.href = '/forms/client/dashboard/dashboard.php', 2000);
            }
        } catch (err) {
            statusEl.textContent = 'Network error. Please check XAMPP.';
            statusEl.style.color = "var(--color-error)";
        }
    }

    addQuestionCard();
</script>

</body>
</html>