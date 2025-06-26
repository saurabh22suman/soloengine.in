<?php
/**
 * Vector Store
 * 
 * Manages vector embeddings in SQLite database for semantic search
 * Handles storing, retrieving, and searching embeddings
 * 
 * SECURITY: Uses prepared statements for all database operations
 */

require_once 'env_loader.php';
require_once 'embedding_service.php';
require_once 'db_connect.php';

class VectorStore {
    private $pdo;
    private $embeddingService;
    private $chunkSize;
    private $chunkOverlap;
    
    /**
     * Initialize the vector store
     */
    public function __construct() {
        $this->pdo = getDbConnection();
        $this->embeddingService = new EmbeddingService();
        
        $env = EnvLoader::getInstance();
        $this->chunkSize = $env->get('CHUNK_SIZE', 500);
        $this->chunkOverlap = $env->get('CHUNK_OVERLAP', 100);
    }
    
    /**
     * Add a document to the vector store
     * 
     * @param string $title Document title
     * @param string $source Source of the document
     * @param string $content Full document content
     * @param array $metadata Optional metadata
     * @return int|false Document ID or false on failure
     */
    public function addDocument(string $title, string $source, string $content, array $metadata = null): ?int {
        // SECURITY: Validate inputs
        if (empty($title) || empty($content)) {
            error_log("Empty title or content in addDocument");
            return false;
        }
        
        // Begin transaction for atomicity
        try {
            $this->pdo->beginTransaction();
            
            // Insert document
            $stmt = $this->pdo->prepare("
                INSERT INTO rag_documents (title, source, content, metadata)
                VALUES (?, ?, ?, ?)
            ");
            
            $metadataJson = !empty($metadata) ? json_encode($metadata) : null;
            
            $stmt->bindParam(1, $title, PDO::PARAM_STR);
            $stmt->bindParam(2, $source, PDO::PARAM_STR);
            $stmt->bindParam(3, $content, PDO::PARAM_STR);
            $stmt->bindParam(4, $metadataJson, PDO::PARAM_STR);
            
            $stmt->execute();
            $documentId = $this->pdo->lastInsertId();
            
            // Create chunks from the document
            $chunks = $this->chunkText($content);
            
            // Generate embeddings for all chunks
            $embeddings = $this->embeddingService->getBatchEmbeddings($chunks);
            
            // Store chunks and their embeddings
            $insertChunkStmt = $this->pdo->prepare("
                INSERT INTO rag_chunks (document_id, chunk_index, content, embedding)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($chunks as $index => $chunk) {
                if (!isset($embeddings[$index])) {
                    continue;  // Skip if embedding generation failed
                }
                
                $embedding = $embeddings[$index];
                $embeddingBlob = $this->serializeEmbedding($embedding);
                
                $insertChunkStmt->bindParam(1, $documentId, PDO::PARAM_INT);
                $insertChunkStmt->bindParam(2, $index, PDO::PARAM_INT);
                $insertChunkStmt->bindParam(3, $chunk, PDO::PARAM_STR);
                $insertChunkStmt->bindParam(4, $embeddingBlob, PDO::PARAM_LOB);
                
                $insertChunkStmt->execute();
            }
            
            $this->pdo->commit();
            return $documentId;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error adding document to vector store: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove a document and all its chunks from the vector store
     * 
     * @param int $documentId ID of document to remove
     * @return bool Success or failure
     */
    public function removeDocument(int $documentId): bool {
        // SECURITY: Validate input
        if ($documentId <= 0) {
            return false;
        }
        
        try {
            // Begin transaction
            $this->pdo->beginTransaction();
            
            // Delete document (chunks will be deleted via ON DELETE CASCADE)
            $stmt = $this->pdo->prepare("DELETE FROM rag_documents WHERE id = ?");
            $stmt->bindParam(1, $documentId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Check if any row was affected
            $success = $stmt->rowCount() > 0;
            
            $this->pdo->commit();
            return $success;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error removing document from vector store: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a document in the vector store
     * 
     * @param int $documentId ID of document to update
     * @param string $title New document title
     * @param string $source New document source
     * @param string $content New document content
     * @param array $metadata New metadata
     * @return bool Success or failure
     */
    public function updateDocument(int $documentId, string $title, string $source, string $content, array $metadata = null): bool {
        // SECURITY: Validate inputs
        if ($documentId <= 0 || empty($title) || empty($content)) {
            return false;
        }
        
        // Implementation strategy: remove and re-add the document
        try {
            $this->pdo->beginTransaction();
            
            // Update the document record
            $stmt = $this->pdo->prepare("
                UPDATE rag_documents 
                SET title = ?, source = ?, content = ?, metadata = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $metadataJson = !empty($metadata) ? json_encode($metadata) : null;
            
            $stmt->bindParam(1, $title, PDO::PARAM_STR);
            $stmt->bindParam(2, $source, PDO::PARAM_STR);
            $stmt->bindParam(3, $content, PDO::PARAM_STR);
            $stmt->bindParam(4, $metadataJson, PDO::PARAM_STR);
            $stmt->bindParam(5, $documentId, PDO::PARAM_INT);
            
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Document not found
                $this->pdo->rollBack();
                return false;
            }
            
            // Remove old chunks
            $deleteStmt = $this->pdo->prepare("DELETE FROM rag_chunks WHERE document_id = ?");
            $deleteStmt->bindParam(1, $documentId, PDO::PARAM_INT);
            $deleteStmt->execute();
            
            // Create new chunks from the updated content
            $chunks = $this->chunkText($content);
            
            // Generate embeddings for all chunks
            $embeddings = $this->embeddingService->getBatchEmbeddings($chunks);
            
            // Store new chunks and their embeddings
            $insertChunkStmt = $this->pdo->prepare("
                INSERT INTO rag_chunks (document_id, chunk_index, content, embedding)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($chunks as $index => $chunk) {
                if (!isset($embeddings[$index])) {
                    continue;  // Skip if embedding generation failed
                }
                
                $embedding = $embeddings[$index];
                $embeddingBlob = $this->serializeEmbedding($embedding);
                
                $insertChunkStmt->bindParam(1, $documentId, PDO::PARAM_INT);
                $insertChunkStmt->bindParam(2, $index, PDO::PARAM_INT);
                $insertChunkStmt->bindParam(3, $chunk, PDO::PARAM_STR);
                $insertChunkStmt->bindParam(4, $embeddingBlob, PDO::PARAM_LOB);
                
                $insertChunkStmt->execute();
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error updating document in vector store: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search for similar chunks based on query text
     * 
     * @param string $query Query text to search for
     * @param int $limit Maximum number of results to return
     * @param float $minScore Minimum similarity score (0-1)
     * @return array Array of results with content and metadata
     */
    public function search(string $query, int $limit = 5, float $minScore = 0.6): array {
        // SECURITY: Validate inputs
        if (empty($query) || $limit <= 0 || $limit > 50) {
            return [];
        }
        
        try {
            // Get embedding for the query
            $queryEmbedding = $this->embeddingService->getEmbedding($query);
            if (empty($queryEmbedding)) {
                error_log("Failed to generate embedding for query");
                return [];
            }
            
            // Get all chunks from database
            $stmt = $this->pdo->query("SELECT id, document_id, content, embedding FROM rag_chunks");
            $chunks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($chunks)) {
                return [];
            }
            
            // Calculate similarity scores
            $results = [];
            foreach ($chunks as $chunk) {
                $chunkEmbedding = $this->deserializeEmbedding($chunk['embedding']);
                
                // Skip invalid embeddings
                if (empty($chunkEmbedding)) {
                    continue;
                }
                
                $similarity = $this->embeddingService->cosineSimilarity($queryEmbedding, $chunkEmbedding);
                
                // Only include results above minimum score
                if ($similarity >= $minScore) {
                    // Get document metadata
                    $docStmt = $this->pdo->prepare("
                        SELECT title, source, metadata FROM rag_documents WHERE id = ?
                    ");
                    $docStmt->bindParam(1, $chunk['document_id'], PDO::PARAM_INT);
                    $docStmt->execute();
                    $document = $docStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $results[] = [
                        'chunk_id' => $chunk['id'],
                        'document_id' => $chunk['document_id'],
                        'content' => $chunk['content'],
                        'title' => $document ? $document['title'] : 'Unknown',
                        'source' => $document ? $document['source'] : 'Unknown',
                        'metadata' => $document && $document['metadata'] ? json_decode($document['metadata'], true) : [],
                        'score' => $similarity
                    ];
                }
            }
            
            // Sort by similarity score (descending)
            usort($results, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            
            // Return top results
            return array_slice($results, 0, $limit);
            
        } catch (PDOException $e) {
            error_log("Error searching vector store: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("General error in vector search: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * List all documents in the vector store
     * 
     * @return array List of documents
     */
    public function listDocuments(): array {
        try {
            $stmt = $this->pdo->query("
                SELECT d.id, d.title, d.source, d.created_at, d.updated_at,
                       COUNT(c.id) as chunk_count
                FROM rag_documents d
                LEFT JOIN rag_chunks c ON d.id = c.document_id
                GROUP BY d.id
                ORDER BY d.updated_at DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error listing documents: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a document by ID
     * 
     * @param int $documentId Document ID
     * @return array|null Document data or null if not found
     */
    public function getDocument(int $documentId): ?array {
        // SECURITY: Validate input
        if ($documentId <= 0) {
            return null;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, title, source, content, metadata, created_at, updated_at
                FROM rag_documents
                WHERE id = ?
            ");
            
            $stmt->bindParam(1, $documentId, PDO::PARAM_INT);
            $stmt->execute();
            
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$document) {
                return null;
            }
            
            // Parse metadata if exists
            if ($document['metadata']) {
                $document['metadata'] = json_decode($document['metadata'], true);
            } else {
                $document['metadata'] = [];
            }
            
            return $document;
            
        } catch (PDOException $e) {
            error_log("Error getting document: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Save query and response to chat history
     * 
     * @param string $sessionId Unique session identifier
     * @param string $query User's query
     * @param string $response System's response
     * @param array $contextIds IDs of context chunks used
     * @param string $ipAddress User's IP address (for rate limiting)
     * @return bool Success or failure
     */
    public function saveHistory(string $sessionId, string $query, string $response, array $contextIds, string $ipAddress): bool {
        // SECURITY: Validate inputs
        if (empty($sessionId) || empty($query) || empty($response)) {
            return false;
        }
        
        try {
            // Get embedding for the query (optional)
            $queryEmbedding = $this->embeddingService->getEmbedding($query);
            $embeddingBlob = $queryEmbedding ? $this->serializeEmbedding($queryEmbedding) : null;
            
            // Save to history
            $stmt = $this->pdo->prepare("
                INSERT INTO rag_chat_history 
                (session_id, user_message, system_response, context_ids, ip_address, embedding)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $contextIdsStr = !empty($contextIds) ? implode(',', $contextIds) : null;
            
            $stmt->bindParam(1, $sessionId, PDO::PARAM_STR);
            $stmt->bindParam(2, $query, PDO::PARAM_STR);
            $stmt->bindParam(3, $response, PDO::PARAM_STR);
            $stmt->bindParam(4, $contextIdsStr, PDO::PARAM_STR);
            $stmt->bindParam(5, $ipAddress, PDO::PARAM_STR);
            $stmt->bindParam(6, $embeddingBlob, PDO::PARAM_LOB);
            
            $stmt->execute();
            return true;
            
        } catch (PDOException $e) {
            error_log("Error saving chat history: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get chat history for a session
     * 
     * @param string $sessionId Session ID
     * @param int $limit Maximum number of messages to return
     * @return array Chat history
     */
    public function getChatHistory(string $sessionId, int $limit = 10): array {
        // SECURITY: Validate inputs
        if (empty($sessionId) || $limit <= 0 || $limit > 100) {
            return [];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, user_message, system_response, created_at
                FROM rag_chat_history
                WHERE session_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            
            $stmt->bindParam(1, $sessionId, PDO::PARAM_STR);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_reverse($history); // Return in chronological order
            
        } catch (PDOException $e) {
            error_log("Error getting chat history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check and update rate limit for an IP address
     * 
     * @param string $ipAddress User's IP address
     * @return bool True if rate limit not exceeded, false otherwise
     */
    public function checkRateLimit(string $ipAddress): bool {
        $env = EnvLoader::getInstance();
        $enableRateLimiting = $env->get('ENABLE_RATE_LIMITING', true);
        
        // Skip rate limiting if disabled
        if (!$enableRateLimiting) {
            return true;
        }
        
        $maxRequestsPerMinute = $env->get('MAX_REQUESTS_PER_MINUTE', 10);
        
        try {
            // Get current rate limit status
            $stmt = $this->pdo->prepare("
                SELECT request_count, first_request_at, last_request_at
                FROM rag_rate_limits
                WHERE ip_address = ?
            ");
            
            $stmt->bindParam(1, $ipAddress, PDO::PARAM_STR);
            $stmt->execute();
            $rateLimit = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $currentTime = time();
            
            if (!$rateLimit) {
                // New IP address, create rate limit entry
                $stmt = $this->pdo->prepare("
                    INSERT INTO rag_rate_limits (ip_address)
                    VALUES (?)
                ");
                $stmt->bindParam(1, $ipAddress, PDO::PARAM_STR);
                $stmt->execute();
                return true;
            }
            
            // Check if the rate window should be reset (after 1 minute)
            $timeSinceFirst = $currentTime - strtotime($rateLimit['first_request_at']);
            
            if ($timeSinceFirst >= 60) {
                // Reset the counter
                $stmt = $this->pdo->prepare("
                    UPDATE rag_rate_limits
                    SET request_count = 1, 
                        first_request_at = CURRENT_TIMESTAMP,
                        last_request_at = CURRENT_TIMESTAMP
                    WHERE ip_address = ?
                ");
                $stmt->bindParam(1, $ipAddress, PDO::PARAM_STR);
                $stmt->execute();
                return true;
            }
            
            // Check if rate limit is exceeded
            if ($rateLimit['request_count'] >= $maxRequestsPerMinute) {
                return false;
            }
            
            // Increment request count
            $stmt = $this->pdo->prepare("
                UPDATE rag_rate_limits
                SET request_count = request_count + 1,
                    last_request_at = CURRENT_TIMESTAMP
                WHERE ip_address = ?
            ");
            $stmt->bindParam(1, $ipAddress, PDO::PARAM_STR);
            $stmt->execute();
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error checking rate limit: " . $e->getMessage());
            return true; // On error, default to allowing the request
        }
    }
    
    /**
     * Split text into chunks for embedding
     * 
     * @param string $text Text to split
     * @return array Array of text chunks
     */
    private function chunkText(string $text): array {
        // Basic chunking strategy: split by paragraphs then combine
        $paragraphs = preg_split('/\n\s*\n/', $text);
        
        $chunks = [];
        $currentChunk = '';
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;
            
            // If adding this paragraph exceeds chunk size, start a new chunk
            if (strlen($currentChunk) + strlen($paragraph) > $this->chunkSize && !empty($currentChunk)) {
                $chunks[] = $currentChunk;
                
                // Start new chunk with overlap
                if (strlen($currentChunk) > $this->chunkOverlap) {
                    $currentChunk = substr($currentChunk, -$this->chunkOverlap);
                } else {
                    $currentChunk = '';
                }
            }
            
            // Add paragraph to current chunk
            if (!empty($currentChunk)) {
                $currentChunk .= "\n\n";
            }
            $currentChunk .= $paragraph;
        }
        
        // Add the last chunk if not empty
        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }
        
        return $chunks;
    }
    
    /**
     * Serialize embedding vector to binary for storage
     * 
     * @param array $embedding Embedding vector
     * @return string Binary serialized data
     */
    private function serializeEmbedding(array $embedding): string {
        return serialize($embedding);
    }
    
    /**
     * Deserialize binary embedding data to vector
     * 
     * @param string $embeddingBlob Binary serialized data
     * @return array Embedding vector
     */
    private function deserializeEmbedding(string $embeddingBlob): array {
        $embedding = @unserialize($embeddingBlob);
        return is_array($embedding) ? $embedding : [];
    }
}
