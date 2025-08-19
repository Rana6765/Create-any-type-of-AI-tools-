<?php
/**
 * Plugin Name: SEO Keyword Generator
 * Plugin URI: https://github.com/yourusername/seo-keyword-generator
 * Description: A WordPress plugin that provides a REST API endpoint to generate SEO keywords using Gemini AI.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: MIT
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define the Gemini API key (replace with your actual key)
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE'); // Change this to your actual API key

// Register the REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route('webloop/v1', '/generate-ai-content', array(
        'methods' => 'POST',
        'callback' => 'generate_ai_content_callback',
        'permission_callback' => '__return_true', // Allow public access; add permissions if needed
    ));
});

/**
 * Callback function for the REST endpoint.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response The response object.
 */
function generate_ai_content_callback(WP_REST_Request $request) {
    // Get the parameters from the request body
    $body = json_decode($request->get_body(), true);
    
    if (!isset($body['system_prompt']) || !isset($body['user_content']) || !isset($body['generation_config'])) {
        return new WP_REST_Response(array('error' => 'Missing required parameters'), 400);
    }
    
    $system_prompt = $body['system_prompt'];
    $user_content = $body['user_content'];
    $generation_config = $body['generation_config'];
    
    // Gemini API endpoint (using gemini-1.5-flash for structured outputs)
    $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . GEMINI_API_KEY;
    
    // Build the payload
    $payload = array(
        'systemInstruction' => array(
            'parts' => array(
                array('text' => $system_prompt)
            )
        ),
        'contents' => array(
            array(
                'parts' => array(
                    array('text' => $user_content)
                )
            )
        ),
        'generationConfig' => $generation_config
    );
    
    // Make the API call using wp_remote_post
    $response = wp_remote_post($api_url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode($payload),
        'timeout' => 30 // Increase timeout if needed
    ));
    
    if (is_wp_error($response)) {
        return new WP_REST_Response(array('error' => $response->get_error_message()), 500);
    }
    
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($response_body['error'])) {
        return new WP_REST_Response(array('error' => $response_body['error']['message']), 500);
    }
    
    // Extract the AI response (assuming it's in candidates[0].content.parts[0].text)
    if (isset($response_body['candidates'][0]['content']['parts'][0]['text'])) {
        $ai_response = $response_body['candidates'][0]['content']['parts'][0]['text'];
        return new WP_REST_Response(array('ai_response' => $ai_response), 200);
    } else {
        return new WP_REST_Response(array('error' => 'Unexpected response format from API'), 500);
    }
}
