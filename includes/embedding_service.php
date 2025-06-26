<?php
/**
 * Embedding Service
 * 
 * Handles the generation of text embeddings using Hugging Face API
 * Includes caching and error handling for API calls
 * 
 * SECURITY: Validates inputs and properly handles API responses
 */

require_once 'env_loader.php';

class EmbeddingService {
    private $apiKey;
    private $apiUrl;
    private $embeddingModel;
    private $cacheTime;
    private $maxRetries;
    
    /**
     * Initialize the embedding service with configuration
     */
    public function __construct() {
        $env = EnvLoader::getInstance();
        $this->apiKey = $env->get('HUGGINGFACE_API_KEY');
        $this->apiUrl = 'https://api-inference.huggingface.co/models/';
        $this->embeddingModel = $env->get('RAG_EMBEDDING_MODEL', 'sentence-transformers/all-MiniLM-L6-v2');
        $this->cacheTime = $env->get('RAG_CACHE_TIME', 3600); // Default cache: 1 hour
        $this->maxRetries = $env->get('RAG_MAX_RETRIES', 3);
        
        if (empty($this->apiKey)) {
            throw new Exception("Hugging Face API key is not configured");
        }
    }
    
    /**
     * Generate embeddings for a single text
     * 
     * @param string $text Text to generate embedding for
     * @return array|null Embedding vector or null on failure
     */
    public function getEmbedding(string $text) {
        // SECURITY: Validate input
        if (empty($text) || strlen($text) > 10000) {
            error_log("Invalid text length for embedding: " . strlen($text));
            return null;
        }
        
        // Try to get from cache first
        $cachedEmbedding = $this->getFromCache($text);
        if ($cachedEmbedding !== null) {
            return $cachedEmbedding;
        }
        
        // Call API to generate embedding
        $embedding = $this->callEmbeddingApi($text);
        
        // Cache the result if successful
        if ($embedding !== null) {
            $this->saveToCache($text, $embedding);
        }
        
        return $embedding;
    }
    
    /**
     * Generate embeddings for multiple texts in batch
     * 
     * @param array $texts Array of texts to generate embeddings for
     * @return array Array of embeddings or empty array on failure
     */
    public function getBatchEmbeddings(array $texts) {
        // SECURITY: Validate input
        if (empty($texts)) {
            return [];
        }
        
        // Filter out any invalid texts
        $validTexts = array_filter($texts, function($text) {
            return !empty($text) && strlen($text) <= 10000;
        });
        
        if (empty($validTexts)) {
            error_log("No valid texts for batch embedding");
            return [];
        }
        
        // Check cache for existing embeddings
        $results = [];
        $textsToProcess = [];
        
        foreach ($validTexts as $index => $text) {
            $cachedEmbedding = $this->getFromCache($text);
            if ($cachedEmbedding !== null) {
                $results[$index] = $cachedEmbedding;
            } else {
                $textsToProcess[$index] = $text;
            }
        }
        
        // If all embeddings were cached, return them
        if (empty($textsToProcess)) {
            return $results;
        }
        
        // Call API for remaining texts
        $newEmbeddings = $this->callBatchEmbeddingApi(array_values($textsToProcess));
        
        // Merge with cached results and save new ones to cache
        if (!empty($newEmbeddings)) {
            $i = 0;
            foreach ($textsToProcess as $index => $text) {
                if (isset($newEmbeddings[$i])) {
                    $results[$index] = $newEmbeddings[$i];
                    $this->saveToCache($text, $newEmbeddings[$i]);
                }
                $i++;
            }
        }
        
        // Sort by original index
        ksort($results);
        return $results;
    }
    
    /**
     * Call Hugging Face API to generate embedding
     * 
     * @param string $text Text to embed
     * @return array|null Embedding vector or null on failure
     */
    private function callEmbeddingApi(string $text) {
        $apiEndpoint = $this->apiUrl . $this->embeddingModel;
        
        $ch = curl_init($apiEndpoint);
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $postData = json_encode(['inputs' => $text]);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $retries = 0;
        $embedding = null;
        
        while ($retries < $this->maxRetries) {
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($result !== false && $httpCode == 200) {
                $response = json_decode($result, true);
                
                // Different models return different response structures
                if (is_array($response)) {
                    if (isset($response[0]) && is_array($response[0])) {
                        // Standard sentence transformer format
                        $embedding = $response[0];
                        break;
                    } elseif (isset($response['embeddings']) && is_array($response['embeddings'])) {
                        // Some models use this format
                        $embedding = $response['embeddings'][0];
                        break;
                    }
                }
                
                // Unexpected response format
                error_log("Unexpected embedding API response format: " . substr(json_encode($response), 0, 100) . "...");
            } else {
                // Handle rate limiting or server errors
                if ($httpCode == 429) {
                    $retries++;
                    error_log("Rate limited by Hugging Face API. Retry {$retries}/{$this->maxRetries}");
                    sleep(2 * $retries); // Exponential backoff
                    continue;
                } else {
                    error_log("Embedding API error: Code {$httpCode}, " . curl_error($ch));
                    break;
                }
            }
            
            $retries++;
        }
        
        curl_close($ch);
        return $embedding;
    }
    
    /**
     * Call Hugging Face API to generate batch embeddings
     * 
     * @param array $texts Array of texts to embed
     * @return array Array of embedding vectors
     */
    private function callBatchEmbeddingApi(array $texts) {
        // Some models don't support true batching, so we process sequentially
        $embeddings = [];
        foreach ($texts as $text) {
            $embedding = $this->callEmbeddingApi($text);
            if ($embedding !== null) {
                $embeddings[] = $embedding;
            } else {
                $embeddings[] = array_fill(0, $this->getEmbeddingDimension(), 0); // Fallback zero vector
            }
        }
        
        return $embeddings;
    }
    
    /**
     * Get embedding from cache
     * 
     * @param string $text Text to find in cache
     * @return array|null Cached embedding or null if not found/expired
     */
    private function getFromCache(string $text) {
        // Generate cache key based on text content and model
        $cacheKey = md5($this->embeddingModel . '-' . $text);
        $cachePath = $this->getCachePath() . '/' . $cacheKey . '.json';
        
        if (file_exists($cachePath)) {
            $cacheData = json_decode(file_get_contents($cachePath), true);
            
            // Check if cache is still valid
            if (isset($cacheData['timestamp']) && 
                time() - $cacheData['timestamp'] < $this->cacheTime && 
                isset($cacheData['embedding'])) {
                return $cacheData['embedding'];
            }
        }
        
        return null;
    }
    
    /**
     * Save embedding to cache
     * 
     * @param string $text Text that was embedded
     * @param array $embedding Embedding vector to cache
     * @return bool Success or failure
     */
    private function saveToCache(string $text, array $embedding) {
        // Create cache directory if it doesn't exist
        $cachePath = $this->getCachePath();
        if (!file_exists($cachePath)) {
            if (!mkdir($cachePath, 0755, true)) {
                error_log("Failed to create embedding cache directory: {$cachePath}");
                return false;
            }
        }
        
        // Generate cache key and prepare data
        $cacheKey = md5($this->embeddingModel . '-' . $text);
        $cacheFile = $cachePath . '/' . $cacheKey . '.json';
        
        $cacheData = [
            'timestamp' => time(),
            'model' => $this->embeddingModel,
            'embedding' => $embedding
        ];
        
        // Write to cache file
        $result = file_put_contents($cacheFile, json_encode($cacheData));
        return ($result !== false);
    }
    
    /**
     * Get the path for the embedding cache
     * 
     * @return string Cache directory path
     */
    private function getCachePath() {
        $env = EnvLoader::getInstance();
        $vectorPath = $env->get('VECTOR_INDEX_PATH', 'data/vector_index');
        $cachePath = dirname(__DIR__) . '/' . $vectorPath . '/cache';
        return $cachePath;
    }
    
    /**
     * Get dimension of the embedding model
     * 
     * @return int Embedding dimension
     */
    public function getEmbeddingDimension() {
        $env = EnvLoader::getInstance();
        return $env->get('VECTOR_DIMENSION', 384);
    }
    
    /**
     * Calculate cosine similarity between two embeddings
     * 
     * @param array $embedding1 First embedding vector
     * @param array $embedding2 Second embedding vector
     * @return float Similarity score between 0 and 1
     */
    public function cosineSimilarity(array $embedding1, array $embedding2) {
        // Check dimensions match
        if (count($embedding1) !== count($embedding2)) {
            error_log("Embedding dimensions don't match for similarity calculation");
            return 0.0;
        }
        
        $dotProduct = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;
        
        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $magnitude1 += $embedding1[$i] * $embedding1[$i];
            $magnitude2 += $embedding2[$i] * $embedding2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0.0 || $magnitude2 == 0.0) {
            return 0.0;
        }
        
        return $dotProduct / ($magnitude1 * $magnitude2);
    }
}
