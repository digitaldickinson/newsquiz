/**
 * This is a Netlify Serverless Function that acts as a secure proxy.
 * Its purpose is to securely call the Google Gemini API without exposing your API key.
 *
 * How it works:
 * 1. The front-end HTML page calls this function's URL.
 * 2. This function runs on Netlify's servers.
 * 3. It retrieves the secret API key from Netlify's environment variables.
 * 4. It makes the actual call to the Google Gemini API.
 * 5. It sends the result back to the front-end page.
 */

// The 'handler' function is the required entry point for any Netlify Function.
exports.handler = async function(event, context) {
    // --- Security Check ---
    // For security, we only want to allow POST requests to this function.
    if (event.httpMethod !== 'POST') {
        return {
            statusCode: 405, // 405 Method Not Allowed
            body: JSON.stringify({ error: 'This function only accepts POST requests.' }),
            headers: { 'Allow': 'POST' }
        };
    }

    // --- API Key Retrieval ---
    // This securely retrieves the API key you've stored in your Netlify site settings.
    // It is NEVER visible to the public or in the front-end code.
    const apiKey = process.env.GEMINI_API_KEY;

    // If the API key hasn't been set in Netlify, return an error.
    if (!apiKey) {
        console.error("GEMINI_API_KEY environment variable not set.");
        return {
            statusCode: 500,
            body: JSON.stringify({ error: "Server configuration error: API key is missing." })
        };
    }

    // --- API Request Preparation ---
    const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-05-20:generateContent?key=${apiKey}`;

    try {
        // The front-end page sends its 'prompt' inside the request's body.
        const { prompt } = JSON.parse(event.body);

        if (!prompt) {
            return { statusCode: 400, body: JSON.stringify({ error: "Bad Request: No prompt was provided." }) };
        }

        // This is the payload we will send to the Google Gemini API.
        const payload = {
            contents: [{ parts: [{ text: prompt }] }],
            generationConfig: {
                responseMimeType: "application/json",
                responseSchema: {
                    type: "ARRAY",
                    items: {
                        type: "OBJECT",
                        properties: {
                            "id": { "type": "NUMBER" },
                            "question": { "type": "STRING" },
                            "options": { "type": "ARRAY", "items": { "type": "STRING" } },
                            "answer": { "type": "STRING" },
                            "explanation": { "type": "STRING" },
                            "source": {
                                "type": "OBJECT",
                                "properties": { "text": { "type": "STRING" }, "url": { "type": "STRING" } }
                            }
                        },
                        required: ["id", "question", "options", "answer", "explanation", "source"]
                    }
                }
            }
        };

        // --- Execute API Call ---
        const apiResponse = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        // If the API call itself fails, return an error.
        if (!apiResponse.ok) {
            const errorBody = await apiResponse.text();
            console.error("Google Gemini API Error:", errorBody);
            return { statusCode: apiResponse.status, body: JSON.stringify({ error: `Failed to fetch from Gemini API. ${errorBody}` }) };
        }
        
        const data = await apiResponse.json();

        // --- Success Response ---
        // Send the successful response from Google back to the front-end page.
        return {
            statusCode: 200,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        };

    } catch (error) {
        // --- Catch-All Error Handling ---
        // This catches any other errors, like if the request body is malformed.
        console.error("An unexpected error occurred in the function:", error);
        return { 
            statusCode: 500, 
            body: JSON.stringify({ error: 'An internal server error occurred.' }) 
        };
    }
};

