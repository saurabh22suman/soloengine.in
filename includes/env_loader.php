<?php
/**
 * Environment Variable Loader
 * 
 * Securely loads environment variables for the RAG chat feature.
 * Performs validation and provides defaults for missing values.
 * 
 * SECURITY: This file should be included before any RAG operations.
 */

class EnvLoader {
    private static $instance = null;
    private $variables = [];
    private $requiredVariables = [
        'HUGGINGFACE_API_KEY'
    ];
    
    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct() {
        $this->loadEnv();
        $this->validateRequiredVariables();
    }
    
    /**
     * Get singleton instance
     * 
     * @return EnvLoader
     */
    public static function getInstance(): EnvLoader {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load environment variables from .env file
     */
    private function loadEnv(): void {
        $envPath = dirname(__DIR__) . '/.env';
        
        if (!file_exists($envPath)) {
            error_log("CRITICAL ERROR: .env file not found. Please create it from .env.example");
            throw new Exception("Environment configuration file not found");
        }
        
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            error_log("ERROR: Failed to read .env file");
            throw new Exception("Failed to read environment configuration");
        }
        
        foreach ($lines as $line) {
            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Split by first equals sign
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes if present
            if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }
            
            $this->variables[$name] = $value;
        }
        
        // Apply default values for missing variables
        $this->applyDefaults();
    }
    
    /**
     * Apply default values for missing but optional variables
     */
    private function applyDefaults(): void {
        $defaults = [
            'VECTOR_DIMENSION' => 384,
            'CHUNK_SIZE' => 500,
            'CHUNK_OVERLAP' => 100,
            'MAX_REQUESTS_PER_MINUTE' => 10,
            'ENABLE_RATE_LIMITING' => true,
            'VECTOR_INDEX_PATH' => 'data/vector_index'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset($this->variables[$key])) {
                $this->variables[$key] = $value;
            }
        }
        
        // Convert string boolean values to actual booleans
        $booleanVars = ['ENABLE_RATE_LIMITING'];
        foreach ($booleanVars as $key) {
            if (isset($this->variables[$key]) && is_string($this->variables[$key])) {
                $this->variables[$key] = in_array(
                    strtolower($this->variables[$key]), 
                    ['true', '1', 'yes', 'on']
                );
            }
        }
        
        // Convert string numeric values to actual numbers
        $numericVars = ['VECTOR_DIMENSION', 'CHUNK_SIZE', 'CHUNK_OVERLAP', 'MAX_REQUESTS_PER_MINUTE'];
        foreach ($numericVars as $key) {
            if (isset($this->variables[$key]) && is_string($this->variables[$key]) && is_numeric($this->variables[$key])) {
                $this->variables[$key] = (int)$this->variables[$key];
            }
        }
    }
    
    /**
     * Validate that all required variables are present
     * 
     * @throws Exception if any required variable is missing
     */
    private function validateRequiredVariables(): void {
        $missing = [];
        
        foreach ($this->requiredVariables as $variable) {
            if (!isset($this->variables[$variable]) || empty($this->variables[$variable])) {
                $missing[] = $variable;
            }
        }
        
        if (!empty($missing)) {
            $errorMsg = "Missing required environment variables: " . implode(', ', $missing);
            error_log("CRITICAL ERROR: " . $errorMsg);
            throw new Exception($errorMsg);
        }
    }
    
    /**
     * Get a specific environment variable
     * 
     * @param string $name The variable name
     * @param mixed $default Default value if not found
     * @return mixed The variable value or default
     */
    public function get(string $name, $default = null) {
        return $this->variables[$name] ?? $default;
    }
    
    /**
     * Get all environment variables
     * 
     * @return array All environment variables
     */
    public function getAll(): array {
        return $this->variables;
    }
    
    /**
     * Check if environment variable exists
     * 
     * @param string $name Variable name
     * @return bool True if exists, false otherwise
     */
    public function has(string $name): bool {
        return isset($this->variables[$name]);
    }
}
