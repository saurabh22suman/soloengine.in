<?php
/**
 * RAG Chat Handler
 * 
 * Manages chat interactions, retrieves relevant context, 
 * and interfaces with Hugging Face API for responses
 * 
 * SECURITY: Implements rate limiting, input validation, and output encoding
 */

require_once 'env_loader.php';
require_once 'vector_store.php';

class ChatHandler {
    private $vectorStore;
    private $apiKey;
    private $llmModel;
    private $maxTokens;
    private $temperature;
    private $maxContextChunks;
    
    /**
     * Initialize chat handler
     */
    public function __construct() {
        $env = EnvLoader::getInstance();
        $this->vectorStore = new VectorStore();
        
        $this->apiKey = $env->get('HUGGINGFACE_API_KEY');
        $this->llmModel = $env->get('RAG_LLM_MODEL', 'google/flan-t5-base');
        $this->maxTokens = $env->get('RAG_MAX_TOKENS', 512);
        $this->temperature = $env->get('RAG_TEMPERATURE', 0.7);
        $this->maxContextChunks = $env->get('RAG_MAX_CONTEXT_CHUNKS', 5);
        
        if (empty($this->apiKey)) {
            throw new Exception("Hugging Face API key is not configured");
        }
    }
    
    /**
     * Process user message and return system response
     * 
     * @param string $sessionId Unique session identifier
     * @param string $message User message
     * @param string $ipAddress User IP address for rate limiting
     * @return array Response data with text and metadata
     */
    public function processMessage(string $sessionId, string $message, string $ipAddress): array {
        // SECURITY: Validate input
        if (empty($sessionId) || empty($message)) {
            return $this->createErrorResponse("Invalid input parameters");
        }
        
        // SECURITY: Check rate limiting
        if (!$this->vectorStore->checkRateLimit($ipAddress)) {
            return $this->createErrorResponse("Rate limit exceeded. Please try again later.");
        }
        
        // Clean and validate the message
        $cleanedMessage = $this->cleanInput($message);
        if (empty($cleanedMessage)) {
            return $this->createErrorResponse("Empty or invalid message");
        }
        
        try {
            // Search for relevant context based on the query
            $searchResults = $this->vectorStore->search($cleanedMessage, $this->maxContextChunks);
            
            // Extract context from search results
            $context = $this->buildContextFromResults($searchResults);
            
            // Get chat history for this session (limited to last 5 exchanges)
            $history = $this->vectorStore->getChatHistory($sessionId, 5);
            
            // Build the prompt with chat history and context
            $prompt = $this->buildPrompt($cleanedMessage, $context, $history);
            
            // Call the LLM API to generate a response
            $response = $this->callLlmApi($prompt);
            
            // If response is empty, provide a fallback
            if (empty($response)) {
                $response = "I'm sorry, I couldn't generate a response. Please try asking in a different way.";
            }
            
            // Extract context chunk IDs for history saving
            $contextIds = array_map(function($result) {
                return $result['chunk_id'];
            }, $searchResults);
            
            // Save the interaction to history
            $this->vectorStore->saveHistory($sessionId, $cleanedMessage, $response, $contextIds, $ipAddress);
            
            // Return the response with metadata
            return [
                'message' => $response,
                'status' => 'success',
                'sources' => $this->extractSources($searchResults)
            ];
        } catch (Exception $e) {
            error_log("Error processing chat message: " . $e->getMessage());
            return $this->createErrorResponse("An error occurred while processing your message");
        }
    }
    
    /**
     * Clean and validate user input
     * 
     * @param string $input Raw user input
     * @return string Cleaned input
     */
    private function cleanInput(string $input): string {
        // Remove HTML tags
        $cleaned = strip_tags($input);
        
        // Remove extra whitespace
        $cleaned = trim($cleaned);
        
        // Limit length
        if (strlen($cleaned) > 1000) {
            $cleaned = substr($cleaned, 0, 1000);
        }
        
        return $cleaned;
    }
    
    /**
     * Build context string from search results
     * 
     * @param array $searchResults Results from vector search
     * @return string Context for the LLM
     */
    private function buildContextFromResults(array $searchResults): string {
        if (empty($searchResults)) {
            return "";
        }
        
        $context = "Context information:\n\n";
        
        foreach ($searchResults as $index => $result) {
            $context .= "---\n";
            $context .= "Source: " . $result['title'];
            if (!empty($result['source'])) {
                $context .= " (" . $result['source'] . ")";
            }
            $context .= "\n\n";
            $context .= $result['content'] . "\n\n";
        }
        
        $context .= "---\n\n";
        return $context;
    }
    
    /**
     * Build the complete prompt for the LLM
     * 
     * @param string $currentMessage Current user message
     * @param string $context Context information
     * @param array $history Chat history
     * @return string Complete prompt
     */
    private function buildPrompt(string $currentMessage, string $context, array $history): string {
        $prompt = "You are a helpful and friendly assistant for a portfolio website. ";
        $prompt .= "Answer questions based on the context provided. ";
        $prompt .= "If you don't know the answer or if the answer cannot be derived from the context, say so.\n\n";
        
        // Add context if available
        if (!empty($context)) {
            $prompt .= $context . "\n";
        }
        
        // Add chat history if available (limited)
        if (!empty($history)) {
            $prompt .= "Previous conversation:\n";
            
            foreach ($history as $exchange) {
                $prompt .= "User: " . $exchange['user_message'] . "\n";
                $prompt .= "Assistant: " . $exchange['system_response'] . "\n";
            }
            
            $prompt .= "\n";
        }
        
        // Add current question
        $prompt .= "User: " . $currentMessage . "\n";
        $prompt .= "Assistant:";
        
        return $prompt;
    }
    
    /**
     * Call Hugging Face API to generate LLM response
     * 
     * @param string $prompt Full prompt for the LLM
     * @return string Generated response text
     */
    private function callLlmApi(string $prompt): string {
        $apiEndpoint = 'https://api-inference.huggingface.co/models/' . $this->llmModel;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $payload = [
            'inputs' => $prompt,
            'parameters' => [
                'max_length' => $this->maxTokens,
                'temperature' => $this->temperature,
                'do_sample' => true,
                'return_full_text' => false
            ]
        ];
        
        $ch = curl_init($apiEndpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log("cURL error in LLM API call: " . $error);
            return "";
        }
        
        curl_close($ch);
        
        if ($httpCode != 200) {
            error_log("LLM API error: HTTP code {$httpCode}, response: " . substr($result, 0, 100));
            return "";
        }
        
        // Process the response based on model
        $response = json_decode($result, true);
        
        if (is_array($response)) {
            // Different models have different response formats
            if (isset($response[0]['generated_text'])) {
                return $response[0]['generated_text'];
            } elseif (isset($response['generated_text'])) {
                return $response['generated_text'];
            } elseif (is_string($response[0])) {
                return $response[0];
            }
        }
        
        // Return raw result if we can't parse it
        return is_string($result) ? $result : "";
    }
    
    /**
     * Extract source information from search results
     * 
     * @param array $searchResults Vector search results
     * @return array Source information
     */
    private function extractSources(array $searchResults): array {
        $sources = [];
        $seenTitles = [];
        
        foreach ($searchResults as $result) {
            $title = $result['title'];
            
            // Only include each source once
            if (!in_array($title, $seenTitles)) {
                $seenTitles[] = $title;
                
                $sources[] = [
                    'title' => $title,
                    'source' => $result['source'],
                ];
            }
        }
        
        return $sources;
    }
    
    /**
     * Create standardized error response
     * 
     * @param string $message Error message
     * @return array Error response
     */
    private function createErrorResponse(string $message): array {
        return [
            'message' => $message,
            'status' => 'error',
            'sources' => []
        ];
    }
}
