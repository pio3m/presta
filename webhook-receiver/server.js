const express = require('express');
const app = express();
const port = 9000;

// Middleware do parsowania JSON
app.use(express.json());

// Endpoint do odbierania webhooków
app.post('/', (req, res) => {
    const timestamp = new Date().toISOString();
    console.log('\n=== WEBHOOK RECEIVED ===');
    console.log('Timestamp:', timestamp);
    console.log('Headers:', JSON.stringify(req.headers, null, 2));
    console.log('Body:', JSON.stringify(req.body, null, 2));
    console.log('========================\n');

    // Zawsze zwracaj sukces
    res.status(200).json({
        status: 'success',
        message: 'Webhook received',
        received_at: timestamp
    });
});

// Health check
app.get('/health', (req, res) => {
    res.status(200).json({ status: 'ok' });
});

app.listen(port, '0.0.0.0', () => {
    console.log(`Webhook receiver listening on port ${port}`);
    console.log('Ready to receive webhooks!');
});
