// Simple serverless proxy for Orbis and Moody's APIs
// Deploy this to Vercel, Netlify, or any serverless platform

const https = require('https');
const http = require('http');

exports.handler = async (event, context) => {
    // Enable CORS
    const headers = {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Headers': 'Content-Type, x-api-client, x-api-key',
        'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
        'Content-Type': 'application/json'
    };

    // Handle preflight requests
    if (event.httpMethod === 'OPTIONS') {
        return {
            statusCode: 200,
            headers,
            body: ''
        };
    }

    try {
        const { url, method = 'GET', headers: requestHeaders = {}, body } = JSON.parse(event.body);
        
        console.log(`Proxying ${method} request to: ${url}`);
        
        const options = {
            method,
            headers: {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ...requestHeaders
            }
        };

        const response = await new Promise((resolve, reject) => {
            const req = https.request(url, options, (res) => {
                let data = '';
                res.on('data', chunk => data += chunk);
                res.on('end', () => {
                    resolve({
                        statusCode: res.statusCode,
                        headers: res.headers,
                        body: data
                    });
                });
            });
            
            req.on('error', reject);
            
            if (body) {
                req.write(body);
            }
            
            req.end();
        });

        return {
            statusCode: response.statusCode,
            headers: {
                ...headers,
                'Content-Type': response.headers['content-type'] || 'application/octet-stream'
            },
            body: response.body
        };

    } catch (error) {
        console.error('Proxy error:', error);
        return {
            statusCode: 500,
            headers,
            body: JSON.stringify({ error: error.message })
        };
    }
};
