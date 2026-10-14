<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scal-e CDP | Customer Data Platform Dashboard</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>

    <header>
        <div class="logo-container">
            <div class="logo-icon">S</div>
            <div class="logo-text">Scal-e CDP <span class="badge">v1.0 Senior Assessment</span></div>
        </div>
        <nav class="nav-tabs">
            <button class="tab-btn active" data-tab="dashboard">Overview</button>
            <button class="tab-btn" data-tab="ingest">Event Ingestion API</button>
            <button class="tab-btn" data-tab="segmentation">Segmentation Engine</button>
            <button class="tab-btn" data-tab="customers">Customers</button>
            <button class="tab-btn" data-tab="events">Event Stream</button>
        </nav>
    </header>

    <main class="content">

        <!-- TAB 1: OVERVIEW DASHBOARD -->
        <section id="tab-dashboard" class="tab-content active">
            <div class="grid-3" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-label">Total Unique Customers</div>
                    <div class="stat-value" id="stat-total-customers">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Events Ingested</div>
                    <div class="stat-value" id="stat-total-events" style="color: #60a5fa;">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Customer Spend</div>
                    <div class="stat-value" id="stat-total-spend" style="color: #34d399;">$0.00</div>
                </div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">⚡ Quick Event Ingestion Simulator</div>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
                        Test live data collection via <code>POST /api/v1/events</code>. Upserts customer profiles automatically.
                    </p>
                    <button class="btn btn-primary" onclick="document.querySelector('[data-tab=ingest]').click()">
                        Open Ingestion Console &rarr;
                    </button>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🎯 Segment Builder & Query AST</div>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
                        Query customer cohorts dynamically with sub-5ms MySQL JSON virtual index execution.
                    </p>
                    <button class="btn btn-primary" onclick="document.querySelector('[data-tab=segmentation]').click()">
                        Launch Segmentation Engine &rarr;
                    </button>
                </div>
            </div>
        </section>

        <!-- TAB 2: INGESTION CONSOLE -->
        <section id="tab-ingest" class="tab-content">
            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">POST /api/v1/events</div>
                    </div>
                    <form id="ingest-form">
                        <div class="form-group">
                            <label class="form-label">API Key (Optional X-API-Key)</label>
                            <input type="text" id="ingest-api-key" class="form-input" value="<?= htmlspecialchars($_ENV['API_KEY'] ?? getenv('API_KEY') ?: 'scal-e-secret-key-2026-dev', ENT_QUOTES) ?>">
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Customer Email *</label>
                                <input type="email" id="ingest-email" class="form-input" value="john.doe@example.com" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Customer Name</label>
                                <input type="text" id="ingest-name" class="form-input" value="John Doe">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Event Name *</label>
                            <input type="text" id="ingest-event-name" class="form-input" value="purchase" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Properties (JSON Object)</label>
                            <textarea id="ingest-props" rows="4" class="form-input">{
  "amount": 120.50,
  "product": "Shoes"
}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Send Event Payload</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">API Response Payload</div>
                    </div>
                    <div id="ingest-result" class="code-block">Waiting for request...</div>
                </div>
            </div>
        </section>

        <!-- TAB 3: SEGMENTATION ENGINE -->
        <section id="tab-segmentation" class="tab-content">
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <div class="card-title">POST /api/v1/segments/query</div>
                    <div>
                        <select id="segment-logic" class="form-select" style="width: 120px; display: inline-block;">
                            <option value="AND">ALL (AND)</option>
                            <option value="OR">ANY (OR)</option>
                        </select>
                    </div>
                </div>

                <div id="rules-container">
                    <!-- Rule Rows appended by app.js -->
                </div>

                <div style="display: flex; gap: 10px; margin-top: 1rem;">
                    <button id="add-rule-btn" class="btn btn-secondary">+ Add Condition</button>
                    <button id="run-segment-btn" class="btn btn-primary">Run Segment Query</button>
                </div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Matched Cohort Results</div>
                        <span id="segment-count" class="badge">0 matched</span>
                    </div>
                    <p id="segment-time" style="font-size: 0.85rem; color: var(--accent-emerald); margin-bottom: 1rem;"></p>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Events</th>
                                    <th>Total Spend</th>
                                </tr>
                            </thead>
                            <tbody id="segment-matched-tbody">
                                <tr><td colspan="5" style="text-align: center;">Run a query above to inspect matching cohort.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">AST SQL & JSON Response</div>
                    </div>
                    <div id="segment-result" class="code-block">Run a segment query to view AST execution details.</div>
                </div>
            </div>
        </section>

        <!-- TAB 4: CUSTOMERS DIRECTORY -->
        <section id="tab-customers" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Customer Profiles (GET /api/v1/customers)</div>
                    <button class="btn btn-secondary" onclick="loadCustomers()">Refresh</button>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Events Count</th>
                                <th>Total Spend</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="customers-tbody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- TAB 5: RECENT EVENTS STREAM -->
        <section id="tab-events" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Live Event Stream</div>
                    <button class="btn btn-secondary" onclick="loadRecentEvents()">Refresh Stream</button>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Customer Email</th>
                                <th>Event Type</th>
                                <th>Properties JSON</th>
                                <th>ID</th>
                            </tr>
                        </thead>
                        <tbody id="events-tbody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

    <!-- CUSTOMER PROFILE MODAL -->
    <div id="customer-modal" class="modal-backdrop">
        <div class="modal-content">
            <button onclick="closeModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer;">&times;</button>
            <div id="modal-customer-body">
                <!-- Loaded dynamically -->
            </div>
        </div>
    </div>

    <script src="/assets/app.js"></script>
</body>
</html>
