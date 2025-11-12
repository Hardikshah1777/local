const express = require('express');
const axios = require('axios');
const path = require('path');
const app = express();
const PORT = 3000;

const MOODLE_URL = 'http://localhost/moodle4/webservice/rest/server.php';
const TOKEN = 'ef08b734810c593ace8278f0371388e9';

async function getSiteInfo() {
    const params = {
        wstoken: TOKEN,
        wsfunction: 'local_nodeapi_get_user_info',
        userid: 3293,
        moodlewsrestformat: 'json'
    };

    const { data } = await axios.get(MOODLE_URL, { params });
    return data;
}

app.use(express.static(path.join(__dirname)));

app.get('/api/siteinfo', async (req, res) => {
    try {
        const info = await getSiteInfo();
        res.json(info);
    } catch (err) {
        console.error(err);
        res.status(500).send('Error fetching Moodle data');
    }
});

app.get('/testnode', (req, res) => {
    res.sendFile(path.join(__dirname, 'nodetest.html'));
});

app.listen(PORT, () => {
    console.log(`✅ Node server running at http://localhost:${PORT}`);
});
