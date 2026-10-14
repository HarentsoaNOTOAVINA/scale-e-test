document.addEventListener('DOMContentLoaded', () => {
    initNavigation();
    loadDashboardStats();
    loadCustomers();
    loadRecentEvents();
    initIngestionForm();
    initSegmentBuilder();
});

// Tab Navigation
function initNavigation() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;

            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            btn.classList.add('active');
            document.getElementById(`tab-${target}`).classList.add('active');

            if (target === 'customers') loadCustomers();
            if (target === 'events') loadRecentEvents();
        });
    });
}

// Load Global Stats
async function loadDashboardStats() {
    try {
        const res = await fetch('/api/v1/customers');
        const data = await res.json();
        if (data.status === 'success') {
            document.getElementById('stat-total-customers').textContent = data.meta.total;
        }

        const eventsRes = await fetch('/api/v1/events/recent?limit=100');
        const eventsData = await eventsRes.json();
        if (eventsData.status === 'success') {
            document.getElementById('stat-total-events').textContent = eventsData.meta.total;
            let totalSpend = 0;
            eventsData.data.forEach(e => {
                if (e.properties && e.properties.amount) {
                    totalSpend += parseFloat(e.properties.amount) || 0;
                }
            });
            document.getElementById('stat-total-spend').textContent = '$' + totalSpend.toFixed(2);
        }
    } catch (err) {
        console.error('Error loading stats:', err);
    }
}

// Load Customers List
async function loadCustomers() {
    const tbody = document.getElementById('customers-tbody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Loading customers...</td></tr>';

    try {
        const res = await fetch('/api/v1/customers');
        const data = await res.json();

        if (data.status === 'success' && data.data.length > 0) {
            tbody.innerHTML = data.data.map(c => `
                <tr>
                    <td><strong>#${c.id}</strong></td>
                    <td>${escapeHtml(c.name || 'N/A')}</td>
                    <td><code>${escapeHtml(c.email)}</code></td>
                    <td><span class="badge">${c.total_events} events</span></td>
                    <td style="color: #34d399; font-weight: 600;">$${parseFloat(c.total_spend).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;" onclick="inspectCustomer(${c.id})">
                            Inspect Profile
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No customers found. Ingest some events to start!</td></tr>';
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color: #f43f5e;">Failed to load customers: ${err.message}</td></tr>`;
    }
}

// Load Recent Events
async function loadRecentEvents() {
    const tbody = document.getElementById('events-tbody');
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Loading activity stream...</td></tr>';

    try {
        const res = await fetch('/api/v1/events/recent?limit=50');
        const data = await res.json();

        if (data.status === 'success' && data.data.length > 0) {
            tbody.innerHTML = data.data.map(e => `
                <tr>
                    <td><code>${escapeHtml(e.timestamp)}</code></td>
                    <td><strong>${escapeHtml(e.customer_email || 'Cust #' + e.customer_id)}</strong></td>
                    <td><span class="badge" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4; border-color: rgba(6, 182, 212, 0.3);">${escapeHtml(e.event_name)}</span></td>
                    <td><code>${escapeHtml(JSON.stringify(e.properties))}</code></td>
                    <td><span style="font-size: 0.8rem; color: #9ca3af;">#${e.id}</span></td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No events ingested yet.</td></tr>';
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color: #f43f5e;">Error loading events: ${err.message}</td></tr>`;
    }
}

// Ingestion Form Handler
function initIngestionForm() {
    const form = document.getElementById('ingest-form');
    const resultBox = document.getElementById('ingest-result');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        resultBox.textContent = 'Processing ingestion...';

        let properties = {};
        const propsRaw = document.getElementById('ingest-props').value.trim();
        if (propsRaw) {
            try {
                properties = JSON.parse(propsRaw);
            } catch (err) {
                console.error('Invalid JSON in properties:', err, propsRaw);
                resultBox.textContent = `❌ Invalid JSON in properties field:\n${err.message}\n\nPlease ensure properties is a valid JSON object, e.g.:\n{\n  "amount": 120.50,\n  "product": "Shoes"\n}`;
                return;
            }
        }

        const payload = {
            customer: {
                email: document.getElementById('ingest-email').value.trim(),
                name: document.getElementById('ingest-name').value.trim()
            },
            event: document.getElementById('ingest-event-name').value.trim(),
            properties: properties,
            timestamp: new Date().toISOString()
        };

        const headers = {
            'Content-Type': 'application/json'
        };
        const apiKey = document.getElementById('ingest-api-key').value.trim();
        if (apiKey) headers['X-API-Key'] = apiKey;

        try {
            const res = await fetch('/api/v1/events', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            resultBox.textContent = JSON.stringify(data, null, 2);

            if (res.ok) {
                loadDashboardStats();
                loadCustomers();
                loadRecentEvents();
            }
        } catch (err) {
            resultBox.textContent = 'Network or Client Error: ' + err.message;
        }
    });
}

// Segment Builder
function initSegmentBuilder() {
    const container = document.getElementById('rules-container');
    const addRuleBtn = document.getElementById('add-rule-btn');
    const runSegmentBtn = document.getElementById('run-segment-btn');
    const resultBox = document.getElementById('segment-result');
    const resultCount = document.getElementById('segment-count');
    const resultTime = document.getElementById('segment-time');
    const segmentTableBody = document.getElementById('segment-matched-tbody');

    // Add initial rule
    addRule();

    addRuleBtn.addEventListener('click', () => addRule());

    function addRule() {
        const div = document.createElement('div');
        div.className = 'rule-row';
        div.innerHTML = `
            <select class="form-select rule-event">
                <option value="purchase">purchase</option>
                <option value="page_view">page_view</option>
                <option value="signup">signup</option>
            </select>
            <input type="text" class="form-input rule-prop" placeholder="Property (e.g. amount, product)" value="amount">
            <select class="form-select rule-op">
                <option value=">">&gt; (Greater than)</option>
                <option value="<">&lt; (Less than)</option>
                <option value="=">= (Equals)</option>
                <option value="!=">!= (Not equals)</option>
                <option value="CONTAINS">CONTAINS</option>
            </select>
            <input type="text" class="form-input rule-val" placeholder="Value (e.g. 100)" value="100">
            <button type="button" class="btn-danger remove-rule-btn">&times;</button>
        `;
        div.querySelector('.remove-rule-btn').addEventListener('click', () => {
            if (container.children.length > 1) {
                div.remove();
            }
        });
        container.appendChild(div);
    }

    runSegmentBtn.addEventListener('click', async () => {
        const rows = container.querySelectorAll('.rule-row');
        const conditions = [];

        rows.forEach(row => {
            conditions.push({
                event: row.querySelector('.rule-event').value,
                property: row.querySelector('.rule-prop').value.trim(),
                operator: row.querySelector('.rule-op').value,
                value: isNaN(row.querySelector('.rule-val').value) ? row.querySelector('.rule-val').value : parseFloat(row.querySelector('.rule-val').value)
            });
        });

        const logic = document.getElementById('segment-logic').value;
        const payload = { logic, conditions };

        resultBox.textContent = 'Executing query AST...';

        try {
            const res = await fetch('/api/v1/segments/query', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            resultBox.textContent = JSON.stringify(data, null, 2);

            if (data.status === 'success') {
                resultCount.textContent = `${data.meta.total_matched} customers matched`;
                resultTime.textContent = `Executed in ${data.meta.execution_time_ms} ms`;

                segmentTableBody.innerHTML = data.data.map(c => `
                    <tr>
                        <td>#${c.id}</td>
                        <td>${escapeHtml(c.name || 'N/A')}</td>
                        <td><code>${escapeHtml(c.email)}</code></td>
                        <td><span class="badge">${c.total_events} events</span></td>
                        <td style="color: #34d399; font-weight: 600;">$${parseFloat(c.total_spend).toFixed(2)}</td>
                    </tr>
                `).join('');
            }
        } catch (err) {
            resultBox.textContent = 'Error: ' + err.message;
        }
    });
}

// Inspect Customer Profile Modal
async function inspectCustomer(id) {
    const modal = document.getElementById('customer-modal');
    const modalBody = document.getElementById('modal-customer-body');
    modalBody.innerHTML = 'Loading profile data...';
    modal.classList.add('open');

    try {
        const res = await fetch(`/api/v1/customers/${id}`);
        const data = await res.json();

        if (data.status === 'success') {
            const c = data.data.customer;
            const s = data.data.statistics;
            const events = data.data.recent_events;

            modalBody.innerHTML = `
                <div style="margin-bottom: 1.5rem;">
                    <h2>${escapeHtml(c.name || 'Customer')}</h2>
                    <p style="color: var(--text-muted);"><code>${escapeHtml(c.email)}</code> | Member since ${escapeHtml(c.first_seen_at)}</p>
                </div>

                <div class="grid-3" style="margin-bottom: 1.5rem;">
                    <div class="stat-card">
                        <div class="stat-label">Total Spend</div>
                        <div class="stat-value" style="color: #34d399;">$${s.total_spend}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Avg Order Value</div>
                        <div class="stat-value" style="color: #60a5fa;">$${s.avg_order_value}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Events</div>
                        <div class="stat-value">${s.total_events}</div>
                    </div>
                </div>

                <h3>Recent Events Stream (Last 10)</h3>
                <table style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Event</th>
                            <th>Properties</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${events.map(e => `
                            <tr>
                                <td><code>${escapeHtml(e.timestamp)}</code></td>
                                <td><span class="badge">${escapeHtml(e.event_name)}</span></td>
                                <td><code>${escapeHtml(JSON.stringify(e.properties))}</code></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }
    } catch (err) {
        modalBody.innerHTML = `<p style="color: #f43f5e;">Error loading customer: ${err.message}</p>`;
    }
}

function closeModal() {
    document.getElementById('customer-modal').classList.remove('open');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
