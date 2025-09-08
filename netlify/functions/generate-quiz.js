/**
 * This is a secure Netlify serverless function that acts as a proxy to the Google Gemini API.
 * It keeps the API key hidden from the public front-end.
 *
 * How it works:
 * 1. It receives a 'POST' request from the front-end containing the prompt.
 * 2. It retrieves the secret GEMINI_API_KEY from Netlify's environment variables.
 * 3. It makes a secure, server-to-server call to the Gemini API.
 * 4. It includes detailed logging to help debug issues via the Netlify function logs.
 * 5. It returns the API's response directly to the front-end.
 */

exports.handler = async function(event, context) {
    // Log the incoming request to see if the function is being triggered
    console.log("Function invoked. Request received from:", event.headers['x-forwarded-for']);

    // --- Security Check: Only allow POST requests ---
    if (event.httpMethod !== 'POST') {
        console.error("Error: Received a non-POST request.");
        return {
            statusCode: 405, // 405 Method Not Allowed
            body: JSON.stringify({ error: 'This function only accepts POST requests.' }),
            headers: { 'Allow': 'POST' }
        };
    }

    // --- Retrieve the secret API key ---
    const apiKey = process.env.GEMINI_API_KEY;
    if (!apiKey) {
        console.error("FATAL ERROR: GEMINI_API_KEY is not set in Netlify environment variables.");
        return {
            statusCode: 500,
            body: JSON.stringify({ error: 'Server configuration error: API key is missing.' })
        };
    }
    console.log("Successfully retrieved API key.");

    // --- Parse the prompt from the incoming request body ---
    let prompt;
    try {
        const body = JSON.parse(event.body);
        prompt = body.prompt;
        if (!prompt) {
            throw new Error("'prompt' field is missing from the request body.");
        }
        console.log("Successfully parsed prompt from request body.");
    } catch (error) {
        console.error("Error parsing request body:", error.message);
        return {
            statusCode: 400, // 400 Bad Request
            body: JSON.stringify({ error: `Invalid request body: ${error.message}` })
        };
    }

    // --- Prepare the payload for the Gemini API ---
    const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-05-20:generateContent?key=${apiKey}`;
    const payload = {
        contents: [{
            parts: [{ text: prompt }]
        }],
        generationConfig: {
            responseMimeType: "application/json",
        }
    };

    console.log("Sending request to Gemini API...");

    // --- Make the secure call to the Gemini API ---
    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        // Check for errors in the Gemini API response itself
        if (!response.ok || result.error) {
            console.error("Error response from Gemini API:", JSON.stringify(result, null, 2));
            throw new Error(result.error ? result.error.message : `API returned status ${response.status}`);
        }
        
        console.log("Successfully received response from Gemini API.");

        // --- Send the successful response back to the front-end ---
        return {
            statusCode: 200,
            body: JSON.stringify(result)
        };

    } catch (error) {
        console.error("An error occurred while calling the Gemini API:", error.message);
        return {
            statusCode: 502, // 502 Bad Gateway
            body: JSON.stringify({ error: `Failed to fetch data from the generation service: ${error.message}` })
        };
    }
};

